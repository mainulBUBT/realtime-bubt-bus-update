<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteStop;
use App\Services\OsrmSegmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    /**
     * Display a listing of routes.
     */
    public function index(Request $request)
    {
        $query = Route::withCount('stops');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('origin_name', 'like', "%{$search}%")
                  ->orWhere('destination_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        // Filter by direction
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        $routes = $query
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.routes.index', compact('routes'));
    }

    /**
     * Show the form for creating a new route.
     */
    public function create()
    {
        return view('admin.routes.create');
    }

    /**
     * Store a newly created route with stops.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'direction' => 'required|in:outbound,inbound',
            'origin_name' => 'required|string|max:255',
            'destination_name' => 'required|string|max:255',
            'polyline' => 'nullable|array',
            'is_active' => 'boolean',
            'stops' => 'nullable|array',
            'stops.*.name' => 'required|string|max:255',
            'stops.*.lat' => 'required|numeric',
            'stops.*.lng' => 'required|numeric',
            'stops.*.sequence' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            // Create route
            $route = Route::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'direction' => $validated['direction'],
                'origin_name' => $validated['origin_name'],
                'destination_name' => $validated['destination_name'],
                'polyline' => $validated['polyline'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create stops if provided
            if (!empty($validated['stops'])) {
                foreach ($validated['stops'] as $stop) {
                    RouteStop::create([
                        'route_id' => $route->id,
                        'name' => $stop['name'],
                        'lat' => $stop['lat'],
                        'lng' => $stop['lng'],
                        'sequence' => $stop['sequence'],
                    ]);
                }
            }

            // Compute road distances for route segments
            app(OsrmSegmentService::class)->computeForRoute($route);
        });

        return redirect()->route('admin.routes.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Route created successfully.']]);
    }

    /**
     * Display the specified route.
     */
    public function show(Route $route)
    {
        $route->load('stops');
        return view('admin.routes.show', compact('route'));
    }

    /**
     * Show the form for editing the specified route.
     */
    public function edit(Route $route)
    {
        $route->load('stops');
        return view('admin.routes.edit', compact('route'));
    }

    /**
     * Update the specified route with stops.
     */
    public function update(Request $request, Route $route)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'direction' => 'required|in:outbound,inbound',
            'origin_name' => 'required|string|max:255',
            'destination_name' => 'required|string|max:255',
            'polyline' => 'nullable|array',
            'is_active' => 'boolean',
            'stops' => 'nullable|array',
            'stops.*.name' => 'required|string|max:255',
            'stops.*.lat' => 'required|numeric',
            'stops.*.lng' => 'required|numeric',
            'stops.*.sequence' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $route) {
            // Update route
            $route->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'direction' => $validated['direction'],
                'origin_name' => $validated['origin_name'],
                'destination_name' => $validated['destination_name'],
                'polyline' => $validated['polyline'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delete existing stops and create new ones
            $route->stops()->delete();

            if (!empty($validated['stops'])) {
                foreach ($validated['stops'] as $stop) {
                    RouteStop::create([
                        'route_id' => $route->id,
                        'name' => $stop['name'],
                        'lat' => $stop['lat'],
                        'lng' => $stop['lng'],
                        'sequence' => $stop['sequence'],
                    ]);
                }
            }

            // Compute road distances for route segments
            app(OsrmSegmentService::class)->computeForRoute($route);
        });

        return redirect()->route('admin.routes.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Route updated successfully.']]);
    }

    /**
     * Remove the specified route.
     */
    public function destroy(Route $route)
    {
        $route->delete();
        return redirect()->route('admin.routes.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Route deleted successfully.']]);
    }
}
