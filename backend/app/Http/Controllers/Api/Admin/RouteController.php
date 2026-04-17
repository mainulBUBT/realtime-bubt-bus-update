<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\RouteStop;

class RouteController extends Controller
{
    /**
     * Display a listing of routes.
     */
    public function index()
    {
        $routes = Route::with(['schedulePeriod', 'stops'])->get();
        return response()->json($routes);
    }

    /**
     * Store a newly created route.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'direction' => 'required|in:up,down',
            'origin_name' => 'required|string',
            'destination_name' => 'required|string',
            'polyline' => 'nullable|array',
            'stops' => 'required|array',
            'stops.*.name' => 'required|string',
            'stops.*.lat' => 'required|numeric',
            'stops.*.lng' => 'required|numeric',
            'stops.*.sequence' => 'required|integer',
        ]);

        $route = Route::create([
            'name' => $request->name,
            'schedule_period_id' => $request->schedule_period_id,
            'direction' => $request->direction,
            'origin_name' => $request->origin_name,
            'destination_name' => $request->destination_name,
            'polyline' => $request->polyline,
        ]);

        // Create stops
        foreach ($request->stops as $stop) {
            RouteStop::create([
                'route_id' => $route->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        return response()->json($route->load('stops'), 201);
    }

    /**
     * Display the specified route.
     */
    public function show(Route $route)
    {
        return response()->json($route->load(['schedulePeriod', 'stops', 'schedules', 'trips']));
    }

    /**
     * Update the specified route.
     */
    public function update(Request $request, Route $route)
    {
        $request->validate([
            'name' => 'required|string',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'direction' => 'required|in:up,down',
            'origin_name' => 'required|string',
            'destination_name' => 'required|string',
            'polyline' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $route->update($request->all());

        // Update stops if provided
        if ($request->has('stops')) {
            $route->stops()->delete();
            foreach ($request->stops as $stop) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'name' => $stop['name'],
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'sequence' => $stop['sequence'],
                ]);
            }
        }

        return response()->json($route->load('stops'));
    }

    /**
     * Remove the specified route.
     */
    public function destroy(Route $route)
    {
        $route->delete();
        return response()->json(['message' => 'Route deleted successfully']);
    }
}
