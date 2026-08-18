<?php

namespace App\Jobs;

use App\Models\VirtualMachine;
use App\Services\NginxProxyManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Creates (or repoints) the NPM proxy host for a VM's technical subdomain
 * (vmXXXX.<base_domain>). Safe to dispatch repeatedly: it reuses the stored
 * npm_proxy_id if one already exists instead of creating a duplicate host.
 */
class SyncVmProxyHostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 90];

    public function __construct(public int $vmId) {}

    public function handle(NginxProxyManagerService $npm): void
    {
        $vm = VirtualMachine::find($this->vmId);
        if (!$vm || !$vm->subdomain) {
            return;
        }

        $forwardHost = $vm->tailscale_ip ?: '';

        if ($vm->npm_proxy_id) {
            $npm->updateForwardTarget($vm->npm_proxy_id, $forwardHost);
            return;
        }

        $host = $npm->createProxyHost($vm->subdomain, $forwardHost);

        if (isset($host['id'])) {
            $vm->update(['npm_proxy_id' => $host['id']]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SyncVmProxyHostJob failed permanently for VM {$this->vmId}: " . $e->getMessage());
    }
}
