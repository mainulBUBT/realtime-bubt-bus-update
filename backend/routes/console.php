<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new \App\Jobs\CompleteExpiredTripsJob)->everyFiveMinutes();
Schedule::job(new \App\Jobs\CleanOldLocations(30))->dailyAt('02:00');
