<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * DSM Web API client for the Synology NAS itself — distinct from LdapService,
 * which only talks to the auth VM's directory. LDAP decides who belongs to
 * the "cloud" group; this sets how much storage that user gets on the NAS,
 * since DSM's per-user shared-folder quota is not an LDAP attribute.
 *
 * Session-based auth like ProxmoxService: log in once per request cycle,
 * reuse the sid for subsequent calls.
 */
class SynologyService
{
    private string $host;
    private string $user;
    private string $password;
    private string $sharedFolder;
    private ?string $sid = null;

    public function __construct()
    {
        $this->host         = rtrim(Setting::get('synology_host', '') ?? '', '/');
        $this->user         = Setting::get('synology_user', '') ?? '';
        $this->password     = Setting::get('synology_password', '') ?? '';
        $this->sharedFolder = Setting::get('synology_cloud_shared_folder', '') ?? '';
    }

    private function authenticate(): void
    {
        if (!$this->host || !$this->user) {
            throw new RuntimeException('Configuration Synology (DSM) incomplète.');
        }

        $response = Http::withoutVerifying()->timeout(10)->get("{$this->host}/webapi/auth.cgi", [
            'api'     => 'SYNO.API.Auth',
            'version' => 6,
            'method'  => 'login',
            'account' => $this->user,
            'passwd'  => $this->password,
            'session' => 'ABPanel',
            'format'  => 'sid',
        ]);

        $data = $response->json();
        if (!($data['success'] ?? false)) {
            $code = $data['error']['code'] ?? '?';
            throw new RuntimeException("Authentification DSM échouée (code {$code}). Vérifiez le compte/mot de passe et les autorisations d'accès aux applications dans DSM.");
        }

        $this->sid = $data['data']['sid'];
    }

    private function request(string $api, string $method, array $params = [], int $version = 1): array
    {
        if (!$this->sid) {
            $this->authenticate();
        }

        $response = Http::withoutVerifying()->timeout(15)->get("{$this->host}/webapi/entry.cgi", array_merge([
            'api'     => $api,
            'version' => $version,
            'method'  => $method,
            '_sid'    => $this->sid,
        ], $params));

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} — " . $response->body());
        }

        $data = $response->json();
        if (!($data['success'] ?? false)) {
            $code = $data['error']['code'] ?? '?';
            throw new RuntimeException("Erreur API DSM [{$api}.{$method}] (code {$code})");
        }

        return $data['data'] ?? [];
    }

    public function testConnection(): bool
    {
        $this->authenticate();
        return true;
    }

    /**
     * Set (or update) the DSM shared-folder quota for a user, in GB.
     * Requires "synology_cloud_shared_folder" to be configured (e.g. "/volume1/cloud").
     */
    public function setUserQuota(string $username, int $quotaGb): void
    {
        if (!$this->sharedFolder) {
            throw new RuntimeException('Dossier partagé Synology (cloud) non configuré dans les paramètres.');
        }

        $this->request('SYNO.Core.Quota', 'set', [
            'quota' => json_encode([[
                'path'  => $this->sharedFolder,
                'name'  => $username,
                'limit' => $quotaGb * 1024 * 1024 * 1024,
            ]]),
        ]);
    }

    /** Current quota for a user on the configured shared folder, in GB (null if unset). */
    public function getUserQuota(string $username): ?int
    {
        $data = $this->request('SYNO.Core.Quota', 'list', [
            'path' => $this->sharedFolder,
        ]);

        foreach ($data['items'] ?? [] as $item) {
            if (($item['name'] ?? null) === $username) {
                return isset($item['limit']) ? (int) round($item['limit'] / 1024 / 1024 / 1024) : null;
            }
        }

        return null;
    }
}
