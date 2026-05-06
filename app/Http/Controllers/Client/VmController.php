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
        } catch (\Exception) {}

        return view('client.vms.show', compact('vm', 'status'));
    }

    public function start(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'start', $vm->vm_type ?? 'qemu');
            $vm->update(['status' => 'running']);
            return back()->with('success', 'Démarré(e).');
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function reboot(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->action($vm->proxmox_node, (int) $vm->proxmox_vmid, 'reboot', $vm->vm_type ?? 'qemu');
            return back()->with('success', 'Redémarré(e).');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function terminal(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

        // VNC is only available for QEMU VMs
        if (($vm->vm_type ?? 'qemu') === 'lxc') {
            return back()->with('error', 'Le terminal noVNC n\'est pas disponible pour les conteneurs LXC.');
        }

        $vncData = null;
        try {
            $vncData = app(ProxmoxService::class)->getVNCProxy($vm->proxmox_node, (int) $vm->proxmox_vmid);
        } catch (\Exception $e) {
            return back()->with('error', 'Impossible d\'ouvrir le terminal : ' . $e->getMessage());
        }

        $proxmoxHost = rtrim(\App\Models\Setting::get('proxmox_host'), '/');

        return view('client.vms.terminal', compact('vm', 'vncData', 'proxmoxHost'));
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
