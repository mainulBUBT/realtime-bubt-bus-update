<?php

namespace App\Services;

use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;
use App\Services\HistoricalDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TripCompletionService
{
    private HistoricalDataService $historicalService;
    
    public function __construct(HistoricalDataService $historicalService)
    {
        $this->historicalService = $historicalService;
    }
    
    /**
     * Detect when buses reach their final destinations
     */
    public function detectCompletedTrips(): array
    {
        $results = [
            'completed_trips' => [],
            'stopped_sessions' => 0,
            'archived_data' => 0,
            'errors' => []
        ];
        
        try {
            $activeSchedules = BusSchedule::active()->get();
            
            foreach ($activeSchedules as $schedule) {
                $completionResult = $this->checkTripCompletion($schedule);
                
                if ($completionResult['completed']) {
                    $results['completed_trips'][] = $completionResult;
                    
                    // Stop GPS data collection for completed trip
                    $stoppedSessions = $this->stopGPSDataCollection($schedule->bus_id);
                    $results['stopped_sessions'] += $stoppedSessions;
                    
                    // Generate trip summary and archive
                    $archiveResult = $this->generateAndArchiveTripSummary($schedule);
                    if ($archiveResult['success']) {
                        $results['archived_data'] += $archiveResult['archived_count'];
                    } else {
                        $results['errors'][] = $archiveResult['error'];
                    }
                }
            }
            
            Log::info('Trip completion detection completed', $results);
            
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Trip completion detection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $results;
    }
    
    /**
     * Check if a specific bus trip is completed
     */
    public function checkTripCompletion(BusSchedule $schedule): array
    {
        $result = [
            'bus_id' => $schedule->bus_id,
            'completed' => false,
            'completion_reason' => null,
            'completion_time' => null,
            'final_destination' => null,
            'trip_direction' => $schedule->getCurrentTripDirection()
        ];
        
        try {
            // Check if bus is still within active schedule time
            if (!$schedule->isCurrentlyActive()) {
                $result['completed'] = true;
                $result['completion_reason'] = 'schedule_ended';
                $result['completion_time'] = now()->toDateTimeString();
                return $result;
            }
            
            // Get final destination based on trip direction
            $finalDestination = $this->getFinalDestination($schedule);
            if (!$finalDestination) {
                return $result;
            }
            
            $result['final_destination'] = $finalDestination->stop_name;
            
            // Check if bus has reached final destination
            $reachedDestination = $this->hasBusReachedDestination($schedule->bus_id, $finalDestination);
            
            if ($reachedDestination['reached']) {
                $result['completed'] = true;
                $result['completion_reason'] = 'destination_reached';
                $result['completion_time'] = $reachedDestination['arrival_time'];
                return $result;
            }
            
            // Check for time-based completion (bus should have reached destination by now)
            $timeBasedCompletion = $this->checkTimeBasedCompletion($schedule, $finalDestination);
            
            if ($timeBasedCompletion['completed']) {
                $result['completed'] = true;
                $result['completion_reason'] = 'time_based';
                $result['completion_time'] = $timeBasedCompletion['expected_time'];
                return $result;
            }
            
            // Check for inactivity-based completion (no GPS data for extended period)
            $inactivityCompletion = $this->checkInactivityCompletion($schedule->bus_id);
            
            if ($inactivityCompletion['completed']) {
                $result['completed'] = true;
                $result['completion_reason'] = 'inactivity';
                $result['completion_time'] = $inactivityCompletion['last_activity'];
                return $result;
            }
            
        } catch (\Exception $e) {
            Log::error('Trip completion check failed', [
                'bus_id' => $schedule->bus_id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get the final destination for current trip direction
     */
    private function getFinalDestination(BusSchedule $schedule): ?BusRoute
    {
        $routes = $schedule->routes()->ordered()->get();
        
        if ($routes->isEmpty()) {
            return null;
        }
        
        if ($schedule->isOnDepartureTrip()) {
            // Final destination is the last stop in order
            return $routes->last();
        } else {
            // For return trip, final destination is the first stop in order
            return $routes->first();
        }
    }
    
    /**
     * Check if bus has reached its final destination
     */
    private function hasBusReachedDestination(string $busId, BusRoute $destination): array
    {
        $result = [
            'reached' => false,
            'arrival_time' => null
        ];
        
        // Check recent location data (last 30 minutes)
        $recentLocations = BusLocation::forBus($busId)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->validated()
            ->trusted(0.6)
            ->latest()
            ->get();
        
        if ($recentLocations->isEmpty()) {
            return $result;
        }
        
        // Check if any recent locations are within destination radius
        foreach ($recentLocations as $location) {
            if ($destination->isWithinRadius($location->latitude, $location->longitude)) {
                $result['reached'] = true;
                $result['arrival_time'] = $location->created_at->toDateTimeString();
                break;
            }
        }
        
        // Additional check: if bus has been stationary near destination
        if (!$result['reached']) {
            $stationaryCheck = $this->checkStationaryAtDestination($busId, $destination);
            if ($stationaryCheck['stationary']) {
                $result['reached'] = true;
                $result['arrival_time'] = $stationaryCheck['stationary_since'];
            }
        }
        
        return $result;
    }
    
    /**
     * Check if bus is stationary at destination
     */
    private function checkStationaryAtDestination(string $busId, BusRoute $destination): array
    {
        $result = [
            'stationary' => false,
            'stationary_since' => null
        ];
        
        // Get locations from last 15 minutes
        $recentLocations = BusLocation::forBus($busId)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->validated()
            ->orderBy('created_at')
            ->get();
        
        if ($recentLocations->count() < 3) {
            return $result;
        }
        
        $stationaryCount = 0;
        $firstStationaryTime = null;
        
        foreach ($recentLocations as $location) {
            $isNearDestination = $destination->isWithinRadius($location->latitude, $location->longitude);
            $isStationary = ($location->speed ?? 0) < 2.0; // Less than 2 m/s
            
            if ($isNearDestination && $isStationary) {
                $stationaryCount++;
                if (!$firstStationaryTime) {
                    $firstStationaryTime = $location->created_at;
                }
            } else {
                // Reset if bus moves away or speeds up
                $stationaryCount = 0;
                $firstStationaryTime = null;
            }
        }
        
        // Consider stationary if at least 3 consecutive readings show stationary at destination
        if ($stationaryCount >= 3 && $firstStationaryTime) {
            $result['stationary'] = true;
            $result['stationary_since'] = $firstStationaryTime->toDateTimeString();
        }
        
        return $result;
    }
    
    /**
     * Check for time-based trip completion
     */
    private function checkTimeBasedCompletion(BusSchedule $schedule, BusRoute $finalDestination): array
    {
        $result = [
            'completed' => false,
            'expected_time' => null
        ];
        
        $now = Carbon::now();
        $expectedArrivalTime = $finalDestination->getEstimatedArrivalTime();
        $expectedTime = Carbon::createFromFormat('H:i', $expectedArrivalTime);
        
        // Add buffer time (15 minutes) for delays
        $completionThreshold = $expectedTime->addMinutes(15);
        
        if ($now->gt($completionThreshold)) {
            $result['completed'] = true;
            $result['expected_time'] = $completionThreshold->toDateTimeString();
        }
        
        return $result;
    }
    
    /**
     * Check for inactivity-based completion
     */
    private function checkInactivityCompletion(string $busId): array
    {
        $result = [
            'completed' => false,
            'last_activity' => null
        ];
        
        // Check for GPS data in last 45 minutes
        $lastLocation = BusLocation::forBus($busId)
            ->latest()
            ->first();
        
        if (!$lastLocation) {
            return $result;
        }
        
        $inactivityThreshold = now()->subMinutes(45);
        
        if ($lastLocation->created_at->lt($inactivityThreshold)) {
            $result['completed'] = true;
            $result['last_activity'] = $lastLocation->created_at->toDateTimeString();
        }
        
        return $result;
    }
    
    /**
     * Stop GPS data collection for completed trips
     */
    public function stopGPSDataCollection(string $busId): int
    {
        try {
            // End all active tracking sessions for this bus
            $stoppedSessions = UserTrackingSession::where('bus_id', $busId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_at' => now()
                ]);
            
            // Clear current position cache
            BusCurrentPosition::where('bus_id', $busId)->delete();
            
            Log::info('GPS data collection stopped for completed trip', [
                'bus_id' => $busId,
                'stopped_sessions' => $stoppedSessions
            ]);
            
            return $stoppedSessions;
            
        } catch (\Exception $e) {
            Log::error('Failed to stop GPS data collection', [
                'bus_id' => $busId,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Generate trip summary and archive completed trip data
     */
    public function generateAndArchiveTripSummary(BusSchedule $schedule): array
    {
        try {
            $busId = $schedule->bus_id;
            $today = now()->format('Y-m-d');
            
            // Get all location data for today's trip
            $tripLocations = BusLocation::forBus($busId)
                ->whereDate('created_at', $today)
                ->orderBy('created_at')
                ->get();
            
            if ($tripLocations->isEmpty()) {
                return [
                    'success' => false,
                    'error' => "No location data found for bus {$busId} on {$today}"
                ];
            }
            
            // Generate comprehensive trip summary
            $tripSummary = $this->generateComprehensiveTripSummary($schedule, $tripLocations);
            
            // Archive the trip data using HistoricalDataService
            $archiveResult = $this->historicalService->archiveCompletedTrips(now());
            
            Log::info('Trip summary generated and archived', [
                'bus_id' => $busId,
                'trip_date' => $today,
                'summary' => $tripSummary,
                'archive_result' => $archiveResult
            ]);
            
            return [
                'success' => true,
                'archived_count' => $archiveResult['archived_locations'] ?? 0,
                'trip_summary' => $tripSummary
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to generate trip summary for {$schedule->bus_id}: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate comprehensive trip summary
     */
    private function generateComprehensiveTripSummary(BusSchedule $schedule, $locations): array
    {
        $validatedLocations = $locations->where('is_validated', true);
        $speeds = $locations->whereNotNull('speed')->pluck('speed');
        $routes = $schedule->routes()->ordered()->get();
        
        // Calculate route coverage
        $routeCoverage = $this->calculateRouteCoverage($locations, $routes);
        
        // Calculate trip efficiency
        $efficiency = $this->calculateTripEfficiency($schedule, $locations);
        
        return [
            'bus_id' => $schedule->bus_id,
            'trip_date' => now()->format('Y-m-d'),
            'trip_direction' => $schedule->getCurrentTripDirection(),
            'schedule_info' => [
                'departure_time' => $schedule->departure_time->format('H:i'),
                'return_time' => $schedule->return_time->format('H:i'),
                'route_name' => $schedule->route_name
            ],
            'location_stats' => [
                'total_locations' => $locations->count(),
                'trusted_locations' => $validatedLocations->count(),
                'average_trust' => round($locations->avg('reputation_weight'), 3),
                'unique_devices' => $locations->unique('device_token')->count()
            ],
            'timing_stats' => [
                'first_location' => $locations->first()->created_at->toDateTimeString(),
                'last_location' => $locations->last()->created_at->toDateTimeString(),
                'trip_duration_minutes' => $locations->first()->created_at->diffInMinutes($locations->last()->created_at),
                'scheduled_duration_minutes' => $schedule->departure_time->diffInMinutes($schedule->return_time)
            ],
            'movement_stats' => [
                'average_speed' => $speeds->isNotEmpty() ? round($speeds->avg(), 2) : null,
                'max_speed' => $speeds->isNotEmpty() ? $speeds->max() : null,
                'min_speed' => $speeds->isNotEmpty() ? $speeds->min() : null,
                'total_distance_meters' => $this->calculateTotalDistance($locations)
            ],
            'route_coverage' => $routeCoverage,
            'trip_efficiency' => $efficiency,
            'completion_info' => [
                'completed_at' => now()->toDateTimeString(),
                'completion_method' => 'automatic_detection'
            ]
        ];
    }
    
    /**
     * Calculate route coverage from location data
     */
    private function calculateRouteCoverage($locations, $routes): array
    {
        $coverage = [
            'stops_covered' => 0,
            'total_stops' => $routes->count(),
            'coverage_percentage' => 0,
            'stop_details' => []
        ];
        
        foreach ($routes as $route) {
            $stopCovered = false;
            $visitTime = null;
            
            foreach ($locations as $location) {
                if ($route->isWithinRadius($location->latitude, $location->longitude)) {
                    $stopCovered = true;
                    $visitTime = $location->created_at->toDateTimeString();
                    break;
                }
            }
            
            $coverage['stop_details'][] = [
                'stop_name' => $route->stop_name,
                'stop_order' => $route->stop_order,
                'covered' => $stopCovered,
                'visit_time' => $visitTime,
                'expected_time' => $route->getEstimatedArrivalTime()
            ];
            
            if ($stopCovered) {
                $coverage['stops_covered']++;
            }
        }
        
        $coverage['coverage_percentage'] = $routes->count() > 0 
            ? round(($coverage['stops_covered'] / $routes->count()) * 100, 1)
            : 0;
        
        return $coverage;
    }
    
    /**
     * Calculate trip efficiency metrics
     */
    private function calculateTripEfficiency(BusSchedule $schedule, $locations): array
    {
        $scheduledStart = $schedule->departure_time;
        $scheduledEnd = $schedule->return_time;
        $actualStart = $locations->first()->created_at;
        $actualEnd = $locations->last()->created_at;
        
        $scheduledDuration = $scheduledStart->diffInMinutes($scheduledEnd);
        $actualDuration = $actualStart->diffInMinutes($actualEnd);
        
        return [
            'on_time_start' => abs($scheduledStart->diffInMinutes($actualStart)) <= 15,
            'on_time_end' => abs($scheduledEnd->diffInMinutes($actualEnd)) <= 15,
            'schedule_adherence_percentage' => $scheduledDuration > 0 
                ? round((min($scheduledDuration, $actualDuration) / $scheduledDuration) * 100, 1)
                : 100,
            'delay_minutes' => [
                'start_delay' => $actualStart->diffInMinutes($scheduledStart, false),
                'end_delay' => $actualEnd->diffInMinutes($scheduledEnd, false)
            ],
            'efficiency_score' => $this->calculateEfficiencyScore($schedule, $locations)
        ];
    }
    
    /**
     * Calculate overall efficiency score (0-100)
     */
    private function calculateEfficiencyScore(BusSchedule $schedule, $locations): int
    {
        $score = 100;
        
        // Deduct points for delays
        $actualStart = $locations->first()->created_at;
        $scheduledStart = $schedule->departure_time;
        $startDelay = max(0, $actualStart->diffInMinutes($scheduledStart));
        $score -= min(20, $startDelay); // Max 20 points deduction for start delay
        
        // Deduct points for low trust data
        $trustPercentage = $locations->where('is_validated', true)->count() / $locations->count() * 100;
        if ($trustPercentage < 70) {
            $score -= (70 - $trustPercentage) * 0.5; // Deduct based on trust deficit
        }
        
        // Deduct points for sparse data
        $expectedDataPoints = $schedule->departure_time->diffInMinutes($schedule->return_time) / 2; // Every 2 minutes
        $actualDataPoints = $locations->count();
        if ($actualDataPoints < $expectedDataPoints * 0.5) {
            $score -= 15; // Deduct for sparse data
        }
        
        return max(0, min(100, (int) $score));
    }
    
    /**
     * Calculate total distance traveled
     */
    private function calculateTotalDistance($locations): float
    {
        if ($locations->count() < 2) {
            return 0;
        }
        
        $totalDistance = 0;
        $previousLocation = null;
        
        foreach ($locations as $location) {
            if ($previousLocation) {
                $distance = $location->distanceTo($previousLocation->latitude, $previousLocation->longitude);
                
                // Only count reasonable distances (not GPS jumps)
                if ($distance <= 2000) { // Max 2km between points
                    $totalDistance += $distance;
                }
            }
            $previousLocation = $location;
        }
        
        return round($totalDistance, 2);
    }
    
    /**
     * Handle transition between trip completion and new trip start
     */
    public function handleTripTransition(string $busId): array
    {
        try {
            // Clear any remaining current position data
            BusCurrentPosition::where('bus_id', $busId)->delete();
            
            // Reset any active sessions (shouldn't be any, but safety check)
            UserTrackingSession::where('bus_id', $busId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_at' => now()
                ]);
            
            // Clean up old location data for this bus
            $cleanedCount = BusLocation::forBus($busId)
                ->where('created_at', '<', now()->subHours(1))
                ->delete();
            
            Log::info('Trip transition handled', [
                'bus_id' => $busId,
                'cleaned_locations' => $cleanedCount
            ]);
            
            return [
                'success' => true,
                'cleaned_locations' => $cleanedCount
            ];
            
        } catch (\Exception $e) {
            Log::error('Trip transition handling failed', [
                'bus_id' => $busId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}