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
            $vm->update(['status' => $status['status'] ?? $vm->status]);
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
            $vncData = app(ProxmoxService::class)->getVNCProxy($vm->proxmox_node, (int) $vm->proxmox_vmid);
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible d\'ouvrir le terminal : ' . $e->getMessage());
        }

        $vncHost  = parse_url(\App\Models\Setting::get('proxmox_host'), PHP_URL_HOST);
        $vncPort  = (int) ($vncData['port'] ?? 0);
        $vncTicket = $vncData['ticket'] ?? '';

        if (!$vncHost || !$vncPort) {
            return back()->with('error', 'Données VNC manquantes (host ou port).');
        }

        // Find a free local port for the WebSocket proxy
        $proxyPort = $this->findFreePort();
        if (!$proxyPort) {
            return back()->with('error', 'Aucun port disponible pour le proxy VNC.');
        }

        $token = \Illuminate\Support\Str::random(48);

        // Start the proxy as a background process
        $artisan = base_path('artisan');
        $cmd = sprintf(
            'php %s vnc:proxy %d %s %d %s > /dev/null 2>&1 &',
            escapeshellarg($artisan),
            $proxyPort,
            escapeshellarg($vncHost),
            $vncPort,
            escapeshellarg($token)
        );
        exec($cmd);

        // Wait up to 2 s for the proxy to start listening
        $ready = false;
        for ($i = 0; $i < 20; $i++) {
            usleep(100_000);
            $test = @stream_socket_client("tcp://127.0.0.1:{$proxyPort}", $e, $es, 0.1);
            if ($test) {
                fclose($test);
                $ready = true;
                break;
            }
        }

        if (!$ready) {
            return back()->with('error', 'Le proxy VNC n\'a pas démarré à temps.');
        }

        return view('client.vms.terminal', [
            'vm'        => $vm,
            'proxyPort' => $proxyPort,
            'token'     => $token,
            'vncTicket' => $vncTicket,
        ]);
    }

    private function findFreePort(): ?int
    {
        for ($i = 0; $i < 20; $i++) {
            $port = random_int(6100, 6300);
            $test = @stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
            if ($test) {
                fclose($test);
                return $port;
            }
        }
        return null;
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

    private function authorizeVm(VirtualMachine $vm): void
    {
        if ($vm->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
