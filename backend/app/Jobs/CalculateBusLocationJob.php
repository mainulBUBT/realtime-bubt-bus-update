<?php

namespace App\Jobs;

use App\Events\BusLocationUpdated;
use App\Models\Bus;
use App\Models\BusLocation;
use App\Models\SystemSetting;
use App\Models\UserLocation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalculateBusLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $busId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $busId)
    {
        $this->busId = $busId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bus = Bus::find($this->busId);
        if (!$bus) return;

        // 1. Get Settings
        $maxAge = SystemSetting::getValue('location_max_age', 120);
        $topUsersCount = SystemSetting::getValue('top_users_for_calculation', 15);
        $proximityThreshold = SystemSetting::getValue('route_proximity_threshold', 100);

        // 2. Fetch Recent User Locations
        $locations = UserLocation::where('bus_id', $this->busId)
            ->recent($maxAge / 60) // Convert seconds to minutes
            ->orderBy('created_at', 'desc')
            ->get();

        if ($locations->isEmpty()) return;

        // 3. Filter by Route Proximity (if route exists)
        $route = $bus->currentRoute();
        $validLocations = $locations;

        if ($route) {
            $validLocations = $locations->filter(function ($loc) use ($route, $proximityThreshold) {
                return $route->isPointNearRoute($loc->lat, $loc->lng, $proximityThreshold);
            });
        }

        // If no valid locations near route, fallback to all recent locations (or handle as outlier)
        if ($validLocations->isEmpty()) {
            $validLocations = $locations;
        }

        // 4. Select Top Users (most recent/accurate)
        // For now, we just take the most recent ones. 
        // Future: Weight by user reputation and GPS accuracy.
        $selectedLocations = $validLocations->take($topUsersCount);

        // 5. Calculate Average Position
        $avgLat = $selectedLocations->avg('lat');
        $avgLng = $selectedLocations->avg('lng');
        $activeUsersCount = $bus->getActiveUsersCount();

        // 6. Save Calculated Location
        $busLocation = BusLocation::create([
            'bus_id' => $this->busId,
            'lat' => $avgLat,
            'lng' => $avgLng,
            'active_users_count' => $activeUsersCount,
            'accuracy_score' => 1.0, // Placeholder for now
            'calculated_at' => now(),
        ]);

        // 7. Broadcast Event
        BusLocationUpdated::dispatch($this->busId, [
            'lat' => $avgLat,
            'lng' => $avgLng,
            'active_users' => $activeUsersCount,
            'calculated_at' => now(),
        ]);

        // Release lock
        Cache::forget("calculating_bus_{$this->busId}");
    }
}
