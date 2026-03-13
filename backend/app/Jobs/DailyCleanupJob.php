<?php

namespace App\Jobs;

use App\Models\BusLocation;
use App\Models\BusTrip;
use App\Models\SystemSetting;
use App\Models\UserLocation;
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
        $userLocationsDays = SystemSetting::getValue('cleanup_old_user_locations_days', 30);
        $busLocationsDays = SystemSetting::getValue('cleanup_old_bus_locations_days', 7);
        $completedTripsDays = SystemSetting::getValue('cleanup_completed_trips_days', 90);

        $results = [
            'user_locations_deleted' => 0,
            'bus_locations_deleted' => 0,
            'trips_archived' => 0,
        ];

        // Cleanup old user locations (batch operation for performance)
        $results['user_locations_deleted'] = $this->cleanupOldUserLocations((int)$userLocationsDays);

        // Cleanup old bus locations (batch operation)
        $results['bus_locations_deleted'] = $this->cleanupOldBusLocations((int)$busLocationsDays);

        // Archive/delete completed trips older than threshold
        $results['trips_archived'] = $this->archiveOldTrips((int)$completedTripsDays);

        Log::info('Daily cleanup completed', $results);
    }

    /**
     * Cleanup old user location records
     */
    private function cleanupOldUserLocations(int $days): int
    {
        $cutoffDate = now()->subDays($days);

        // Use chunk deletion for performance
        $deleted = 0;
        do {
            $deletedBatch = UserLocation::olderThan($days)
                ->where('created_at', '<', $cutoffDate)
                ->limit(1000)
                ->delete();
            $deleted += $deletedBatch;
        } while ($deletedBatch > 0);

        return $deleted;
    }

    /**
     * Cleanup old bus location records
     */
    private function cleanupOldBusLocations(int $days): int
    {
        $cutoffDate = now()->subDays($days);

        // Use chunk deletion for performance
        $deleted = 0;
        do {
            $deletedBatch = BusLocation::olderThan($days)
                ->where('calculated_at', '<', $cutoffDate)
                ->limit(1000)
                ->delete();
            $deleted += $deletedBatch;
        } while ($deletedBatch > 0);

        return $deleted;
    }

    /**
     * Archive old completed trips (soft delete)
     */
    private function archiveOldTrips(int $days): int
    {
        $cutoffDate = now()->subDays($days);

        $archived = BusTrip::completed()
            ->where('actual_ended_at', '<', $cutoffDate)
            ->update(['deleted_at' => now()]);

        return $archived;
    }
}
