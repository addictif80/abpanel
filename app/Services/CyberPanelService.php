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

    private function token(): string
    {
        return 'Basic ' . hash('sha256', $this->adminUser . ':' . $this->adminPassword);
    }

    private function request(string $controller, array $data = []): array
    {
        $response = Http::withoutVerifying()
            ->timeout(15)
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $this->token(),
            ])
            ->post("{$this->host}/cloudAPI/", array_merge([
                'serverUserName' => $this->adminUser,
                'controller'     => $controller,
            ], $data));

        if ($response->failed()) {
            Log::error("CyberPanel API error [{$controller}]: " . $response->body());
            throw new \RuntimeException("HTTP {$response->status()} — " . $response->body());
        }

        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 0) {
            $msg = $result['error_message'] ?? 'Erreur inconnue';
            Log::error("CyberPanel error [{$controller}]: {$msg}");
            throw new \RuntimeException("CyberPanel : {$msg}");
        }

        return $result;
    }

    /**
     * Create a CyberPanel user + website in one call.
     * UserAccountName becomes both the login and the domain owner.
     */
    public function createWebsite(
        string $domain,
        string $username,
        string $password,
        string $email,
        string $fullName = '',
        string $package = 'Default'
    ): array {
        return $this->request('submitWebsiteCreation', [
            'domainName'      => $domain,
            'ownerEmail'      => $email,
            'adminEmail'      => $email,
            'websiteOwner'    => $username,
            'package'         => $package,
            'UserAccountName' => $username,
            'UserPassword'    => $password,
            'FullName'        => $fullName ?: $username,
            'websitesLimit'   => 1,
        ]);
    }

    public function deleteWebsite(string $domain): array
    {
        return $this->request('submitWebsiteDeletion', [
            'domainName' => $domain,
        ]);
    }

    public function listWebsites(int $page = 1): array
    {
        return $this->request('fetchWebsites', ['page' => $page]);
    }

    public function getWebsiteData(string $domain): array
    {
        return $this->request('fetchWebsiteDataJSON', [
            'domainName' => $domain,
        ]);
    }

    public function createDatabase(string $domain, string $dbName, string $dbUser, string $dbPassword): array
    {
        return $this->request('submitDBCreation', [
            'domainName' => $domain,
            'dbName'     => $dbName,
            'dbUsername' => $dbUser,
            'dbPassword' => $dbPassword,
        ]);
    }

    public function listPackages(): array
    {
        $result = $this->request('fetchPackagesJson');
        // Returns array of package names
        $raw = $result['packages'] ?? $result['data'] ?? [];
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }
        return array_values(array_filter(array_map(
            fn($p) => is_array($p) ? ($p['packageName'] ?? $p['name'] ?? null) : $p,
            $raw
        )));
    }

    public function testConnection(): bool
    {
        $this->request('fetchWebsites', ['page' => 1]);
        return true;
    }
}
