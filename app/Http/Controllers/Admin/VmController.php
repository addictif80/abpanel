<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsTemplate;
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

        $nodes = [];
        try {
            $nodes = collect(app(ProxmoxService::class)->getNodes())->pluck('node')->sort()->values()->all();
        } catch (\Exception) {}

        $osTemplates = OsTemplate::where('status', 'ready')->where('is_active', true)
            ->orderBy('template_type')
            ->orderBy('name')
            ->get()
            ->groupBy('template_type');

        return view('admin.vms.create', compact('clients', 'nodes', 'osTemplates'));
    }

    // AJAX: disk storages for a node + vm_type
    public function diskStorages(Request $request)
    {
        $request->validate(['node' => 'required|string', 'vm_type' => 'required|in:qemu,lxc']);
        try {
            $storages = app(ProxmoxService::class)->getDiskStorages($request->node, $request->vm_type);
            return response()->json(
                collect($storages)->map(fn($s) => ['id' => $s['storage'], 'name' => $s['storage'] . ' (' . ($s['type'] ?? '?') . ')'])->values()
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'vm_type'          => 'required|in:qemu,lxc',
            'name'             => 'required|string|max:50',
            'proxmox_node'     => 'required|string',
            'cores'            => 'required|integer|min:1|max:128',
            'memory_mb'        => 'required|integer|min:128',
            'disk_gb'          => 'required|integer|min:1',
            'disk_storage'     => 'required|string',
            'monthly_price'    => 'required|numeric|min:0',
            'tailscale_ip'     => 'nullable|ip',
            'os_template_id'   => 'nullable|exists:os_templates,id',
            'swap_mb'          => 'nullable|integer|min:0',
        ]);

        $proxmox  = app(ProxmoxService::class);
        $vmid     = $proxmox->getNextVMID();
        $isLxc    = $request->vm_type === 'lxc';

        try {
            if ($isLxc) {
                $this->createLxcOnProxmox($proxmox, $request, $vmid);
            } else {
                $this->createQemuOnProxmox($proxmox, $request, $vmid);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['proxmox' => 'Erreur Proxmox : ' . $e->getMessage()])->withInput();
        }

        $baseDomain = \App\Models\Setting::get('vms_base_domain');
        $subdomain  = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;

        if ($subdomain && $request->tailscale_ip) {
            try {
                app(NginxProxyManagerService::class)->createProxyHost($subdomain, $request->tailscale_ip);
            } catch (\Exception) {}
        }

        $osTemplate = $request->os_template_id
            ? OsTemplate::find($request->os_template_id)?->name
            : null;

        $vm = VirtualMachine::create([
            'user_id'       => $request->user_id,
            'name'          => $request->name,
            'proxmox_vmid'  => $vmid,
            'proxmox_node'  => $request->proxmox_node,
            'vm_type'       => $request->vm_type,
            'status'        => 'stopped',
            'cores'         => $request->cores,
            'memory_mb'     => $request->memory_mb,
            'swap_mb'       => $isLxc ? ($request->swap_mb ?? 512) : null,
            'disk_gb'       => $request->disk_gb,
            'disk_storage'  => $request->disk_storage,
            'os_template'   => $osTemplate,
            'tailscale_ip'  => $request->tailscale_ip,
            'subdomain'     => $subdomain,
            'monthly_price' => $request->monthly_price,
        ]);

        $typeLabel = $isLxc ? 'Conteneur' : 'VM';
        return redirect()->route('admin.vms.index')
            ->with('success', "{$typeLabel} {$vm->name} créé(e) (VMID: {$vmid}).");
    }

    private function createQemuOnProxmox(ProxmoxService $proxmox, Request $request, int $vmid): void
    {
        $config = [
            'vmid'    => $vmid,
            'name'    => $request->name,
            'cores'   => $request->cores,
            'memory'  => $request->memory_mb,
            'scsihw'  => 'virtio-scsi-pci',
            'scsi0'   => "{$request->disk_storage}:{$request->disk_gb}",
            'boot'    => 'order=scsi0',
            'net0'    => 'virtio,bridge=vmbr0',
            'ostype'  => 'l26',
        ];

        if ($request->os_template_id) {
            $tpl = OsTemplate::find($request->os_template_id);
            if ($tpl?->proxmox_volume) {
                $config['ide2'] = $tpl->proxmox_volume . ',media=cdrom';
                $config['boot'] = 'order=ide2;scsi0';
            }
        }

        $proxmox->createVM($request->proxmox_node, $config);
    }

    private function createLxcOnProxmox(ProxmoxService $proxmox, Request $request, int $vmid): void
    {
        $tpl = OsTemplate::find($request->os_template_id);
        if (!$tpl?->proxmox_volume) {
            throw new \RuntimeException('Template LXC introuvable ou non disponible.');
        }

        $proxmox->createCT($request->proxmox_node, [
            'vmid'         => $vmid,
            'hostname'     => $request->name,
            'ostemplate'   => $tpl->proxmox_volume,
            'cores'        => $request->cores,
            'memory'       => $request->memory_mb,
            'swap'         => $request->swap_mb ?? 512,
            'rootfs'       => "{$request->disk_storage}:{$request->disk_gb}",
            'net0'         => 'name=eth0,bridge=vmbr0,ip=dhcp',
            'unprivileged' => $request->boolean('unprivileged', true) ? 1 : 0,
        ]);
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
            app(ProxmoxService::class)->deleteInstance($vm->proxmox_node, (int) $vm->proxmox_vmid, $vm->vm_type ?? 'qemu');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Erreur Proxmox : ' . $e->getMessage()]);
        }

        $vm->delete();
        return redirect()->route('admin.vms.index')->with('success', 'VM/Conteneur supprimé.');
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

        $alreadyImported = VirtualMachine::pluck('proxmox_vmid')->map(fn($id) => (int) $id)->flip();

        $proxmoxVms = array_map(function ($vm) use ($alreadyImported) {
            $vm['imported'] = $alreadyImported->has((int) $vm['vmid']);
            return $vm;
        }, $proxmoxVms);

        usort($proxmoxVms, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['node'], $b['node']) ?: $a['vmid'] <=> $b['vmid']);

        return view('admin.vms.import-index', compact('proxmoxVms', 'error'));
    }

    public function importShow(string $node, int $vmid)
    {
        if (VirtualMachine::where('proxmox_vmid', $vmid)->exists()) {
            return redirect()->route('admin.vms.import.index')
                ->with('error', "La VM/CT {$vmid} est déjà importée dans le panel.");
        }

        $proxmox = app(ProxmoxService::class);

        // Try QEMU first, then LXC
        $vmType = 'qemu';
        $config = $status = [];
        try {
            $config = $proxmox->getVMConfig($node, $vmid);
            $status = $proxmox->getVMStatus($node, $vmid);
        } catch (\Exception) {
            try {
                $config = $proxmox->getCTConfig($node, $vmid);
                $status = $proxmox->getCTStatus($node, $vmid);
                $vmType = 'lxc';
            } catch (\Exception $e) {
                return redirect()->route('admin.vms.import.index')
                    ->with('error', 'Impossible de récupérer les infos Proxmox : ' . $e->getMessage());
            }
        }

        $diskGb = 0;
        if ($vmType === 'qemu') {
            foreach ($config as $key => $value) {
                if (preg_match('/^(scsi|virtio|ide|sata)\d+$/', $key) && is_string($value)) {
                    if (preg_match('/size=(\d+)G/i', $value, $m)) {
                        $diskGb = max($diskGb, (int) $m[1]);
                    }
                }
            }
        } else {
            // LXC rootfs: "local-lvm:vm-100-disk-0,size=30G"
            if (isset($config['rootfs']) && preg_match('/size=(\d+)G/i', $config['rootfs'], $m)) {
                $diskGb = (int) $m[1];
            }
        }

        $vmInfo = [
            'vmid'      => $vmid,
            'node'      => $node,
            'vm_type'   => $vmType,
            'name'      => $config['name'] ?? $config['hostname'] ?? "vm-{$vmid}",
            'cores'     => (int) ($config['cores'] ?? $config['sockets'] ?? 1),
            'memory_mb' => (int) ($config['memory'] ?? 512),
            'swap_mb'   => isset($config['swap']) ? (int) $config['swap'] : null,
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
                ->with('error', "La VM/CT {$vmid} est déjà importée.");
        }

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'vm_type'       => 'required|in:qemu,lxc',
            'name'          => 'required|string|max:50',
            'cores'         => 'required|integer|min:1',
            'memory_mb'     => 'required|integer|min:128',
            'disk_gb'       => 'nullable|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'tailscale_ip'  => 'nullable|ip',
            'status'        => 'required|in:running,stopped,hibernated',
        ]);

        $baseDomain = \App\Models\Setting::get('vms_base_domain');
        $subdomain  = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;

        if ($subdomain && $request->tailscale_ip) {
            try {
                app(NginxProxyManagerService::class)->createProxyHost($subdomain, $request->tailscale_ip);
            } catch (\Exception) {}
        }

        $vm = VirtualMachine::create([
            'user_id'       => $request->user_id,
            'name'          => $request->name,
            'proxmox_vmid'  => $vmid,
            'proxmox_node'  => $node,
            'vm_type'       => $request->vm_type,
            'status'        => $request->status,
            'cores'         => $request->cores,
            'memory_mb'     => $request->memory_mb,
            'swap_mb'       => $request->vm_type === 'lxc' ? $request->swap_mb : null,
            'disk_gb'       => $request->disk_gb,
            'tailscale_ip'  => $request->tailscale_ip,
            'subdomain'     => $subdomain,
            'monthly_price' => $request->monthly_price,
        ]);

        return redirect()->route('admin.vms.edit', $vm)
            ->with('success', "« {$vm->name} » (VMID {$vmid}) importé(e) et assigné(e) à {$vm->user->full_name}.");
    }
}
