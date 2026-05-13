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

    private function request(string $controller, string $function, array $data = []): array
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
                'function'       => $function,
            ], $data));

        if ($response->failed()) {
            Log::error("CyberPanel API error [{$controller}/{$function}]: " . $response->body());
            throw new \RuntimeException("HTTP {$response->status()} — " . $response->body());
        }

        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 0) {
            $msg = $result['error_message'] ?? 'Erreur inconnue';
            Log::error("CyberPanel error [{$controller}/{$function}]: {$msg}");
            throw new \RuntimeException("CyberPanel : {$msg}");
        }

        return $result;
    }

    public function createUser(string $username, string $email, string $password, string $package = 'Default'): array
    {
        return $this->request('WebsiteFunctions', 'createWebsite', [
            'domainName'    => $username,
            'ownerEmail'    => $email,
            'ownerPassword' => $password,
            'package'       => $package,
            'websiteOwner'  => $username,
        ]);
    }

    public function changeUserPassword(string $username, string $newPassword): array
    {
        return $this->request('UsersFunctions', 'changeUserPassAPI', [
            'websiteOwner' => $username,
            'newPassword'  => $newPassword,
        ]);
    }

    public function deleteWebsite(string $domain): array
    {
        return $this->request('WebsiteFunctions', 'deleteWebsite', [
            'domainName' => $domain,
        ]);
    }

    public function createWebsite(string $domain, string $ownerUsername, string $package = 'Default'): array
    {
        return $this->request('WebsiteFunctions', 'createWebsite', [
            'domainName'   => $domain,
            'ownerEmail'   => '',
            'package'      => $package,
            'websiteOwner' => $ownerUsername,
        ]);
    }

    public function listWebsites(): array
    {
        return $this->request('WebsiteFunctions', 'listWebsitesJson');
    }

    public function getWebsiteUsage(string $domain): array
    {
        return $this->request('WebsiteFunctions', 'getDomainDiskUsage', [
            'domainName' => $domain,
        ]);
    }

    public function createDatabase(string $domain, string $dbName, string $dbUser, string $dbPassword): array
    {
        return $this->request('DatabaseFunctions', 'createDatabase', [
            'domainName' => $domain,
            'dbName'     => $dbName,
            'dbUsername' => $dbUser,
            'dbPassword' => $dbPassword,
        ]);
    }

    public function testConnection(): bool
    {
        $this->request('WebsiteFunctions', 'listWebsitesJson');
        return true;
    }
}
