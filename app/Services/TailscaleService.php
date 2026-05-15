<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TailscaleService
{
    private string $apiKey;
    private string $tailnet;

    public function __construct()
    {
        $this->apiKey  = Setting::get('tailscale_api_key', '');
        $this->tailnet = Setting::get('tailscale_tailnet', '-');
    }

    /** Return all devices in the tailnet, keyed by hostname. */
    public function getDevices(): array
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('Clé API Tailscale non configurée.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->get("https://api.tailscale.com/api/v2/tailnet/{$this->tailnet}/devices");

        if ($response->failed()) {
            throw new \RuntimeException("Tailscale API {$response->status()}: " . $response->body());
        }

        $devices = [];
        foreach ($response->json('devices', []) as $device) {
            $hostname = strtolower($device['hostname'] ?? '');
            if ($hostname) {
                $devices[$hostname] = $device;
            }
        }

        return $devices;
    }

    /** Find a device by hostname and return its first IPv4 address, or null. */
    public function findIpByHostname(string $hostname): ?string
    {
        $devices = $this->getDevices();
        $hostname = strtolower($hostname);

        $device = $devices[$hostname]
            ?? $devices[explode('.', $hostname)[0]]
            ?? null;

        if (!$device) {
            return null;
        }

        foreach ($device['addresses'] ?? [] as $addr) {
            if (!str_contains($addr, ':')) {
                return $addr;
            }
        }

        return null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /** Generate a cloud-init userdata snippet that installs and joins Tailscale. */
    public static function cloudInitScript(string $authKey, string $hostname): string
    {
        $authKey  = escapeshellarg($authKey);
        $hostname = escapeshellarg($hostname);

        return <<<BASH
#!/bin/bash
curl -fsSL https://tailscale.com/install.sh | sh
tailscale up --authkey={$authKey} --hostname={$hostname} --accept-routes
BASH;
    }
}
