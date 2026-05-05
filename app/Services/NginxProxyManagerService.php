<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NginxProxyManagerService
{
    private string $host;
    private string $email;
    private string $password;
    private ?string $token = null;

    public function __construct()
    {
        $this->host = rtrim(Setting::get('npm_host', ''), '/');
        $this->email = Setting::get('npm_email', '');
        $this->password = Setting::get('npm_password', '');
    }

    private function authenticate(): void
    {
        $response = Http::post("{$this->host}/api/tokens", [
            'identity' => $this->email,
            'secret' => $this->password,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('NPM authentication failed');
        }

        $this->token = $response->json('token');
    }

    private function request(string $method, string $path, array $data = []): array
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $response = Http::withToken($this->token)
            ->$method("{$this->host}/api{$path}", $data);

        if ($response->failed()) {
            Log::error("NPM API error [{$method} {$path}]: " . $response->body());
            throw new \RuntimeException("NPM API error: " . $response->status());
        }

        return $response->json() ?? [];
    }

    public function createProxyHost(string $domain, string $forwardHost, int $forwardPort = 80, bool $ssl = true): array
    {
        $data = [
            'domain_names' => [$domain],
            'forward_scheme' => 'http',
            'forward_host' => $forwardHost,
            'forward_port' => $forwardPort,
            'block_exploits' => true,
            'allow_websocket_upgrade' => true,
            'http2_support' => false,
        ];

        $host = $this->request('post', '/nginx/proxy-hosts', $data);

        if ($ssl && isset($host['id'])) {
            $this->enableSSL($host['id'], [$domain]);
        }

        return $host;
    }

    public function enableSSL(int $hostId, array $domains): array
    {
        return $this->request('post', "/nginx/proxy-hosts/{$hostId}/enable-ssl", [
            'domain_names' => $domains,
            'meta' => ['letsencrypt_agree' => true],
        ]);
    }

    public function deleteProxyHost(int $hostId): array
    {
        return $this->request('delete', "/nginx/proxy-hosts/{$hostId}");
    }

    public function listProxyHosts(): array
    {
        return $this->request('get', '/nginx/proxy-hosts');
    }

    public function findProxyHostByDomain(string $domain): ?array
    {
        $hosts = $this->listProxyHosts();
        foreach ($hosts as $host) {
            if (in_array($domain, $host['domain_names'] ?? [])) {
                return $host;
            }
        }
        return null;
    }

    public function updateProxyHost(int $hostId, array $data): array
    {
        return $this->request('put', "/nginx/proxy-hosts/{$hostId}", $data);
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
