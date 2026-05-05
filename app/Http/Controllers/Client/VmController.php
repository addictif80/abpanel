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
            $status = app(ProxmoxService::class)->getVMStatus($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $vm->update(['status' => $status['status'] ?? $vm->status]);
        } catch (\Exception) {
            // Proxmox unreachable, show cached status
        }

        return view('client.vms.show', compact('vm', 'status'));
    }

    public function start(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->startVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $vm->update(['status' => 'running']);
            return back()->with('success', 'VM démarrée.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function stop(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->shutdownVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $vm->update(['status' => 'stopped']);
            return back()->with('success', 'VM arrêtée proprement.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function hibernate(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->suspendVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $vm->update(['status' => 'hibernated']);
            return back()->with('success', 'VM mise en hibernation.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function resume(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->resumeVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
            $vm->update(['status' => 'running']);
            return back()->with('success', 'VM reprise.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function reboot(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);
        try {
            app(ProxmoxService::class)->rebootVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
            return back()->with('success', 'VM redémarrée.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function terminal(VirtualMachine $vm)
    {
        $this->authorizeVm($vm);

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
