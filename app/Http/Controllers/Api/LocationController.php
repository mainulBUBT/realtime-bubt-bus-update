<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Location;
use App\Events\BusMoved;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class LocationController extends Controller
{
    /**
     * Receive GPS ping from bus tracking apps
     * Rate limited to 4 requests per minute per IP
     */
    public function ping(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'location-ping:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 4)) {
            return response()->json([
                'error' => 'Too many requests. Limit: 4 per minute.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 seconds

        // Validate request
        $validator = Validator::make($request->all(), [
            'bus_id' => 'required|exists:buses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'timestamp' => 'nullable|date',
            'source' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            // Get bus info
            $bus = Bus::findOrFail($request->bus_id);
            
            if (!$bus->is_active) {
                return response()->json([
                    'error' => 'Bus is not active'
                ], 400);
            }

            // Create location record
            $location = Location::create([
                'bus_id' => $request->bus_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'recorded_at' => $request->timestamp ? 
                    \Carbon\Carbon::parse($request->timestamp) : 
                    now(),
                'source' => $request->source ?? 'api'
            ]);

            // Broadcast bus movement event (only if broadcasting is configured)
            try {
                broadcast(new BusMoved($bus, $location));
            } catch (\Exception $e) {
                // Log but don't fail the request if broadcasting fails
                \Log::warning('Broadcasting failed: ' . $e->getMessage());
            }

            // Clean old locations (keep only last 24 hours)
            $this->cleanOldLocations();

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'bus' => $bus->name,
                    'location_id' => $location->id,
                    'recorded_at' => $location->recorded_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Location ping error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get current bus positions (public endpoint)
     */
    public function positions(): JsonResponse
    {
        try {
            // Get recent locations (last 10 minutes)
            $recentLocations = Location::with('bus')
                ->where('recorded_at', '>=', now()->subMinutes(10))
                ->orderBy('recorded_at', 'desc')
                ->get()
                ->groupBy('bus_id')
                ->map(function ($locations) {
                    return $locations->first(); // Get most recent per bus
                })
                ->values();

            // Format for clustering
            $locationData = $recentLocations->map(function ($location) {
                return [
                    'bus_id' => $location->bus_id,
                    'bus_name' => $location->bus->name,
                    'route_name' => $location->bus->route_name,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'recorded_at' => $location->recorded_at->toISOString()
                ];
            })->toArray();

            // Apply clustering
            $cluster = new \App\Support\Cluster(60, 2); // 60m radius, min 2 points
            $clusteredPositions = $cluster->getBusPositions($locationData);

            return response()->json([
                'success' => true,
                'data' => [
                    'positions' => $clusteredPositions,
                    'last_updated' => now()->toISOString(),
                    'active_buses' => count($locationData)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get positions error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch bus positions'
            ], 500);
        }
    }

    /**
     * Clean old location records
     */
    private function cleanOldLocations(): void
    {
        try {
            $deleted = Location::where('recorded_at', '<', now()->subHours(24))->delete();
            
            if ($deleted > 0) {
                \Log::info("Cleaned {$deleted} old location records");
            }
        } catch (\Exception $e) {
            \Log::error('Failed to clean old locations: ' . $e->getMessage());
        }
    }
}