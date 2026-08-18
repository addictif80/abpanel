<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\JoinTailscaleJob;
use App\Models\VirtualMachine;
use App\Services\MailService;
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
            if ($proxmoxStatus === 'stopped' && str_contains(strtolower($status['lock'] ?? ''), 'suspend')) {
                // Proxmox lock=suspended → definitely hibernated
                $proxmoxStatus = 'hibernated';
            } elseif ($vm->status === 'hibernated' && in_array($proxmoxStatus, ['stopped', 'running'])) {
                // Suspend may not be finished yet (Proxmox still shows running) or VM is
                // stopped-with-no-lock. Trust our own status until resume is explicitly called.
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

            if (($vm->vm_type ?? 'qemu') !== 'lxc' && !$vm->tailscale_ip) {
                JoinTailscaleJob::dispatch($vm->id)->delay(now()->addSeconds(15));
            }

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

    public function forceStop(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'stop', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'stopped']);
            return back()->with('success', 'VM arrêtée de force.');
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

    public function reinstall(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $templateType = ($vm->vm_type ?? 'qemu') === 'lxc' ? 'ct' : 'iso';
        $templates = \App\Models\OsTemplate::where('status', 'ready')
            ->where('is_active', true)
            ->where('template_type', $templateType)
            ->get();

        return view('client.vms.reinstall', compact('vm', 'templates'));
    }

    public function doReinstall(Request $request, VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $request->validate([
            'os_template_id' => 'required|exists:os_templates,id',
        ]);

        $template = \App\Models\OsTemplate::findOrFail($request->os_template_id);
        $proxmox  = app(ProxmoxService::class);
        $vmType   = $vm->vm_type ?? 'qemu';

        try {
            // Force-stop the VM and wait up to 30 s
            if ($vm->status !== 'stopped') {
                $proxmox->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'stop', $vmType);
                for ($i = 0; $i < 30; $i++) {
                    sleep(1);
                    $s = $proxmox->getStatus($vm->proxmox_node, (int) $vm->proxmox_vmid, $vmType);
                    if (($s['status'] ?? '') === 'stopped') break;
                }
            }

            $newPassword = null;

            // Resolve disk storage: use stored value, or read it from Proxmox config,
            // or fall back to the panel default.
            $diskStorage = $vm->disk_storage ?: $this->resolveDiskStorage($proxmox, $vm, $vmType);

            if ($vmType === 'lxc') {
                $newPassword = \Illuminate\Support\Str::random(8) . '!' . \Illuminate\Support\Str::random(8);
                $proxmox->deleteCT($vm->proxmox_node, (int) $vm->proxmox_vmid);
                sleep(5);
                $proxmox->createCT($vm->proxmox_node, [
                    'vmid'         => (int) $vm->proxmox_vmid,
                    'hostname'     => $vm->name,
                    'ostemplate'   => $template->proxmox_volume,
                    'cores'        => $vm->cores,
                    'memory'       => $vm->memory_mb,
                    'swap'         => $vm->swap_mb ?? 512,
                    'rootfs'       => "{$diskStorage}:{$vm->disk_gb}",
                    'net0'         => 'name=eth0,bridge=vmbr0,ip=dhcp',
                    'unprivileged' => 1,
                    'password'     => $newPassword,
                ]);
            } else {
                // Delete existing disk then recreate + swap ISO
                $proxmox->unlinkVMDisk($vm->proxmox_node, (int) $vm->proxmox_vmid, 'scsi0');
                sleep(2);
                $proxmox->updateVMConfig($vm->proxmox_node, (int) $vm->proxmox_vmid, [
                    'scsi0' => "{$diskStorage}:{$vm->disk_gb}",
                    'ide2'  => "{$template->proxmox_volume},media=cdrom",
                    'boot'  => 'order=ide2;scsi0',
                ]);
            }

            $proxmox->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'start', $vmType);

            $vm->update([
                'os_template'   => $template->name,
                'status'        => 'running',
                'root_password' => $newPassword,
            ]);

            $msg = $vmType === 'lxc'
                ? 'Réinstallation terminée. Le nouveau mot de passe root est affiché ci-dessous.'
                : 'Réinstallation terminée. Utilisez le terminal noVNC pour finaliser l\'installation de l\'OS.';

            return redirect()->route('client.vms.show', $vm)->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la réinstallation : ' . $e->getMessage());
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

    public function cancelRequest(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(15);

        $vm->update([
            'cancellation_code'            => $code,
            'cancellation_code_expires_at' => $expires,
        ]);

        try {
            app(MailService::class)->sendFromTemplate('vm_cancellation_code', auth()->user()->email, [
                'first_name' => auth()->user()->first_name ?? auth()->user()->name,
                'vm_name'    => $vm->name,
                'code'       => $code,
                'expires_at' => $expires->format('H:i'),
            ]);
        } catch (\Throwable) {}

        return view('client.vms.cancel', compact('vm'));
    }

    public function cancelConfirm(Request $request, VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        $request->validate(['code' => 'required|string|size:6']);

        if (!$vm->cancellation_code
            || $vm->cancellation_code_expires_at?->isPast()
            || $request->code !== $vm->cancellation_code
        ) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.'])->withInput();
        }

        try {
            $proxmox = app(ProxmoxService::class);
            $vmType  = $vm->vm_type ?? 'qemu';

            if ($vm->status !== 'stopped') {
                $proxmox->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'stop', $vmType);
                for ($i = 0; $i < 20; $i++) {
                    sleep(1);
                    $s = $proxmox->getStatus($vm->proxmox_node, (int) $vm->proxmox_vmid, $vmType);
                    if (($s['status'] ?? '') === 'stopped') break;
                }
            }

            $proxmox->deleteInstance($vm->proxmox_node, (int) $vm->proxmox_vmid, $vmType);
        } catch (\Throwable) {}

        $vm->delete();

        return redirect()->route('client.vms.index')
            ->with('success', "La VM « {$vm->name} » a été résiliée et supprimée définitivement.");
    }

    private function resolveDiskStorage(ProxmoxService $proxmox, \App\Models\VirtualMachine $vm, string $vmType): string
    {
        // Try to read the storage name from the current Proxmox config (scsi0 or rootfs)
        try {
            $config = $proxmox->getConfig($vm->proxmox_node, (int) $vm->proxmox_vmid, $vmType);
            $field  = $vmType === 'lxc' ? 'rootfs' : 'scsi0';
            $value  = $config[$field] ?? '';
            // Format is "storage:volume-name,..." — extract the storage part
            if ($value && str_contains($value, ':')) {
                $storage = explode(':', $value)[0];
                if ($storage) {
                    $vm->update(['disk_storage' => $storage]);
                    return $storage;
                }
            }
        } catch (\Throwable) {}

        // Fall back to the panel default storage setting
        $default = \App\Models\Setting::get('proxmox_default_storage', 'local-lvm');
        return $default ?: 'local-lvm';
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
