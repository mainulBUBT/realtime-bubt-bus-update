<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\UserTrackingSession;
use App\Models\DeviceToken;
use App\Services\StoppageCoordinateValidator;
use App\Services\BusTrackingReliabilityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * GPS Location Collection Service
 * Handles comprehensive GPS data collection, validation, and processing
 */
class GPSLocationCollectionService
{
    private StoppageCoordinateValidator $validator;
    private BusTrackingReliabilityService $reliabilityService;
    
    // Configuration constants
    private const BATCH_SIZE = 10;
    private const MAX_ACCURACY_METERS = 100;
    private const MAX_SPEED_KMH = 80;
    private const SESSION_TIMEOUT_MINUTES = 120;
    private const MIN_LOCATIONS_FOR_STATS = 5;

    public function __construct(
        StoppageCoordinateValidator $validator,
        BusTrackingReliabilityService $reliabilityService
    ) {
        $this->validator = $validator;
        $this->reliabilityService = $reliabilityService;
    }

    /**
     * Start a new GPS tracking session
     *
     * @param string $deviceToken Device token
     * @param string $busId Bus identifier
     * @param array $metadata Additional session metadata
     * @return array Session start result
     */
    public function startTrackingSession(string $deviceToken, string $busId, array $metadata = []): array
    {
        try {
            $deviceTokenHash = hash('sha256', $deviceToken);
            
            // Validate device token
            $device = DeviceToken::where('token_hash', $deviceTokenHash)->first();
            if (!$device) {
                return [
                    'success' => false,
                    'message' => 'Invalid device token'
                ];
            }

            // Check for existing active sessions for this device
            $existingSession = UserTrackingSession::where('device_token', $deviceToken)
                ->where('is_active', true)
                ->first();

            if ($existingSession) {
                // End the existing session
                $existingSession->endSession();
                Log::info('Ended existing session for device', [
                    'device_token' => substr($deviceToken, 0, 8) . '...',
                    'old_session_id' => $existingSession->session_id
                ]);
            }

            // Generate unique session ID
            $sessionId = $this->generateSessionId($deviceToken, $busId);

            // Create new tracking session
            $session = UserTrackingSession::create([
                'device_token' => $deviceToken,
                'device_token_hash' => $deviceTokenHash,
                'bus_id' => $busId,
                'session_id' => $sessionId,
                'started_at' => now(),
                'is_active' => true,
                'trust_score_at_start' => $device->trust_score ?? 0.5,
                'session_metadata' => $metadata
            ]);

            // Start reliability tracking session
            $reliabilityResult = $this->reliabilityService->startTrackingSession($deviceToken, $busId);

            Log::info('GPS tracking session started', [
                'session_id' => $sessionId,
                'device_token' => substr($deviceToken, 0, 8) . '...',
                'bus_id' => $busId,
                'trust_score' => $device->trust_score
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'bus_id' => $busId,
                'trust_score' => $device->trust_score,
                'reliability_session' => $reliabilityResult,
                'message' => 'Tracking session started successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to start tracking session', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 8) . '...',
                'bus_id' => $busId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start tracking session'
            ];
        }
    }

    /**
     * Process batch of GPS location data
     *
     * @param array $locationBatch Array of location data
     * @param string $deviceToken Device token
     * @param string $sessionId Session identifier
     * @return array Processing result
     */
    public function processBatchLocationData(array $locationBatch, string $deviceToken, string $sessionId): array
    {
        $results = [
            'success' => true,
            'processed' => 0,
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
            'location_ids' => []
        ];

        try {
            // Validate session
            $session = UserTrackingSession::where('session_id', $sessionId)
                ->where('is_active', true)
                ->first();

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Invalid or inactive tracking session'
                ];
            }

            // Process each location in the batch
            DB::beginTransaction();

            foreach ($locationBatch as $locationData) {
                try {
                    $processResult = $this->processSingleLocation($locationData, $deviceToken, $session);
                    
                    $results['processed']++;
                    
                    if ($processResult['success']) {
                        $results['valid']++;
                        $results['location_ids'][] = $processResult['location_id'];
                    } else {
                        $results['invalid']++;
                        $results['errors'][] = $processResult['message'];
                    }

                } catch (\Exception $e) {
                    $results['invalid']++;
                    $results['errors'][] = 'Location processing failed: ' . $e->getMessage();
                    
                    Log::error('Single location processing failed', [
                        'error' => $e->getMessage(),
                        'session_id' => $sessionId,
                        'location_data' => $locationData
                    ]);
                }
            }

            // Update session statistics
            $this->updateSessionStatistics($session, $results);

            DB::commit();

            // Trigger location aggregation for the bus
            $this->triggerLocationAggregation($session->bus_id);

            Log::info('Location batch processed', [
                'session_id' => $sessionId,
                'processed' => $results['processed'],
                'valid' => $results['valid'],
                'invalid' => $results['invalid']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Batch location processing failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'batch_size' => count($locationBatch)
            ]);

            $results['success'] = false;
            $results['message'] = 'Batch processing failed';
        }

        return $results;
    }

    /**
     * Process a single GPS location
     *
     * @param array $locationData Location data
     * @param string $deviceToken Device token
     * @param UserTrackingSession $session Tracking session
     * @return array Processing result
     */
    private function processSingleLocation(array $locationData, string $deviceToken, UserTrackingSession $session): array
    {
        // Validate required fields
        $requiredFields = ['latitude', 'longitude', 'accuracy', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($locationData[$field])) {
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }

        // Sanitize location data
        $cleanData = [
            'bus_id' => $session->bus_id,
            'device_token' => hash('sha256', $deviceToken),
            'session_id' => $session->session_id,
            'latitude' => (float) $locationData['latitude'],
            'longitude' => (float) $locationData['longitude'],
            'accuracy' => (float) $locationData['accuracy'],
            'speed' => isset($locationData['speed']) ? (float) $locationData['speed'] : null,
            'heading' => isset($locationData['heading']) ? (float) $locationData['heading'] : null,
            'timestamp' => Carbon::createFromTimestamp($locationData['timestamp'] / 1000),
            'collected_at' => now()
        ];

        // Comprehensive validation
        $validationResult = $this->validateLocationData($cleanData);
        
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'message' => 'Location validation failed: ' . implode(', ', $validationResult['errors']),
                'validation' => $validationResult
            ];
        }

        // Calculate reputation weight
        $reputationWeight = $this->calculateLocationReputationWeight($cleanData, $validationResult, $session);
        $cleanData['reputation_weight'] = $reputationWeight;
        $cleanData['is_validated'] = true;

        // Store location data
        $location = BusLocation::create($cleanData);

        return [
            'success' => true,
            'location_id' => $location->id,
            'reputation_weight' => $reputationWeight,
            'validation' => $validationResult
        ];
    }

    /**
     * Comprehensive location data validation
     *
     * @param array $locationData Location data to validate
     * @return array Validation result
     */
    private function validateLocationData(array $locationData): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'confidence' => 1.0,
            'details' => []
        ];

        // Basic coordinate validation
        if (!$this->isValidCoordinate($locationData['latitude'], $locationData['longitude'])) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Invalid GPS coordinates';
            return $validation;
        }

        // Accuracy validation
        if ($locationData['accuracy'] > self::MAX_ACCURACY_METERS) {
            $validation['warnings'][] = "Low GPS accuracy: {$locationData['accuracy']}m";
            $validation['confidence'] *= 0.7;
        }

        // Timestamp validation
        $timeDiff = abs(now()->timestamp - $locationData['timestamp']->timestamp);
        if ($timeDiff > 300) { // More than 5 minutes old
            $validation['warnings'][] = 'Location data is outdated';
            $validation['confidence'] *= 0.8;
        }

        // Speed validation (if previous location exists)
        $speedValidation = $this->validateLocationSpeed($locationData);
        if ($speedValidation) {
            $validation['details']['speed'] = $speedValidation;
            if (!$speedValidation['valid']) {
                $validation['warnings'][] = "Unrealistic speed: {$speedValidation['calculated_speed']} km/h";
                $validation['confidence'] *= 0.5;
            }
        }

        // Route validation
        $routeValidation = $this->validator->validateStoppageRadius(
            $locationData['latitude'],
            $locationData['longitude']
        );
        $validation['details']['route'] = $routeValidation;

        if (!$routeValidation['is_valid']) {
            $validation['warnings'][] = 'Location outside expected route area';
            $validation['confidence'] *= 0.6;
        }

        // Movement consistency validation (skip in testing environment)
        if (!app()->environment('testing')) {
            $movementValidation = $this->reliabilityService->analyzeMovementConsistency($locationData);
            $validation['details']['movement'] = $movementValidation;

            if (!$movementValidation['consistent']) {
                $validation['warnings'][] = 'Inconsistent movement pattern';
                $validation['confidence'] *= 0.7;
            }
        }

        // Set overall validity based on confidence
        if ($validation['confidence'] < 0.3) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Location confidence too low';
        }

        return $validation;
    }

    /**
     * Validate location speed against previous location
     */
    private function validateLocationSpeed(array $locationData): ?array
    {
        $previousLocation = BusLocation::where('device_token', $locationData['device_token'])
            ->where('bus_id', $locationData['bus_id'])
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousLocation) {
            return null;
        }

        $distance = $this->calculateDistance(
            $previousLocation->latitude,
            $previousLocation->longitude,
            $locationData['latitude'],
            $locationData['longitude']
        );

        $timeDiff = $locationData['timestamp']->diffInSeconds($previousLocation->created_at);
        
        if ($timeDiff <= 0) {
            return [
                'valid' => false,
                'reason' => 'Invalid time sequence',
                'calculated_speed' => 0
            ];
        }

        $speedKmh = ($distance / $timeDiff) * 3.6;
        $isValid = $speedKmh <= self::MAX_SPEED_KMH;

        return [
            'valid' => $isValid,
            'calculated_speed' => round($speedKmh, 2),
            'distance_meters' => round($distance, 2),
            'time_seconds' => $timeDiff,
            'max_allowed_speed' => self::MAX_SPEED_KMH
        ];
    }

    /**
     * Calculate reputation weight for location
     */
    private function calculateLocationReputationWeight(array $locationData, array $validation, UserTrackingSession $session): float
    {
        $baseWeight = $session->trust_score_at_start;
        
        // Adjust based on validation confidence
        $weight = $baseWeight * $validation['confidence'];
        
        // Adjust based on GPS accuracy
        $accuracyFactor = min(1.0, 50 / $locationData['accuracy']); // 50m = 1.0, 100m = 0.5
        $weight *= $accuracyFactor;
        
        // Adjust based on session quality
        $sessionQuality = $session->getQualityScore();
        $weight *= (0.5 + ($sessionQuality * 0.5)); // Range: 0.5 to 1.0
        
        return max(0.01, min(1.0, $weight));
    }

    /**
     * Update session statistics
     */
    private function updateSessionStatistics(UserTrackingSession $session, array $results): void
    {
        $session->increment('locations_contributed', $results['processed']);
        $session->increment('valid_locations', $results['valid']);
        
        // Update average accuracy if we have valid locations
        if ($results['valid'] > 0) {
            $avgAccuracy = BusLocation::where('session_id', $session->session_id)
                ->where('is_validated', true)
                ->avg('accuracy');
            
            $session->update(['average_accuracy' => $avgAccuracy]);
        }
    }

    /**
     * Trigger location aggregation for bus
     */
    private function triggerLocationAggregation(string $busId): void
    {
        // This would typically be done via a job queue in production
        try {
            $locationService = app(LocationService::class);
            $aggregatedPosition = $locationService->aggregateLocationData($busId);
            
            if ($aggregatedPosition) {
                // Cache the aggregated position
                Cache::put("bus_position_{$busId}", $aggregatedPosition, now()->addMinutes(5));
            }
        } catch (\Exception $e) {
            Log::warning('Location aggregation failed', [
                'bus_id' => $busId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * End a tracking session
     *
     * @param string $sessionId Session identifier
     * @return array End session result
     */
    public function endTrackingSession(string $sessionId): array
    {
        try {
            $session = UserTrackingSession::where('session_id', $sessionId)
                ->where('is_active', true)
                ->first();

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found or already ended'
                ];
            }

            // Calculate final session statistics
            $finalStats = $this->calculateFinalSessionStats($session);
            
            // End the session
            $session->endSession();
            
            // Update session metadata with final stats
            $session->update([
                'session_metadata' => array_merge(
                    $session->session_metadata ?? [],
                    ['final_stats' => $finalStats]
                )
            ]);

            Log::info('GPS tracking session ended', [
                'session_id' => $sessionId,
                'duration_minutes' => $session->getDurationMinutes(),
                'locations_contributed' => $session->locations_contributed,
                'accuracy_rate' => $session->getAccuracyRate(),
                'quality_score' => $session->getQualityScore()
            ]);

            return [
                'success' => true,
                'session_stats' => $finalStats,
                'message' => 'Session ended successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to end tracking session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to end session'
            ];
        }
    }

    /**
     * Calculate final session statistics
     */
    private function calculateFinalSessionStats(UserTrackingSession $session): array
    {
        $locations = BusLocation::where('session_id', $session->session_id)->get();
        
        $stats = [
            'duration_minutes' => $session->getDurationMinutes(),
            'locations_contributed' => $session->locations_contributed,
            'valid_locations' => $session->valid_locations,
            'accuracy_rate' => $session->getAccuracyRate(),
            'quality_score' => $session->getQualityScore(),
            'average_accuracy' => $session->average_accuracy,
            'total_distance' => $this->calculateTotalDistance($locations),
            'average_speed' => $this->calculateAverageSpeed($locations),
            'route_adherence' => $this->calculateRouteAdherence($locations)
        ];

        return $stats;
    }

    /**
     * Calculate total distance covered in session
     */
    private function calculateTotalDistance($locations): float
    {
        if ($locations->count() < 2) {
            return 0.0;
        }

        $totalDistance = 0;
        $previousLocation = null;

        foreach ($locations as $location) {
            if ($previousLocation) {
                $distance = $this->calculateDistance(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );
                $totalDistance += $distance;
            }
            $previousLocation = $location;
        }

        return round($totalDistance, 2);
    }

    /**
     * Calculate average speed for session
     */
    private function calculateAverageSpeed($locations): float
    {
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

        return empty($speeds) ? 0.0 : round(array_sum($speeds) / count($speeds), 2);
    }

    /**
     * Calculate route adherence percentage
     */
    private function calculateRouteAdherence($locations): float
    {
        if ($locations->isEmpty()) {
            return 0.0;
        }

        $validRouteLocations = 0;
        
        foreach ($locations as $location) {
            $validation = $this->validator->validateStoppageRadius(
                $location->latitude,
                $location->longitude
            );
            
            if ($validation['is_valid']) {
                $validRouteLocations++;
            }
        }

        return round(($validRouteLocations / $locations->count()) * 100, 2);
    }

    /**
     * Utility methods
     */
    private function generateSessionId(string $deviceToken, string $busId): string
    {
        $timestamp = now()->timestamp;
        $random = bin2hex(random_bytes(4));
        $devicePrefix = substr(hash('sha256', $deviceToken), 0, 8);
        
        return "{$devicePrefix}_{$busId}_{$timestamp}_{$random}";
    }

    private function isValidCoordinate(float $lat, float $lng): bool
    {
        // Basic coordinate bounds
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }
        
        // Check for obviously invalid coordinates
        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }
        
        // Check if within Bangladesh bounds
        if ($lat < 20.5 || $lat > 26.5 || $lng < 88.0 || $lng > 92.7) {
            return false;
        }
        
        return true;
    }

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
     * Get collection statistics for monitoring
     */
    public function getCollectionStatistics(): array
    {
        return [
            'active_sessions' => UserTrackingSession::where('is_active', true)->count(),
            'locations_today' => BusLocation::whereDate('created_at', today())->count(),
            'valid_locations_today' => BusLocation::whereDate('created_at', today())
                ->where('is_validated', true)->count(),
            'average_accuracy_today' => BusLocation::whereDate('created_at', today())
                ->avg('accuracy'),
            'sessions_today' => UserTrackingSession::whereDate('started_at', today())->count(),
            'high_quality_sessions_today' => UserTrackingSession::whereDate('started_at', today())
                ->get()
                ->filter(function ($session) {
                    return $session->getQualityScore() > 0.7;
                })
                ->count()
        ];
    }

    /**
     * Clean up old data
     */
    public function cleanupOldData(): array
    {
        $sessionsCleanedUp = UserTrackingSession::cleanupOldSessions();
        $locationsCleanedUp = BusLocation::where('created_at', '<', now()->subDays(7))->delete();
        
        return [
            'sessions_cleaned' => $sessionsCleanedUp,
            'locations_cleaned' => $locationsCleanedUp
        ];
    }
}