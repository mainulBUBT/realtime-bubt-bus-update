<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Location;

class TrackingController extends Controller
{
    /**
     * Get all active routes
     */
    public function routes(Request $request)
    {
        $routes = Route::active()
            ->with(['stops', 'schedulePeriod'])
            ->get();

        return response()->json($routes);
    }

    /**
     * Get route details with stops
     */
    public function routeDetail(Request $request, $id)
    {
        $route = Route::with(['stops' => function ($query) {
            $query->orderBy('sequence');
        }, 'schedulePeriod'])->findOrFail($id);

        return response()->json($route);
    }

    /**
     * Get all ongoing/active trips
     */
    public function activeTrips(Request $request)
    {
        $trips = Trip::where('status', 'ongoing')
            ->with(['bus', 'route', 'route.stops', 'driver', 'latestLocation'])
            ->get();

        return response()->json($trips);
    }

    /**
     * Get trip details with location history
     */
    public function tripLocations(Request $request, $tripId)
    {
        $trip = Trip::with(['bus', 'route', 'route.stops', 'driver'])
            ->findOrFail($tripId);

        $locations = Location::where('trip_id', $tripId)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'trip' => $trip,
            'locations' => $locations,
        ]);
    }

    /**
     * Get latest location for a trip
     */
    public function latestLocation(Request $request, $tripId)
    {
        $location = Location::where('trip_id', $tripId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$location) {
            return response()->json(['message' => 'No location data'], 404);
        }

        return response()->json($location);
    }

    /**
     * Get today's schedules with route and bus details
     */
    public function schedules(Request $request)
    {
        $schedules = \App\Models\Schedule::with(['bus', 'route.stops'])
            ->active()
            ->orderBy('departure_time')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'departure_time' => $schedule->departure_time,
                    'weekdays' => $schedule->weekdays,
                    'formatted_weekdays' => $schedule->formatted_weekdays,
                    'is_today' => in_array(strtolower(now()->englishDayOfWeek), $schedule->weekdays ?? []),
                    'bus' => $schedule->bus ? [
                        'id' => $schedule->bus->id,
                        'plate_number' => $schedule->bus->plate_number,
                        'code' => $schedule->bus->code,
                        'capacity' => $schedule->bus->capacity,
                    ] : null,
                    'route' => $schedule->route ? [
                        'id' => $schedule->route->id,
                        'name' => $schedule->route->name,
                        'code' => $schedule->route->code,
                        'direction' => $schedule->route->direction,
                        'origin_name' => $schedule->route->origin_name,
                        'destination_name' => $schedule->route->destination_name,
                        'stops' => $schedule->route->stops->map(fn($s) => [
                            'id' => $s->id,
                            'name' => $s->name,
                            'sequence' => $s->sequence,
                        ]),
                    ] : null,
                ];
            });

        return response()->json($schedules);
    }
}
