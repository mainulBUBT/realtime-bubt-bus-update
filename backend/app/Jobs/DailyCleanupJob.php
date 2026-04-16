<?php

namespace App\Jobs;

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyCleanupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $results = [
            'locations_deleted' => 0,
            'trips_archived' => 0,
        ];

        $results['locations_deleted'] = $this->cleanupOldLocations();

        Log::info('Daily cleanup completed', $results);
    }

    /**
     * Cleanup old location records (keep 30 days)
     */
    private function cleanupOldLocations(): int
    {
        return Location::where('created_at', '<', now()->subDays(30))->delete();
    }
}
