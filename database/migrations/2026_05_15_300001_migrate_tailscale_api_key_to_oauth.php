<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les anciennes clés expirantes
        Setting::where('key', 'tailscale_api_key')->delete();
        Setting::where('key', 'tailscale_auth_key')->delete();

        // Créer les nouveaux champs OAuth si absents
        foreach (['tailscale_oauth_client_id', 'tailscale_oauth_client_secret'] as $key) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::create(['key' => $key, 'value' => '', 'group' => 'tailscale']);
            }
        }
    }

    public function down(): void
    {
        Setting::where('key', 'tailscale_oauth_client_id')->delete();
        Setting::where('key', 'tailscale_oauth_client_secret')->delete();

        foreach (['tailscale_api_key', 'tailscale_auth_key'] as $key) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::create(['key' => $key, 'value' => '', 'group' => 'tailscale']);
            }
        }
    }
};
