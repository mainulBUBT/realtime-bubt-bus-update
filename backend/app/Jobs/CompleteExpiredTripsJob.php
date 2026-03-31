<?php

namespace App\Jobs;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CompleteExpiredTripsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $completedCount = 0;

        Trip::staleOngoing()
            ->chunkById(100, function ($trips) use (&$completedCount) {
                foreach ($trips as $trip) {
                    $trip->update([
                        'status' => 'completed',
                        'ended_at' => $this->resolveEndedAt($trip),
                    ]);

                    $completedCount++;

                    Log::info('Completed stale trip', [
                        'trip_id' => $trip->id,
                        'bus_id' => $trip->bus_id,
                        'driver_id' => $trip->driver_id,
                        'trip_date' => (string) $trip->trip_date,
                    ]);
                }
            });

        if ($completedCount > 0) {
            Log::info("Completed {$completedCount} stale trips.");
        }
    }

    private function resolveEndedAt(Trip $trip): Carbon
    {
        if ($trip->last_location_at) {
            return Carbon::parse($trip->last_location_at);
        }

        return Carbon::parse($trip->trip_date)->endOfDay();
    }
}
