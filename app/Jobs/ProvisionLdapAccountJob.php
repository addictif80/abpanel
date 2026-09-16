<?php

namespace App\Jobs;

use App\Models\Plan;
use App\Models\User;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionLdapAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 90];

    public function __construct(
        public int $userId,
        public int $planId,
    ) {}

    public function handle(ProvisioningService $provisioning): void
    {
        $user = User::find($this->userId);
        $plan = Plan::find($this->planId);

        if (!$user || !$plan) {
            return;
        }

        $provisioning->provisionLdap($user, $plan);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProvisionLdapAccountJob failed permanently for user {$this->userId}: " . $e->getMessage());
    }
}
