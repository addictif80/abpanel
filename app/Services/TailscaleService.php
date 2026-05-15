<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TailscaleService
{
    private string $clientId;
    private string $clientSecret;
    private string $tailnet;

    public function __construct()
    {
        $this->clientId     = Setting::get('tailscale_oauth_client_id', '');
        $this->clientSecret = Setting::get('tailscale_oauth_client_secret', '');
        $this->tailnet      = Setting::get('tailscale_tailnet', '-');
    }

    /**
     * Obtain an OAuth 2.0 access token using client credentials.
     * Cached for 50 minutes (tokens are valid 1 hour).
     */
    private function getAccessToken(): string
    {
        return Cache::remember('tailscale_oauth_token', 3000, function () {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://api.tailscale.com/api/v2/oauth/token', [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("Tailscale OAuth failed {$response->status()}: " . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /** Return all devices in the tailnet, keyed by lowercase hostname. */
    public function getDevices(): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('OAuth client Tailscale non configuré.');
        }

        $token = $this->getAccessToken();

        $response = Http::withToken($token)
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
        $devices  = $this->getDevices();
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

    /**
     * Create a one-time ephemeral auth key via the API.
     * Used at VM provisioning time so no static key is ever stored.
     *
     * @param  string  $description  Human-readable label (e.g. VM hostname)
     * @param  bool    $ephemeral    Device is removed when it goes offline
     * @param  bool    $reusable     Allow multiple devices to use this key
     * @param  int     $expirySeconds  Max 7776000 (90 days); default 3600 (1 hour)
     */
    public function createAuthKey(
        string $description = '',
        bool   $ephemeral = true,
        bool   $reusable = false,
        int    $expirySeconds = 3600
    ): string {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('OAuth client Tailscale non configuré.');
        }

        $token    = $this->getAccessToken();
        $response = Http::withToken($token)
            ->timeout(10)
            ->post("https://api.tailscale.com/api/v2/tailnet/{$this->tailnet}/keys", [
                'capabilities' => [
                    'devices' => [
                        'create' => [
                            'reusable'      => $reusable,
                            'ephemeral'     => $ephemeral,
                            'preauthorized' => true,
                            'tags'          => [],
                        ],
                    ],
                ],
                'expirySeconds' => $expirySeconds,
                'description'   => $description ?: 'abpanel-vm',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Création auth key Tailscale échouée {$response->status()}: " . $response->body());
        }

        return $response->json('key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
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
