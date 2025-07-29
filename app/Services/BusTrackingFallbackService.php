<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;
use App\Models\BusSchedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Bus Tracking Fallback and Error Handling Service
 * Handles scenarios where tracking data is unavailable or limited
 */
class BusTrackingFallbackService
{
    private const CACHE_TTL_MINUTES = 30;
    private const LAST_SEEN_THRESHOLD_MINUTES = 15;
    private const HISTORICAL_DATA_DAYS = 7;

    /**
     * Get bus tracking status with fallback handling
     *
     * @param string $busId Bus identifier
     * @return array Comprehensive tracking status
     */
    public function getBusTrackingStatus(string $busId): array
    {
        $cacheKey = "bus_tracking_status_{$busId}";

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($busId) {
            // Check if bus is scheduled to be active
            $schedule = BusSchedule::where('bus_id', $busId)->active()->first();

            if (!$schedule || !$schedule->isCurrentlyActive()) {
                return $this->createInactiveStatus($busId, $schedule);
            }

            // Get current tracking data
            $currentPosition = BusCurrentPosition::where('bus_id', $busId)->first();
            $activeTrackers = $this->getActiveTrackersCount($busId);

            // Determine tracking scenario
            if ($activeTrackers === 0) {
                return $this->createNoTrackingStatus($busId, $currentPosition);
            } elseif ($activeTrackers === 1) {
                return $this->createSingleTrackerStatus($busId, $currentPosition);
            } else {
                return $this->createMultipleTrackersStatus($busId, $currentPosition, $activeTrackers);
            }
        });
    }

    /**
     * Get last known location with historical context
     *
     * @param string $busId Bus identifier
     * @return array|null Last known location data
     */
    public function getLastKnownLocation(string $busId): ?array
    {
        $cacheKey = "last_known_location_{$busId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($busId) {
            // Try current position first
            $currentPosition = BusCurrentPosition::where('bus_id', $busId)->first();

            if ($currentPosition && $currentPosition->last_updated > now()->subMinutes(self::LAST_SEEN_THRESHOLD_MINUTES)) {
                return [
                    'latitude' => $currentPosition->latitude,
                    'longitude' => $currentPosition->longitude,
                    'timestamp' => $currentPosition->last_updated,
                    'confidence_level' => $currentPosition->confidence_level,
                    'source' => 'current_position',
                    'age_minutes' => now()->diffInMinutes($currentPosition->last_updated),
                    'is_recent' => true
                ];
            }

            // Fall back to recent location data
            $recentLocation = BusLocation::where('bus_id', $busId)
                ->where('created_at', '>', now()->subHours(2))
                ->where('is_validated', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentLocation) {
                return [
                    'latitude' => $recentLocation->latitude,
                    'longitude' => $recentLocation->longitude,
                    'timestamp' => $recentLocation->created_at,
                    'confidence_level' => $recentLocation->reputation_weight,
                    'source' => 'recent_location',
                    'age_minutes' => now()->diffInMinutes($recentLocation->created_at),
                    'is_recent' => false
                ];
            }

            // Fall back to historical pattern
            return $this->getHistoricalLocationPattern($busId);
        });
    }

    /**
     * Handle single user tracking scenario
     *
     * @param string $busId Bus identifier
     * @param string $deviceToken Device token of the single tracker
     * @return array Single user tracking analysis
     */
    public function handleSingleUserTracking(string $busId, string $deviceToken): array
    {
        $cacheKey = "single_user_tracking_{$busId}_{$deviceToken}";

        return Cache::remember($cacheKey, now()->addMinutes(1), function () use ($busId, $deviceToken) {
            // Get recent locations from this user
            $recentLocations = BusLocation::where('bus_id', $busId)
                ->where('device_token', hash('sha256', $deviceToken))
                ->where('created_at', '>', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentLocations->isEmpty()) {
                return [
                    'status' => 'no_data',
                    'confidence_level' => 0.0,
                    'validation_score' => 0.0,
                    'message' => 'No recent location data from user',
                    'recommendations' => ['Check GPS permissions', 'Ensure location services are enabled']
                ];
            }

            // Analyze single user data quality
            $dataQuality = $this->analyzeSingleUserDataQuality($recentLocations);

            // Ensure data_quality has required structure
            if (!is_array($dataQuality) || !isset($dataQuality['overall_score'])) {
                $dataQuality = ['overall_score' => 0.0, 'issues' => ['analysis_error']];
            }

            // Calculate confidence based on data quality
            $confidenceLevel = $this->calculateSingleUserConfidence($dataQuality);

            // Get current position from single user
            $latestLocation = $recentLocations->first();

            return [
                'status' => 'single_user_tracking',
                'confidence_level' => $confidenceLevel,
                'validation_score' => $dataQuality['overall_score'],
                'current_location' => [
                    'latitude' => $latestLocation->latitude,
                    'longitude' => $latestLocation->longitude,
                    'accuracy' => $latestLocation->accuracy,
                    'timestamp' => $latestLocation->created_at
                ],
                'data_quality' => $dataQuality,
                'message' => $this->getSingleUserMessage($confidenceLevel, $dataQuality),
                'recommendations' => $this->getSingleUserRecommendations($dataQuality),
                'warning_flags' => $this->getSingleUserWarningFlags($dataQuality)
            ];
        });
    }

    /**
     * Handle transition from single to multiple users
     *
     * @param string $busId Bus identifier
     * @return array Transition handling result
     */
    public function handleMultiUserTransition(string $busId): array
    {
        $activeTrackers = $this->getActiveTrackersCount($busId);

        if ($activeTrackers < 2) {
            return [
                'status' => 'no_transition_needed',
                'message' => 'Still single or no trackers'
            ];
        }

        // Get all active tracking sessions
        $activeSessions = UserTrackingSession::where('bus_id', $busId)
            ->where('is_active', true)
            ->where('started_at', '>', now()->subHours(2))
            ->get();

        // Analyze transition quality
        $transitionAnalysis = $this->analyzeMultiUserTransition($activeSessions);

        // Update bus current position with multi-user data
        $this->updateBusPositionFromMultipleUsers($busId, $activeSessions);

        return [
            'status' => 'transition_completed',
            'previous_trackers' => 1,
            'current_trackers' => $activeTrackers,
            'transition_quality' => $transitionAnalysis,
            'message' => "Transitioned to {$activeTrackers} trackers",
            'confidence_improvement' => $transitionAnalysis['confidence_improvement'],
            'data_reliability' => $transitionAnalysis['data_reliability']
        ];
    }

    /**
     * Generate fallback display data for UI
     *
     * @param string $busId Bus identifier
     * @return array UI display data with fallbacks
     */
    public function generateFallbackDisplayData(string $busId): array
    {
        $trackingStatus = $this->getBusTrackingStatus($busId);
        $lastKnown = $this->getLastKnownLocation($busId);

        $displayData = [
            'bus_id' => $busId,
            'status' => $trackingStatus['status'],
            'display_message' => $this->generateDisplayMessage($trackingStatus, $lastKnown),
            'confidence_level' => $trackingStatus['confidence_level'] ?? 0.0,
            'last_updated' => $trackingStatus['last_updated'] ?? null,
            'active_trackers' => $trackingStatus['active_trackers'] ?? 0,
            'show_map' => $this->shouldShowMap($trackingStatus, $lastKnown),
            'map_data' => $this->generateMapData($trackingStatus, $lastKnown),
            'status_indicators' => $this->generateStatusIndicators($trackingStatus),
            'user_actions' => $this->generateUserActions($trackingStatus),
            'fallback_type' => $this->determineFallbackType($trackingStatus)
        ];

        return $displayData;
    }

    /**
     * Private helper methods
     */

    private function createInactiveStatus(string $busId, ?BusSchedule $schedule): array
    {
        $nextSchedule = $this->getNextScheduledTime($busId);

        return [
            'status' => 'inactive',
            'bus_active' => false,
            'confidence_level' => 0.0,
            'active_trackers' => 0,
            'message' => 'Bus is not currently scheduled to run',
            'next_schedule' => $nextSchedule,
            'last_updated' => now(),
            'fallback_reason' => 'bus_not_scheduled'
        ];
    }

    private function createNoTrackingStatus(string $busId, ?BusCurrentPosition $currentPosition): array
    {
        $lastKnown = $this->getLastKnownLocation($busId);

        return [
            'status' => 'no_tracking',
            'bus_active' => true,
            'confidence_level' => 0.0,
            'active_trackers' => 0,
            'message' => 'Tracking not active - no passengers sharing location',
            'last_known_location' => $lastKnown,
            'last_updated' => now(),
            'fallback_reason' => 'no_active_trackers',
            'historical_pattern' => $this->getHistoricalPattern($busId)
        ];
    }

    private function createSingleTrackerStatus(string $busId, ?BusCurrentPosition $currentPosition): array
    {
        $singleUserData = $this->getSingleUserTrackingData($busId);

        return [
            'status' => 'single_tracker',
            'bus_active' => true,
            'confidence_level' => $singleUserData['confidence_level'],
            'active_trackers' => 1,
            'message' => 'Single passenger tracking - limited accuracy',
            'current_location' => $singleUserData['current_location'] ?? null,
            'data_quality' => $singleUserData['data_quality'] ?? ['overall_score' => 0.0],
            'last_updated' => now(),
            'fallback_reason' => 'single_user_tracking',
            'validation_warnings' => $singleUserData['warning_flags'] ?? []
        ];
    }

    private function createMultipleTrackersStatus(string $busId, ?BusCurrentPosition $currentPosition, int $activeTrackers): array
    {
        return [
            'status' => 'active',
            'bus_active' => true,
            'confidence_level' => $currentPosition->confidence_level ?? 0.8,
            'active_trackers' => $activeTrackers,
            'message' => "Active tracking with {$activeTrackers} passengers",
            'current_location' => $currentPosition ? [
                'latitude' => $currentPosition->latitude,
                'longitude' => $currentPosition->longitude,
                'last_updated' => $currentPosition->last_updated
            ] : null,
            'last_updated' => $currentPosition->last_updated ?? now(),
            'fallback_reason' => null
        ];
    }

    private function getActiveTrackersCount(string $busId): int
    {
        return UserTrackingSession::where('bus_id', $busId)
            ->where('is_active', true)
            ->where('started_at', '>', now()->subHours(2))
            ->count();
    }

    private function getHistoricalLocationPattern(string $busId): ?array
    {
        // Get historical data for this time of day over the past week
        $currentTime = now()->format('H:i:s');
        $dayOfWeek = now()->dayOfWeek;

        $historicalData = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subDays(self::HISTORICAL_DATA_DAYS))
            ->whereTime('created_at', '>=', Carbon::createFromFormat('H:i:s', $currentTime)->subMinutes(30)->format('H:i:s'))
            ->whereTime('created_at', '<=', Carbon::createFromFormat('H:i:s', $currentTime)->addMinutes(30)->format('H:i:s'))
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($historicalData->isEmpty()) {
            return null;
        }

        // Calculate average position
        $avgLat = $historicalData->avg('latitude');
        $avgLng = $historicalData->avg('longitude');

        return [
            'latitude' => $avgLat,
            'longitude' => $avgLng,
            'timestamp' => now(),
            'confidence_level' => 0.3, // Low confidence for historical data
            'source' => 'historical_pattern',
            'data_points' => $historicalData->count(),
            'is_recent' => false
        ];
    }

    private function analyzeSingleUserDataQuality($locations): array
    {
        if ($locations->isEmpty()) {
            return [
                'overall_score' => 0.0,
                'accuracy_score' => 0.0,
                'consistency_score' => 0.0,
                'movement_score' => 0.0,
                'issues' => ['no_data']
            ];
        }

        $accuracyScore = $this->calculateAccuracyScore($locations);
        $consistencyScore = $this->calculateConsistencyScore($locations);
        $movementScore = $this->calculateMovementScore($locations);

        $overallScore = ($accuracyScore + $consistencyScore + $movementScore) / 3;

        $issues = [];
        if ($accuracyScore < 0.5)
            $issues[] = 'poor_gps_accuracy';
        if ($consistencyScore < 0.5)
            $issues[] = 'inconsistent_movement';
        if ($movementScore < 0.5)
            $issues[] = 'unrealistic_movement';

        return [
            'overall_score' => round($overallScore, 2),
            'accuracy_score' => round($accuracyScore, 2),
            'consistency_score' => round($consistencyScore, 2),
            'movement_score' => round($movementScore, 2),
            'issues' => $issues,
            'location_count' => $locations->count(),
            'time_span_minutes' => $locations->first()->created_at->diffInMinutes($locations->last()->created_at)
        ];
    }

    private function calculateSingleUserConfidence(array $dataQuality): float
    {
        $baseConfidence = 0.4; // Lower base confidence for single user

        // Adjust based on data quality
        $qualityBonus = ($dataQuality['overall_score'] ?? 0.0) * 0.3;

        // Penalty for issues
        $issuePenalty = count($dataQuality['issues'] ?? []) * 0.1;

        $confidence = $baseConfidence + $qualityBonus - $issuePenalty;

        return max(0.1, min(0.8, $confidence)); // Cap at 0.8 for single user
    }

    private function getSingleUserMessage(float $confidence, array $dataQuality): string
    {
        if ($confidence < 0.3) {
            return 'Single user tracking - low confidence due to data quality issues';
        } elseif ($confidence < 0.5) {
            return 'Single user tracking - moderate confidence';
        } else {
            return 'Single user tracking - good data quality';
        }
    }

    private function getSingleUserRecommendations(array $dataQuality): array
    {
        $recommendations = [];

        if (in_array('poor_gps_accuracy', $dataQuality['issues'] ?? [])) {
            $recommendations[] = 'Move to an area with better GPS signal';
            $recommendations[] = 'Ensure location services are enabled';
        }

        if (in_array('inconsistent_movement', $dataQuality['issues'] ?? [])) {
            $recommendations[] = 'Keep the app open while on the bus';
            $recommendations[] = 'Avoid switching between apps frequently';
        }

        if (in_array('unrealistic_movement', $dataQuality['issues'] ?? [])) {
            $recommendations[] = 'Ensure you are actually on the bus';
            $recommendations[] = 'Check if GPS is working correctly';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Continue tracking to help other passengers';
        }

        return $recommendations;
    }

    private function getSingleUserWarningFlags(array $dataQuality): array
    {
        $flags = [];

        if (($dataQuality['overall_score'] ?? 0.0) < 0.3) {
            $flags[] = 'low_data_quality';
        }

        if (($dataQuality['location_count'] ?? 0) < 3) {
            $flags[] = 'insufficient_data_points';
        }

        if ($dataQuality['time_span_minutes'] < 2) {
            $flags[] = 'short_tracking_duration';
        }

        return $flags;
    }

    private function analyzeMultiUserTransition($activeSessions): array
    {
        $sessionCount = $activeSessions->count();

        // Calculate confidence improvement
        $singleUserConfidence = 0.5; // Assumed previous single user confidence
        $multiUserConfidence = min(0.9, 0.6 + ($sessionCount * 0.1));

        $confidenceImprovement = $multiUserConfidence - $singleUserConfidence;

        // Analyze data reliability
        $reliabilityScore = min(1.0, $sessionCount / 5); // Optimal at 5 users

        return [
            'confidence_improvement' => round($confidenceImprovement, 2),
            'data_reliability' => round($reliabilityScore, 2),
            'session_count' => $sessionCount,
            'transition_quality' => $confidenceImprovement > 0.2 ? 'good' : 'moderate'
        ];
    }

    private function updateBusPositionFromMultipleUsers(string $busId, $activeSessions): void
    {
        // This would typically aggregate location data from multiple users
        // For now, we'll just update the timestamp
        BusCurrentPosition::where('bus_id', $busId)->update([
            'last_updated' => now(),
            'active_trackers' => $activeSessions->count()
        ]);
    }

    private function generateDisplayMessage(array $trackingStatus, ?array $lastKnown): string
    {
        switch ($trackingStatus['status']) {
            case 'inactive':
                return 'Bus is not currently scheduled to run';

            case 'no_tracking':
                if ($lastKnown && $lastKnown['is_recent']) {
                    $ageText = $this->formatTimeAgo($lastKnown['timestamp']);
                    return "No active tracking • Last seen {$ageText}";
                } else {
                    return 'No passengers are currently sharing their location';
                }

            case 'single_tracker':
                return 'One passenger is sharing location • Limited accuracy';

            case 'active':
                $trackerCount = $trackingStatus['active_trackers'];
                return "{$trackerCount} passengers sharing location • High accuracy";

            default:
                return 'Tracking status unknown';
        }
    }

    private function shouldShowMap(array $trackingStatus, ?array $lastKnown): bool
    {
        return $trackingStatus['status'] === 'active' ||
            $trackingStatus['status'] === 'single_tracker' ||
            ($trackingStatus['status'] === 'no_tracking' && $lastKnown !== null);
    }

    private function generateMapData(array $trackingStatus, ?array $lastKnown): ?array
    {
        if ($trackingStatus['status'] === 'active' || $trackingStatus['status'] === 'single_tracker') {
            return $trackingStatus['current_location'] ?? null;
        }

        if ($trackingStatus['status'] === 'no_tracking' && $lastKnown) {
            return [
                'latitude' => $lastKnown['latitude'],
                'longitude' => $lastKnown['longitude'],
                'is_last_known' => true,
                'age_minutes' => $lastKnown['age_minutes']
            ];
        }

        return null;
    }

    private function generateStatusIndicators(array $trackingStatus): array
    {
        $indicators = [];

        switch ($trackingStatus['status']) {
            case 'inactive':
                $indicators[] = ['type' => 'warning', 'text' => 'Not Scheduled'];
                break;

            case 'no_tracking':
                $indicators[] = ['type' => 'danger', 'text' => 'No Tracking'];
                break;

            case 'single_tracker':
                $indicators[] = ['type' => 'warning', 'text' => 'Limited Accuracy'];
                break;

            case 'active':
                $indicators[] = ['type' => 'success', 'text' => 'Live Tracking'];
                break;
        }

        // Add confidence indicator
        $confidence = $trackingStatus['confidence_level'] ?? 0;
        if ($confidence > 0) {
            $confidenceText = $confidence > 0.7 ? 'High' : ($confidence > 0.4 ? 'Medium' : 'Low');
            $confidenceType = $confidence > 0.7 ? 'success' : ($confidence > 0.4 ? 'warning' : 'danger');
            $indicators[] = ['type' => $confidenceType, 'text' => "{$confidenceText} Confidence"];
        }

        return $indicators;
    }

    private function generateUserActions(array $trackingStatus): array
    {
        $actions = [];

        if ($trackingStatus['status'] === 'no_tracking' || $trackingStatus['status'] === 'single_tracker') {
            $actions[] = [
                'type' => 'primary',
                'text' => "I'm on this Bus",
                'action' => 'start_tracking',
                'description' => 'Help others by sharing your location'
            ];
        }

        $actions[] = [
            'type' => 'secondary',
            'text' => 'Refresh',
            'action' => 'refresh_data',
            'description' => 'Check for updated tracking data'
        ];

        return $actions;
    }

    private function determineFallbackType(array $trackingStatus): string
    {
        return $trackingStatus['fallback_reason'] ?? 'none';
    }

    private function getNextScheduledTime(string $busId): ?array
    {
        $schedule = BusSchedule::where('bus_id', $busId)->active()->first();

        if (!$schedule) {
            return null;
        }

        $now = now();
        $today = $now->toDateString();

        // Check if departure time is later today
        $departureToday = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $schedule->departure_time->format('H:i:s'));

        if ($departureToday->isFuture()) {
            return [
                'type' => 'departure',
                'time' => $departureToday,
                'formatted' => $departureToday->format('g:i A'),
                'minutes_until' => $now->diffInMinutes($departureToday)
            ];
        }

        // Check if return time is later today
        if ($schedule->return_time) {
            $returnToday = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $schedule->return_time->format('H:i:s'));

            if ($returnToday->isFuture()) {
                return [
                    'type' => 'return',
                    'time' => $returnToday,
                    'formatted' => $returnToday->format('g:i A'),
                    'minutes_until' => $now->diffInMinutes($returnToday)
                ];
            }
        }

        // Next departure is tomorrow
        $departureTomorrow = $departureToday->addDay();

        return [
            'type' => 'departure',
            'time' => $departureTomorrow,
            'formatted' => $departureTomorrow->format('g:i A \t\o\m\o\r\r\o\w'),
            'minutes_until' => $now->diffInMinutes($departureTomorrow)
        ];
    }

    private function getSingleUserTrackingData(string $busId): array
    {
        $activeSession = UserTrackingSession::where('bus_id', $busId)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            return [
                'confidence_level' => 0.0,
                'current_location' => null,
                'data_quality' => ['overall_score' => 0.0],
                'warning_flags' => ['no_active_session']
            ];
        }

        return $this->handleSingleUserTracking($busId, $activeSession->device_token);
    }

    private function getHistoricalPattern(string $busId): ?array
    {
        // Get typical location pattern for this time of day
        return $this->getHistoricalLocationPattern($busId);
    }

    private function calculateAccuracyScore($locations): float
    {
        $avgAccuracy = $locations->avg('accuracy');

        if ($avgAccuracy <= 10)
            return 1.0;
        if ($avgAccuracy <= 25)
            return 0.8;
        if ($avgAccuracy <= 50)
            return 0.6;
        if ($avgAccuracy <= 100)
            return 0.4;

        return 0.2;
    }

    private function calculateConsistencyScore($locations): float
    {
        if ($locations->count() < 2)
            return 0.5;

        $speeds = [];
        for ($i = 1; $i < $locations->count(); $i++) {
            $current = $locations[$i - 1];
            $previous = $locations[$i];

            $distance = $this->calculateDistance(
                $previous->latitude,
                $previous->longitude,
                $current->latitude,
                $current->longitude
            );

            $timeDiff = $current->created_at->diffInSeconds($previous->created_at);

            if ($timeDiff > 0) {
                $speed = ($distance / $timeDiff) * 3.6; // km/h
                $speeds[] = $speed;
            }
        }

        if (empty($speeds))
            return 0.5;

        $avgSpeed = array_sum($speeds) / count($speeds);
        $variance = 0;
        foreach ($speeds as $speed) {
            $variance += pow($speed - $avgSpeed, 2);
        }
        $stdDev = sqrt($variance / count($speeds));

        $cv = $avgSpeed > 0 ? $stdDev / $avgSpeed : 1;

        return max(0, 1 - $cv);
    }

    private function calculateMovementScore($locations): float
    {
        if ($locations->count() < 2)
            return 0.5;

        $totalDistance = 0;
        $timeSpan = $locations->first()->created_at->diffInSeconds($locations->last()->created_at);

        for ($i = 1; $i < $locations->count(); $i++) {
            $current = $locations[$i - 1];
            $previous = $locations[$i];

            $distance = $this->calculateDistance(
                $previous->latitude,
                $previous->longitude,
                $current->latitude,
                $current->longitude
            );

            $totalDistance += $distance;
        }

        if ($timeSpan <= 0)
            return 0.5;

        $avgSpeed = ($totalDistance / $timeSpan) * 3.6; // km/h

        // Score based on realistic bus speed (5-60 km/h)
        if ($avgSpeed >= 5 && $avgSpeed <= 60)
            return 1.0;
        if ($avgSpeed >= 2 && $avgSpeed <= 80)
            return 0.7;
        if ($avgSpeed >= 1 && $avgSpeed <= 100)
            return 0.4;

        return 0.2;
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

    private function formatTimeAgo(Carbon $timestamp): string
    {
        $diff = now()->diffInMinutes($timestamp);

        if ($diff < 1)
            return 'just now';
        if ($diff < 60)
            return "{$diff} minutes ago";

        $hours = floor($diff / 60);
        if ($hours < 24)
            return "{$hours} hours ago";

        $days = floor($hours / 24);
        return "{$days} days ago";
    }
}