<?php

namespace App\Jobs;

use App\Models\BusActiveUser;
use App\Models\BusTrip;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CompleteExpiredTripsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inactiveMinutes = SystemSetting::getValue('auto_complete_trips_after_minutes', 10);
        $bufferHours = SystemSetting::getValue('trip_duration_buffer_hours', 4);

        // Find all active trips that should be completed
        $activeTrips = BusTrip::active()->get();

        $completedCount = 0;

        foreach ($activeTrips as $trip) {
            if ($trip->shouldComplete((int)$inactiveMinutes, (int)$bufferHours)) {
                $trip->completeTrip();
                $completedCount++;

                Log::info("Trip completed", [
                    'trip_id' => $trip->id,
                    'bus_id' => $trip->bus_id,
                    'trip_date' => $trip->trip_date,
                ]);
            }
        }

        if ($completedCount > 0) {
            Log::info("Completed {$completedCount} expired trips.");
        }
    }
}
