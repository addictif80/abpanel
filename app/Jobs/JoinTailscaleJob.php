<?php

namespace App\Jobs;

use App\Models\VirtualMachine;
use App\Services\ProxmoxService;
use App\Services\TailscaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Waits for a freshly-started QEMU VM's guest agent to come online, installs
 * and joins Tailscale via a one-time auth key, then polls the Tailscale API
 * until the device's IP shows up. Re-dispatches itself with a delay instead
 * of blocking a queue worker while it waits.
 *
 * Only applies to QEMU (guest agent exec). LXC containers have no equivalent
 * API for arbitrary command execution — their template must already ship
 * with Tailscale pre-configured, or the admin must set the IP manually /
 * rely on the `vm:sync-tailscale` scheduled sync.
 */
class JoinTailscaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    private const MAX_ATTEMPTS = 40; // ~13 min at 20s between attempts
    private const RETRY_DELAY_SECONDS = 20;

    public function __construct(
        public int $vmId,
        public int $attempt = 0,
    ) {}

    public function handle(ProxmoxService $proxmox, TailscaleService $tailscale): void
    {
        $vm = VirtualMachine::find($this->vmId);

        if (!$vm || $vm->tailscale_ip || $vm->vm_type === 'lxc' || $vm->status !== 'running') {
            return;
        }

        if (!$tailscale->isConfigured()) {
            Log::warning("JoinTailscaleJob: Tailscale OAuth non configuré, abandon pour VM {$vm->id}");
            return;
        }

        if (!$proxmox->pingGuestAgent($vm->proxmox_node, (int) $vm->proxmox_vmid)) {
            $this->retryOrGiveUp("guest agent not responding yet");
            return;
        }

        $joinCacheKey = "tailscale-join-sent:{$vm->id}";

        if (!Cache::has($joinCacheKey)) {
            try {
                $authKey = $tailscale->createAuthKey(description: $vm->name, expirySeconds: 3600);
                $script  = TailscaleService::cloudInitScript($authKey, $vm->name);
                $proxmox->execInGuest($vm->proxmox_node, (int) $vm->proxmox_vmid, ['/bin/bash', '-c', $script]);
                Cache::put($joinCacheKey, true, now()->addHours(2));
            } catch (\Throwable $e) {
                Log::error("JoinTailscaleJob: exec failed for VM {$vm->id}: " . $e->getMessage());
                $this->retryOrGiveUp('exec failed: ' . $e->getMessage());
                return;
            }
        }

        try {
            $ip = $tailscale->findIpByHostname($vm->name);
        } catch (\Throwable $e) {
            Log::warning("JoinTailscaleJob: Tailscale API lookup failed for VM {$vm->id}: " . $e->getMessage());
            $ip = null;
        }

        if ($ip) {
            $vm->update(['tailscale_ip' => $ip]);
            Cache::forget($joinCacheKey);
            SyncVmProxyHostJob::dispatch($vm->id);
            return;
        }

        $this->retryOrGiveUp('no Tailscale IP yet');
    }

    private function retryOrGiveUp(string $reason): void
    {
        if ($this->attempt >= self::MAX_ATTEMPTS) {
            Log::warning("JoinTailscaleJob: giving up for VM {$this->vmId} after " . self::MAX_ATTEMPTS . " attempts ({$reason}). L'IP Tailscale devra être renseignée manuellement ou via `vm:sync-tailscale`.");
            return;
        }

        self::dispatch($this->vmId, $this->attempt + 1)
            ->delay(now()->addSeconds(self::RETRY_DELAY_SECONDS));
    }
}
