<?php

namespace App\Services;

use App\Models\InfraServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyberPanelService
{
    private string $host;
    private string $adminUser;
    private string $apiToken;

    public function __construct(?InfraServer $server = null)
    {
        $server ??= InfraServer::ofType('cyberpanel')->default()->first();
        $this->host = rtrim($server?->get('host', '') ?? '', '/');
        $this->adminUser = $server?->get('user', 'admin') ?? 'admin';
        // CyberPanel's cloudAPI used to authenticate with sha256(username:password);
        // current builds (Users > API Access) issue an opaque "cp_api_v1_..." bearer
        // token instead and reject the old hash scheme with "Invalid login information".
        // Strip a leading "Basic " in case it was copy-pasted from CyberPanel's own
        // display of the full Authorization header value — we add that prefix ourselves.
        $rawToken = trim($server?->get('api_token', '') ?? '');
        $this->apiToken = preg_replace('/^Basic\s+/i', '', $rawToken);
    }

    private function token(): string
    {
        return 'Basic ' . $this->apiToken;
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
            'websitesLimit'   => 0, // unlimited — ABPanel handles billing
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

    /**
     * fetchWebsites paginates (10 per page by default in CyberPanel), so
     * fetching just page 1 silently truncates the list — walk pages until
     * one comes back empty, or repeats the previous page (defends against
     * an API that ignores an out-of-range page number instead of returning
     * nothing).
     */
    public function listAllWebsites(): array
    {
        $all             = [];
        $page            = 1;
        $previousFirstDomain = null;

        while ($page <= 200) {
            $result = $this->request('fetchWebsites', ['page' => $page]);
            $raw = $result['data'] ?? [];
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?? [];
            }
            $sites = array_values(array_filter($raw, fn($s) => isset($s['domain'])));

            if (empty($sites)) {
                break;
            }

            $firstDomain = $sites[0]['domain'];
            if ($page > 1 && $firstDomain === $previousFirstDomain) {
                break;
            }
            $previousFirstDomain = $firstDomain;

            $all = array_merge($all, $sites);
            $page++;
        }

        return $all;
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

    /**
     * Create a CyberPanel user account (without a website).
     * Returns the generated or provided username.
     */
    public function createUser(
        string $username,
        string $password,
        string $email,
        string $fullName = '',
        int $websitesLimit = 0
    ): array {
        return $this->request('submitUserCreation', [
            'userName'       => $username,
            'userPassword'   => $password,
            'confirmPassword'=> $password,
            'firstName'      => $fullName ?: $username,
            'lastName'       => '',
            'email'          => $email,
            'websitesLimit'  => $websitesLimit,
            'selectedACL'    => 'user',
            'securityLevel'  => 'HIGH',
        ]);
    }

    /** Transfer ownership of an existing website to another CyberPanel user. */
    public function changeWebsiteOwner(string $domain, string $newOwner): array
    {
        return $this->request('changeOwner', [
            'domainName' => $domain,
            'newOwner'   => $newOwner,
        ]);
    }

    public function listPackages(): array
    {
        $result = $this->request('fetchPackages');
        $raw = $result['data'] ?? [];
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }
        return array_values(array_filter($raw, fn($p) => isset($p['packageName'])));
    }

    public function testConnection(): bool
    {
        $this->request('fetchWebsites', ['page' => 1]);
        return true;
    }
}
