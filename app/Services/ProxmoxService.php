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

    public function getNodes(): array
    {
        return $this->request('get', '/nodes');
    }

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

    public function getNextVMID(): int
    {
        $data = $this->request('get', '/cluster/nextid');
        return (int) $data;
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
