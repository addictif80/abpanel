<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyberPanelService
{
    private string $host;
    private string $adminUser;
    private string $adminPassword;

    public function __construct()
    {
        $this->host = rtrim(Setting::get('cyberpanel_host', ''), '/');
        $this->adminUser = Setting::get('cyberpanel_user', 'admin');
        $this->adminPassword = Setting::get('cyberpanel_password', '');
    }

    private function request(string $endpoint, array $data = []): array
    {
        $response = Http::withoutVerifying()
            ->timeout(10)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->host}/api/v1/{$endpoint}", array_merge([
                'adminUser' => $this->adminUser,
                'adminPass' => $this->adminPassword,
            ], $data));

        if ($response->failed()) {
            Log::error("CyberPanel API error [{$endpoint}]: " . $response->body());
            throw new \RuntimeException("HTTP {$response->status()} — " . $response->body());
        }

        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 0) {
            throw new \RuntimeException($result['error_message'] ?? 'Authentification refusée');
        }

        return $result;
    }

    public function createUser(string $username, string $email, string $password, string $package = 'Default'): array
    {
        return $this->request('createWebsite', [
            'domainName' => $username,
            'ownerEmail' => $email,
            'ownerPassword' => $password,
            'package' => $package,
            'websiteOwner' => $username,
        ]);
    }

    public function changeUserPassword(string $username, string $newPassword): array
    {
        return $this->request('changeUserPassAPI', [
            'websiteOwner' => $username,
            'newPassword' => $newPassword,
        ]);
    }

    public function deleteWebsite(string $domain): array
    {
        return $this->request('deleteWebsite', [
            'domainName' => $domain,
        ]);
    }

    public function createWebsite(string $domain, string $ownerUsername, string $package = 'Default'): array
    {
        return $this->request('createWebsite', [
            'domainName' => $domain,
            'ownerEmail' => '',
            'package' => $package,
            'websiteOwner' => $ownerUsername,
        ]);
    }

    public function listWebsites(): array
    {
        return $this->request('listWebsitesJson');
    }

    public function getWebsiteUsage(string $domain): array
    {
        return $this->request('getDomainDiskUsage', [
            'domainName' => $domain,
        ]);
    }

    public function createDatabase(string $domain, string $dbName, string $dbUser, string $dbPassword): array
    {
        return $this->request('createDatabase', [
            'domainName' => $domain,
            'dbName' => $dbName,
            'dbUsername' => $dbUser,
            'dbPassword' => $dbPassword,
        ]);
    }

    public function testConnection(): bool
    {
        $this->request('verifyConn');
        return true;
    }
}
