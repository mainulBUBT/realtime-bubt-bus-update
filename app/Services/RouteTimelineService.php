<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\BusTimelineProgression;
use App\Models\BusRoute;
use App\Models\BusSchedule;
use App\Services\BusScheduleService;
use App\Services\RouteValidator;
use App\Services\StopCoordinateManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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

            // Initialize or get existing timeline progression
            $this->initializeTimelineProgression($busId, $tripDirection);
            
            // Get timeline progression from database
            $timelineProgression = $this->getTimelineProgressionFromDB($busId, $tripDirection['direction']);
            
            if ($timelineProgression->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No timeline progression data available',
                    'timeline' => []
                ];
            }

            // Get current bus location
            $currentLocation = $this->getCurrentBusLocation($busId);
            
            // Update progression based on current location
            $this->updateProgressionBasedOnLocation($busId, $currentLocation, $timelineProgression);
            
            // Build timeline with current status
            $timeline = $this->buildTimelineFromProgression($timelineProgression, $currentLocation, $busId);
            
            // Calculate route statistics
            $routeStats = $this->calculateRouteStats($timeline);
            
            return [
                'success' => true,
                'bus_id' => $busId,
                'trip_direction' => $tripDirection['direction'],
                'schedule_id' => $tripDirection['schedule_id'],
                'timeline' => $timeline,
                'route_stats' => $routeStats,
                'current_location' => $currentLocation,
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
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentLocations->count() < 2) {
            // Fallback to schedule-based ETA
            return $this->calculateScheduleBasedETA($busId, $targetStop);
        }

        // Calculate average speed from recent locations
        $averageSpeed = $this->calculateAverageSpeed($recentLocations);
        
        if ($averageSpeed <= 0) {
            return $this->calculateScheduleBasedETA($busId, $targetStop);
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

        // Calculate base ETA in minutes
        $baseEtaMinutes = ($distance / 1000) / ($averageSpeed / 60);
        
        // Apply traffic and stop buffers based on time of day
        $bufferMultiplier = $this->getTrafficBufferMultiplier();
        $etaWithBuffer = $baseEtaMinutes * $bufferMultiplier;
        
        // Add stop time if not the final destination
        $stopTimeBuffer = $this->isIntermediateStop($targetStop) ? 2 : 0; // 2 minutes stop time
        
        // Round to nearest minute with minimum of 1
        $finalETA = max(1, round($etaWithBuffer + $stopTimeBuffer));

        // Calculate confidence based on data quality
        $confidence = $this->calculateETAConfidence($recentLocations, $averageSpeed);

        return [
            'eta_available' => true,
            'estimated_minutes' => $finalETA,
            'distance_meters' => round($distance, 2),
            'average_speed_kmh' => round($averageSpeed, 2),
            'confidence' => $confidence,
            'calculation_method' => 'real_time_gps',
            'buffer_applied' => $bufferMultiplier,
            'stop_time_buffer' => $stopTimeBuffer,
            'base_eta_minutes' => round($baseEtaMinutes, 1),
            'last_updated' => now()
        ];
    }

    /**
     * Calculate schedule-based ETA as fallback
     *
     * @param string $busId Bus identifier
     * @param array $targetStop Target stop details
     * @return array Schedule-based ETA result
     */
    private function calculateScheduleBasedETA(string $busId, array $targetStop): array
    {
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        if (!$tripDirection['direction']) {
            return [
                'eta_available' => false,
                'message' => 'Bus is not currently active',
                'estimated_minutes' => null
            ];
        }

        $schedule = BusSchedule::find($tripDirection['schedule_id']);
        
        if (!$schedule) {
            return [
                'eta_available' => false,
                'message' => 'Schedule not found',
                'estimated_minutes' => null
            ];
        }

        // Get estimated time from route
        $route = BusRoute::find($targetStop['id']);
        
        if (!$route) {
            return [
                'eta_available' => false,
                'message' => 'Route not found',
                'estimated_minutes' => null
            ];
        }

        $estimatedTime = $tripDirection['direction'] === BusScheduleService::DIRECTION_DEPARTURE
            ? $route->estimated_departure_time
            : $route->estimated_return_time;

        if (!$estimatedTime) {
            return [
                'eta_available' => false,
                'message' => 'No estimated time available',
                'estimated_minutes' => null
            ];
        }

        $now = now();
        $estimatedArrival = Carbon::createFromFormat('H:i:s', $estimatedTime);
        
        // If estimated time is in the past, add a day
        if ($estimatedArrival < $now) {
            $estimatedArrival->addDay();
        }

        $etaMinutes = $now->diffInMinutes($estimatedArrival);

        return [
            'eta_available' => true,
            'estimated_minutes' => $etaMinutes,
            'confidence' => 0.6, // Lower confidence for schedule-based
            'calculation_method' => 'schedule_based',
            'estimated_arrival_time' => $estimatedArrival->format('H:i'),
            'last_updated' => now()
        ];
    }

    /**
     * Get traffic buffer multiplier based on time of day
     *
     * @return float Buffer multiplier
     */
    private function getTrafficBufferMultiplier(): float
    {
        $hour = now()->hour;
        
        // Peak hours: 7-9 AM and 5-7 PM
        if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
            return 1.5; // 50% buffer for peak hours
        }
        
        // Regular hours
        return 1.3; // 30% buffer for regular hours
    }

    /**
     * Check if this is an intermediate stop (not final destination)
     *
     * @param array $targetStop Target stop details
     * @return bool True if intermediate stop
     */
    private function isIntermediateStop(array $targetStop): bool
    {
        // This would need to be enhanced based on actual route data
        // For now, assume all stops except the last one are intermediate
        return true; // Simplified implementation
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
        try {
            $currentLocation = $this->getCurrentBusLocation($busId);
            
            if (!$currentLocation) {
                return [
                    'auto_updated' => false,
                    'message' => 'No current location for automatic updates'
                ];
            }

            // Get current timeline
            $timeline = $this->getRouteTimeline($busId);
            
            if (!$timeline['success']) {
                return [
                    'auto_updated' => false,
                    'message' => 'Cannot get timeline for updates: ' . $timeline['message']
                ];
            }

            // Update progression
            $updateResult = $this->updateTimelineProgression(
                $busId,
                $currentLocation['latitude'],
                $currentLocation['longitude']
            );

            // If a stop was reached, trigger additional updates
            if ($updateResult['updated'] && isset($updateResult['stop_completed'])) {
                // Update ETAs for remaining stops
                $this->updateRemainingStopETAs($busId);
                
                // Clear cache to ensure fresh data
                $this->clearTimelineCache($busId);
            }

            return $updateResult;

        } catch (\Exception $e) {
            Log::error('Automatic timeline update failed', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);

            return [
                'auto_updated' => false,
                'message' => 'Automatic update failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update ETAs for remaining stops after a stop is completed
     *
     * @param string $busId Bus identifier
     * @return void
     */
    private function updateRemainingStopETAs(string $busId): void
    {
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        if (!$tripDirection['direction']) {
            return;
        }
        
        $progression = $this->getTimelineProgressionFromDB($busId, $tripDirection['direction']);
        $upcomingStops = $progression->where('status', BusTimelineProgression::STATUS_UPCOMING);
        
        foreach ($upcomingStops as $stop) {
            $etaResult = $this->calculateCurrentStopETA($busId, $stop->route->toArray());
            
            if ($etaResult['eta_available']) {
                $stop->updateETA($etaResult['estimated_minutes'], $etaResult['confidence']);
            }
        }
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
            
            // Get current trip direction
            $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
            
            if (!$tripDirection['direction']) {
                return [
                    'reversed' => false,
                    'message' => 'Bus is not currently active',
                    'current_direction' => null
                ];
            }
            
            // Check if we need to handle direction change
            if ($tripDirection['direction'] !== $direction) {
                // End current trip progression
                BusTimelineProgression::forBus($busId)
                    ->activeTrip()
                    ->update(['is_active_trip' => false]);
                
                // Initialize new trip progression for new direction
                $this->initializeTimelineProgression($busId, [
                    'direction' => $direction,
                    'schedule_id' => $tripDirection['schedule_id'],
                    'route_stops' => $this->scheduleService->getRouteStopsForDirection(
                        $tripDirection['schedule_id'], 
                        $direction
                    )
                ]);
                
                Log::info('Route direction changed and timeline reset', [
                    'bus_id' => $busId,
                    'from_direction' => $tripDirection['direction'],
                    'to_direction' => $direction
                ]);
                
                return [
                    'reversed' => true,
                    'direction' => $direction,
                    'previous_direction' => $tripDirection['direction'],
                    'message' => "Route direction changed from {$tripDirection['direction']} to {$direction}"
                ];
            }

            // Same direction, just refresh timeline
            $this->resetTimelineProgression($busId, $direction);
            
            Log::info('Route timeline refreshed', [
                'bus_id' => $busId,
                'direction' => $direction
            ]);

            return [
                'reversed' => true,
                'direction' => $direction,
                'message' => "Route timeline refreshed for {$direction} trip"
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
     * Initialize timeline progression for a new trip
     *
     * @param string $busId Bus identifier
     * @param array $tripDirection Trip direction details
     * @return void
     */
    public function initializeTimelineProgression(string $busId, array $tripDirection): void
    {
        $scheduleId = $tripDirection['schedule_id'];
        $direction = $tripDirection['direction'];
        
        // Check if progression already exists for this trip
        $existingProgression = BusTimelineProgression::forBus($busId)
            ->forDirection($direction)
            ->activeTrip()
            ->exists();
            
        if ($existingProgression) {
            return; // Already initialized
        }
        
        // End any previous active trips
        BusTimelineProgression::forBus($busId)
            ->activeTrip()
            ->update(['is_active_trip' => false]);
        
        // Get route stops for this direction
        $routeStops = $tripDirection['route_stops'];
        
        // Create progression records for each stop
        foreach ($routeStops as $index => $stop) {
            BusTimelineProgression::create([
                'bus_id' => $busId,
                'schedule_id' => $scheduleId,
                'route_id' => $stop['id'],
                'trip_direction' => $direction,
                'status' => $index === 0 ? BusTimelineProgression::STATUS_CURRENT : BusTimelineProgression::STATUS_UPCOMING,
                'estimated_arrival' => $this->calculateEstimatedArrival($stop, $tripDirection),
                'progress_percentage' => 0,
                'confidence_score' => 0.5,
                'is_active_trip' => true
            ]);
        }
        
        Log::info('Timeline progression initialized', [
            'bus_id' => $busId,
            'direction' => $direction,
            'stops_count' => count($routeStops)
        ]);
    }

    /**
     * Get timeline progression from database
     *
     * @param string $busId Bus identifier
     * @param string $direction Trip direction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getTimelineProgressionFromDB(string $busId, string $direction)
    {
        return BusTimelineProgression::forBus($busId)
            ->forDirection($direction)
            ->activeTrip()
            ->with(['route', 'schedule'])
            ->orderedByRoute()
            ->get();
    }

    /**
     * Update progression based on current location
     *
     * @param string $busId Bus identifier
     * @param array|null $currentLocation Current bus location
     * @param \Illuminate\Database\Eloquent\Collection $timelineProgression Timeline progression records
     * @return void
     */
    private function updateProgressionBasedOnLocation(string $busId, ?array $currentLocation, $timelineProgression): void
    {
        if (!$currentLocation) {
            return;
        }
        
        // Find the closest stop
        $closestStopAnalysis = $this->findClosestStop($currentLocation, $timelineProgression);
        
        if (!$closestStopAnalysis['stop']) {
            return;
        }
        
        $closestStop = $closestStopAnalysis['stop'];
        $distance = $closestStopAnalysis['distance'];
        
        // Check if bus has reached a new stop
        if ($distance <= self::STOP_COMPLETION_RADIUS) {
            $this->handleStopReached($busId, $closestStop, $timelineProgression);
        } else {
            // Update current stop progress
            $this->updateCurrentStopProgress($busId, $currentLocation, $timelineProgression);
        }
    }

    /**
     * Handle when bus reaches a stop
     *
     * @param string $busId Bus identifier
     * @param BusTimelineProgression $reachedStop Reached stop progression
     * @param \Illuminate\Database\Eloquent\Collection $timelineProgression All progression records
     * @return void
     */
    private function handleStopReached(string $busId, BusTimelineProgression $reachedStop, $timelineProgression): void
    {
        DB::transaction(function () use ($busId, $reachedStop, $timelineProgression) {
            // Mark all previous stops as completed if not already
            $timelineProgression->where('route.stop_order', '<', $reachedStop->route->stop_order)
                ->where('status', '!=', BusTimelineProgression::STATUS_COMPLETED)
                ->each(function ($stop) {
                    $stop->markAsCompleted();
                });
            
            // Mark reached stop as completed
            if (!$reachedStop->isCompleted()) {
                $reachedStop->markAsCompleted();
                
                // Find next stop and mark as current
                $nextStop = $timelineProgression->where('route.stop_order', '>', $reachedStop->route->stop_order)
                    ->sortBy('route.stop_order')
                    ->first();
                    
                if ($nextStop) {
                    $nextStop->markAsCurrent();
                }
                
                Log::info('Stop reached and timeline updated', [
                    'bus_id' => $busId,
                    'completed_stop' => $reachedStop->route->stop_name,
                    'next_stop' => $nextStop ? $nextStop->route->stop_name : 'Final destination'
                ]);
            }
        });
    }

    /**
     * Update current stop progress
     *
     * @param string $busId Bus identifier
     * @param array $currentLocation Current bus location
     * @param \Illuminate\Database\Eloquent\Collection $timelineProgression Timeline progression records
     * @return void
     */
    private function updateCurrentStopProgress(string $busId, array $currentLocation, $timelineProgression): void
    {
        $currentStop = $timelineProgression->where('status', BusTimelineProgression::STATUS_CURRENT)->first();
        
        if (!$currentStop) {
            return;
        }
        
        // Find next stop for progress calculation
        $nextStop = $timelineProgression->where('route.stop_order', '>', $currentStop->route->stop_order)
            ->sortBy('route.stop_order')
            ->first();
            
        if (!$nextStop) {
            // At final stop
            $currentStop->updateProgress(100);
            return;
        }
        
        // Calculate progress percentage
        $progressResult = $this->calculateStopProgressPercentage(
            $busId,
            $currentStop->route->toArray(),
            $nextStop->route->toArray()
        );
        
        if ($progressResult['progress_available']) {
            $currentStop->updateProgress($progressResult['percentage']);
        }
        
        // Calculate and update ETA
        $etaResult = $this->calculateCurrentStopETA($busId, $nextStop->route->toArray());
        
        if ($etaResult['eta_available']) {
            $nextStop->updateETA($etaResult['estimated_minutes'], $etaResult['confidence']);
        }
    }

    /**
     * Build timeline from progression records
     *
     * @param \Illuminate\Database\Eloquent\Collection $timelineProgression Timeline progression records
     * @param array|null $currentLocation Current bus location
     * @param string $busId Bus identifier
     * @return array Timeline array
     */
    private function buildTimelineFromProgression($timelineProgression, ?array $currentLocation, string $busId): array
    {
        $timeline = [];
        
        foreach ($timelineProgression as $progression) {
            $route = $progression->route;
            
            $timelineItem = [
                'id' => $progression->id,
                'stop_order' => $route->stop_order,
                'stop_name' => $route->stop_name,
                'coordinates' => [
                    'latitude' => $route->latitude,
                    'longitude' => $route->longitude
                ],
                'coverage_radius' => $route->coverage_radius,
                'status' => $progression->status,
                'is_current' => $progression->isCurrent(),
                'is_completed' => $progression->isCompleted(),
                'is_upcoming' => $progression->isUpcoming(),
                'direction' => $progression->trip_direction,
                'progress_percentage' => $progression->progress_percentage,
                'confidence_score' => $progression->confidence_score,
                'reached_at' => $progression->reached_at,
                'estimated_arrival' => $progression->estimated_arrival,
                'eta_minutes' => $progression->eta_minutes,
                'formatted_eta' => $progression->getFormattedETA(),
                'time_since_reached' => $progression->getTimeSinceReached()
            ];
            
            // Add distance from current location if available
            if ($currentLocation) {
                $distance = $this->calculateDistance(
                    $currentLocation['latitude'],
                    $currentLocation['longitude'],
                    $route->latitude,
                    $route->longitude
                );
                $timelineItem['distance_from_current'] = round($distance, 2);
            }
            
            $timeline[] = $timelineItem;
        }
        
        return $timeline;
    }

    /**
     * Find closest stop to current location
     *
     * @param array $currentLocation Current bus location
     * @param \Illuminate\Database\Eloquent\Collection $timelineProgression Timeline progression records
     * @return array Closest stop analysis
     */
    private function findClosestStop(array $currentLocation, $timelineProgression): array
    {
        $closestStop = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($timelineProgression as $progression) {
            $route = $progression->route;
            $distance = $this->calculateDistance(
                $currentLocation['latitude'],
                $currentLocation['longitude'],
                $route->latitude,
                $route->longitude
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $progression;
            }
        }
        
        return [
            'stop' => $closestStop,
            'distance' => $minDistance
        ];
    }

    /**
     * Calculate estimated arrival time for a stop
     *
     * @param array $stop Stop details
     * @param array $tripDirection Trip direction details
     * @return Carbon|null Estimated arrival time
     */
    private function calculateEstimatedArrival(array $stop, array $tripDirection): ?Carbon
    {
        $schedule = BusSchedule::find($tripDirection['schedule_id']);
        
        if (!$schedule) {
            return null;
        }
        
        $baseTime = $tripDirection['direction'] === BusScheduleService::DIRECTION_DEPARTURE
            ? $schedule->departure_time
            : $schedule->return_time;
            
        // Add estimated time based on stop order (rough calculation)
        $estimatedMinutes = ($stop['stop_order'] - 1) * 10; // 10 minutes between stops
        
        return Carbon::createFromFormat('H:i:s', $baseTime)->addMinutes($estimatedMinutes);
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
     * Get timeline status management summary
     *
     * @param string $busId Bus identifier
     * @return array Timeline status summary
     */
    public function getTimelineStatusManagement(string $busId): array
    {
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        if (!$tripDirection['direction']) {
            return [
                'active' => false,
                'message' => 'Bus is not currently active'
            ];
        }
        
        $progression = $this->getTimelineProgressionFromDB($busId, $tripDirection['direction']);
        
        $statusCounts = [
            'completed' => $progression->where('status', BusTimelineProgression::STATUS_COMPLETED)->count(),
            'current' => $progression->where('status', BusTimelineProgression::STATUS_CURRENT)->count(),
            'upcoming' => $progression->where('status', BusTimelineProgression::STATUS_UPCOMING)->count(),
            'skipped' => $progression->where('status', BusTimelineProgression::STATUS_SKIPPED)->count()
        ];
        
        $currentStop = $progression->where('status', BusTimelineProgression::STATUS_CURRENT)->first();
        $nextStop = $progression->where('status', BusTimelineProgression::STATUS_UPCOMING)
            ->sortBy('route.stop_order')
            ->first();
        
        return [
            'active' => true,
            'bus_id' => $busId,
            'trip_direction' => $tripDirection['direction'],
            'status_counts' => $statusCounts,
            'current_stop' => $currentStop ? [
                'name' => $currentStop->route->stop_name,
                'order' => $currentStop->route->stop_order,
                'progress_percentage' => $currentStop->progress_percentage,
                'eta_minutes' => $currentStop->eta_minutes
            ] : null,
            'next_stop' => $nextStop ? [
                'name' => $nextStop->route->stop_name,
                'order' => $nextStop->route->stop_order,
                'eta_minutes' => $nextStop->eta_minutes,
                'formatted_eta' => $nextStop->getFormattedETA()
            ] : null,
            'completion_percentage' => $progression->count() > 0 
                ? round(($statusCounts['completed'] / $progression->count()) * 100, 1) 
                : 0
        ];
    }

    /**
     * Calculate route statistics
     */
    private function calculateRouteStats(array $timeline): array
    {
        $totalStops = count($timeline);
        $completedStops = count(array_filter($timeline, fn($stop) => $stop['is_completed']));
        $upcomingStops = count(array_filter($timeline, fn($stop) => $stop['is_upcoming']));
        $currentStops = count(array_filter($timeline, fn($stop) => $stop['is_current']));

        $currentStop = collect($timeline)->where('is_current', true)->first();
        $nextStop = collect($timeline)->where('is_upcoming', true)->sortBy('stop_order')->first();

        return [
            'total_stops' => $totalStops,
            'completed_stops' => $completedStops,
            'current_stops' => $currentStops,
            'upcoming_stops' => $upcomingStops,
            'completion_percentage' => $totalStops > 0 ? round(($completedStops / $totalStops) * 100, 1) : 0,
            'current_stop_number' => $completedStops + 1,
            'current_stop_name' => $currentStop ? $currentStop['stop_name'] : null,
            'next_stop_name' => $nextStop ? $nextStop['stop_name'] : null,
            'estimated_completion_time' => $this->calculateEstimatedCompletionTime($timeline),
            'average_progress' => $this->calculateAverageProgress($timeline)
        ];
    }

    /**
     * Calculate estimated completion time for the route
     */
    private function calculateEstimatedCompletionTime(array $timeline): ?string
    {
        $upcomingStops = array_filter($timeline, fn($stop) => $stop['is_upcoming'] || $stop['is_current']);
        
        if (empty($upcomingStops)) {
            return null; // Route completed
        }
        
        $totalEtaMinutes = 0;
        $hasValidEta = false;
        
        foreach ($upcomingStops as $stop) {
            if ($stop['eta_minutes']) {
                $totalEtaMinutes += $stop['eta_minutes'];
                $hasValidEta = true;
            }
        }
        
        if (!$hasValidEta) {
            return null;
        }
        
        $completionTime = now()->addMinutes($totalEtaMinutes);
        return $completionTime->format('H:i');
    }

    /**
     * Calculate average progress across all stops
     */
    private function calculateAverageProgress(array $timeline): float
    {
        if (empty($timeline)) {
            return 0.0;
        }
        
        $totalProgress = array_sum(array_column($timeline, 'progress_percentage'));
        return round($totalProgress / count($timeline), 1);
    }

    /**
     * Get stop progression logic based on GPS location and time estimates
     *
     * @param string $busId Bus identifier
     * @param float $latitude Current latitude
     * @param float $longitude Current longitude
     * @return array Stop progression analysis
     */
    public function getStopProgressionLogic(string $busId, float $latitude, float $longitude): array
    {
        try {
            $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
            
            if (!$tripDirection['direction']) {
                return [
                    'success' => false,
                    'message' => 'Bus is not currently active'
                ];
            }

            $progression = $this->getTimelineProgressionFromDB($busId, $tripDirection['direction']);
            
            if ($progression->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No timeline progression data available'
                ];
            }

            // Analyze current position relative to route
            $locationAnalysis = $this->analyzeLocationRelativeToRoute($latitude, $longitude, $progression);
            
            // Determine progression logic
            $progressionLogic = $this->determineProgressionLogic($locationAnalysis, $progression);
            
            // Calculate time-based progression
            $timeBasedProgression = $this->calculateTimeBasedProgression($progression);
            
            // Combine GPS and time-based analysis
            $combinedAnalysis = $this->combineProgressionAnalysis($progressionLogic, $timeBasedProgression);

            return [
                'success' => true,
                'bus_id' => $busId,
                'current_location' => ['latitude' => $latitude, 'longitude' => $longitude],
                'location_analysis' => $locationAnalysis,
                'progression_logic' => $progressionLogic,
                'time_based_progression' => $timeBasedProgression,
                'combined_analysis' => $combinedAnalysis,
                'recommendations' => $this->generateProgressionRecommendations($combinedAnalysis)
            ];

        } catch (\Exception $e) {
            Log::error('Stop progression logic analysis failed', [
                'error' => $e->getMessage(),
                'bus_id' => $busId,
                'coordinates' => ['lat' => $latitude, 'lng' => $longitude]
            ]);

            return [
                'success' => false,
                'message' => 'Progression analysis failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze location relative to route
     *
     * @param float $latitude Current latitude
     * @param float $longitude Current longitude
     * @param \Illuminate\Database\Eloquent\Collection $progression Timeline progression
     * @return array Location analysis
     */
    private function analyzeLocationRelativeToRoute(float $latitude, float $longitude, $progression): array
    {
        $stopDistances = [];
        $closestStop = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($progression as $stop) {
            $route = $stop->route;
            $distance = $this->calculateDistance($latitude, $longitude, $route->latitude, $route->longitude);
            
            $stopDistances[] = [
                'stop' => $stop,
                'distance' => $distance,
                'within_radius' => $distance <= $route->coverage_radius,
                'status' => $stop->status
            ];

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $stop;
            }
        }

        // Sort by distance
        usort($stopDistances, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return [
            'closest_stop' => $closestStop ? [
                'name' => $closestStop->route->stop_name,
                'distance' => $minDistance,
                'status' => $closestStop->status,
                'within_radius' => $minDistance <= $closestStop->route->coverage_radius
            ] : null,
            'stop_distances' => $stopDistances,
            'stops_within_radius' => array_filter($stopDistances, fn($stop) => $stop['within_radius']),
            'location_confidence' => $this->calculateLocationConfidence($stopDistances)
        ];
    }

    /**
     * Determine progression logic based on location analysis
     *
     * @param array $locationAnalysis Location analysis results
     * @param \Illuminate\Database\Eloquent\Collection $progression Timeline progression
     * @return array Progression logic
     */
    private function determineProgressionLogic(array $locationAnalysis, $progression): array
    {
        $closestStop = $locationAnalysis['closest_stop'];
        $currentStop = $progression->where('status', BusTimelineProgression::STATUS_CURRENT)->first();
        $nextStop = $progression->where('status', BusTimelineProgression::STATUS_UPCOMING)
            ->sortBy('route.stop_order')
            ->first();

        $logic = [
            'should_advance' => false,
            'should_mark_completed' => false,
            'should_update_current' => false,
            'confidence' => 0.0,
            'reason' => ''
        ];

        if (!$closestStop) {
            $logic['reason'] = 'No location data available';
            return $logic;
        }

        // Check if bus has reached a new stop
        if ($closestStop['within_radius'] && $closestStop['distance'] <= self::STOP_COMPLETION_RADIUS) {
            $closestStopProgression = $progression->where('route.stop_name', $closestStop['name'])->first();
            
            if ($closestStopProgression && $closestStopProgression->status === BusTimelineProgression::STATUS_UPCOMING) {
                $logic['should_advance'] = true;
                $logic['should_mark_completed'] = true;
                $logic['confidence'] = 0.9;
                $logic['reason'] = "Bus reached {$closestStop['name']} stop";
            } elseif ($closestStopProgression && $closestStopProgression->status === BusTimelineProgression::STATUS_CURRENT) {
                $logic['should_update_current'] = true;
                $logic['confidence'] = 0.8;
                $logic['reason'] = "Bus at current stop {$closestStop['name']}";
            }
        } else {
            // Bus is between stops
            $logic['should_update_current'] = true;
            $logic['confidence'] = 0.6;
            $logic['reason'] = "Bus traveling between stops";
        }

        return $logic;
    }

    /**
     * Calculate time-based progression
     *
     * @param \Illuminate\Database\Eloquent\Collection $progression Timeline progression
     * @return array Time-based progression analysis
     */
    private function calculateTimeBasedProgression($progression): array
    {
        $now = now();
        $timeAnalysis = [];

        foreach ($progression as $stop) {
            $estimatedArrival = $stop->estimated_arrival;
            $isOverdue = $estimatedArrival && $now->gt($estimatedArrival);
            $minutesUntilArrival = $estimatedArrival ? $now->diffInMinutes($estimatedArrival, false) : null;

            $timeAnalysis[] = [
                'stop_name' => $stop->route->stop_name,
                'status' => $stop->status,
                'estimated_arrival' => $estimatedArrival,
                'is_overdue' => $isOverdue,
                'minutes_until_arrival' => $minutesUntilArrival,
                'should_be_current' => $isOverdue && $stop->status === BusTimelineProgression::STATUS_UPCOMING
            ];
        }

        return [
            'analysis' => $timeAnalysis,
            'overdue_stops' => array_filter($timeAnalysis, fn($stop) => $stop['is_overdue']),
            'next_scheduled_stop' => collect($timeAnalysis)
                ->where('status', BusTimelineProgression::STATUS_UPCOMING)
                ->sortBy('minutes_until_arrival')
                ->first()
        ];
    }

    /**
     * Combine GPS and time-based progression analysis
     *
     * @param array $progressionLogic GPS-based progression logic
     * @param array $timeBasedProgression Time-based progression
     * @return array Combined analysis
     */
    private function combineProgressionAnalysis(array $progressionLogic, array $timeBasedProgression): array
    {
        $gpsConfidence = $progressionLogic['confidence'];
        $timeConfidence = count($timeBasedProgression['overdue_stops']) > 0 ? 0.7 : 0.5;

        // Weight GPS data higher if confidence is high
        $combinedConfidence = ($gpsConfidence * 0.7) + ($timeConfidence * 0.3);

        return [
            'should_advance' => $progressionLogic['should_advance'],
            'should_mark_completed' => $progressionLogic['should_mark_completed'],
            'should_update_current' => $progressionLogic['should_update_current'],
            'combined_confidence' => $combinedConfidence,
            'gps_confidence' => $gpsConfidence,
            'time_confidence' => $timeConfidence,
            'primary_reason' => $progressionLogic['reason'],
            'time_factors' => $timeBasedProgression['overdue_stops'],
            'recommendation_strength' => $combinedConfidence > 0.7 ? 'high' : ($combinedConfidence > 0.5 ? 'medium' : 'low')
        ];
    }

    /**
     * Generate progression recommendations
     *
     * @param array $combinedAnalysis Combined analysis results
     * @return array Recommendations
     */
    private function generateProgressionRecommendations(array $combinedAnalysis): array
    {
        $recommendations = [];

        if ($combinedAnalysis['should_advance'] && $combinedAnalysis['combined_confidence'] > 0.7) {
            $recommendations[] = [
                'action' => 'advance_timeline',
                'priority' => 'high',
                'message' => 'Advance timeline progression to next stop',
                'confidence' => $combinedAnalysis['combined_confidence']
            ];
        }

        if ($combinedAnalysis['should_update_current'] && $combinedAnalysis['combined_confidence'] > 0.5) {
            $recommendations[] = [
                'action' => 'update_progress',
                'priority' => 'medium',
                'message' => 'Update current stop progress percentage',
                'confidence' => $combinedAnalysis['combined_confidence']
            ];
        }

        if ($combinedAnalysis['combined_confidence'] < 0.5) {
            $recommendations[] = [
                'action' => 'require_validation',
                'priority' => 'low',
                'message' => 'Low confidence - require additional validation',
                'confidence' => $combinedAnalysis['combined_confidence']
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate location confidence based on stop distances
     *
     * @param array $stopDistances Stop distance analysis
     * @return float Location confidence (0.0 to 1.0)
     */
    private function calculateLocationConfidence(array $stopDistances): float
    {
        if (empty($stopDistances)) {
            return 0.0;
        }

        $closestStop = $stopDistances[0];
        $distance = $closestStop['distance'];
        $radius = $closestStop['stop']['route']['coverage_radius'] ?? 100;

        if ($distance <= $radius) {
            return 1.0 - ($distance / $radius) * 0.3; // 0.7 to 1.0 within radius
        } else {
            return max(0.1, 0.7 - (($distance - $radius) / $radius) * 0.6); // Decreasing outside radius
        }
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
        DB::transaction(function () use ($busId, $direction) {
            // End current active trip
            BusTimelineProgression::forBus($busId)
                ->activeTrip()
                ->update(['is_active_trip' => false]);
            
            // Get current trip direction to reinitialize
            $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
            
            if ($tripDirection['direction']) {
                $this->initializeTimelineProgression($busId, $tripDirection);
            }
        });
        
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