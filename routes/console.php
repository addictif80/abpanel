<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run reminder checks every day at 8h
Schedule::command('reminders:send')->dailyAt('08:00')->withoutOverlapping();

// Safety net: catches any VM whose Tailscale IP wasn't picked up by
// JoinTailscaleJob (e.g. LXC containers, or a QEMU VM whose join failed).
Schedule::command('vm:sync-tailscale')->everyFiveMinutes()->withoutOverlapping();

// The command itself checks urssaf_report_enabled/urssaf_report_day and is a
// no-op most days; running it daily just lets it fire on the configured day.
Schedule::command('urssaf:report')->dailyAt('09:00')->withoutOverlapping();

// GDPR storage-limitation: mail_logs holds full email bodies indefinitely
// otherwise. See mail_logs_retention_months in Settings (default 12 months).
Schedule::command('mail-logs:purge')->dailyAt('03:00')->withoutOverlapping();
