<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('visits:monitor-time', function () {
    $result = app(\App\Services\VisitTimeMonitor::class)->process();

    $this->info("Visit monitor complete. Reminders sent: {$result['reminders_sent']}. Missed visits marked: {$result['missed_marked']}.");
})->purpose('Send visit start reminders and mark expired unattended visits as missed');

Schedule::command('visits:monitor-time')->everyMinute()->withoutOverlapping();
