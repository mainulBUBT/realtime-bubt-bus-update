<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CleanOldLocations implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * The number of days to retain location data.
     */
    private int $retentionDays = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(int $retentionDays = 30)
    {
        $this->retentionDays = $retentionDays;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = now()->subDays($this->retentionDays);
        $totalDeleted = 0;

        do {
            // Delete in batches of 10,000 to prevent memory issues
            $deleted = Location::where('recorded_at', '<', $cutoff)
                ->limit(10000)
                ->delete();

            $totalDeleted += $deleted;

            if ($deleted > 0) {
                // Small delay between batches to prevent database overload
                usleep(10000); // 10ms delay
            }
        } while ($deleted > 0);

        Log::info("Cleaned old locations ({$this->retentionDays} days retention): {$totalDeleted} records deleted");
    }
}
