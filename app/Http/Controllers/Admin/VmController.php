<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VirtualMachine;
use App\Services\NginxProxyManagerService;
use App\Services\ProxmoxService;
use Illuminate\Http\Request;

class VmController extends Controller
{
    public function index()
    {
        $vms = VirtualMachine::with('user')->latest()->paginate(20);
        return view('admin.vms.index', compact('vms'));
    }

    public function create()
    {
        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();
        return view('admin.vms.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'name'        => 'required|string|max:50',
            'proxmox_node'=> 'required|string',
            'cores'       => 'required|integer|min:1|max:32',
            'memory_mb'   => 'required|integer|min:512',
            'disk_gb'     => 'required|integer|min:5',
            'os_template' => 'nullable|string',
            'monthly_price'=> 'required|numeric|min:0',
        ]);

        try {
            $proxmox = app(ProxmoxService::class);
            $vmid = $proxmox->getNextVMID();

            $proxmox->createVM($request->proxmox_node, [
                'vmid'    => $vmid,
                'name'    => $request->name,
                'cores'   => $request->cores,
                'memory'  => $request->memory_mb,
                'scsihw'  => 'virtio-scsi-pci',
                'boot'    => 'order=scsi0',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['proxmox' => 'Erreur Proxmox : ' . $e->getMessage()])->withInput();
        }

        $baseDomain = \App\Models\Setting::get('vms_base_domain');
        $subdomain = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;

        // Create NPM proxy rule for the subdomain
        if ($subdomain && $request->tailscale_ip) {
            try {
                app(NginxProxyManagerService::class)->createProxyHost($subdomain, $request->tailscale_ip);
            } catch (\Exception) {
                // Non-blocking
            }
        }

        $vm = VirtualMachine::create([
            'user_id'       => $request->user_id,
            'name'          => $request->name,
            'proxmox_vmid'  => $vmid,
            'proxmox_node'  => $request->proxmox_node,
            'status'        => 'stopped',
            'cores'         => $request->cores,
            'memory_mb'     => $request->memory_mb,
            'disk_gb'       => $request->disk_gb,
            'os_template'   => $request->os_template,
            'tailscale_ip'  => $request->tailscale_ip,
            'subdomain'     => $subdomain,
            'monthly_price' => $request->monthly_price,
        ]);

        return redirect()->route('admin.vms.index')->with('success', "VM {$vm->name} créée (VMID: {$vmid}).");
    }

    public function show(VirtualMachine $vm)
    {
        $vm->load('user');
        return view('admin.vms.show', compact('vm'));
    }

    public function edit(VirtualMachine $vm)
    {
        $clients = User::where('is_admin', false)->orderBy('last_name')->get();
        return view('admin.vms.edit', compact('vm', 'clients'));
    }

    public function update(Request $request, VirtualMachine $vm)
    {
        $request->validate([
            'name'          => 'required|string|max:50',
            'monthly_price' => 'required|numeric|min:0',
            'tailscale_ip'  => 'nullable|string',
            'custom_domain' => 'nullable|string',
        ]);

        $vm->update($request->only(['name', 'monthly_price', 'tailscale_ip', 'custom_domain', 'status']));

        return back()->with('success', 'VM mise à jour.');
    }

    public function destroy(VirtualMachine $vm)
    {
        try {
            app(ProxmoxService::class)->deleteVM($vm->proxmox_node, (int) $vm->proxmox_vmid);
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Erreur Proxmox : ' . $e->getMessage()]);
        }

        $vm->delete();
        return redirect()->route('admin.vms.index')->with('success', 'VM supprimée.');
    }
}
