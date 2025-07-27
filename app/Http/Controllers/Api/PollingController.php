<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusLocation;
use App\Models\BusSchedule;
use App\Models\UserTrackingSession;
use App\Services\LocationService;
use App\Services\BusTrackingReliabilityService;
use App\Services\SmartBroadcastingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PollingController extends Controller
{
    protected $locationService;
    protected $reliabilityService;
    protected $smartBroadcastingService;

    public function __construct(
        LocationService $locationService,
        BusTrackingReliabilityService $reliabilityService,
        SmartBroadcastingService $smartBroadcastingService
    ) {
        $this->locationService = $locationService;
        $this->reliabilityService = $reliabilityService;
        $this->smartBroadcastingService = $smartBroadcastingService;
    }

    /**
     * Get current bus locations for polling
     */
    public function getBusLocations(Request $request): JsonResponse
    {
        $busIds = $request->input('bus_ids', []);
        $lastUpdate = $request->input('last_update');
        
        // Get current positions from smart broadcasting service
        $allPositions = $this->smartBroadcastingService->getCurrentBusPositions();
        
        $locations = [];
        
        // Filter by requested bus IDs if specified
        if (!empty($busIds)) {
            $allPositions = array_filter($allPositions, function($position) use ($busIds) {
                return in_array($position['bus_id'], $busIds);
            });
        }
        
        // Filter by last update time if specified
        if ($lastUpdate) {
            $lastUpdateTime = Carbon::parse($lastUpdate);
            $allPositions = array_filter($allPositions, function($position) use ($lastUpdateTime) {
                if (!$position['last_updated']) {
                    return true; // Include positions without timestamp
                }
                $positionTime = Carbon::parse($position['last_updated']);
                return $positionTime->gt($lastUpdateTime);
            });
        }
        
        // Format positions for response
        foreach ($allPositions as $position) {
            $locations[$position['bus_id']] = $this->formatLocationResponse($position);
        }

        return response()->json([
            'success' => true,
            'locations' => $locations,
            'timestamp' => Carbon::now()->toISOString(),
            'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'total_buses' => count($locations)
        ]);
    }

    /**
     * Get specific bus location data
     */
    public function getBusLocation(string $busId, Request $request): JsonResponse
    {
        $lastUpdate = $request->input('last_update');
        
        // Get position from smart broadcasting service
        $allPositions = $this->smartBroadcastingService->getCurrentBusPositions();
        $position = collect($allPositions)->firstWhere('bus_id', $busId);
        
        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'No location data available for this bus',
                'bus_id' => $busId
            ], 404);
        }
        
        // Check if data has been updated since last request
        if ($lastUpdate && $position['last_updated']) {
            $lastUpdateTime = Carbon::parse($lastUpdate);
            $positionTime = Carbon::parse($position['last_updated']);
            
            if ($positionTime->lte($lastUpdateTime)) {
                return response()->json([
                    'success' => true,
                    'bus_id' => $busId,
                    'no_new_data' => true,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'bus_id' => $busId,
            'location' => $this->formatLocationResponse($position),
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Submit location data via polling
     */
    public function submitLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bus_id' => 'required|string|max:10',
            'device_token' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'timestamp' => 'nullable|date'
        ]);

        try {
            // Validate that the bus is currently active
            $busSchedule = BusSchedule::where('bus_id', $validated['bus_id'])
                ->active()
                ->first();

            if (!$busSchedule || !$busSchedule->isCurrentlyActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bus is not currently scheduled to run'
                ], 400);
            }

            // Process location through LocationService
            $locationData = [
                'bus_id' => $validated['bus_id'],
                'device_token' => $validated['device_token'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'timestamp' => $validated['timestamp'] ? Carbon::parse($validated['timestamp']) : Carbon::now()
            ];

            $result = $this->locationService->processLocationData($locationData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Location data processed successfully',
                    'validation_result' => $result['validation'],
                    'reputation_updated' => $result['reputation_updated'] ?? false
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to process location data',
                    'validation_errors' => $result['validation_errors'] ?? []
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error processing location data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get tracking session status
     */
    public function getTrackingStatus(Request $request): JsonResponse
    {
        $busId = $request->input('bus_id');
        $deviceToken = $request->input('device_token');

        $data = [];

        if ($busId) {
            // Get active tracking sessions for the bus
            $activeSessions = UserTrackingSession::where('bus_id', $busId)
                ->where('is_active', true)
                ->where('started_at', '>', Carbon::now()->subHours(2))
                ->count();

            $data['bus_tracking'] = [
                'bus_id' => $busId,
                'active_trackers' => $activeSessions,
                'status' => $this->getTrackingStatusText($activeSessions),
                'confidence_level' => $this->calculateConfidenceLevel($busId, $activeSessions)
            ];
        }

        if ($deviceToken) {
            // Get user's active tracking session
            $userSession = UserTrackingSession::where('device_token', $deviceToken)
                ->where('is_active', true)
                ->first();

            $data['user_tracking'] = [
                'is_tracking' => $userSession !== null,
                'bus_id' => $userSession?->bus_id,
                'started_at' => $userSession?->started_at?->toISOString(),
                'locations_contributed' => $userSession?->locations_contributed ?? 0
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Health check endpoint for polling system
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'timestamp' => Carbon::now()->toISOString(),
            'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'polling_available' => true
        ]);
    }
    
    /**
     * Get system statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->smartBroadcastingService->getStatistics();
        
        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }
    
    /**
     * Format location data for API response
     */
    private function formatLocationResponse(array $position): array
    {
        return [
            'status' => $position['status'],
            'latitude' => $position['latitude'],
            'longitude' => $position['longitude'],
            'confidence_level' => $position['confidence_level'],
            'active_trackers' => $position['active_trackers'],
            'trusted_trackers' => $position['trusted_trackers'],
            'last_updated' => $position['last_updated'],
            'last_seen' => $position['last_seen'],
            'is_reliable' => $position['is_reliable'],
            'last_known_location' => $position['last_known_location'],
            'message' => $this->getStatusMessage($position)
        ];
    }
    
    /**
     * Get status message based on position data
     */
    private function getStatusMessage(array $position): string
    {
        return match($position['status']) {
            'active' => $position['trusted_trackers'] > 0 
                ? "Live tracking with {$position['active_trackers']} passengers"
                : "Basic tracking active",
            'inactive' => "Last seen {$position['last_seen']}",
            'no_data' => "No tracking data available",
            default => "Status unknown"
        };
    }

    /**
     * Get bus location data with caching and reliability checks
     */
    private function getBusLocationData(string $busId, ?string $lastUpdate = null): ?array
    {
        // Check if bus is currently active
        $busSchedule = BusSchedule::where('bus_id', $busId)
            ->active()
            ->first();

        if (!$busSchedule || !$busSchedule->isCurrentlyActive()) {
            return [
                'status' => 'inactive',
                'message' => 'Bus is not currently scheduled',
                'last_known' => null
            ];
        }

        // Get aggregated location from recent trusted data
        $locationData = $this->getAggregatedLocationData($busId);

        if (!$locationData) {
            // Try to get last known location
            $lastLocation = BusLocation::where('bus_id', $busId)
                ->where('is_validated', true)
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                'status' => 'no_tracking',
                'message' => 'No active tracking data',
                'last_known' => $lastLocation ? [
                    'latitude' => $lastLocation->latitude,
                    'longitude' => $lastLocation->longitude,
                    'timestamp' => $lastLocation->created_at->toISOString(),
                    'time_ago' => $lastLocation->created_at->diffForHumans()
                ] : null
            ];
        }

        // Check if data has been updated since last request
        if ($lastUpdate) {
            $lastUpdateTime = Carbon::parse($lastUpdate);
            $dataUpdateTime = Carbon::parse($locationData['last_updated']);
            
            if ($dataUpdateTime->lte($lastUpdateTime)) {
                return null; // No new data
            }
        }

        return [
            'status' => 'active',
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'accuracy' => $locationData['accuracy'] ?? null,
            'speed' => $locationData['speed'] ?? 0,
            'confidence_level' => $locationData['confidence_level'],
            'active_trackers' => $locationData['active_trackers'],
            'trusted_trackers' => $locationData['trusted_trackers'],
            'last_updated' => $locationData['last_updated'],
            'movement_consistency' => $locationData['movement_consistency'] ?? 0.5
        ];
    }

    /**
     * Get tracking status text based on active trackers
     */
    private function getTrackingStatusText(int $activeTrackers): string
    {
        if ($activeTrackers === 0) {
            return 'no_tracking';
        } elseif ($activeTrackers === 1) {
            return 'single_tracker';
        } else {
            return 'multiple_trackers';
        }
    }

    /**
     * Get aggregated location data for a bus
     */
    private function getAggregatedLocationData(string $busId): ?array
    {
        // Get recent validated locations
        $recentLocations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(2))
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recentLocations->isEmpty()) {
            return null;
        }

        // Calculate weighted average based on reputation weights
        $totalWeight = $recentLocations->sum('reputation_weight');
        
        if ($totalWeight <= 0) {
            return null;
        }

        $weightedLat = $recentLocations->sum(function ($location) {
            return $location->latitude * $location->reputation_weight;
        }) / $totalWeight;
        
        $weightedLng = $recentLocations->sum(function ($location) {
            return $location->longitude * $location->reputation_weight;
        }) / $totalWeight;

        // Get active trackers count
        $activeTrackers = UserTrackingSession::where('bus_id', $busId)
            ->where('is_active', true)
            ->where('started_at', '>', now()->subHours(2))
            ->count();

        // Count trusted trackers (those with high reputation)
        $trustedTrackers = $recentLocations->filter(function ($location) {
            return $location->reputation_weight >= 0.7;
        })->count();

        return [
            'latitude' => round($weightedLat, 8),
            'longitude' => round($weightedLng, 8),
            'accuracy' => $recentLocations->avg('accuracy'),
            'speed' => $recentLocations->avg('speed') ?? 0,
            'confidence_level' => $this->calculateConfidenceLevel($busId, $activeTrackers),
            'active_trackers' => $activeTrackers,
            'trusted_trackers' => $trustedTrackers,
            'last_updated' => $recentLocations->first()->created_at->toISOString(),
            'movement_consistency' => 0.8 // Placeholder value
        ];
    }

    /**
     * Calculate confidence level based on tracking data
     */
    private function calculateConfidenceLevel(string $busId, int $activeTrackers): float
    {
        $baseConfidence = 0.3;
        
        // Increase confidence based on number of trackers
        if ($activeTrackers > 0) {
            $baseConfidence += min(0.4, $activeTrackers * 0.15);
        }
        
        // Get additional confidence from recent location data quality
        $recentLocations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(5))
            ->where('is_validated', true)
            ->get();
            
        if ($recentLocations->isNotEmpty()) {
            $avgReputationWeight = $recentLocations->avg('reputation_weight');
            $baseConfidence += $avgReputationWeight * 0.3;
        }
        
        return min(1.0, max(0.0, $baseConfidence));
    }
}