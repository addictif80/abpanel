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
        $response = Http::timeout(10)->post("{$this->host}/api/tokens", [
            'identity' => $this->email,
            'secret' => $this->password,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("HTTP {$response->status()} — " . $response->body());
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
            'domain_names'            => [$domain],
            'forward_scheme'          => 'http',
            'forward_host'            => $forwardHost,
            'forward_port'            => $forwardPort,
            'block_exploits'          => true,
            'allow_websocket_upgrade' => true,
            'http2_support'           => false,
        ];

        $host = $this->request('post', '/nginx/proxy-hosts', $data);

        if ($ssl && isset($host['id'])) {
            $this->enableSSL($host['id'], [$domain]);
        }

        return $host;
    }

    /** Create a proxy host without SSL. Returns the host record with its id. */
    public function createProxyHostPlain(
        array  $domainNames,
        string $forwardHost,
        int    $forwardPort,
        string $forwardScheme = 'http'
    ): array {
        return $this->request('post', '/nginx/proxy-hosts', [
            'domain_names'            => $domainNames,
            'forward_scheme'          => $forwardScheme,
            'forward_host'            => $forwardHost,
            'forward_port'            => $forwardPort,
            'block_exploits'          => true,
            'allow_websocket_upgrade' => true,
            'http2_support'           => false,
            'ssl_forced'              => false,
            'certificate_id'          => 0,
        ]);
    }

    /**
     * Request a Let's Encrypt certificate via NPM and return the certificate record.
     * Throws on failure.
     */
    public function requestLetsEncryptCertificate(array $domainNames, string $email): array
    {
        return $this->request('post', '/nginx/certificates', [
            'provider'     => 'letsencrypt',
            'domain_names' => $domainNames,
            'meta'         => [
                'letsencrypt_agree' => true,
                'letsencrypt_email' => $email,
                'dns_challenge'     => false,
            ],
        ]);
    }

    /** Attach an existing certificate to a proxy host and force SSL. */
    public function attachCertificateToHost(int $hostId, int $certificateId): array
    {
        $host = $this->getProxyHost($hostId);
        return $this->request('put', "/nginx/proxy-hosts/{$hostId}", array_merge($host, [
            'certificate_id' => $certificateId,
            'ssl_forced'     => true,
            'http2_support'  => true,
            'hsts_enabled'   => false,
        ]));
    }

    /** Get a single proxy host record including certificate info. */
    public function getProxyHost(int $hostId): array
    {
        return $this->request('get', "/nginx/proxy-hosts/{$hostId}");
    }

    /** Get certificate expiry date from NPM (ISO string or null). */
    public function getCertificateExpiry(int $certId): ?string
    {
        try {
            $cert = $this->request('get', "/nginx/certificates/{$certId}");
            return $cert['expires_on'] ?? $cert['meta']['letsencrypt_certificate']['notAfter'] ?? null;
        } catch (\Throwable) {
            return null;
        }
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

    /** Repoint an existing proxy host at a new forward target, keeping its other settings. */
    public function updateForwardTarget(int $hostId, string $forwardHost, ?int $forwardPort = null): array
    {
        $host = $this->getProxyHost($hostId);
        $data = array_merge($host, ['forward_host' => $forwardHost]);
        if ($forwardPort !== null) {
            $data['forward_port'] = $forwardPort;
        }

        return $this->request('put', "/nginx/proxy-hosts/{$hostId}", $data);
    }

    public function testConnection(): bool
    {
        $this->authenticate();
        return true;
    }
}
