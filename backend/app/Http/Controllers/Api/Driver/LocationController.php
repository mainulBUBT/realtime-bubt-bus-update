<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Trip;
use App\Events\BusLocationUpdated;

class LocationController extends Controller
{
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
        ]);

        // Get the trip
        $trip = Trip::find($request->trip_id);

        // Validate trip belongs to driver
        if ($trip->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate trip is ongoing
        if ($trip->status !== 'ongoing') {
            return response()->json(['message' => 'Trip is not active'], 400);
        }

        // Upsert location — 1 row per bus (keeps table lean, always current position)
        $location = Location::updateOrCreate(
            ['trip_id' => $trip->id, 'bus_id' => $trip->bus_id],
            [
                'lat'         => $request->lat,
                'lng'         => $request->lng,
                'speed'       => $request->speed,
                'recorded_at' => now(),
            ]
        );

        // Update trip's current location cache for instant map load
        $trip->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
            'last_location_at' => now(),
        ]);

        // Broadcast real-time location update via Reverb
        broadcast(new BusLocationUpdated($trip->bus_id, [
            'lat'         => $location->lat,
            'lng'         => $location->lng,
            'speed'       => $location->speed,
            'recorded_at' => $location->recorded_at,
        ]))->toOthers();

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
            'locations' => 'required|array',
            'locations.*.lat' => 'required|numeric',
            'locations.*.lng' => 'required|numeric',
            'locations.*.speed' => 'nullable|numeric',
        ]);

        $trip = Trip::find($request->trip_id);

        if ($trip->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $location = null;
        $latestLat = null;
        $latestLng = null;

        foreach ($request->locations as $loc) {
            $latestLat = $loc['lat'];
            $latestLng = $loc['lng'];

            // Each iteration replaces the previous — only the latest survives (1 row per bus)
            $location = Location::updateOrCreate(
                ['trip_id' => $trip->id, 'bus_id' => $trip->bus_id],
                [
                    'lat'         => $latestLat,
                    'lng'         => $latestLng,
                    'speed'       => $loc['speed'] ?? null,
                    'recorded_at' => now(),
                ]
            );
        }

        // Update trip's current location cache with the latest location
        if ($latestLat && $latestLng) {
            $trip->update([
                'current_lat' => $latestLat,
                'current_lng' => $latestLng,
                'last_location_at' => now(),
            ]);
        }

        // Broadcast the final (latest) location for this trip
        if ($location) {
            broadcast(new BusLocationUpdated($trip->bus_id, [
                'lat'         => $location->lat,
                'lng'         => $location->lng,
                'speed'       => $location->speed,
                'recorded_at' => $location->recorded_at,
            ]))->toOthers();
        }

        return response()->json([
            'message' => 'Locations batch updated successfully',
        ]);
    }
}
