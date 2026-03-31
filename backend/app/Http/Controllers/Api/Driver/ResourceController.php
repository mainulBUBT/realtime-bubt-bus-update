<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * Get available buses for drivers
     */
    public function buses()
    {
        $buses = Bus::where('status', 'active')
            ->whereDoesntHave('trips', function ($query) {
                $query->activeToday();
            })
            ->select('id', 'plate_number', 'code', 'display_name', 'capacity')
            ->get();

        return response()->json($buses);
    }

    /**
     * Get available routes for drivers
     */
    public function routes(Request $request)
    {
        $query = Route::with('stops')
            ->where('is_active', true);

        // Filter by bus if bus_id is provided
        if ($request->has('bus_id')) {
            $busId = $request->input('bus_id');
            // Get route IDs that have schedules for this bus
            $routeIds = Schedule::where('bus_id', $busId)
                ->pluck('route_id')
                ->unique()
                ->toArray();

            // Filter routes to only include those with schedules for this bus
            $query->whereIn('id', $routeIds);
        }

        $routes = $query->select('id', 'name', 'code', 'direction', 'origin_name', 'destination_name')
            ->get();

        return response()->json($routes);
    }
}
