<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VirtualMachine;
use App\Services\ProxmoxService;
use Illuminate\Http\Request;


class VmController extends Controller
{
    public function index()
    {
        $vms = auth()->user()->virtualMachines()->latest()->get();
        return view('client.vms.index', compact('vms'));
    }

    public function show(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $status = null;
        try {
            $proxmox = app(ProxmoxService::class);
            $status  = $proxmox->getStatus($vm->proxmox_node, (int) $vm->proxmox_vmid, $vm->vm_type ?? 'qemu');
            $proxmoxStatus = $status['status'] ?? $vm->status;
            // Proxmox represents suspended VMs as "stopped" (with optional lock:"suspended").
            // Preserve our hibernated state whenever Proxmox returns stopped for a VM we suspended.
            if ($vm->status === 'hibernated' && $proxmoxStatus === 'stopped') {
                $proxmoxStatus = 'hibernated';
            } elseif ($proxmoxStatus === 'stopped' && str_contains(strtolower($status['lock'] ?? ''), 'suspend')) {
                $proxmoxStatus = 'hibernated';
            }
            $vm->update(['status' => $proxmoxStatus]);
        } catch (\Throwable) {}

        return view('client.vms.show', compact('vm', 'status'));
    }

    public function start(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'start', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'running']);
            return back()->with('success', 'Démarré(e).');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function stop(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'shutdown', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'stopped']);
            return back()->with('success', 'Arrêté(e) proprement.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function hibernate(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'suspend', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'hibernated']);
            return back()->with('success', 'Mis(e) en hibernation.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function resume(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'resume', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'running']);
            return back()->with('success', 'Repris(e).');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function reboot(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'reboot', $vm->vm_type ?? 'qemu');
            return back()->with('success', 'Redémarré(e).');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function terminal(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        if (($vm->vm_type ?? 'qemu') === 'lxc') {
            return back()->with('error', 'Le terminal noVNC n\'est pas disponible pour les conteneurs LXC.');
        }

        try {
            $proxmox   = app(ProxmoxService::class);
            $vncData   = $proxmox->getVNCProxy($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $authCookie = $proxmox->getAuthTicket();
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible d\'ouvrir le terminal : ' . $e->getMessage());
        }

        $proxmoxUrl  = \App\Models\Setting::get('proxmox_host');
        $vncHost     = parse_url($proxmoxUrl, PHP_URL_HOST);
        $proxmoxPort = (int) (parse_url($proxmoxUrl, PHP_URL_PORT) ?: 8006);
        $vncPort     = (int) ($vncData['port'] ?? 0);
        $vncTicket   = $vncData['ticket'] ?? '';

        if (!$vncHost || !$vncPort) {
            return back()->with('error', 'Données VNC manquantes (host ou port).');
        }

        $token = \Illuminate\Support\Str::random(48);

        // Write session file for the daemon to pick up
        $sessionFile = sys_get_temp_dir() . "/vnc-proxy-{$token}";
        file_put_contents($sessionFile, json_encode([
            'vnc_host'     => $vncHost,
            'proxmox_port' => $proxmoxPort,
            'vnc_port'     => $vncPort,
            'node'         => $vm->proxmox_node,
            'vmid'         => (int) $vm->proxmox_vmid,
            'ticket'       => $vncTicket,
            'auth_cookie'  => $authCookie,
            'expires'      => time() + 30,
        ]));

        // Start the daemon if it is not already listening on port 6080
        $proxyPort = 6080;
        $test = @stream_socket_client("tcp://127.0.0.1:{$proxyPort}", $errno, $errstr, 0.5);
        if (!$test) {
            $artisan  = base_path('artisan');
            $logFile  = sys_get_temp_dir() . '/vnc-proxy-daemon.log';
            $phpBin   = $this->findPhpBinary();
            $cmd = sprintf(
                '%s %s vnc:proxy-server --port=%d >> %s 2>&1 &',
                escapeshellarg($phpBin),
                escapeshellarg($artisan),
                $proxyPort,
                escapeshellarg($logFile)
            );
            exec($cmd);

            // Wait up to 3 s for the daemon to start
            $ready = false;
            for ($i = 0; $i < 30; $i++) {
                usleep(100_000);
                $sock = @stream_socket_client("tcp://127.0.0.1:{$proxyPort}", $e, $es, 0.1);
                if ($sock) {
                    fclose($sock);
                    $ready = true;
                    break;
                }
            }

            if (!$ready) {
                @unlink($sessionFile);
                return back()->with('error', 'Le proxy VNC n\'a pas démarré à temps.');
            }
        } else {
            fclose($test);
        }

        return view('client.vms.terminal', [
            'vm'        => $vm,
            'proxyPort' => $proxyPort,
            'token'     => $token,
            'vncTicket' => $vncTicket,
        ]);
    }

    public function changeRootPassword(Request $request, VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        try {
            app(ProxmoxService::class)->setRootPassword(
                $vm->proxmox_node,
                (int) $vm->proxmox_vmid,
                $request->password,
                $vm->vm_type ?? 'qemu'
            );

            $vm->update(['root_password' => $request->password]);

            return back()->with('success', 'Mot de passe root mis à jour.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible de changer le mot de passe : ' . $e->getMessage());
        }
    }

    public function updateDomain(Request $request, VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $request->validate([
            'custom_domain' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9\-\.]+$/',
        ]);

        $vm->update(['custom_domain' => $request->custom_domain ?: null]);

        return back()->with('success', 'Domaine personnalisé mis à jour.');
    }

    private function findPhpBinary(): string
    {
        // PHP_BINARY may be lsphp or php-fpm on managed hosts (CyberPanel/OpenLiteSpeed).
        // Search for an actual CLI php binary instead.
        $candidates = ['/usr/bin/php', '/usr/local/bin/php'];
        foreach (['8.3', '8.2', '8.1', '8.0'] as $ver) {
            $candidates[] = "/usr/bin/php{$ver}";
            $candidates[] = "/usr/local/bin/php{$ver}";
            $candidates[] = "/usr/local/lsws/lsphp" . str_replace('.', '', $ver) . "/bin/php";
        }
        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        return PHP_BINARY;
    }

    private function authorizeVm(VirtualMachine $vm): void
    {
        if ($vm->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
