<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class EncryptSettingSecrets extends Command
{
    protected $signature   = 'settings:encrypt-secrets';
    protected $description = 'Chiffre les identifiants tiers (Proxmox, CyberPanel, LDAP, Synology, NPM, SMTP, Stripe, Tailscale) stockés en clair dans la table settings';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        foreach (Setting::SENSITIVE_KEYS as $key) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting || $setting->value === null || $setting->value === '') {
                continue;
            }

            try {
                Crypt::decryptString($setting->value);
                $skipped++; // already encrypted
                continue;
            } catch (DecryptException) {
                // plaintext legacy value — encrypt it below
            }

            $setting->update(['value' => Crypt::encryptString($setting->value)]);
            Cache::forget("setting_{$key}");
            $updated++;
            $this->line("Chiffré : {$key}");
        }

        $this->info("{$updated} valeur(s) chiffrée(s), {$skipped} déjà chiffrée(s) ou absente(s).");

        return self::SUCCESS;
    }
}
