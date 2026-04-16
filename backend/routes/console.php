<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule jobs
Schedule::job(new \App\Jobs\DailyCleanupJob)->dailyAt('00:05'); // Daily cleanup at midnight (12:05 AM)
Schedule::job(new \App\Jobs\CleanOldLocations(30))->dailyAt('02:00'); // Clean old locations daily at 2 AM (30-day retention)
Schedule::job(new \App\Jobs\CompleteExpiredTripsJob)->everyFiveMinutes(); // Complete expired trips every 5 minutes
