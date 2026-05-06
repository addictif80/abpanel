<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxmoxService
{
    private string $host;
    private string $user;
    private string $password;
    private string $realm;
    private ?string $ticket = null;
    private ?string $csrfToken = null;

    public function __construct()
    {
        $this->host = rtrim(Setting::get('proxmox_host', ''), '/');
        $this->user = Setting::get('proxmox_user', 'root');
        $this->password = Setting::get('proxmox_password', '');
        $this->realm = Setting::get('proxmox_realm', 'pam');
    }

    private function authenticate(): void
    {
        $response = Http::withoutVerifying()->post("{$this->host}/api2/json/access/ticket", [
            'username' => "{$this->user}@{$this->realm}",
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Proxmox authentication failed');
        }

        $data = $response->json('data');
        $this->ticket = $data['ticket'];
        $this->csrfToken = $data['CSRFPreventionToken'];
    }

    private function request(string $method, string $path, array $data = []): array
    {
        if (!$this->ticket) {
            $this->authenticate();
        }

        $response = Http::withoutVerifying()
            ->withCookies(['PVEAuthCookie' => $this->ticket], parse_url($this->host, PHP_URL_HOST))
            ->withHeaders(['CSRFPreventionToken' => $this->csrfToken])
            ->$method("{$this->host}/api2/json{$path}", $data);

        if ($response->failed()) {
            Log::error("Proxmox API error [{$method} {$path}]: " . $response->body());
            throw new \RuntimeException("Proxmox API error: " . $response->status());
        }

        return $response->json('data') ?? [];
    }

    // ── Nodes ─────────────────────────────────────────────────────────────────

    public function getNodes(): array
    {
        return $this->request('get', '/nodes');
    }

    // ── QEMU (KVM) VMs ───────────────────────────────────────────────────────

    public function getVMs(string $node): array
    {
        return $this->request('get', "/nodes/{$node}/qemu");
    }

    public function getVMStatus(string $node, int $vmid): array
    {
        return $this->request('get', "/nodes/{$node}/qemu/{$vmid}/status/current");
    }

    public function startVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/start");
    }

    public function stopVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/stop");
    }

    public function shutdownVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/shutdown");
    }

    public function suspendVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/suspend");
    }

    public function resumeVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/resume");
    }

    public function rebootVM(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/status/reboot");
    }

    public function getVNCProxy(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/qemu/{$vmid}/vncproxy", ['websocket' => 1]);
    }

    public function createVM(string $node, array $config): array
    {
        return $this->request('post', "/nodes/{$node}/qemu", $config);
    }

    public function deleteVM(string $node, int $vmid): array
    {
        return $this->request('delete', "/nodes/{$node}/qemu/{$vmid}");
    }

    public function getVMConfig(string $node, int $vmid): array
    {
        return $this->request('get', "/nodes/{$node}/qemu/{$vmid}/config");
    }

    // ── LXC Containers ───────────────────────────────────────────────────────

    public function getCTs(string $node): array
    {
        return $this->request('get', "/nodes/{$node}/lxc");
    }

    public function getCTStatus(string $node, int $vmid): array
    {
        return $this->request('get', "/nodes/{$node}/lxc/{$vmid}/status/current");
    }

    public function startCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/start");
    }

    public function stopCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/stop");
    }

    public function shutdownCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/shutdown");
    }

    public function suspendCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/suspend");
    }

    public function resumeCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/resume");
    }

    public function rebootCT(string $node, int $vmid): array
    {
        return $this->request('post', "/nodes/{$node}/lxc/{$vmid}/status/reboot");
    }

    public function createCT(string $node, array $config): array
    {
        return $this->request('post', "/nodes/{$node}/lxc", $config);
    }

    public function deleteCT(string $node, int $vmid): array
    {
        return $this->request('delete', "/nodes/{$node}/lxc/{$vmid}");
    }

    public function getCTConfig(string $node, int $vmid): array
    {
        return $this->request('get', "/nodes/{$node}/lxc/{$vmid}/config");
    }

    // ── Unified dispatch by vm_type ───────────────────────────────────────────

    public function getStatus(string $node, int $vmid, string $type = 'qemu'): array
    {
        return $type === 'lxc'
            ? $this->getCTStatus($node, $vmid)
            : $this->getVMStatus($node, $vmid);
    }

    public function action(string $node, int $vmid, string $action, string $type = 'qemu'): array
    {
        if ($type === 'lxc') {
            return match($action) {
                'start'    => $this->startCT($node, $vmid),
                'stop'     => $this->stopCT($node, $vmid),
                'shutdown' => $this->shutdownCT($node, $vmid),
                'suspend'  => $this->suspendCT($node, $vmid),
                'resume'   => $this->resumeCT($node, $vmid),
                'reboot'   => $this->rebootCT($node, $vmid),
                default    => throw new \InvalidArgumentException("Unknown action: {$action}"),
            };
        }

        return match($action) {
            'start'    => $this->startVM($node, $vmid),
            'stop'     => $this->stopVM($node, $vmid),
            'shutdown' => $this->shutdownVM($node, $vmid),
            'suspend'  => $this->suspendVM($node, $vmid),
            'resume'   => $this->resumeVM($node, $vmid),
            'reboot'   => $this->rebootVM($node, $vmid),
            default    => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };
    }

    public function deleteInstance(string $node, int $vmid, string $type = 'qemu'): array
    {
        return $type === 'lxc' ? $this->deleteCT($node, $vmid) : $this->deleteVM($node, $vmid);
    }

    public function getConfig(string $node, int $vmid, string $type = 'qemu'): array
    {
        return $type === 'lxc' ? $this->getCTConfig($node, $vmid) : $this->getVMConfig($node, $vmid);
    }

    // ── Storage & templates ───────────────────────────────────────────────────

    public function getStorages(string $node, string $contentFilter = ''): array
    {
        $storages = $this->request('get', "/nodes/{$node}/storage");
        if (!$contentFilter) {
            return $storages;
        }
        return array_values(array_filter($storages, fn($s) => str_contains($s['content'] ?? '', $contentFilter)));
    }

    /** Storages that can hold ISOs (for QEMU) */
    public function getIsoStorages(string $node): array
    {
        return $this->getStorages($node, 'iso');
    }

    /** Storages that can hold CT templates (for LXC) */
    public function getCtTemplateStorages(string $node): array
    {
        return $this->getStorages($node, 'vztmpl');
    }

    /** Storages that can hold VM disk images */
    public function getDiskStorages(string $node, string $type = 'qemu'): array
    {
        $content = $type === 'lxc' ? 'rootdir' : 'images';
        return $this->getStorages($node, $content);
    }

    public function downloadTemplate(string $node, string $storage, string $url, string $filename, string $content = 'iso'): string
    {
        $result = $this->request('post', "/nodes/{$node}/storage/{$storage}/download-url", [
            'url'      => $url,
            'filename' => $filename,
            'content'  => $content,
        ]);
        return is_string($result) ? $result : ($result['upid'] ?? $result[0] ?? '');
    }

    public function getTaskStatus(string $node, string $upid): array
    {
        return $this->request('get', "/nodes/{$node}/tasks/" . urlencode($upid) . "/status");
    }

    public function listStorageContent(string $node, string $storage, string $content): array
    {
        return $this->request('get', "/nodes/{$node}/storage/{$storage}/content", compact('content'));
    }

    public function deleteStorageContent(string $node, string $storage, string $volume): array
    {
        return $this->request('delete', "/nodes/{$node}/storage/{$storage}/content/" . urlencode($volume));
    }

    // ── Import helpers ────────────────────────────────────────────────────────

    public function getAllVMs(): array
    {
        $nodes = $this->getNodes();
        $all = [];

        foreach ($nodes as $node) {
            $nodeName = $node['node'];
            foreach (['qemu', 'lxc'] as $type) {
                try {
                    $items = $type === 'lxc' ? $this->getCTs($nodeName) : $this->getVMs($nodeName);
                    foreach ($items as $vm) {
                        $vm['node']    = $nodeName;
                        $vm['vm_type'] = $type;
                        $all[] = $vm;
                    }
                } catch (\Exception) {}
            }
        }

        return $all;
    }

    // ── Cluster ───────────────────────────────────────────────────────────────

    public function getNextVMID(): int
    {
        return (int) $this->request('get', '/cluster/nextid');
    }

    public function testConnection(): bool
    {
        try {
            $this->authenticate();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
