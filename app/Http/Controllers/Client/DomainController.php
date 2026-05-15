<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientDomain;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\NginxProxyManagerService;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index()
    {
        $domains = auth()->user()
            ->clientDomains()
            ->with(['virtualMachine', 'hostingAccount'])
            ->latest()
            ->get();

        $npmPublicIp = $this->npmPublicIp();

        return view('client.domains.index', compact('domains', 'npmPublicIp'));
    }

    public function create()
    {
        $vps      = auth()->user()->virtualMachines()->where('status', 'running')->get();
        $hosting  = auth()->user()->hostingAccounts()->where('is_active', true)->get();
        $npmPublicIp = $this->npmPublicIp();
        $cyberpanelIp = Setting::get('cyberpanel_tailscale_ip', '');

        return view('client.domains.create', compact('vps', 'hosting', 'npmPublicIp', 'cyberpanelIp'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain'         => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]{0,251}[a-zA-Z0-9])?$/'],
            'type'           => 'required|in:vps,hosting',
            'target_port'    => 'required|integer|min:1|max:65535',
            'forward_scheme' => 'required|in:http,https',
            'www_redirect'   => 'boolean',
            'vps_id'         => 'required_if:type,vps|nullable|exists:virtual_machines,id',
            'hosting_id'     => 'required_if:type,hosting|nullable|exists:hosting_accounts,id',
        ]);

        $domain = strtolower(trim($request->domain));

        // Port blacklist
        $blacklistedPorts = [22, 23, 25, 110, 143, 3306, 5432, 6379, 27017];
        if (in_array((int) $request->target_port, $blacklistedPorts)) {
            return back()->withErrors(['target_port' => 'Ce port n\'est pas autorisé.'])->withInput();
        }

        // Cooldown check
        $cooldown = (int) Setting::get('domain_deletion_cooldown', 60);
        $blocked  = ClientDomain::withTrashed()
            ->where('domain', $domain)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '>', now()->subMinutes($cooldown))
            ->exists();

        if ($blocked) {
            return back()->withErrors(['domain' => "Ce domaine a été récemment résilié. Veuillez attendre {$cooldown} minute(s) avant de le recréer."])->withInput();
        }

        // Already active
        if (ClientDomain::where('domain', $domain)->exists()) {
            return back()->withErrors(['domain' => 'Ce domaine est déjà configuré.'])->withInput();
        }

        // Resolve target IP
        $targetIp   = null;
        $vmId       = null;
        $hostingId  = null;

        if ($request->type === 'vps') {
            $vm = auth()->user()->virtualMachines()->findOrFail($request->vps_id);
            $targetIp = $vm->tailscale_ip;
            $vmId     = $vm->id;
            if (!$targetIp) {
                return back()->withErrors(['vps_id' => 'Ce VPS n\'a pas d\'IP Tailscale renseignée.'])->withInput();
            }
        } else {
            $targetIp  = Setting::get('cyberpanel_tailscale_ip', '');
            $hostingId = $request->hosting_id;
            if (!$targetIp) {
                return back()->withErrors(['type' => 'L\'IP Tailscale de CyberPanel n\'est pas configurée (contactez le support).'])->withInput();
            }
        }

        // DNS check
        $npmIp  = $this->npmPublicIp();
        $dnsOk  = $this->checkDns($domain, $npmIp);

        // Create NPM proxy host
        $npm      = app(NginxProxyManagerService::class);
        $allNames = $request->boolean('www_redirect')
            ? array_unique([$domain, 'www.' . ltrim($domain, 'www.')])
            : [$domain];

        try {
            $proxyHost = $npm->createProxyHostPlain(
                $allNames,
                $targetIp,
                (int) $request->target_port,
                $request->forward_scheme
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['domain' => 'Erreur NPM : ' . $e->getMessage()])->withInput();
        }

        ClientDomain::create([
            'user_id'            => auth()->id(),
            'virtual_machine_id' => $vmId,
            'hosting_account_id' => $hostingId,
            'domain'             => $domain,
            'type'               => $request->type,
            'target_ip'          => $targetIp,
            'target_port'        => (int) $request->target_port,
            'forward_scheme'     => $request->forward_scheme,
            'www_redirect'       => $request->boolean('www_redirect'),
            'npm_proxy_id'       => $proxyHost['id'] ?? null,
            'dns_ok'             => $dnsOk,
            'dns_checked_at'     => now(),
        ]);

        return redirect()->route('client.domains.index')
            ->with('success', "Domaine « {$domain} » configuré." . ($dnsOk ? '' : ' Le DNS ne pointe pas encore vers notre serveur.'));
    }

    public function checkDnsAjax(Request $request)
    {
        $request->validate(['domain' => 'required|string']);
        $domain  = strtolower(trim($request->domain));
        $npmIp   = $this->npmPublicIp();
        $ok      = $this->checkDns($domain, $npmIp);
        $resolved = gethostbyname($domain);

        // Update DB record if it exists
        ClientDomain::where('domain', $domain)
            ->where('user_id', auth()->id())
            ->update(['dns_ok' => $ok, 'dns_checked_at' => now()]);

        return response()->json([
            'ok'       => $ok,
            'npm_ip'   => $npmIp,
            'resolved' => $resolved !== $domain ? $resolved : null,
        ]);
    }

    public function enableSsl(Request $request, ClientDomain $domain)
    {
        $this->authorizeDomain($domain);

        if (!$domain->npm_proxy_id) {
            return back()->with('error', 'Proxy NPM introuvable.');
        }

        if (!$domain->dns_ok) {
            return back()->with('error', 'Le DNS doit d\'abord pointer vers notre serveur avant d\'activer le SSL.');
        }

        $npm   = app(NginxProxyManagerService::class);
        $email = Setting::get('mail_from_address', auth()->user()->email);
        $user  = auth()->user();

        try {
            $cert = $npm->requestLetsEncryptCertificate($domain->allDomainNames(), $email);
            $certId = $cert['id'] ?? null;

            if (!$certId) {
                throw new \RuntimeException('Aucun ID de certificat retourné par NPM.');
            }

            $npm->attachCertificateToHost($domain->npm_proxy_id, $certId);

            $expiryRaw = $npm->getCertificateExpiry($certId);
            $expiresAt = $expiryRaw ? \Carbon\Carbon::parse($expiryRaw) : now()->addDays(90);

            $domain->update([
                'ssl_enabled'        => true,
                'ssl_certificate_id' => $certId,
                'ssl_expires_at'     => $expiresAt,
            ]);

            try {
                app(MailService::class)->sendFromTemplate('domain_ssl_enabled', $user->email, [
                    'first_name' => $user->first_name ?? $user->name,
                    'domain'     => $domain->domain,
                    'expires_at' => $expiresAt->format('d/m/Y'),
                ]);
            } catch (\Throwable) {}

            return back()->with('success', 'SSL Let\'s Encrypt activé pour ' . $domain->domain . '.');
        } catch (\Throwable $e) {
            try {
                app(MailService::class)->sendFromTemplate('domain_ssl_failed', $user->email, [
                    'first_name' => $user->first_name ?? $user->name,
                    'domain'     => $domain->domain,
                    'error'      => $e->getMessage(),
                ]);
            } catch (\Throwable) {}

            return back()->with('error', 'Échec SSL : ' . $e->getMessage());
        }
    }

    public function destroy(ClientDomain $domain)
    {
        $this->authorizeDomain($domain);

        if ($domain->npm_proxy_id) {
            try {
                app(NginxProxyManagerService::class)->deleteProxyHost($domain->npm_proxy_id);
            } catch (\Throwable) {}
        }

        $domain->delete(); // soft delete

        return back()->with('success', "Domaine « {$domain->domain} » supprimé.");
    }

    private function authorizeDomain(ClientDomain $domain): void
    {
        if ($domain->user_id !== auth()->id()) abort(403);
    }

    private function npmPublicIp(): string
    {
        $npmHost = Setting::get('npm_host', '');
        if (!$npmHost) return '';
        return gethostbyname(parse_url($npmHost, PHP_URL_HOST) ?: '');
    }

    private function checkDns(string $domain, string $expectedIp): bool
    {
        if (!$expectedIp) return false;
        $records = @dns_get_record($domain, DNS_A);
        if (!$records) return false;
        foreach ($records as $r) {
            if (($r['ip'] ?? '') === $expectedIp) return true;
        }
        return false;
    }
}
