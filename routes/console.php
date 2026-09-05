<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run reminder checks every day at 8h
Schedule::command('reminders:send')->dailyAt('08:00');

// Check daily whether it's the configured day to send the monthly URSSAF report
Schedule::command('urssaf:report')->dailyAt('09:00');
