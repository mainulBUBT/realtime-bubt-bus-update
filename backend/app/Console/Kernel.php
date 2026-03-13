<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily cleanup at midnight (12:05 AM)
        $schedule->job(new \App\Jobs\DailyCleanupJob)->dailyAt('00:05');

        // Clean old locations daily at 2 AM (30-day retention)
        $schedule->job(new \App\Jobs\CleanOldLocations(30))->dailyAt('02:00');

        // Complete expired trips every 5 minutes
        $schedule->job(new \App\Jobs\CompleteExpiredTripsJob)->everyFiveMinutes();

        // Cleanup inactive users every 2 minutes (existing logic)
        $schedule->job(new \App\Jobs\CleanupInactiveUsersJob)->everyTwoMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
