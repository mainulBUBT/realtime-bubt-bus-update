<?php

namespace App\Services;

use App\Models\BusRoute;
use App\Models\BusSchedule;
use App\Services\BusScheduleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Route Validator
 * Validates GPS coordinates against sequential route stops and progression
 */
class RouteValidator
{
    private BusScheduleService $scheduleService;
    
    // Validation constants
    private const STOP_PROGRESSION_TOLERANCE = 2; // Allow skipping up to 2 stops
    private const ROUTE_CORRIDOR_WIDTH = 500;     // meters on each side of route
    private const BACKTRACK_PENALTY = 0.3;        // Penalty for going backwards
    private const PROGRESSION_BONUS = 1.2;        // Bonus for correct progression
    
    public function __construct(BusScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Validate GPS coordinates against sequential route stops
     *
     * @param float $latitude GPS latitude
     * @param float $longitude GPS longitude
     * @param string $busId Bus identifier
     * @param Carbon|null $timestamp GPS timestamp
     * @return array Validation result
     */
    public function validateRouteProgression(float $latitude, float $longitude, string $busId, ?Carbon $timestamp = null): array
    {
        $timestamp = $timestamp ?? now();
        
        // Get current trip direction and route
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId, $timestamp);
        
        if (!$tripDirection['direction']) {
            return [
                'valid' => false,
                'reason' => 'bus_not_active',
                'message' => 'Bus is not currently active',
                'confidence' => 0.0
            ];
        }

        $routeStops = $tripDirection['route_stops'];
        if (empty($routeStops)) {
            return [
                'valid' => false,
                'reason' => 'no_route_data',
                'message' => 'No route data available for this bus',
                'confidence' => 0.0
            ];
        }

        // Find closest stop and validate progression
        $closestStopAnalysis = $this->findClosestStopWithProgression($latitude, $longitude, $routeStops, $busId);
        
        // Validate against route corridor
        $corridorValidation = $this->validateRouteCorridorProgression($latitude, $longitude, $routeStops, $closestStopAnalysis);
        
        // Calculate overall validation score
        $validationScore = $this->calculateValidationScore($closestStopAnalysis, $corridorValidation, $tripDirection);
        
        return [
            'valid' => $validationScore['valid'],
            'confidence' => $validationScore['confidence'],
            'closest_stop' => $closestStopAnalysis,
            'corridor_validation' => $corridorValidation,
            'trip_direction' => $tripDirection['direction'],
            'route_adherence_score' => $validationScore['route_adherence'],
            'progression_analysis' => $validationScore['progression_analysis'],
            'message' => $validationScore['message']
        ];
    }

    /**
     * Validate coordinates against route corridor between stops
     *
     * @param float $latitude GPS latitude
     * @param float $longitude GPS longitude
     * @param string $busId Bus identifier
     * @param int $fromStopOrder Starting stop order
     * @param int $toStopOrder Ending stop order
     * @return array Corridor validation result
     */
    public function validateRouteCorridorBetweenStops(
        float $latitude, 
        float $longitude, 
        string $busId, 
        int $fromStopOrder, 
        int $toStopOrder
    ): array {
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        if (!$tripDirection['direction']) {
            return [
                'valid' => false,
                'reason' => 'bus_not_active',
                'distance_from_corridor' => null
            ];
        }

        $routeStops = $tripDirection['route_stops'];
        
        // Find the stops
        $fromStop = collect($routeStops)->firstWhere('stop_order', $fromStopOrder);
        $toStop = collect($routeStops)->firstWhere('stop_order', $toStopOrder);
        
        if (!$fromStop || !$toStop) {
            return [
                'valid' => false,
                'reason' => 'stops_not_found',
                'distance_from_corridor' => null
            ];
        }

        // Calculate distance from route corridor
        $distanceFromCorridor = $this->calculateDistanceFromRouteCorridor(
            $latitude, 
            $longitude,
            $fromStop['latitude'], 
            $fromStop['longitude'],
            $toStop['latitude'], 
            $toStop['longitude']
        );

        $isValid = $distanceFromCorridor <= self::ROUTE_CORRIDOR_WIDTH;

        return [
            'valid' => $isValid,
            'distance_from_corridor' => round($distanceFromCorridor, 2),
            'corridor_width' => self::ROUTE_CORRIDOR_WIDTH,
            'from_stop' => $fromStop['stop_name'],
            'to_stop' => $toStop['stop_name'],
            'adherence_percentage' => $isValid ? 100 : max(0, (1 - ($distanceFromCorridor / self::ROUTE_CORRIDOR_WIDTH)) * 100)
        ];
    }

    /**
     * Validate direction-aware coordinates (different validation for departure vs return)
     *
     * @param float $latitude GPS latitude
     * @param float $longitude GPS longitude
     * @param string $busId Bus identifier
     * @param string $expectedDirection Expected trip direction
     * @return array Direction validation result
     */
    public function validateDirectionAwareCoordinates(
        float $latitude, 
        float $longitude, 
        string $busId, 
        string $expectedDirection
    ): array {
        $currentDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        // Check if direction matches expectation
        if ($currentDirection['direction'] !== $expectedDirection) {
            return [
                'valid' => false,
                'reason' => 'direction_mismatch',
                'expected_direction' => $expectedDirection,
                'actual_direction' => $currentDirection['direction'],
                'message' => "Expected {$expectedDirection} trip but bus is on {$currentDirection['direction']} trip"
            ];
        }

        // Get direction-specific route validation
        $routeValidation = $this->validateRouteProgression($latitude, $longitude, $busId);
        
        // Add direction-specific scoring
        $directionBonus = 1.0;
        if ($expectedDirection === BusScheduleService::DIRECTION_DEPARTURE) {
            // For departure trips, validate progression towards city
            $directionBonus = $this->calculateDepartureProgressionBonus($latitude, $longitude, $currentDirection['route_stops']);
        } else {
            // For return trips, validate progression towards campus
            $directionBonus = $this->calculateReturnProgressionBonus($latitude, $longitude, $currentDirection['route_stops']);
        }

        $routeValidation['confidence'] *= $directionBonus;
        $routeValidation['direction_bonus'] = $directionBonus;
        $routeValidation['validated_direction'] = $expectedDirection;

        return $routeValidation;
    }

    /**
     * Get expected next stops based on current location and progression
     *
     * @param float $latitude Current GPS latitude
     * @param float $longitude Current GPS longitude
     * @param string $busId Bus identifier
     * @return array Expected next stops
     */
    public function getExpectedNextStops(float $latitude, float $longitude, string $busId): array
    {
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId);
        
        if (!$tripDirection['direction']) {
            return [
                'next_stops' => [],
                'message' => 'Bus is not currently active'
            ];
        }

        $routeStops = $tripDirection['route_stops'];
        $closestStopAnalysis = $this->findClosestStopWithProgression($latitude, $longitude, $routeStops, $busId);
        
        if (!$closestStopAnalysis['closest_stop']) {
            return [
                'next_stops' => [],
                'message' => 'Could not determine current position on route'
            ];
        }

        $currentStopOrder = $closestStopAnalysis['closest_stop']['stop_order'];
        $nextStops = [];

        // Get next 3 stops in sequence
        for ($i = 1; $i <= 3; $i++) {
            $nextStopOrder = $currentStopOrder + $i;
            $nextStop = collect($routeStops)->firstWhere('stop_order', $nextStopOrder);
            
            if ($nextStop) {
                $distance = $this->calculateDistance(
                    $latitude, $longitude,
                    $nextStop['latitude'], $nextStop['longitude']
                );
                
                $nextStops[] = [
                    'stop_name' => $nextStop['stop_name'],
                    'stop_order' => $nextStop['stop_order'],
                    'distance_meters' => round($distance, 2),
                    'estimated_time' => $nextStop['estimated_time'],
                    'coordinates' => [
                        'latitude' => $nextStop['latitude'],
                        'longitude' => $nextStop['longitude']
                    ]
                ];
            }
        }

        return [
            'current_stop' => $closestStopAnalysis['closest_stop'],
            'next_stops' => $nextStops,
            'trip_direction' => $tripDirection['direction'],
            'progression_confidence' => $closestStopAnalysis['progression_confidence']
        ];
    }

    /**
     * Private helper methods
     */

    /**
     * Find closest stop with progression analysis
     */
    private function findClosestStopWithProgression(float $latitude, float $longitude, array $routeStops, string $busId): array
    {
        $distances = [];
        
        foreach ($routeStops as $stop) {
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                $stop['latitude'], $stop['longitude']
            );
            
            $distances[] = [
                'stop' => $stop,
                'distance' => $distance,
                'within_radius' => $distance <= $stop['coverage_radius']
            ];
        }

        // Sort by distance
        usort($distances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $closestStop = $distances[0];
        
        // Analyze progression based on recent location history
        $progressionAnalysis = $this->analyzeStopProgression($busId, $closestStop['stop']['stop_order'], $routeStops);
        
        return [
            'closest_stop' => $closestStop['stop'],
            'distance_to_closest' => $closestStop['distance'],
            'within_stop_radius' => $closestStop['within_radius'],
            'all_distances' => $distances,
            'progression_analysis' => $progressionAnalysis,
            'progression_confidence' => $progressionAnalysis['confidence']
        ];
    }

    /**
     * Validate route corridor progression
     */
    private function validateRouteCorridorProgression(float $latitude, float $longitude, array $routeStops, array $closestStopAnalysis): array
    {
        $currentStopOrder = $closestStopAnalysis['closest_stop']['stop_order'];
        
        // Check corridor to next stop
        $nextStopOrder = $currentStopOrder + 1;
        $nextStop = collect($routeStops)->firstWhere('stop_order', $nextStopOrder);
        
        if (!$nextStop) {
            // At final stop
            return [
                'valid' => $closestStopAnalysis['within_stop_radius'],
                'reason' => 'at_final_stop',
                'distance_from_corridor' => $closestStopAnalysis['distance_to_closest']
            ];
        }

        // Calculate distance from corridor between current and next stop
        $corridorDistance = $this->calculateDistanceFromRouteCorridor(
            $latitude, $longitude,
            $closestStopAnalysis['closest_stop']['latitude'],
            $closestStopAnalysis['closest_stop']['longitude'],
            $nextStop['latitude'],
            $nextStop['longitude']
        );

        return [
            'valid' => $corridorDistance <= self::ROUTE_CORRIDOR_WIDTH,
            'distance_from_corridor' => round($corridorDistance, 2),
            'corridor_width' => self::ROUTE_CORRIDOR_WIDTH,
            'between_stops' => [
                'from' => $closestStopAnalysis['closest_stop']['stop_name'],
                'to' => $nextStop['stop_name']
            ]
        ];
    }

    /**
     * Calculate overall validation score
     */
    private function calculateValidationScore(array $closestStopAnalysis, array $corridorValidation, array $tripDirection): array
    {
        $baseScore = 0.5;
        $confidence = 0.5;
        $messages = [];

        // Stop proximity score
        if ($closestStopAnalysis['within_stop_radius']) {
            $baseScore += 0.3;
            $confidence += 0.2;
            $messages[] = 'Within stop radius';
        }

        // Corridor adherence score
        if ($corridorValidation['valid']) {
            $baseScore += 0.2;
            $confidence += 0.15;
            $messages[] = 'Within route corridor';
        }

        // Progression analysis score
        $progressionAnalysis = $closestStopAnalysis['progression_analysis'];
        if ($progressionAnalysis['is_progressing']) {
            $baseScore += 0.2 * self::PROGRESSION_BONUS;
            $confidence += 0.15;
            $messages[] = 'Correct route progression';
        } elseif ($progressionAnalysis['is_backtracking']) {
            $baseScore *= self::BACKTRACK_PENALTY;
            $confidence *= 0.7;
            $messages[] = 'Backtracking detected';
        }

        // Direction consistency
        $confidence *= $progressionAnalysis['confidence'];

        $isValid = $baseScore >= 0.6 && $confidence >= 0.5;

        return [
            'valid' => $isValid,
            'confidence' => min(1.0, $confidence),
            'route_adherence' => min(1.0, $baseScore),
            'progression_analysis' => $progressionAnalysis,
            'message' => $isValid ? 
                'Valid route progression: ' . implode(', ', $messages) :
                'Invalid route progression: ' . implode(', ', $messages)
        ];
    }

    /**
     * Analyze stop progression based on history
     */
    private function analyzeStopProgression(string $busId, int $currentStopOrder, array $routeStops): array
    {
        $cacheKey = "progression_analysis_{$busId}_{$currentStopOrder}";
        
        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($busId, $currentStopOrder, $routeStops) {
            // Get recent location history for this bus
            $recentLocations = \App\Models\BusLocation::where('bus_id', $busId)
                ->where('created_at', '>', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentLocations->count() < 2) {
                return [
                    'is_progressing' => true,
                    'is_backtracking' => false,
                    'confidence' => 0.5,
                    'reason' => 'insufficient_history'
                ];
            }

            // Analyze progression pattern
            $stopOrders = [];
            foreach ($recentLocations as $location) {
                $closestStop = $this->findClosestStopToCoordinates(
                    $location->latitude, 
                    $location->longitude, 
                    $routeStops
                );
                $stopOrders[] = $closestStop['stop_order'];
            }

            // Check for progression (increasing stop orders)
            $isProgressing = $this->isSequenceProgressing($stopOrders);
            $isBacktracking = $this->isSequenceBacktracking($stopOrders);
            
            // Calculate confidence based on consistency
            $confidence = $this->calculateProgressionConfidence($stopOrders);

            return [
                'is_progressing' => $isProgressing,
                'is_backtracking' => $isBacktracking,
                'confidence' => $confidence,
                'stop_sequence' => $stopOrders,
                'reason' => $isProgressing ? 'normal_progression' : 
                           ($isBacktracking ? 'backtracking_detected' : 'irregular_movement')
            ];
        });
    }

    /**
     * Calculate distance from route corridor (point to line segment)
     */
    private function calculateDistanceFromRouteCorridor(
        float $pointLat, float $pointLng,
        float $line1Lat, float $line1Lng,
        float $line2Lat, float $line2Lng
    ): float {
        // Convert to approximate meters for calculation
        $A = $pointLng - $line1Lng;
        $B = $pointLat - $line1Lat;
        $C = $line2Lng - $line1Lng;
        $D = $line2Lat - $line1Lat;

        $dot = $A * $C + $B * $D;
        $lenSq = $C * $C + $D * $D;
        
        if ($lenSq == 0) {
            // Line segment is actually a point
            return $this->calculateDistance($pointLat, $pointLng, $line1Lat, $line1Lng);
        }

        $param = $dot / $lenSq;

        if ($param < 0) {
            // Closest point is start of segment
            return $this->calculateDistance($pointLat, $pointLng, $line1Lat, $line1Lng);
        } elseif ($param > 1) {
            // Closest point is end of segment
            return $this->calculateDistance($pointLat, $pointLng, $line2Lat, $line2Lng);
        } else {
            // Closest point is on the segment
            $closestLng = $line1Lng + $param * $C;
            $closestLat = $line1Lat + $param * $D;
            return $this->calculateDistance($pointLat, $pointLng, $closestLat, $closestLng);
        }
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
     * Find closest stop to given coordinates
     */
    private function findClosestStopToCoordinates(float $latitude, float $longitude, array $routeStops): array
    {
        $minDistance = PHP_FLOAT_MAX;
        $closestStop = null;

        foreach ($routeStops as $stop) {
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                $stop['latitude'], $stop['longitude']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $stop;
            }
        }

        return $closestStop;
    }

    /**
     * Check if sequence is progressing (increasing)
     */
    private function isSequenceProgressing(array $stopOrders): bool
    {
        if (count($stopOrders) < 2) return true;

        $increasingCount = 0;
        for ($i = 1; $i < count($stopOrders); $i++) {
            if ($stopOrders[$i] > $stopOrders[$i-1]) {
                $increasingCount++;
            }
        }

        return $increasingCount >= (count($stopOrders) - 1) * 0.6; // 60% threshold
    }

    /**
     * Check if sequence is backtracking (decreasing)
     */
    private function isSequenceBacktracking(array $stopOrders): bool
    {
        if (count($stopOrders) < 2) return false;

        $decreasingCount = 0;
        for ($i = 1; $i < count($stopOrders); $i++) {
            if ($stopOrders[$i] < $stopOrders[$i-1]) {
                $decreasingCount++;
            }
        }

        return $decreasingCount >= (count($stopOrders) - 1) * 0.6; // 60% threshold
    }

    /**
     * Calculate progression confidence
     */
    private function calculateProgressionConfidence(array $stopOrders): float
    {
        if (count($stopOrders) < 2) return 0.5;

        $totalTransitions = count($stopOrders) - 1;
        $consistentTransitions = 0;

        for ($i = 1; $i < count($stopOrders); $i++) {
            $diff = abs($stopOrders[$i] - $stopOrders[$i-1]);
            // Consistent if difference is 0 or 1 (same stop or next stop)
            if ($diff <= 1) {
                $consistentTransitions++;
            }
        }

        return $consistentTransitions / $totalTransitions;
    }

    /**
     * Calculate departure progression bonus
     */
    private function calculateDepartureProgressionBonus(float $latitude, float $longitude, array $routeStops): float
    {
        // For departure trips, bonus for being closer to later stops in sequence
        $totalStops = count($routeStops);
        if ($totalStops === 0) return 1.0;

        $closestStop = $this->findClosestStopToCoordinates($latitude, $longitude, $routeStops);
        $progressPercentage = $closestStop['stop_order'] / $totalStops;

        // Bonus increases as we progress through the route
        return 0.8 + ($progressPercentage * 0.4); // Range: 0.8 to 1.2
    }

    /**
     * Calculate return progression bonus
     */
    private function calculateReturnProgressionBonus(float $latitude, float $longitude, array $routeStops): float
    {
        // For return trips, bonus for being closer to earlier stops in reversed sequence
        $totalStops = count($routeStops);
        if ($totalStops === 0) return 1.0;

        $closestStop = $this->findClosestStopToCoordinates($latitude, $longitude, $routeStops);
        $progressPercentage = $closestStop['stop_order'] / $totalStops;

        // Bonus increases as we progress through the return route
        return 0.8 + ($progressPercentage * 0.4); // Range: 0.8 to 1.2
    }
}