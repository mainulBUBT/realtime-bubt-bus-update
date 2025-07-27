<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Services\StoppageCoordinateValidator;
use App\Services\RouteValidator;
use App\Services\BusScheduleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Comprehensive GPS Data Validation System
 * Implements all validation requirements for GPS location data
 */
class GPSDataValidator
{
    private StoppageCoordinateValidator $stoppageValidator;
    private RouteValidator $routeValidator;
    private BusScheduleService $scheduleService;

    // Bangladesh geographical boundaries
    private const BANGLADESH_BOUNDS = [
        'min_lat' => 20.670883,
        'max_lat' => 26.446526,
        'min_lng' => 88.084422,
        'max_lng' => 92.674797
    ];

    // Speed validation constants
    private const MAX_SPEED_KMH = 80;           // Maximum realistic bus speed
    private const MAX_ACCELERATION_MS2 = 3.0;   // Maximum realistic acceleration
    private const MIN_TIME_INTERVAL = 5;        // Minimum seconds between location updates

    // Accuracy and quality thresholds
    private const MIN_GPS_ACCURACY = 100;       // Minimum acceptable GPS accuracy in meters
    private const MAX_GPS_ACCURACY = 1000;      // Maximum acceptable GPS accuracy in meters
    private const TIMESTAMP_TOLERANCE = 300;    // 5 minutes tolerance for timestamp validation

    public function __construct(
        StoppageCoordinateValidator $stoppageValidator,
        RouteValidator $routeValidator,
        BusScheduleService $scheduleService
    ) {
        $this->stoppageValidator = $stoppageValidator;
        $this->routeValidator = $routeValidator;
        $this->scheduleService = $scheduleService;
    }

    /**
     * Comprehensive GPS data validation
     *
     * @param array $locationData GPS location data
     * @return array Validation result with detailed analysis
     */
    public function validateGPSData(array $locationData): array
    {
        $result = [
            'valid' => false,
            'confidence_score' => 0.0,
            'validation_results' => [],
            'flags' => [],
            'recommendations' => []
        ];

        try {
            // 1. Coordinate boundary validation for Bangladesh region
            $boundaryValidation = $this->validateCoordinateBoundaries($locationData);
            $result['validation_results']['boundary'] = $boundaryValidation;

            if (!$boundaryValidation['valid']) {
                $result['flags'][] = 'coordinates_outside_bangladesh';
                return $result;
            }

            // 2. Speed limit validation to prevent impossible movements
            $speedValidation = $this->validateSpeedLimits($locationData);
            $result['validation_results']['speed'] = $speedValidation;

            // 3. Route adherence checking against expected bus paths
            $routeValidation = $this->validateRouteAdherence($locationData);
            $result['validation_results']['route'] = $routeValidation;

            // 4. Timestamp validation for location data consistency
            $timestampValidation = $this->validateTimestamp($locationData);
            $result['validation_results']['timestamp'] = $timestampValidation;

            // 5. GPS accuracy and quality validation
            $accuracyValidation = $this->validateGPSAccuracy($locationData);
            $result['validation_results']['accuracy'] = $accuracyValidation;

            // 6. Movement pattern validation
            $movementValidation = $this->validateMovementPattern($locationData);
            $result['validation_results']['movement'] = $movementValidation;

            // 7. Schedule-based validation
            $scheduleValidation = $this->validateAgainstSchedule($locationData);
            $result['validation_results']['schedule'] = $scheduleValidation;

            // Calculate overall confidence score
            $result['confidence_score'] = $this->calculateConfidenceScore($result['validation_results']);
            
            // Determine if data is valid based on confidence score and critical validations
            $result['valid'] = $this->determineOverallValidity($result['validation_results'], $result['confidence_score']);

            // Generate flags and recommendations
            $result['flags'] = $this->generateValidationFlags($result['validation_results']);
            $result['recommendations'] = $this->generateRecommendations($result['validation_results']);

            Log::info('GPS data validation completed', [
                'device_token' => substr($locationData['device_token'] ?? 'unknown', 0, 8) . '...',
                'bus_id' => $locationData['bus_id'] ?? 'unknown',
                'valid' => $result['valid'],
                'confidence_score' => $result['confidence_score'],
                'flags' => $result['flags']
            ]);

        } catch (\Exception $e) {
            Log::error('GPS validation failed', [
                'error' => $e->getMessage(),
                'location_data' => $locationData
            ]);
            
            $result['flags'][] = 'validation_error';
            $result['validation_results']['error'] = [
                'valid' => false,
                'message' => 'Validation process failed: ' . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Validate coordinate boundaries for Bangladesh region
     */
    private function validateCoordinateBoundaries(array $locationData): array
    {
        $lat = $locationData['latitude'] ?? null;
        $lng = $locationData['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return [
                'valid' => false,
                'message' => 'Invalid coordinate format',
                'details' => ['lat' => $lat, 'lng' => $lng]
            ];
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        // Check Bangladesh boundaries
        $withinBounds = (
            $lat >= self::BANGLADESH_BOUNDS['min_lat'] &&
            $lat <= self::BANGLADESH_BOUNDS['max_lat'] &&
            $lng >= self::BANGLADESH_BOUNDS['min_lng'] &&
            $lng <= self::BANGLADESH_BOUNDS['max_lng']
        );

        // Check for obviously invalid coordinates
        $obviouslyInvalid = (
            ($lat == 0 && $lng == 0) ||
            abs($lat) < 0.001 ||
            abs($lng) < 0.001 ||
            abs($lat) > 90 ||
            abs($lng) > 180
        );

        $valid = $withinBounds && !$obviouslyInvalid;

        return [
            'valid' => $valid,
            'within_bangladesh' => $withinBounds,
            'obviously_invalid' => $obviouslyInvalid,
            'coordinates' => ['lat' => $lat, 'lng' => $lng],
            'message' => $valid ? 'Coordinates within Bangladesh bounds' : 
                        ($obviouslyInvalid ? 'Obviously invalid coordinates' : 'Coordinates outside Bangladesh'),
            'confidence' => $valid ? 1.0 : 0.0
        ];
    }

    /**
     * Validate speed limits to prevent impossible movements
     */
    private function validateSpeedLimits(array $locationData): array
    {
        $deviceToken = $locationData['device_token'] ?? null;
        $currentTime = isset($locationData['timestamp']) ? 
            Carbon::createFromTimestamp($locationData['timestamp'] / 1000) : now();
        $currentLat = (float) ($locationData['latitude'] ?? 0);
        $currentLng = (float) ($locationData['longitude'] ?? 0);

        if (!$deviceToken) {
            return [
                'valid' => true,
                'message' => 'No device token for speed validation',
                'confidence' => 0.5
            ];
        }

        // Get previous location for this device
        $previousLocation = BusLocation::where('device_token', hash('sha256', $deviceToken))
            ->where('created_at', '>', $currentTime->copy()->subMinutes(30))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousLocation) {
            return [
                'valid' => true,
                'message' => 'No previous location for speed validation',
                'confidence' => 0.7,
                'is_first_location' => true
            ];
        }

        // Calculate distance and time difference
        $distance = $this->calculateDistance(
            $previousLocation->latitude,
            $previousLocation->longitude,
            $currentLat,
            $currentLng
        );

        $timeDiff = $currentTime->diffInSeconds($previousLocation->created_at);
        
        if ($timeDiff < self::MIN_TIME_INTERVAL) {
            return [
                'valid' => false,
                'message' => 'Location updates too frequent',
                'time_interval' => $timeDiff,
                'min_interval' => self::MIN_TIME_INTERVAL,
                'confidence' => 0.2
            ];
        }

        // Calculate speed in km/h
        $speedKmh = ($distance / $timeDiff) * 3.6;

        // Calculate acceleration if we have speed data
        $acceleration = null;
        if ($previousLocation->speed !== null && isset($locationData['speed'])) {
            $speedDiff = $locationData['speed'] - $previousLocation->speed;
            $acceleration = $speedDiff / $timeDiff; // m/s²
        }

        // Validate speed
        $speedValid = $speedKmh <= self::MAX_SPEED_KMH;
        
        // Validate acceleration if available
        $accelerationValid = $acceleration === null || abs($acceleration) <= self::MAX_ACCELERATION_MS2;

        $overallValid = $speedValid && $accelerationValid;

        return [
            'valid' => $overallValid,
            'calculated_speed_kmh' => round($speedKmh, 2),
            'max_speed_kmh' => self::MAX_SPEED_KMH,
            'distance_meters' => round($distance, 2),
            'time_seconds' => $timeDiff,
            'acceleration_ms2' => $acceleration ? round($acceleration, 2) : null,
            'speed_valid' => $speedValid,
            'acceleration_valid' => $accelerationValid,
            'message' => $overallValid ? 'Speed validation passed' : 
                        (!$speedValid ? "Speed too high: {$speedKmh} km/h" : "Acceleration too high: {$acceleration} m/s²"),
            'confidence' => $overallValid ? 0.9 : ($speedKmh > self::MAX_SPEED_KMH * 2 ? 0.1 : 0.4)
        ];
    }

    /**
     * Validate route adherence against expected bus paths
     */
    private function validateRouteAdherence(array $locationData): array
    {
        $lat = (float) ($locationData['latitude'] ?? 0);
        $lng = (float) ($locationData['longitude'] ?? 0);
        $busId = $locationData['bus_id'] ?? null;

        if (!$busId) {
            return [
                'valid' => false,
                'message' => 'No bus ID provided for route validation',
                'confidence' => 0.0
            ];
        }

        // Validate against bus stops
        $stoppageValidation = $this->stoppageValidator->validateStoppageRadius($lat, $lng);
        
        // Validate against bus route progression
        $routeProgression = $this->routeValidator->validateRouteProgression($lat, $lng, $busId);

        // Validate against expected bus route
        $routeValidation = $this->stoppageValidator->validateAgainstBusRoute($lat, $lng, $busId);

        // Calculate combined route adherence score
        $adherenceScore = 0;
        $validationMessages = [];

        if ($stoppageValidation['within_radius']) {
            $adherenceScore += 0.4;
            $validationMessages[] = 'At valid bus stop';
        }

        if ($routeProgression['valid']) {
            $adherenceScore += 0.4;
            $validationMessages[] = 'Following route progression';
        }

        if ($routeValidation['on_expected_route']) {
            $adherenceScore += 0.3;
            $validationMessages[] = 'On expected route';
        }

        $isValid = $adherenceScore >= 0.4; // At least one major validation must pass

        return [
            'valid' => $isValid,
            'adherence_score' => round($adherenceScore, 2),
            'stoppage_validation' => $stoppageValidation,
            'route_progression' => $routeProgression,
            'route_validation' => $routeValidation,
            'message' => $isValid ? implode(', ', $validationMessages) : 'Outside expected route area',
            'confidence' => min(1.0, $adherenceScore)
        ];
    }

    /**
     * Validate timestamp for location data consistency
     */
    private function validateTimestamp(array $locationData): array
    {
        $timestamp = $locationData['timestamp'] ?? null;
        $now = now();

        if (!$timestamp) {
            return [
                'valid' => false,
                'message' => 'No timestamp provided',
                'confidence' => 0.0
            ];
        }

        // Convert timestamp (assuming milliseconds)
        $locationTime = Carbon::createFromTimestamp($timestamp / 1000);
        
        // Check if timestamp is reasonable (not too far in past or future)
        $timeDiff = abs($now->diffInSeconds($locationTime));
        
        $valid = $timeDiff <= self::TIMESTAMP_TOLERANCE;

        // Check for obviously wrong timestamps
        $obviouslyWrong = (
            $locationTime->year < 2020 ||
            $locationTime->year > $now->year + 1 ||
            $locationTime->isFuture() && $timeDiff > 60 // More than 1 minute in future
        );

        $overallValid = $valid && !$obviouslyWrong;

        return [
            'valid' => $overallValid,
            'timestamp' => $locationTime->toISOString(),
            'time_diff_seconds' => $timeDiff,
            'tolerance_seconds' => self::TIMESTAMP_TOLERANCE,
            'obviously_wrong' => $obviouslyWrong,
            'message' => $overallValid ? 'Timestamp valid' : 
                        ($obviouslyWrong ? 'Obviously wrong timestamp' : 'Timestamp outside tolerance'),
            'confidence' => $overallValid ? 0.9 : ($obviouslyWrong ? 0.1 : 0.5)
        ];
    }

    /**
     * Validate GPS accuracy and quality
     */
    private function validateGPSAccuracy(array $locationData): array
    {
        $accuracy = $locationData['accuracy'] ?? null;

        if (!is_numeric($accuracy) || $accuracy <= 0) {
            return [
                'valid' => false,
                'message' => 'Invalid accuracy value',
                'confidence' => 0.0
            ];
        }

        $accuracy = (float) $accuracy;

        // Check if accuracy is within acceptable range
        $withinRange = $accuracy >= self::MIN_GPS_ACCURACY && $accuracy <= self::MAX_GPS_ACCURACY;
        
        // Calculate quality score based on accuracy
        $qualityScore = 0;
        if ($accuracy <= 10) {
            $qualityScore = 1.0; // Excellent
        } elseif ($accuracy <= 25) {
            $qualityScore = 0.9; // Very good
        } elseif ($accuracy <= 50) {
            $qualityScore = 0.8; // Good
        } elseif ($accuracy <= self::MIN_GPS_ACCURACY) {
            $qualityScore = 0.6; // Acceptable
        } else {
            $qualityScore = max(0.1, 1.0 - ($accuracy / self::MAX_GPS_ACCURACY));
        }

        return [
            'valid' => $withinRange,
            'accuracy_meters' => $accuracy,
            'min_accuracy' => self::MIN_GPS_ACCURACY,
            'max_accuracy' => self::MAX_GPS_ACCURACY,
            'quality_score' => round($qualityScore, 2),
            'quality_level' => $this->getAccuracyQualityLevel($accuracy),
            'message' => $withinRange ? "GPS accuracy acceptable: {$accuracy}m" : 
                        "GPS accuracy outside range: {$accuracy}m",
            'confidence' => $qualityScore
        ];
    }

    /**
     * Validate movement pattern consistency
     */
    private function validateMovementPattern(array $locationData): array
    {
        $deviceToken = $locationData['device_token'] ?? null;
        $busId = $locationData['bus_id'] ?? null;

        if (!$deviceToken || !$busId) {
            return [
                'valid' => true,
                'message' => 'Insufficient data for movement pattern validation',
                'confidence' => 0.5
            ];
        }

        // Get recent locations for pattern analysis
        $recentLocations = BusLocation::where('device_token', hash('sha256', $deviceToken))
            ->where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(15))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentLocations->count() < 2) {
            return [
                'valid' => true,
                'message' => 'Insufficient location history for pattern validation',
                'confidence' => 0.6,
                'pattern_type' => 'insufficient_data'
            ];
        }

        // Analyze movement consistency
        $movementAnalysis = $this->analyzeMovementConsistency($recentLocations);
        
        // Check for stationary behavior (might indicate user not on bus)
        $stationaryAnalysis = $this->analyzeStationaryBehavior($recentLocations);
        
        // Check for erratic movement patterns
        $erraticAnalysis = $this->analyzeErraticMovement($recentLocations);

        $overallValid = (
            $movementAnalysis['consistent'] &&
            !$stationaryAnalysis['is_stationary'] &&
            !$erraticAnalysis['is_erratic']
        );

        return [
            'valid' => $overallValid,
            'movement_consistency' => $movementAnalysis,
            'stationary_analysis' => $stationaryAnalysis,
            'erratic_analysis' => $erraticAnalysis,
            'pattern_type' => $this->determineMovementPatternType($movementAnalysis, $stationaryAnalysis, $erraticAnalysis),
            'message' => $overallValid ? 'Movement pattern consistent with bus travel' : 
                        'Movement pattern inconsistent with bus travel',
            'confidence' => $this->calculateMovementConfidence($movementAnalysis, $stationaryAnalysis, $erraticAnalysis)
        ];
    }

    /**
     * Validate against bus schedule
     */
    private function validateAgainstSchedule(array $locationData): array
    {
        $busId = $locationData['bus_id'] ?? null;
        $timestamp = isset($locationData['timestamp']) ? 
            Carbon::createFromTimestamp($locationData['timestamp'] / 1000) : now();

        if (!$busId) {
            return [
                'valid' => false,
                'message' => 'No bus ID provided for schedule validation',
                'confidence' => 0.0
            ];
        }

        // Check if bus is currently scheduled to be active
        $isActive = $this->scheduleService->isBusCurrentlyActive($busId, $timestamp);
        
        if (!$isActive) {
            return [
                'valid' => false,
                'message' => 'Bus is not scheduled to be active at this time',
                'bus_id' => $busId,
                'timestamp' => $timestamp->toISOString(),
                'confidence' => 0.0
            ];
        }

        // Get current trip direction
        $tripDirection = $this->scheduleService->getCurrentTripDirection($busId, $timestamp);

        return [
            'valid' => true,
            'bus_active' => true,
            'trip_direction' => $tripDirection,
            'message' => 'Bus is scheduled to be active',
            'confidence' => 0.9
        ];
    }

    /**
     * Calculate overall confidence score
     */
    private function calculateConfidenceScore(array $validationResults): float
    {
        $weights = [
            'boundary' => 0.2,
            'speed' => 0.15,
            'route' => 0.25,
            'timestamp' => 0.1,
            'accuracy' => 0.15,
            'movement' => 0.1,
            'schedule' => 0.05
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $key => $weight) {
            if (isset($validationResults[$key]['confidence'])) {
                $totalScore += $validationResults[$key]['confidence'] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0;
    }

    /**
     * Determine overall validity
     */
    private function determineOverallValidity(array $validationResults, float $confidenceScore): bool
    {
        // Critical validations that must pass
        $criticalValidations = ['boundary', 'timestamp', 'schedule'];
        
        foreach ($criticalValidations as $validation) {
            if (isset($validationResults[$validation]) && !$validationResults[$validation]['valid']) {
                return false;
            }
        }

        // Overall confidence must be above threshold
        return $confidenceScore >= 0.6;
    }

    /**
     * Generate validation flags
     */
    private function generateValidationFlags(array $validationResults): array
    {
        $flags = [];

        foreach ($validationResults as $type => $result) {
            if (!$result['valid']) {
                $flags[] = "{$type}_validation_failed";
            }
        }

        // Add specific flags based on validation details
        if (isset($validationResults['speed']['calculated_speed_kmh']) && 
            $validationResults['speed']['calculated_speed_kmh'] > self::MAX_SPEED_KMH * 1.5) {
            $flags[] = 'extremely_high_speed';
        }

        if (isset($validationResults['accuracy']['accuracy_meters']) && 
            $validationResults['accuracy']['accuracy_meters'] > 200) {
            $flags[] = 'poor_gps_accuracy';
        }

        return array_unique($flags);
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(array $validationResults): array
    {
        $recommendations = [];

        if (isset($validationResults['accuracy']) && !$validationResults['accuracy']['valid']) {
            $recommendations[] = 'Improve GPS signal by moving to open area';
        }

        if (isset($validationResults['route']) && !$validationResults['route']['valid']) {
            $recommendations[] = 'Ensure you are on the correct bus route';
        }

        if (isset($validationResults['speed']) && !$validationResults['speed']['valid']) {
            $recommendations[] = 'Check if location services are working correctly';
        }

        return $recommendations;
    }

    /**
     * Helper methods
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

    private function getAccuracyQualityLevel(float $accuracy): string
    {
        if ($accuracy <= 10) return 'excellent';
        if ($accuracy <= 25) return 'very_good';
        if ($accuracy <= 50) return 'good';
        if ($accuracy <= 100) return 'acceptable';
        return 'poor';
    }

    private function analyzeMovementConsistency($locations): array
    {
        if ($locations->count() < 2) {
            return ['consistent' => true, 'reason' => 'insufficient_data'];
        }

        $speeds = [];
        $directions = [];

        for ($i = 1; $i < $locations->count(); $i++) {
            $current = $locations[$i-1];
            $previous = $locations[$i];

            $distance = $this->calculateDistance(
                $previous->latitude, $previous->longitude,
                $current->latitude, $current->longitude
            );

            $timeDiff = $current->created_at->diffInSeconds($previous->created_at);
            
            if ($timeDiff > 0) {
                $speed = ($distance / $timeDiff) * 3.6; // km/h
                $speeds[] = $speed;

                // Calculate bearing/direction
                $direction = $this->calculateBearing(
                    $previous->latitude, $previous->longitude,
                    $current->latitude, $current->longitude
                );
                $directions[] = $direction;
            }
        }

        if (empty($speeds)) {
            return ['consistent' => true, 'reason' => 'no_movement_data'];
        }

        // Check speed consistency (coefficient of variation)
        $avgSpeed = array_sum($speeds) / count($speeds);
        $speedVariance = 0;
        foreach ($speeds as $speed) {
            $speedVariance += pow($speed - $avgSpeed, 2);
        }
        $speedStdDev = sqrt($speedVariance / count($speeds));
        $speedCV = $avgSpeed > 0 ? $speedStdDev / $avgSpeed : 0;

        // Check direction consistency
        $directionConsistency = $this->calculateDirectionConsistency($directions);

        $consistent = $speedCV < 1.0 && $directionConsistency > 0.6;

        return [
            'consistent' => $consistent,
            'average_speed' => round($avgSpeed, 2),
            'speed_coefficient_variation' => round($speedCV, 2),
            'direction_consistency' => round($directionConsistency, 2),
            'reason' => $consistent ? 'movement_consistent' : 'movement_inconsistent'
        ];
    }

    private function analyzeStationaryBehavior($locations): array
    {
        if ($locations->count() < 3) {
            return ['is_stationary' => false, 'reason' => 'insufficient_data'];
        }

        $totalDistance = 0;
        $timeSpan = $locations->first()->created_at->diffInSeconds($locations->last()->created_at);

        for ($i = 1; $i < $locations->count(); $i++) {
            $current = $locations[$i-1];
            $previous = $locations[$i];

            $distance = $this->calculateDistance(
                $previous->latitude, $previous->longitude,
                $current->latitude, $current->longitude
            );

            $totalDistance += $distance;
        }

        $avgSpeed = $timeSpan > 0 ? ($totalDistance / $timeSpan) * 3.6 : 0; // km/h

        $isStationary = $avgSpeed < 2.0 && $timeSpan > 300; // Less than 2 km/h for more than 5 minutes

        return [
            'is_stationary' => $isStationary,
            'total_distance' => round($totalDistance, 2),
            'time_span_seconds' => $timeSpan,
            'average_speed_kmh' => round($avgSpeed, 2),
            'reason' => $isStationary ? 'stationary_detected' : 'normal_movement'
        ];
    }

    private function analyzeErraticMovement($locations): array
    {
        if ($locations->count() < 3) {
            return ['is_erratic' => false, 'reason' => 'insufficient_data'];
        }

        $erraticCount = 0;
        $totalMeasurements = 0;

        for ($i = 2; $i < $locations->count(); $i++) {
            $current = $locations[$i-2];
            $middle = $locations[$i-1];
            $previous = $locations[$i];

            // Calculate direction changes
            $dir1 = $this->calculateBearing(
                $previous->latitude, $previous->longitude,
                $middle->latitude, $middle->longitude
            );
            
            $dir2 = $this->calculateBearing(
                $middle->latitude, $middle->longitude,
                $current->latitude, $current->longitude
            );

            $directionChange = abs($dir1 - $dir2);
            if ($directionChange > 180) {
                $directionChange = 360 - $directionChange;
            }

            // Large direction changes indicate erratic movement
            if ($directionChange > 90) {
                $erraticCount++;
            }

            $totalMeasurements++;
        }

        $erraticRatio = $totalMeasurements > 0 ? $erraticCount / $totalMeasurements : 0;
        $isErratic = $erraticRatio > 0.5; // More than 50% erratic movements

        return [
            'is_erratic' => $isErratic,
            'erratic_ratio' => round($erraticRatio, 2),
            'erratic_count' => $erraticCount,
            'total_measurements' => $totalMeasurements,
            'reason' => $isErratic ? 'erratic_movement_detected' : 'normal_movement'
        ];
    }

    private function calculateBearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLngRad = deg2rad($lng2 - $lng1);

        $y = sin($deltaLngRad) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLngRad);

        $bearing = atan2($y, $x);
        return fmod(rad2deg($bearing) + 360, 360);
    }

    private function calculateDirectionConsistency(array $directions): float
    {
        if (count($directions) < 2) return 1.0;

        $consistentCount = 0;
        $totalComparisons = 0;

        for ($i = 1; $i < count($directions); $i++) {
            $diff = abs($directions[$i] - $directions[$i-1]);
            if ($diff > 180) {
                $diff = 360 - $diff;
            }

            // Consider consistent if direction change is less than 45 degrees
            if ($diff < 45) {
                $consistentCount++;
            }

            $totalComparisons++;
        }

        return $totalComparisons > 0 ? $consistentCount / $totalComparisons : 1.0;
    }

    private function determineMovementPatternType($movementAnalysis, $stationaryAnalysis, $erraticAnalysis): string
    {
        if ($stationaryAnalysis['is_stationary']) return 'stationary';
        if ($erraticAnalysis['is_erratic']) return 'erratic';
        if ($movementAnalysis['consistent']) return 'consistent_movement';
        return 'irregular_movement';
    }

    private function calculateMovementConfidence($movementAnalysis, $stationaryAnalysis, $erraticAnalysis): float
    {
        $confidence = 0.5;

        if ($movementAnalysis['consistent']) {
            $confidence += 0.3;
        }

        if (!$stationaryAnalysis['is_stationary']) {
            $confidence += 0.1;
        }

        if (!$erraticAnalysis['is_erratic']) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }
}