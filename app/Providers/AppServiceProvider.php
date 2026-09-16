<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyMailSettings();
    }

    /**
     * Admin\SettingsController::saveMail() only pushes the DB-stored SMTP
     * config into config() for the duration of that one request — every
     * other request (queued jobs, scheduled commands, webhooks) falls back
     * to .env, where MAIL_FROM_NAME defaults to the panel's own APP_NAME
     * rather than the client-facing company name. Apply it here instead,
     * on every boot, so emails sent from anywhere use the real settings.
     */
    private function applyMailSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $fromName = Setting::get('company_name') ?: Setting::get('mail_from_name');

            config([
                'mail.from.name'               => $fromName ?: config('mail.from.name'),
                'mail.from.address'            => Setting::get('mail_from_address') ?: config('mail.from.address'),
                'mail.mailers.smtp.host'       => Setting::get('mail_host') ?: config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port'       => Setting::get('mail_port') ? (int) Setting::get('mail_port') : config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username'   => Setting::get('mail_username') ?: config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password'   => Setting::get('mail_password') ?: config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => Setting::get('mail_encryption') ?: config('mail.mailers.smtp.encryption'),
            ]);
        } catch (\Throwable $e) {
            // Settings table not ready yet (fresh install, migrations running) — keep .env defaults.
        }
    }
}
