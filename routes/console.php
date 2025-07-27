<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule bus position updates every 30 seconds
Schedule::command('bus:update-positions')
    ->everyThirtySeconds()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule cleanup operations
Schedule::command('bus:cleanup --sessions')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('bus:cleanup --archive')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();
