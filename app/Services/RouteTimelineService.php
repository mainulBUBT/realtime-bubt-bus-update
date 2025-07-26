<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Services\BusScheduleService;
use App\Services\RouteValidator;
use App\Services\StopCoordinateManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Route Timeline Service
 * Manages route timeline progression with completed, current, and upcoming stops
 */
class RouteTimelineService
{
    private BusScheduleService $scheduleService;
    private RouteValidator $routeValidator;
    private StopCoordinateManager $stopManager;
    
    // Timeline status constants
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CURRENT = 'current';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_SKIPPED = 'skipped';
    
    // Progress calculation constants
    private const ETA_CALCULATION_WINDOW = 10; // minutes of location data for ETA
    private const STOP_COMPLETION_RADIUS = 100; // meters to consider stop "reached"
    private const PROGRESS_UPDATE_INTERVAL = 30; // seconds between progress updates
    
    public function __construct(
        BusScheduleService $scheduleService,
        RouteValidator $routeValidator,
        StopCoordinateManager $stopManager
    ) {
        $this->scheduleService = $scheduleService;
        $this->routeValidator = $routeValidator;
        $this->stopManager = $stopManager;
    }

    /**
     * Get current route timeline with stop progression
     *
     * @param string $busId Bus identifier
     * @param Carbon|null $currentTime Current time
     * @return array Route timeline with status
     */
    public function getRouteTimeline(string $busId, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? now();
        $cacheKey = "route_timeline_{$busId}_{$currentTime->format('Y-m-d_H-i')}";
        
        return Cache::remember($cacheKey, now()->addSeconds(self::PROGRESS_UPDATE_INTERVAL), function () use ($busId, $currentTime) {
            // Get current trip direction and route
            $tripDirection = $this->scheduleService->getCurrentTripDirection($busId, $currentTime);
            
            if (!$tripDirection['direction']) {
                return [
                    'success' => false,
                    'message' => 'Bus is not currently active',
                    'timeline' => []
                ];
            }

            $routeStops = $tripDirection['route_stops'];
            if (empty($routeStops)) {
                return [
                    'success' => false,
                    'message' => 'No route data available',
                    'timeline' => []
                ];
            }

            // Get current bus location
            $currentLocation = $this->getCurrentBusLocation($busId);
            
            // Determine current stop and progression
            $progressionAnalysis = $this->analyzeRouteProgression($busId, $routeStops, $currentLocation);
            
            // Build timeline with status for each stop
            $timeline = $this->buildTimelineWithStatus($routeStops, $progressionAnalysis, $tripDirection);
            
            // Calculate ETAs and progress percentages
            $timelineWithETA = $this->calculateTimelineETAs($timeline, $currentLocation, $busId);
            
            return [
                'success' => true,
                'bus_id' => $busId,
                'trip_direction' => $tripDirection['direction'],
                'current_stop_analysis' => $progressionAnalysis,
                'timeline' => $timelineWithETA,
                'route_stats' => $this->calculateRouteStats($timelineWithETA),
                'last_updated' => $currentTime,
                'next_update' => $currentTime->addSeconds(self::PROGRESS_UPDATE_INTERVAL)
            ];
        });
    }

    /**
     * Update timeline when bus reaches a stop
     *
     * @param string $busId Bus identifier
     * @param float $latitude Current latitude
     * @param float $longitude Current longitude
     * @return array Update result
     */
    public function updateTimelineProgression(string $busId, float $latitude, float $longitude): array
    {
        try {
            // Get current timeline
            $currentTimeline = $this->getRouteTimeline($busId);
            
            if (!$currentTimeline['success']) {
                return [
                    'updated' => false,
                    'message' => 'Cannot update timeline: ' . $currentTimeline['message']
                ];
            }

            // Check if bus has reached a new stop
            $stopReachedAnalysis = $this->checkStopReached($latitude, $longitude, $currentTimeline['timeline']);
            
            if ($stopReachedAnalysis['stop_reached']) {
                // Update stop status to completed
                $updateResult = $this->markStopAsCompleted($busId, $stopReachedAnalysis['stop']);
                
                // Clear timeline cache to force refresh
                $this->clearTimelineCache($busId);
                
                // Log progression update
                Log::info('Bus timeline progression updated', [
                    'bus_id' => $busId,
                    'completed_stop' => $stopReachedAnalysis['stop']['stop_name'],
                    'stop_order' => $stopReachedAnalysis['stop']['stop_order'],
                    'coordinates' => ['lat' => $latitude, 'lng' => $longitude]
                ]);

                return [
                    'updated' => true,
                    'stop_completed' => $stopReachedAnalysis['stop'],
                    'update_result' => $updateResult,
                    'message' => "Bus reached {$stopReachedAnalysis['stop']['stop_name']}"
                ];
            }

            return [
                'updated' => false,
                'message' => 'No stop progression detected',
                'closest_stop' => $stopReachedAnalysis['closest_stop']
            ];

        } catch (\Exception $e) {
            Log::error('Timeline progression update failed', [
                'error' => $e->getMessage(),
                'bus_id' => $busId,
                'coordinates' => ['lat' => $latitude, 'lng' => $longitude]
            ]);

            return [
                'updated' => false,
                'message' => 'Timeline update failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate ETA for current stop based on real-time location data
     *
     * @param string $busId Bus identifier
     * @param array $targetStop Target stop details
     * @return array ETA calculation result
     */
    public function calculateCurrentStopETA(string $busId, array $targetStop): array
    {
        // Get recent location data for speed calculation
        $recentLocations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(self::ETA_CALCULATION_WINDOW))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentLocations->count() < 2) {
            return [
                'eta_available' => false,
                'message' => 'Insufficient location data for ETA calculation',
                'estimated_minutes' => null
            ];
        }

        // Calculate average speed from recent locations
        $averageSpeed = $this->calculateAverageSpeed($recentLocations);
        
        if ($averageSpeed <= 0) {
            return [
                'eta_available' => false,
                'message' => 'Bus appears to be stationary',
                'estimated_minutes' => null
            ];
        }

        // Get current location
        $currentLocation = $recentLocations->first();
        
        // Calculate distance to target stop
        $distance = $this->calculateDistance(
            $currentLocation->latitude,
            $currentLocation->longitude,
            $targetStop['latitude'],
            $targetStop['longitude']
        );

        // Calculate ETA in minutes
        $etaMinutes = ($distance / 1000) / ($averageSpeed / 60); // Convert to minutes
        
        // Add buffer for traffic and stops
        $etaWithBuffer = $etaMinutes * 1.3; // 30% buffer
        
        // Round to nearest minute
        $finalETA = max(1, round($etaWithBuffer));

        return [
            'eta_available' => true,
            'estimated_minutes' => $finalETA,
            'distance_meters' => round($distance, 2),
            'average_speed_kmh' => round($averageSpeed, 2),
            'confidence' => $this->calculateETAConfidence($recentLocations, $averageSpeed),
            'calculation_method' => 'real_time_gps',
            'last_updated' => now()
        ];
    }

    /**
     * Calculate progress bar percentage for current stop completion
     *
     * @param string $busId Bus identifier
     * @param array $currentStop Current stop details
     * @param array $nextStop Next stop details
     * @return array Progress calculation result
     */
    public function calculateStopProgressPercentage(string $busId, array $currentStop, ?array $nextStop = null): array
    {
        if (!$nextStop) {
            return [
                'progress_available' => false,
                'percentage' => 100,
                'message' => 'At final stop'
            ];
        }

        // Get current bus location
        $currentLocation = $this->getCurrentBusLocation($busId);
        
        if (!$currentLocation) {
            return [
                'progress_available' => false,
                'percentage' => 0,
                'message' => 'No current location data'
            ];
        }

        // Calculate distances
        $totalDistance = $this->calculateDistance(
            $currentStop['latitude'],
            $currentStop['longitude'],
            $nextStop['latitude'],
            $nextStop['longitude']
        );

        $remainingDistance = $this->calculateDistance(
            $currentLocation['latitude'],
            $currentLocation['longitude'],
            $nextStop['latitude'],
            $nextStop['longitude']
        );

        // Calculate progress percentage
        $progressDistance = max(0, $totalDistance - $remainingDistance);
        $progressPercentage = $totalDistance > 0 ? ($progressDistance / $totalDistance) * 100 : 0;
        
        // Ensure percentage is between 0 and 100
        $progressPercentage = max(0, min(100, $progressPercentage));

        return [
            'progress_available' => true,
            'percentage' => round($progressPercentage, 1),
            'total_distance' => round($totalDistance, 2),
            'remaining_distance' => round($remainingDistance, 2),
            'progress_distance' => round($progressDistance, 2),
            'from_stop' => $currentStop['stop_name'],
            'to_stop' => $nextStop['stop_name']
        ];
    }

    /**
     * Handle automatic timeline updates when bus reaches each stop
     *
     * @param string $busId Bus identifier
     * @return array Auto-update result
     */
    public function handleAutomaticTimelineUpdates(string $busId): array
    {
        $currentLocation = $this->getCurrentBusLocation($busId);
        
        if (!$currentLocation) {
            return [
                'auto_updated' => false,
                'message' => 'No current location for automatic updates'
            ];
        }

        return $this->updateTimelineProgression(
            $busId,
            $currentLocation['latitude'],
            $currentLocation['longitude']
        );
    }

    /**
     * Support route reversal during return trips
     *
     * @param string $busId Bus identifier
     * @param string $direction Trip direction
     * @return array Route reversal result
     */
    public function handleRouteReversal(string $busId, string $direction): array
    {
        try {
            // Clear existing timeline cache
            $this->clearTimelineCache($busId);
            
            // Get reversed route stops
            $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
            
            if ($tripDirection['direction'] !== $direction) {
                return [
                    'reversed' => false,
                    'message' => "Bus is not on {$direction} trip",
                    'current_direction' => $tripDirection['direction']
                ];
            }

            // Reset timeline progression for new direction
            $this->resetTimelineProgression($busId, $direction);
            
            Log::info('Route reversal handled', [
                'bus_id' => $busId,
                'direction' => $direction,
                'route_stops_count' => count($tripDirection['route_stops'])
            ]);

            return [
                'reversed' => true,
                'direction' => $direction,
                'route_stops' => $tripDirection['route_stops'],
                'message' => "Route reversed for {$direction} trip"
            ];

        } catch (\Exception $e) {
            Log::error('Route reversal failed', [
                'error' => $e->getMessage(),
                'bus_id' => $busId,
                'direction' => $direction
            ]);

            return [
                'reversed' => false,
                'message' => 'Route reversal failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Private helper methods
     */

    /**
     * Get current bus location from recent GPS data
     */
    private function getCurrentBusLocation(string $busId): ?array
    {
        $location = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(5))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$location) {
            return null;
        }

        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'timestamp' => $location->created_at,
            'accuracy' => $location->accuracy
        ];
    }

    /**
     * Analyze route progression based on current location
     */
    private function analyzeRouteProgression(string $busId, array $routeStops, ?array $currentLocation): array
    {
        if (!$currentLocation) {
            return [
                'current_stop_index' => 0,
                'current_stop' => $routeStops[0] ?? null,
                'next_stop' => $routeStops[1] ?? null,
                'confidence' => 0.0,
                'message' => 'No current location data'
            ];
        }

        // Find closest stop
        $distances = [];
        foreach ($routeStops as $index => $stop) {
            $distance = $this->calculateDistance(
                $currentLocation['latitude'],
                $currentLocation['longitude'],
                $stop['latitude'],
                $stop['longitude']
            );
            
            $distances[] = [
                'index' => $index,
                'stop' => $stop,
                'distance' => $distance
            ];
        }

        // Sort by distance
        usort($distances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $closestStop = $distances[0];
        $currentStopIndex = $closestStop['index'];
        
        // Determine next stop
        $nextStopIndex = $currentStopIndex + 1;
        $nextStop = $nextStopIndex < count($routeStops) ? $routeStops[$nextStopIndex] : null;

        // Calculate confidence based on distance and route adherence
        $confidence = $this->calculateProgressionConfidence($closestStop['distance'], $closestStop['stop']);

        return [
            'current_stop_index' => $currentStopIndex,
            'current_stop' => $closestStop['stop'],
            'next_stop' => $nextStop,
            'distance_to_current' => round($closestStop['distance'], 2),
            'confidence' => $confidence,
            'message' => $confidence > 0.7 ? 'High confidence location' : 'Low confidence location'
        ];
    }

    /**
     * Build timeline with status for each stop
     */
    private function buildTimelineWithStatus(array $routeStops, array $progressionAnalysis, array $tripDirection): array
    {
        $timeline = [];
        $currentStopIndex = $progressionAnalysis['current_stop_index'];

        foreach ($routeStops as $index => $stop) {
            $status = $this->determineStopStatus($index, $currentStopIndex);
            
            $timeline[] = [
                'stop_order' => $stop['stop_order'],
                'stop_name' => $stop['stop_name'],
                'coordinates' => [
                    'latitude' => $stop['latitude'],
                    'longitude' => $stop['longitude']
                ],
                'coverage_radius' => $stop['coverage_radius'],
                'estimated_time' => $stop['estimated_time'],
                'status' => $status,
                'is_current' => $index === $currentStopIndex,
                'is_next' => $index === $currentStopIndex + 1,
                'direction' => $tripDirection['direction']
            ];
        }

        return $timeline;
    }

    /**
     * Calculate ETAs for timeline stops
     */
    private function calculateTimelineETAs(array $timeline, ?array $currentLocation, string $busId): array
    {
        if (!$currentLocation) {
            return $timeline;
        }

        foreach ($timeline as &$stop) {
            if ($stop['status'] === self::STATUS_UPCOMING || $stop['is_current']) {
                $etaResult = $this->calculateCurrentStopETA($busId, $stop);
                $stop['eta'] = $etaResult;
                
                if ($stop['is_current'] && isset($timeline[array_search($stop, $timeline) + 1])) {
                    $nextStopIndex = array_search($stop, $timeline) + 1;
                    $progressResult = $this->calculateStopProgressPercentage(
                        $busId, 
                        $stop, 
                        $timeline[$nextStopIndex] ?? null
                    );
                    $stop['progress'] = $progressResult;
                }
            }
        }

        return $timeline;
    }

    /**
     * Determine stop status based on progression
     */
    private function determineStopStatus(int $stopIndex, int $currentStopIndex): string
    {
        if ($stopIndex < $currentStopIndex) {
            return self::STATUS_COMPLETED;
        } elseif ($stopIndex === $currentStopIndex) {
            return self::STATUS_CURRENT;
        } else {
            return self::STATUS_UPCOMING;
        }
    }

    /**
     * Check if bus has reached a stop
     */
    private function checkStopReached(float $latitude, float $longitude, array $timeline): array
    {
        $closestStop = null;
        $minDistance = PHP_FLOAT_MAX;
        $stopReached = false;

        foreach ($timeline as $stop) {
            if ($stop['status'] === self::STATUS_UPCOMING || $stop['is_current']) {
                $distance = $this->calculateDistance(
                    $latitude, $longitude,
                    $stop['coordinates']['latitude'],
                    $stop['coordinates']['longitude']
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestStop = $stop;
                }

                // Check if within completion radius
                if ($distance <= self::STOP_COMPLETION_RADIUS) {
                    $stopReached = true;
                    break;
                }
            }
        }

        return [
            'stop_reached' => $stopReached,
            'stop' => $stopReached ? $closestStop : null,
            'closest_stop' => $closestStop,
            'distance_to_closest' => round($minDistance, 2)
        ];
    }

    /**
     * Mark stop as completed
     */
    private function markStopAsCompleted(string $busId, array $stop): array
    {
        // This would typically update a database record
        // For now, we'll just log the completion
        
        Log::info('Stop marked as completed', [
            'bus_id' => $busId,
            'stop_name' => $stop['stop_name'],
            'stop_order' => $stop['stop_order'],
            'completed_at' => now()
        ]);

        return [
            'marked_completed' => true,
            'stop' => $stop,
            'completed_at' => now()
        ];
    }

    /**
     * Calculate average speed from recent locations
     */
    private function calculateAverageSpeed($locations): float
    {
        if ($locations->count() < 2) {
            return 0;
        }

        $speeds = [];
        $previousLocation = null;

        foreach ($locations as $location) {
            if ($previousLocation) {
                $distance = $this->calculateDistance(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );

                $timeDiff = $location->created_at->diffInSeconds($previousLocation->created_at);
                
                if ($timeDiff > 0) {
                    $speed = ($distance / $timeDiff) * 3.6; // km/h
                    $speeds[] = $speed;
                }
            }
            $previousLocation = $location;
        }

        return empty($speeds) ? 0 : array_sum($speeds) / count($speeds);
    }

    /**
     * Calculate distance between two GPS coordinates
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate ETA confidence based on data quality
     */
    private function calculateETAConfidence($locations, float $averageSpeed): float
    {
        $confidence = 0.5;

        // More locations = higher confidence
        $locationCount = $locations->count();
        if ($locationCount >= 5) {
            $confidence += 0.2;
        } elseif ($locationCount >= 3) {
            $confidence += 0.1;
        }

        // Reasonable speed = higher confidence
        if ($averageSpeed >= 10 && $averageSpeed <= 60) {
            $confidence += 0.2;
        }

        // Recent data = higher confidence
        $latestLocation = $locations->first();
        $dataAge = now()->diffInMinutes($latestLocation->created_at);
        if ($dataAge <= 2) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }

    /**
     * Calculate progression confidence
     */
    private function calculateProgressionConfidence(float $distance, array $stop): float
    {
        $radius = $stop['coverage_radius'];
        
        if ($distance <= $radius) {
            return 1.0 - ($distance / $radius) * 0.3; // 0.7 to 1.0 within radius
        } else {
            return max(0.1, 0.7 - (($distance - $radius) / $radius) * 0.6); // Decreasing outside radius
        }
    }

    /**
     * Calculate route statistics
     */
    private function calculateRouteStats(array $timeline): array
    {
        $totalStops = count($timeline);
        $completedStops = count(array_filter($timeline, fn($stop) => $stop['status'] === self::STATUS_COMPLETED));
        $upcomingStops = count(array_filter($timeline, fn($stop) => $stop['status'] === self::STATUS_UPCOMING));

        return [
            'total_stops' => $totalStops,
            'completed_stops' => $completedStops,
            'upcoming_stops' => $upcomingStops,
            'completion_percentage' => $totalStops > 0 ? round(($completedStops / $totalStops) * 100, 1) : 0,
            'current_stop_number' => $completedStops + 1
        ];
    }

    /**
     * Clear timeline cache
     */
    private function clearTimelineCache(string $busId): void
    {
        $patterns = [
            "route_timeline_{$busId}_*",
            "progression_analysis_{$busId}_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Reset timeline progression for new direction
     */
    private function resetTimelineProgression(string $busId, string $direction): void
    {
        // Clear all timeline-related caches
        $this->clearTimelineCache($busId);
        
        // Log the reset
        Log::info('Timeline progression reset', [
            'bus_id' => $busId,
            'direction' => $direction,
            'reset_at' => now()
        ]);
    }
}