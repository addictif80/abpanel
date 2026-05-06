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

    // ── Import existing Proxmox VMs ──────────────────────────────────────────

    public function importIndex()
    {
        $proxmoxVms = [];
        $error = null;

        try {
            $proxmoxVms = app(ProxmoxService::class)->getAllVMs();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        // VMIDs already in the panel DB
        $alreadyImported = VirtualMachine::pluck('proxmox_vmid')->map(fn($id) => (int) $id)->flip();

        // Annotate each VM with its import status
        $proxmoxVms = array_map(function ($vm) use ($alreadyImported) {
            $vm['imported'] = $alreadyImported->has((int) $vm['vmid']);
            return $vm;
        }, $proxmoxVms);

        // Sort: not-imported first, then by node+vmid
        usort($proxmoxVms, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['node'], $b['node']) ?: $a['vmid'] <=> $b['vmid']);

        return view('admin.vms.import-index', compact('proxmoxVms', 'error'));
    }

    public function importShow(string $node, int $vmid)
    {
        // Block if already imported
        if (VirtualMachine::where('proxmox_vmid', $vmid)->exists()) {
            return redirect()->route('admin.vms.import.index')
                ->with('error', "La VM {$vmid} est déjà importée dans le panel.");
        }

        $proxmox = app(ProxmoxService::class);

        try {
            $config = $proxmox->getVMConfig($node, $vmid);
            $status = $proxmox->getVMStatus($node, $vmid);
        } catch (\Exception $e) {
            return redirect()->route('admin.vms.import.index')
                ->with('error', 'Impossible de récupérer les infos Proxmox : ' . $e->getMessage());
        }

        // Parse disk size from config (e.g. "local-lvm:vm-100-disk-0,size=30G")
        $diskGb = 0;
        foreach ($config as $key => $value) {
            if (preg_match('/^(scsi|virtio|ide|sata)\d+$/', $key) && is_string($value)) {
                if (preg_match('/size=(\d+)G/i', $value, $m)) {
                    $diskGb = max($diskGb, (int) $m[1]);
                }
            }
        }

        $vmInfo = [
            'vmid'      => $vmid,
            'node'      => $node,
            'name'      => $config['name'] ?? "vm-{$vmid}",
            'cores'     => (int) ($config['cores'] ?? $config['sockets'] ?? 1),
            'memory_mb' => (int) ($config['memory'] ?? 512),
            'disk_gb'   => $diskGb ?: null,
            'status'    => $status['status'] ?? 'stopped',
            'os_type'   => $config['ostype'] ?? null,
            'description' => $config['description'] ?? null,
        ];

        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();

        return view('admin.vms.import-show', compact('vmInfo', 'clients'));
    }

    public function importStore(Request $request, string $node, int $vmid)
    {
        if (VirtualMachine::where('proxmox_vmid', $vmid)->exists()) {
            return redirect()->route('admin.vms.import.index')
                ->with('error', "La VM {$vmid} est déjà importée.");
        }

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'name'          => 'required|string|max:50',
            'cores'         => 'required|integer|min:1',
            'memory_mb'     => 'required|integer|min:128',
            'disk_gb'       => 'nullable|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'tailscale_ip'  => 'nullable|ip',
            'status'        => 'required|in:running,stopped,hibernated',
        ]);

        $baseDomain = \App\Models\Setting::get('vms_base_domain');
        $subdomain = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;

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
            'proxmox_node'  => $node,
            'status'        => $request->status,
            'cores'         => $request->cores,
            'memory_mb'     => $request->memory_mb,
            'disk_gb'       => $request->disk_gb,
            'tailscale_ip'  => $request->tailscale_ip,
            'subdomain'     => $subdomain,
            'monthly_price' => $request->monthly_price,
        ]);

        return redirect()->route('admin.vms.edit', $vm)
            ->with('success', "VM \"{$vm->name}\" (VMID {$vmid}) importée et assignée à {$vm->user->full_name}.");
    }
}
