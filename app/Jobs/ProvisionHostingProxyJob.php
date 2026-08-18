<?php

namespace App\Jobs;

use App\Models\ClientDomain;
use App\Models\HostingAccount;
use App\Models\Setting;
use App\Services\NginxProxyManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Creates the NPM proxy host + ClientDomain record for a freshly provisioned
 * hosting account, pointing at CyberPanel's Tailscale IP. Mirrors what the
 * client would otherwise do by hand from "Mes domaines" — SSL stays a manual
 * step since it requires DNS to be pointed first.
 */
class ProvisionHostingProxyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 90];

    public function __construct(
        public int $userId,
        public string $domain,
    ) {}

    public function handle(NginxProxyManagerService $npm): void
    {
        if (ClientDomain::withTrashed()->where('domain', $this->domain)->exists()) {
            return;
        }

        $targetIp = Setting::get('cyberpanel_tailscale_ip', '');
        if (!$targetIp) {
            Log::warning("ProvisionHostingProxyJob: cyberpanel_tailscale_ip non configurée, abandon pour {$this->domain}");
            return;
        }

        $proxyHost = $npm->createProxyHostPlain([$this->domain], $targetIp, 80, 'http');

        ClientDomain::create([
            'user_id'            => $this->userId,
            'hosting_account_id' => HostingAccount::where('user_id', $this->userId)
                ->where('domain', $this->domain)
                ->value('id'),
            'domain'             => $this->domain,
            'type'               => 'hosting',
            'target_ip'          => $targetIp,
            'target_port'        => 80,
            'forward_scheme'     => 'http',
            'www_redirect'       => false,
            'npm_proxy_id'       => $proxyHost['id'] ?? null,
            'dns_ok'             => false,
            'dns_checked_at'     => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProvisionHostingProxyJob failed permanently for {$this->domain}: " . $e->getMessage());
    }
}
