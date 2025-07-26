<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Services\StoppageCoordinateValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Advanced Bus Tracking Reliability System
 * Handles movement consistency, user clustering, device trust scoring, and smart broadcasting
 */
class BusTrackingReliabilityService
{
    private StoppageCoordinateValidator $validator;
    
    // Configuration constants
    private const CLUSTERING_RADIUS_METERS = 25; // Group users within 25 meters
    private const MIN_MOVEMENT_SPEED_KMH = 5; // Minimum speed to consider as moving
    private const MAX_STATIONARY_TIME_MINUTES = 10; // Max time to stay stationary
    private const TRUST_SCORE_THRESHOLD = 0.7; // Minimum trust score for trusted users
    private const OUTLIER_DISTANCE_THRESHOLD = 100; // Distance to consider as outlier
    private const MOVEMENT_CONSISTENCY_WINDOW = 5; // Minutes to analyze movement consistency
    private const AUTO_DEACTIVATION_TIMEOUT = 15; // Minutes of inactivity before deactivation

    public function __construct(StoppageCoordinateValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate location source - only accept GPS when user actively tracking
     *
     * @param array $locationData
     * @return array Validation result
     */
    public function validateLocationSource(array $locationData): array
    {
        $deviceToken = $locationData['device_token'];
        $busId = $locationData['bus_id'];
        
        // Check if user has active tracking session
        $activeSession = $this->getActiveTrackingSession($deviceToken, $busId);
        
        if (!$activeSession) {
            return [
                'valid' => false,
                'reason' => 'no_active_session',
                'message' => 'No active tracking session found. User must click "I\'m on this bus" first.',
                'requires_user_action' => true
            ];
        }

        // Check if session is still within timeout
        $sessionAge = now()->diffInMinutes($activeSession['started_at']);
        if ($sessionAge > self::AUTO_DEACTIVATION_TIMEOUT) {
            $this->deactivateTrackingSession($deviceToken, $busId);
            return [
                'valid' => false,
                'reason' => 'session_expired',
                'message' => 'Tracking session expired due to inactivity.',
                'session_age_minutes' => $sessionAge,
                'requires_user_action' => true
            ];
        }

        // Update session last activity
        $this->updateSessionActivity($deviceToken, $busId);

        return [
            'valid' => true,
            'session' => $activeSession,
            'message' => 'Valid active tracking session'
        ];
    }

    /**
     * Track movement consistency (speed + direction validation)
     *
     * @param array $locationData
     * @return array Movement analysis result
     */
    public function analyzeMovementConsistency(array $locationData): array
    {
        $deviceToken = $locationData['device_token'];
        $currentLat = $locationData['latitude'];
        $currentLng = $locationData['longitude'];
        $currentTime = isset($locationData['timestamp']) && is_numeric($locationData['timestamp']) 
            ? Carbon::createFromTimestamp($locationData['timestamp'] / 1000)
            : now();

        // Get recent location history for this device
        $recentLocations = BusLocation::where('device_token', hash('sha256', $deviceToken))
            ->where('created_at', '>', $currentTime->copy()->subMinutes(self::MOVEMENT_CONSISTENCY_WINDOW))
            ->orderBy('created_at', 'asc')
            ->get();

        if ($recentLocations->count() < 2) {
            return [
                'consistent' => true,
                'reason' => 'insufficient_data',
                'message' => 'Not enough location history for consistency analysis',
                'movement_pattern' => 'unknown'
            ];
        }

        // Analyze movement patterns
        $movements = $this->calculateMovements($recentLocations, $currentLat, $currentLng, $currentTime);
        
        // Check for bus-like movement patterns
        $movementAnalysis = $this->analyzeMovementPattern($movements);
        
        // Validate speed consistency
        $speedConsistency = $this->validateSpeedConsistency($movements);
        
        // Validate direction consistency
        $directionConsistency = $this->validateDirectionConsistency($movements);

        $isConsistent = $movementAnalysis['is_bus_like'] && 
                       $speedConsistency['consistent'] && 
                       $directionConsistency['consistent'];

        return [
            'consistent' => $isConsistent,
            'movement_analysis' => $movementAnalysis,
            'speed_consistency' => $speedConsistency,
            'direction_consistency' => $directionConsistency,
            'confidence_score' => $this->calculateMovementConfidence($movementAnalysis, $speedConsistency, $directionConsistency)
        ];
    }

    /**
     * Implement user clustering logic to group users within radius
     *
     * @param string $busId
     * @return array Clustering result
     */
    public function clusterUsers(string $busId): array
    {
        // Get recent locations for this bus
        $recentLocations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(2))
            ->where('is_validated', true)
            ->get();

        if ($recentLocations->isEmpty()) {
            return [
                'clusters' => [],
                'outliers' => [],
                'main_cluster' => null,
                'total_users' => 0
            ];
        }

        // Group locations by clustering algorithm
        $clusters = $this->performClustering($recentLocations);
        
        // Identify outliers
        $outliers = $this->identifyOutliers($recentLocations, $clusters);
        
        // Find main cluster (highest trust score sum)
        $mainCluster = $this->findMainCluster($clusters);

        return [
            'clusters' => $clusters,
            'outliers' => $outliers,
            'main_cluster' => $mainCluster,
            'total_users' => $recentLocations->count(),
            'clustered_users' => $clusters->flatten()->count(),
            'outlier_users' => $outliers->count()
        ];
    }

    /**
     * Calculate device trust score based on historical behavior
     *
     * @param string $deviceToken
     * @return array Trust score analysis
     */
    public function calculateDeviceTrustScore(string $deviceToken): array
    {
        $hashedToken = hash('sha256', $deviceToken);
        
        // Get or create device record
        $device = DeviceToken::firstOrCreate(
            ['token_hash' => $hashedToken],
            [
                'reputation_score' => 0.5,
                'trust_score' => 0.5,
                'total_contributions' => 0,
                'accurate_contributions' => 0
            ]
        );

        // Analyze recent behavior (last 7 days)
        $recentBehavior = $this->analyzeRecentBehavior($hashedToken);
        
        // Calculate trust factors
        $trustFactors = [
            'frequency_score' => $this->calculateFrequencyScore($recentBehavior),
            'consistency_score' => $this->calculateConsistencyScore($recentBehavior),
            'accuracy_score' => $this->calculateAccuracyScore($recentBehavior),
            'clustering_score' => $this->calculateClusteringScore($hashedToken),
            'historical_score' => $device->trust_score ?? 0.5
        ];

        // Calculate weighted trust score
        $newTrustScore = $this->calculateWeightedTrustScore($trustFactors);
        
        // Update device trust score
        $device->update([
            'trust_score' => $newTrustScore,
            'last_activity' => now()
        ]);

        return [
            'trust_score' => $newTrustScore,
            'is_trusted' => $newTrustScore >= self::TRUST_SCORE_THRESHOLD,
            'trust_factors' => $trustFactors,
            'device_stats' => [
                'total_contributions' => $device->total_contributions,
                'accurate_contributions' => $device->accurate_contributions,
                'accuracy_rate' => $device->total_contributions > 0 ? 
                    ($device->accurate_contributions / $device->total_contributions) : 0
            ]
        ];
    }

    /**
     * Auto-deactivate static or off-route GPS data
     *
     * @param string $busId
     * @return array Deactivation results
     */
    public function autoDeactivateInvalidSources(string $busId): array
    {
        $deactivated = [];
        
        // Find devices with static GPS data
        $staticDevices = $this->findStaticDevices($busId);
        foreach ($staticDevices as $deviceToken) {
            $this->deactivateTrackingSession($deviceToken, $busId);
            $deactivated[] = [
                'device' => substr($deviceToken, 0, 8) . '...',
                'reason' => 'static_gps',
                'message' => 'GPS data has been static for too long'
            ];
        }

        // Find devices consistently off-route
        $offRouteDevices = $this->findOffRouteDevices($busId);
        foreach ($offRouteDevices as $deviceToken) {
            $this->deactivateTrackingSession($deviceToken, $busId);
            $deactivated[] = [
                'device' => substr($deviceToken, 0, 8) . '...',
                'reason' => 'off_route',
                'message' => 'GPS data consistently off expected route'
            ];
        }

        // Find inactive sessions
        $inactiveSessions = $this->findInactiveSessions($busId);
        foreach ($inactiveSessions as $deviceToken) {
            $this->deactivateTrackingSession($deviceToken, $busId);
            $deactivated[] = [
                'device' => substr($deviceToken, 0, 8) . '...',
                'reason' => 'inactive',
                'message' => 'No GPS data received within timeout period'
            ];
        }

        Log::info('Auto-deactivated tracking sessions', [
            'bus_id' => $busId,
            'deactivated_count' => count($deactivated),
            'deactivated' => $deactivated
        ]);

        return [
            'deactivated_count' => count($deactivated),
            'deactivated_sessions' => $deactivated
        ];
    }

    /**
     * Smart broadcasting using averaged GPS from trusted users only
     *
     * @param string $busId
     * @return array|null Broadcast data or null if no trusted data
     */
    public function generateSmartBroadcast(string $busId): ?array
    {
        // Get user clustering data
        $clusteringResult = $this->clusterUsers($busId);
        
        if (!$clusteringResult['main_cluster']) {
            return $this->generateFallbackBroadcast($busId);
        }

        $mainCluster = $clusteringResult['main_cluster'];
        
        // Filter for trusted users only
        $trustedLocations = $mainCluster->filter(function ($location) {
            $trustScore = $this->getDeviceTrustScore($location->device_token);
            return $trustScore >= self::TRUST_SCORE_THRESHOLD;
        });

        if ($trustedLocations->isEmpty()) {
            return $this->generateFallbackBroadcast($busId);
        }

        // Calculate weighted average from trusted users
        $totalWeight = $trustedLocations->sum('reputation_weight');
        $weightedLat = $trustedLocations->sum(function ($location) {
            return $location->latitude * $location->reputation_weight;
        }) / $totalWeight;
        
        $weightedLng = $trustedLocations->sum(function ($location) {
            return $location->longitude * $location->reputation_weight;
        }) / $totalWeight;

        // Calculate broadcast confidence
        $confidence = $this->calculateBroadcastConfidence($trustedLocations, $clusteringResult);

        return [
            'bus_id' => $busId,
            'latitude' => round($weightedLat, 8),
            'longitude' => round($weightedLng, 8),
            'confidence' => $confidence,
            'trusted_users' => $trustedLocations->count(),
            'total_users' => $clusteringResult['total_users'],
            'data_quality' => 'trusted_users_only',
            'last_updated' => now(),
            'broadcast_type' => 'smart'
        ];
    }

    /**
     * Generate fallback broadcast for "No active tracking" scenarios
     *
     * @param string $busId
     * @return array|null Fallback broadcast data
     */
    public function generateFallbackBroadcast(string $busId): ?array
    {
        // Try to get last known good location
        $lastKnownLocation = $this->getLastKnownLocation($busId);
        
        if (!$lastKnownLocation) {
            return null;
        }

        $timeSinceLastUpdate = now()->diffInMinutes($lastKnownLocation['timestamp']);

        return [
            'bus_id' => $busId,
            'latitude' => $lastKnownLocation['latitude'],
            'longitude' => $lastKnownLocation['longitude'],
            'confidence' => max(0.1, 1.0 - ($timeSinceLastUpdate / 60)), // Decay over time
            'trusted_users' => 0,
            'total_users' => 0,
            'data_quality' => 'last_known_location',
            'last_updated' => $lastKnownLocation['timestamp'],
            'minutes_since_update' => $timeSinceLastUpdate,
            'broadcast_type' => 'fallback'
        ];
    }

    /**
     * Get active tracking session for device and bus
     */
    private function getActiveTrackingSession(string $deviceToken, string $busId): ?array
    {
        $sessionKey = "tracking_session:{$deviceToken}:{$busId}";
        return Cache::get($sessionKey);
    }

    /**
     * Update session activity timestamp
     */
    private function updateSessionActivity(string $deviceToken, string $busId): void
    {
        $sessionKey = "tracking_session:{$deviceToken}:{$busId}";
        $session = Cache::get($sessionKey);
        
        if ($session) {
            $session['last_activity'] = now();
            Cache::put($sessionKey, $session, now()->addMinutes(self::AUTO_DEACTIVATION_TIMEOUT));
        }
    }

    /**
     * Deactivate tracking session
     */
    private function deactivateTrackingSession(string $deviceToken, string $busId): void
    {
        $sessionKey = "tracking_session:{$deviceToken}:{$busId}";
        Cache::forget($sessionKey);
        
        Log::info('Tracking session deactivated', [
            'device_token' => substr($deviceToken, 0, 8) . '...',
            'bus_id' => $busId
        ]);
    }

    /**
     * Calculate movements from location history
     */
    private function calculateMovements(Collection $locations, float $currentLat, float $currentLng, Carbon $currentTime): array
    {
        $movements = [];
        $previousLocation = null;

        // Add current location to the sequence
        $allLocations = $locations->push((object)[
            'latitude' => $currentLat,
            'longitude' => $currentLng,
            'created_at' => $currentTime
        ]);

        foreach ($allLocations as $location) {
            if ($previousLocation) {
                $distance = $this->calculateDistance(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );

                $timeDiff = $location->created_at->diffInSeconds($previousLocation->created_at);
                $speed = $timeDiff > 0 ? ($distance / $timeDiff) * 3.6 : 0; // km/h

                $bearing = $this->calculateBearing(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );

                $movements[] = [
                    'distance' => $distance,
                    'time_seconds' => $timeDiff,
                    'speed_kmh' => $speed,
                    'bearing' => $bearing,
                    'timestamp' => $location->created_at
                ];
            }
            $previousLocation = $location;
        }

        return $movements;
    }

    /**
     * Analyze movement pattern to determine if it's bus-like
     */
    private function analyzeMovementPattern(array $movements): array
    {
        if (empty($movements)) {
            return [
                'is_bus_like' => false,
                'pattern' => 'no_movement_data',
                'confidence' => 0
            ];
        }

        $speeds = array_column($movements, 'speed_kmh');
        $avgSpeed = array_sum($speeds) / count($speeds);
        $maxSpeed = max($speeds);
        $minSpeed = min($speeds);

        // Check for stationary periods (bus stops)
        $stationaryCount = count(array_filter($speeds, fn($speed) => $speed < self::MIN_MOVEMENT_SPEED_KMH));
        $movingCount = count($speeds) - $stationaryCount;

        // Analyze pattern
        $isBusLike = true;
        $pattern = 'bus_like';
        $confidence = 0.8;

        // Too fast for a bus
        if ($maxSpeed > 60) {
            $isBusLike = false;
            $pattern = 'too_fast';
            $confidence = 0.2;
        }
        // Always stationary (not moving)
        elseif ($stationaryCount == count($speeds)) {
            $isBusLike = false;
            $pattern = 'stationary';
            $confidence = 0.1;
        }
        // Walking speed pattern
        elseif ($avgSpeed < 8 && $maxSpeed < 15) {
            $isBusLike = false;
            $pattern = 'walking';
            $confidence = 0.3;
        }
        // Good bus-like pattern with stops and movement
        elseif ($movingCount > 0 && $stationaryCount > 0 && $avgSpeed >= 8 && $avgSpeed <= 40) {
            $pattern = 'bus_with_stops';
            $confidence = 0.9;
        }

        return [
            'is_bus_like' => $isBusLike,
            'pattern' => $pattern,
            'confidence' => $confidence,
            'avg_speed' => round($avgSpeed, 2),
            'max_speed' => round($maxSpeed, 2),
            'stationary_periods' => $stationaryCount,
            'moving_periods' => $movingCount
        ];
    }

    /**
     * Validate speed consistency
     */
    private function validateSpeedConsistency(array $movements): array
    {
        if (count($movements) < 2) {
            return ['consistent' => true, 'reason' => 'insufficient_data'];
        }

        $speeds = array_column($movements, 'speed_kmh');
        $avgSpeed = array_sum($speeds) / count($speeds);
        $speedVariance = $this->calculateVariance($speeds);

        // Check for unrealistic speed jumps
        $maxSpeedJump = 0;
        for ($i = 1; $i < count($speeds); $i++) {
            $speedJump = abs($speeds[$i] - $speeds[$i - 1]);
            $maxSpeedJump = max($maxSpeedJump, $speedJump);
        }

        $isConsistent = $speedVariance < 400 && $maxSpeedJump < 30; // Reasonable thresholds

        return [
            'consistent' => $isConsistent,
            'avg_speed' => round($avgSpeed, 2),
            'speed_variance' => round($speedVariance, 2),
            'max_speed_jump' => round($maxSpeedJump, 2)
        ];
    }

    /**
     * Validate direction consistency
     */
    private function validateDirectionConsistency(array $movements): array
    {
        if (count($movements) < 3) {
            return ['consistent' => true, 'reason' => 'insufficient_data'];
        }

        $bearings = array_column($movements, 'bearing');
        $directionChanges = [];

        for ($i = 1; $i < count($bearings); $i++) {
            $change = abs($bearings[$i] - $bearings[$i - 1]);
            // Handle circular nature of bearings
            if ($change > 180) {
                $change = 360 - $change;
            }
            $directionChanges[] = $change;
        }

        $avgDirectionChange = array_sum($directionChanges) / count($directionChanges);
        $maxDirectionChange = max($directionChanges);

        // Buses should have relatively consistent direction with some turns
        $isConsistent = $avgDirectionChange < 45 && $maxDirectionChange < 120;

        return [
            'consistent' => $isConsistent,
            'avg_direction_change' => round($avgDirectionChange, 2),
            'max_direction_change' => round($maxDirectionChange, 2)
        ];
    }

    /**
     * Calculate movement confidence score
     */
    private function calculateMovementConfidence(array $movementAnalysis, array $speedConsistency, array $directionConsistency): float
    {
        $confidence = 0.0;

        if ($movementAnalysis['is_bus_like']) {
            $confidence += $movementAnalysis['confidence'] * 0.5;
        }

        if ($speedConsistency['consistent']) {
            $confidence += 0.25;
        }

        if ($directionConsistency['consistent']) {
            $confidence += 0.25;
        }

        return min(1.0, $confidence);
    }

    /**
     * Perform clustering algorithm on locations
     */
    private function performClustering(Collection $locations): Collection
    {
        $clusters = collect();
        
        foreach ($locations as $location) {
            $addedToCluster = false;
            
            // Try to add to existing cluster
            foreach ($clusters as $cluster) {
                $clusterCenter = $this->calculateClusterCenter($cluster);
                $distance = $this->calculateDistance(
                    $location->latitude,
                    $location->longitude,
                    $clusterCenter['lat'],
                    $clusterCenter['lng']
                );
                
                if ($distance <= self::CLUSTERING_RADIUS_METERS) {
                    $cluster->push($location);
                    $addedToCluster = true;
                    break;
                }
            }
            
            // Create new cluster if not added to existing one
            if (!$addedToCluster) {
                $clusters->push(collect([$location]));
            }
        }
        
        return $clusters;
    }

    /**
     * Calculate cluster center
     */
    private function calculateClusterCenter(Collection $cluster): array
    {
        return [
            'lat' => $cluster->avg('latitude'),
            'lng' => $cluster->avg('longitude')
        ];
    }

    /**
     * Identify outliers from clusters
     */
    private function identifyOutliers(Collection $locations, Collection $clusters): Collection
    {
        $outliers = collect();
        $clusteredLocations = $clusters->flatten();
        
        foreach ($locations as $location) {
            if (!$clusteredLocations->contains('id', $location->id)) {
                $outliers->push($location);
            }
        }
        
        return $outliers;
    }

    /**
     * Find main cluster with highest trust score
     */
    private function findMainCluster(Collection $clusters): ?Collection
    {
        if ($clusters->isEmpty()) {
            return null;
        }

        $bestCluster = null;
        $highestTrustSum = 0;

        foreach ($clusters as $cluster) {
            $trustSum = $cluster->sum(function ($location) {
                return $this->getDeviceTrustScore($location->device_token);
            });
            
            if ($trustSum > $highestTrustSum) {
                $highestTrustSum = $trustSum;
                $bestCluster = $cluster;
            }
        }

        return $bestCluster;
    }

    /**
     * Get device trust score from cache or database
     */
    private function getDeviceTrustScore(string $hashedToken): float
    {
        $cacheKey = "device_trust:{$hashedToken}";
        
        return Cache::remember($cacheKey, 300, function () use ($hashedToken) {
            $device = DeviceToken::where('token_hash', $hashedToken)->first();
            return $device ? $device->trust_score : 0.5;
        });
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
     * Calculate bearing between two points
     */
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

    /**
     * Calculate variance of an array
     */
    private function calculateVariance(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($value) => pow($value - $mean, 2), $values);
        return array_sum($squaredDiffs) / count($squaredDiffs);
    }

    /**
     * Analyze recent behavior for trust calculation
     */
    private function analyzeRecentBehavior(string $hashedToken): array
    {
        $recentLocations = BusLocation::where('device_token', $hashedToken)
            ->where('created_at', '>', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSubmissions = $recentLocations->count();
        $validSubmissions = $recentLocations->where('is_validated', true)->count();
        $highReputationSubmissions = $recentLocations->where('reputation_weight', '>', 0.7)->count();

        // Analyze submission frequency
        $submissionDays = $recentLocations->groupBy(function ($location) {
            return $location->created_at->format('Y-m-d');
        })->count();

        // Analyze consistency with other users
        $consistencyScore = $this->calculateLocationConsistency($recentLocations);

        return [
            'total_submissions' => $totalSubmissions,
            'valid_submissions' => $validSubmissions,
            'high_reputation_submissions' => $highReputationSubmissions,
            'submission_days' => $submissionDays,
            'consistency_score' => $consistencyScore,
            'accuracy_rate' => $totalSubmissions > 0 ? ($validSubmissions / $totalSubmissions) : 0
        ];
    }

    /**
     * Calculate frequency score based on submission patterns
     */
    private function calculateFrequencyScore(array $behavior): float
    {
        $totalSubmissions = $behavior['total_submissions'];
        $submissionDays = $behavior['submission_days'];

        // Reward consistent daily usage
        $frequencyScore = 0.0;

        if ($submissionDays >= 5) {
            $frequencyScore += 0.3; // Regular user bonus
        } elseif ($submissionDays >= 3) {
            $frequencyScore += 0.2; // Occasional user
        } elseif ($submissionDays >= 1) {
            $frequencyScore += 0.1; // New user
        }

        // Reward reasonable submission volume (not too few, not spam)
        $avgSubmissionsPerDay = $submissionDays > 0 ? ($totalSubmissions / $submissionDays) : 0;
        
        if ($avgSubmissionsPerDay >= 5 && $avgSubmissionsPerDay <= 50) {
            $frequencyScore += 0.2; // Good submission rate
        } elseif ($avgSubmissionsPerDay > 50) {
            $frequencyScore -= 0.1; // Potential spam
        }

        return min(1.0, max(0.0, $frequencyScore));
    }

    /**
     * Calculate consistency score based on validation success
     */
    private function calculateConsistencyScore(array $behavior): float
    {
        $accuracyRate = $behavior['accuracy_rate'];
        $consistencyScore = $behavior['consistency_score'];

        // Base score from accuracy
        $score = $accuracyRate * 0.6;

        // Add consistency with other users
        $score += $consistencyScore * 0.4;

        return min(1.0, max(0.0, $score));
    }

    /**
     * Calculate accuracy score based on validation results
     */
    private function calculateAccuracyScore(array $behavior): float
    {
        $totalSubmissions = $behavior['total_submissions'];
        $highReputationSubmissions = $behavior['high_reputation_submissions'];

        if ($totalSubmissions == 0) {
            return 0.5; // Neutral for new users
        }

        $highReputationRate = $highReputationSubmissions / $totalSubmissions;
        
        // Scale the rate to a score
        return min(1.0, $highReputationRate * 1.2); // Slight bonus for high reputation
    }

    /**
     * Calculate clustering score based on proximity to other users
     */
    private function calculateClusteringScore(string $hashedToken): float
    {
        // Get recent locations for this device
        $deviceLocations = BusLocation::where('device_token', $hashedToken)
            ->where('created_at', '>', now()->subHours(24))
            ->get();

        if ($deviceLocations->isEmpty()) {
            return 0.5; // Neutral for no recent data
        }

        $clusteringScores = [];

        foreach ($deviceLocations as $location) {
            // Find other users' locations at similar times
            $nearbyLocations = BusLocation::where('bus_id', $location->bus_id)
                ->where('device_token', '!=', $hashedToken)
                ->whereBetween('created_at', [
                    $location->created_at->subMinutes(2),
                    $location->created_at->addMinutes(2)
                ])
                ->get();

            if ($nearbyLocations->isEmpty()) {
                $clusteringScores[] = 0.3; // Alone, but not necessarily bad
                continue;
            }

            // Calculate how well this location clusters with others
            $nearbyCount = 0;
            foreach ($nearbyLocations as $nearbyLocation) {
                $distance = $this->calculateDistance(
                    $location->latitude,
                    $location->longitude,
                    $nearbyLocation->latitude,
                    $nearbyLocation->longitude
                );

                if ($distance <= self::CLUSTERING_RADIUS_METERS) {
                    $nearbyCount++;
                }
            }

            // Score based on clustering with others
            $clusterScore = min(1.0, $nearbyCount / 3); // 3+ nearby users = perfect score
            $clusteringScores[] = $clusterScore;
        }

        return array_sum($clusteringScores) / count($clusteringScores);
    }

    /**
     * Calculate weighted trust score from all factors
     */
    private function calculateWeightedTrustScore(array $trustFactors): float
    {
        $weights = [
            'frequency_score' => 0.15,
            'consistency_score' => 0.25,
            'accuracy_score' => 0.25,
            'clustering_score' => 0.20,
            'historical_score' => 0.15
        ];

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($trustFactors as $factor => $score) {
            if (isset($weights[$factor])) {
                $weightedSum += $score * $weights[$factor];
                $totalWeight += $weights[$factor];
            }
        }

        $newScore = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0.5;

        // Apply gradual change to prevent dramatic score swings
        $historicalScore = $trustFactors['historical_score'];
        $maxChange = 0.1; // Maximum change per calculation
        
        if (abs($newScore - $historicalScore) > $maxChange) {
            $newScore = $historicalScore + ($newScore > $historicalScore ? $maxChange : -$maxChange);
        }

        return min(1.0, max(0.0, $newScore));
    }

    /**
     * Calculate location consistency with other users
     */
    private function calculateLocationConsistency(Collection $locations): float
    {
        if ($locations->isEmpty()) {
            return 0.5;
        }

        $consistencyScores = [];

        foreach ($locations as $location) {
            // Find other users' locations for the same bus at similar times
            $otherLocations = BusLocation::where('bus_id', $location->bus_id)
                ->where('device_token', '!=', $location->device_token)
                ->whereBetween('created_at', [
                    $location->created_at->subMinutes(1),
                    $location->created_at->addMinutes(1)
                ])
                ->get();

            if ($otherLocations->isEmpty()) {
                $consistencyScores[] = 0.5; // Neutral when alone
                continue;
            }

            // Calculate average distance to other users
            $distances = [];
            foreach ($otherLocations as $otherLocation) {
                $distance = $this->calculateDistance(
                    $location->latitude,
                    $location->longitude,
                    $otherLocation->latitude,
                    $otherLocation->longitude
                );
                $distances[] = $distance;
            }

            $avgDistance = array_sum($distances) / count($distances);
            
            // Score based on proximity to others (closer = more consistent)
            if ($avgDistance <= 25) {
                $consistencyScores[] = 1.0; // Very close to others
            } elseif ($avgDistance <= 50) {
                $consistencyScores[] = 0.8; // Reasonably close
            } elseif ($avgDistance <= 100) {
                $consistencyScores[] = 0.6; // Somewhat close
            } elseif ($avgDistance <= 200) {
                $consistencyScores[] = 0.4; // Far but possible
            } else {
                $consistencyScores[] = 0.1; // Very far (likely outlier)
            }
        }

        return array_sum($consistencyScores) / count($consistencyScores);
    }

    /**
     * Find devices with static GPS data
     */
    private function findStaticDevices(string $busId): array
    {
        $staticDevices = [];
        
        // Get active tracking sessions
        $activeSessions = $this->getActiveTrackingSessions($busId);
        
        foreach ($activeSessions as $deviceToken) {
            $hashedToken = hash('sha256', $deviceToken);
            
            // Get recent locations for this device
            $recentLocations = BusLocation::where('device_token', $hashedToken)
                ->where('bus_id', $busId)
                ->where('created_at', '>', now()->subMinutes(self::MAX_STATIONARY_TIME_MINUTES))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentLocations->count() < 3) {
                continue; // Not enough data
            }

            // Check if all recent locations are very close (static)
            $isStatic = true;
            $firstLocation = $recentLocations->first();
            
            foreach ($recentLocations as $location) {
                $distance = $this->calculateDistance(
                    $firstLocation->latitude,
                    $firstLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );
                
                if ($distance > 10) { // 10 meters tolerance
                    $isStatic = false;
                    break;
                }
            }

            if ($isStatic) {
                $staticDevices[] = $deviceToken;
            }
        }

        return $staticDevices;
    }

    /**
     * Find devices consistently off-route
     */
    private function findOffRouteDevices(string $busId): array
    {
        $offRouteDevices = [];
        
        // Get active tracking sessions
        $activeSessions = $this->getActiveTrackingSessions($busId);
        
        foreach ($activeSessions as $deviceToken) {
            $hashedToken = hash('sha256', $deviceToken);
            
            // Get recent locations for this device
            $recentLocations = BusLocation::where('device_token', $hashedToken)
                ->where('bus_id', $busId)
                ->where('created_at', '>', now()->subMinutes(10))
                ->get();

            if ($recentLocations->count() < 3) {
                continue; // Not enough data
            }

            // Check how many locations are off-route
            $offRouteCount = 0;
            foreach ($recentLocations as $location) {
                $routeValidation = $this->validator->validateAgainstBusRoute(
                    $location->latitude,
                    $location->longitude,
                    $busId
                );
                
                if (!$routeValidation['is_valid']) {
                    $offRouteCount++;
                }
            }

            // If more than 70% of locations are off-route, flag device
            $offRouteRate = $offRouteCount / $recentLocations->count();
            if ($offRouteRate > 0.7) {
                $offRouteDevices[] = $deviceToken;
            }
        }

        return $offRouteDevices;
    }

    /**
     * Find inactive tracking sessions
     */
    private function findInactiveSessions(string $busId): array
    {
        $inactiveDevices = [];
        
        // Get all tracking sessions for this bus
        $sessionPattern = "tracking_session:*:{$busId}";
        $sessionKeys = Cache::getRedis()->keys($sessionPattern);
        
        foreach ($sessionKeys as $sessionKey) {
            $session = Cache::get($sessionKey);
            if (!$session) {
                continue;
            }

            $lastActivity = Carbon::parse($session['last_activity']);
            $inactiveMinutes = now()->diffInMinutes($lastActivity);
            
            if ($inactiveMinutes > self::AUTO_DEACTIVATION_TIMEOUT) {
                // Extract device token from session key
                $keyParts = explode(':', $sessionKey);
                if (count($keyParts) >= 3) {
                    $inactiveDevices[] = $keyParts[1]; // device token
                }
            }
        }

        return $inactiveDevices;
    }

    /**
     * Get active tracking sessions for a bus
     */
    private function getActiveTrackingSessions(string $busId): array
    {
        $activeSessions = [];
        
        // Get all tracking sessions for this bus
        $sessionPattern = "tracking_session:*:{$busId}";
        $sessionKeys = Cache::getRedis()->keys($sessionPattern);
        
        foreach ($sessionKeys as $sessionKey) {
            $session = Cache::get($sessionKey);
            if ($session) {
                // Extract device token from session key
                $keyParts = explode(':', $sessionKey);
                if (count($keyParts) >= 3) {
                    $activeSessions[] = $keyParts[1]; // device token
                }
            }
        }

        return $activeSessions;
    }

    /**
     * Calculate broadcast confidence based on trusted locations and clustering
     */
    private function calculateBroadcastConfidence(Collection $trustedLocations, array $clusteringResult): float
    {
        $baseConfidence = 0.5;
        
        // Factor 1: Number of trusted users
        $trustedCount = $trustedLocations->count();
        if ($trustedCount >= 3) {
            $baseConfidence += 0.3;
        } elseif ($trustedCount >= 2) {
            $baseConfidence += 0.2;
        } elseif ($trustedCount >= 1) {
            $baseConfidence += 0.1;
        }

        // Factor 2: GPS accuracy
        $avgAccuracy = $trustedLocations->avg('accuracy');
        if ($avgAccuracy <= 20) {
            $baseConfidence += 0.2;
        } elseif ($avgAccuracy <= 50) {
            $baseConfidence += 0.1;
        }

        // Factor 3: Clustering quality
        $totalUsers = $clusteringResult['total_users'];
        $clusteredUsers = $clusteringResult['clustered_users'];
        
        if ($totalUsers > 0) {
            $clusteringRatio = $clusteredUsers / $totalUsers;
            $baseConfidence += $clusteringRatio * 0.2;
        }

        // Factor 4: Reputation weights
        $avgReputationWeight = $trustedLocations->avg('reputation_weight');
        $baseConfidence += ($avgReputationWeight - 0.5) * 0.2;

        return min(1.0, max(0.1, $baseConfidence));
    }

    /**
     * Get last known good location for fallback
     */
    private function getLastKnownLocation(string $busId): ?array
    {
        $cacheKey = "last_known_location:{$busId}";
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        // Get most recent high-reputation location
        $lastLocation = BusLocation::where('bus_id', $busId)
            ->where('reputation_weight', '>', 0.6)
            ->where('created_at', '>', now()->subHours(2))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLocation) {
            return null;
        }

        $locationData = [
            'latitude' => $lastLocation->latitude,
            'longitude' => $lastLocation->longitude,
            'timestamp' => $lastLocation->created_at,
            'reputation_weight' => $lastLocation->reputation_weight
        ];

        // Cache for 10 minutes
        Cache::put($cacheKey, $locationData, now()->addMinutes(10));

        return $locationData;
    }

    /**
     * Start tracking session for device and bus
     */
    public function startTrackingSession(string $deviceToken, string $busId): array
    {
        $sessionKey = "tracking_session:{$deviceToken}:{$busId}";
        $sessionData = [
            'device_token' => $deviceToken,
            'bus_id' => $busId,
            'started_at' => now(),
            'last_activity' => now(),
            'is_active' => true
        ];

        Cache::put($sessionKey, $sessionData, now()->addMinutes(self::AUTO_DEACTIVATION_TIMEOUT));

        Log::info('Tracking session started', [
            'device_token' => substr($deviceToken, 0, 8) . '...',
            'bus_id' => $busId
        ]);

        return [
            'success' => true,
            'session_id' => $sessionKey,
            'expires_at' => now()->addMinutes(self::AUTO_DEACTIVATION_TIMEOUT)
        ];
    }

    /**
     * Get comprehensive reliability metrics for monitoring
     */
    public function getReliabilityMetrics(string $busId): array
    {
        $clusteringResult = $this->clusterUsers($busId);
        $smartBroadcast = $this->generateSmartBroadcast($busId);
        
        return [
            'bus_id' => $busId,
            'total_active_users' => $clusteringResult['total_users'],
            'clustered_users' => $clusteringResult['clustered_users'],
            'outlier_users' => $clusteringResult['outlier_users'],
            'trusted_users' => $smartBroadcast ? $smartBroadcast['trusted_users'] : 0,
            'broadcast_confidence' => $smartBroadcast ? $smartBroadcast['confidence'] : 0,
            'data_quality' => $smartBroadcast ? $smartBroadcast['data_quality'] : 'no_data',
            'last_updated' => now(),
            'clustering_efficiency' => $clusteringResult['total_users'] > 0 ? 
                ($clusteringResult['clustered_users'] / $clusteringResult['total_users']) : 0
        ];
    }
}