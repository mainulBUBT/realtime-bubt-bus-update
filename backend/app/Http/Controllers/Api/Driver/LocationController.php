<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Trip;
use App\Events\BusLocationUpdated;
use App\Services\TripProgressService;
use App\Services\TripTrackingSnapshotService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function __construct(
        private readonly TripProgressService $tripProgressService,
        private readonly TripTrackingSnapshotService $tripTrackingSnapshotService,
    ) {
    }

    /**
     * Update bus location
     */
    public function update(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $trip = Trip::find($request->trip_id);

        if ($trip->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($trip->status !== 'ongoing') {
            return response()->json(['message' => 'Trip is not active'], 400);
        }

        $location = $this->createLocationRecord($trip, [
            'lat' => $request->lat,
            'lng' => $request->lng,
            'speed' => $request->speed,
            'recorded_at' => $request->recorded_at,
        ]);

        $this->updateTripCache($trip, $location);
        $this->tripProgressService->updateTripProgress($trip->fresh(['route.stops', 'latestLocation']), $location);
        $trip->refresh();
        $this->broadcastLatestLocation($trip, $location);

        return response()->json([
            'message' => 'Location updated successfully',
            'location' => $location,
        ]);
    }

    /**
     * Batch update locations (for background tracking)
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'locations' => 'required|array|min:1',
            'locations.*.lat' => 'required|numeric',
            'locations.*.lng' => 'required|numeric',
            'locations.*.speed' => 'nullable|numeric',
            'locations.*.recorded_at' => 'nullable|date',
        ]);

        $trip = Trip::find($request->trip_id);

        if ($trip->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($trip->status !== 'ongoing') {
            return response()->json(['message' => 'Trip is not active'], 400);
        }

        $latestLocation = DB::transaction(function () use ($request, $trip) {
            $latest = null;
            $locations = collect($request->locations)
                ->map(function (array $payload): array {
                    return [
                        ...$payload,
                        '_recorded_at' => !empty($payload['recorded_at'])
                            ? Carbon::parse($payload['recorded_at'])->utc()
                            : now(),
                    ];
                })
                ->sortBy('_recorded_at')
                ->values();

            foreach ($locations as $payload) {
                $location = $this->createLocationRecord($trip, $payload);
                $this->updateTripCache($trip, $location);
                $this->tripProgressService->updateTripProgress($trip->fresh(['route.stops', 'latestLocation']), $location);
                $trip->refresh();

                if (!$latest || $location->recorded_at->greaterThan($latest->recorded_at)) {
                    $latest = $location;
                }
            }

            return $latest;
        });

        if ($latestLocation) {
            $this->broadcastLatestLocation($trip, $latestLocation);
        }

        return response()->json([
            'message' => 'Locations batch updated successfully',
        ]);
    }

    private function createLocationRecord(Trip $trip, array $payload): Location
    {
        return Location::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'lat' => $payload['lat'],
            'lng' => $payload['lng'],
            'speed' => $payload['speed'] ?? null,
            'recorded_at' => $payload['_recorded_at']
                ?? ($payload['recorded_at']
                    ? Carbon::parse($payload['recorded_at'])->utc()
                    : now()),
        ]);
    }

    private function updateTripCache(Trip $trip, Location $location): void
    {
        $trip->update([
            'current_lat' => $location->lat,
            'current_lng' => $location->lng,
            'last_location_at' => $location->recorded_at,
        ]);
    }

    private function broadcastLatestLocation(Trip $trip, Location $location): void
    {
        $trip = $trip->fresh(['route.stops', 'latestLocation']);
        $trackingSnapshot = $this->tripTrackingSnapshotService->snapshot($trip);

        broadcast(new BusLocationUpdated($trip->bus_id, [
            'trip_id' => $trip->id,
            'lat' => $location->lat,
            'lng' => $location->lng,
            'speed' => $location->speed,
            'recorded_at' => $location->recorded_at,
            ...$trackingSnapshot,
        ]))->toOthers();
    }
}
