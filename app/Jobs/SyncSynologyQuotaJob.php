<?php

namespace App\Jobs;

use App\Models\InfraServer;
use App\Models\User;
use App\Services\SynologyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSynologyQuotaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 90];

    public function __construct(
        public int $userId,
        public int $quotaGb,
        public ?int $synologyServerId = null,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user || !$user->ldap_username) {
            return;
        }

        $server = $this->synologyServerId ? InfraServer::find($this->synologyServerId) : null;
        $synology = new SynologyService($server);

        $synology->setUserQuota($user->ldap_username, $this->quotaGb);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SyncSynologyQuotaJob failed permanently for user {$this->userId}: " . $e->getMessage());
    }
}
