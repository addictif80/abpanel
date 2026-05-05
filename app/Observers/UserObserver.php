<?php

namespace App\Observers;

use App\Models\User;
use App\Services\CyberPanelService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function updated(User $user): void
    {
        if (!$user->wasChanged('password')) {
            return;
        }

        if (!$user->cyberpanel_username) {
            return;
        }

        try {
            app(CyberPanelService::class)->changeUserPassword(
                $user->cyberpanel_username,
                request()->input('password') // plain password before hashing
            );
        } catch (\Exception $e) {
            Log::error("CyberPanel password sync failed for user {$user->id}: " . $e->getMessage());
        }
    }
}
