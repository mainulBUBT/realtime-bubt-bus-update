<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;
use App\Services\StoppageCoordinateValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Bus Last Seen Service
 * Handles "Last seen at [time/location]" functionality with historical data
 */
class BusLastSeenService
{
    private StoppageCoordinateValidator $stoppageValidator;
    
    private const CACHE_TTL_MINUTES = 5;
    private const RECENT_THRESHOLD_MINUTES = 15;
    private const HISTORICAL_SEARCH_HOURS = 6;

    public function __construct(StoppageCoordinateValidator $stoppageValidator)
    {
        $this->stoppageValidator = $stoppageValidator;
    }

    /**
     * Get last seen information for a bus
     *
     * @param string $busId Bus identifier
     * @return array|null Last seen data or null if no data available
     */
    public function getLastSeenInfo(string $busId): ?array
    {
        $cacheKey = "bus_last_seen_{$busId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($busId) {
            // Try current position first
            $currentPosition = BusCurrentPosition::where('bus_id', $busId)->first();
            
            if ($currentPosition && $this->isRecentEnough($currentPosition->last_updated)) {
                return $this->formatLastSeenFromCurrentPosition($currentPosition);
            }

            // Fall back to recent location data
            $recentLocation = $this->getRecentLocationData($busId);
            
            if ($recentLocation) {
                return $this->formatLastSeenFromLocation($recentLocation);
            }

            // Fall back to historical data
            return $this->getHistoricalLastSeen($busId);
        });
    }

    /**
     * Get last seen with location context (stop name, area, etc.)
     *
     * @param string $busId Bus identifier
     * @return array|null Enhanced last seen data with location context
     */
    public function getLastSeenWithContext(string $busId): ?array
    {
        $lastSeen = $this->getLastSeenInfo($busId);
        
        if (!$lastSeen) {
            return null;
        }

        // Add location context
        $locationContext = $this->getLocationContext(
            $lastSeen['latitude'],
            $lastSeen['longitude']
        );

        return array_merge($lastSeen, [
            'location_context' => $locationContext,
            'display_location' => $this->generateDisplayLocation($locationContext),
            'confidence_description' => $this->getConfidenceDescription($lastSeen['confidence_level']),
            'age_description' => $this->getAgeDescription($lastSeen['timestamp'])
        ]);
    }

    /**
     * Get formatted last seen message for UI display
     *
     * @param string $busId Bus identifier
     * @return string Formatted message for display
     */
    public function getLastSeenMessage(string $busId): string
    {
        $lastSeenWithContext = $this->getLastSeenWithContext($busId);
        
        if (!$lastSeenWithContext) {
            return 'No recent location data available';
        }

        $timeAgo = $this->formatTimeAgo($lastSeenWithContext['timestamp']);
        $location = $lastSeenWithContext['display_location'];
        $confidence = $lastSeenWithContext['confidence_level'];

        if ($confidence > 0.7) {
            return "Last seen {$timeAgo} at {$location}";
        } elseif ($confidence > 0.4) {
            return "Last seen {$timeAgo} near {$location} (approximate)";
        } else {
            return "Last seen {$timeAgo} in general area (low confidence)";
        }
    }

    /**
     * Check if bus has been seen recently
     *
     * @param string $busId Bus identifier
     * @param int $thresholdMinutes Threshold in minutes (default: 15)
     * @return bool True if seen recently
     */
    public function hasBeenSeenRecently(string $busId, int $thresholdMinutes = self::RECENT_THRESHOLD_MINUTES): bool
    {
        $lastSeen = $this->getLastSeenInfo($busId);
        
        if (!$lastSeen) {
            return false;
        }

        $threshold = now()->subMinutes($thresholdMinutes);
        return $lastSeen['timestamp']->isAfter($threshold);
    }

    /**
     * Get tracking gap information (time since last tracking)
     *
     * @param string $busId Bus identifier
     * @return array Tracking gap analysis
     */
    public function getTrackingGapInfo(string $busId): array
    {
        $lastSeen = $this->getLastSeenInfo($busId);
        
        if (!$lastSeen) {
            return [
                'has_gap' => true,
                'gap_duration_minutes' => null,
                'gap_severity' => 'critical',
                'message' => 'No tracking data available',
                'recommendations' => ['Check if bus is scheduled to run', 'Wait for passengers to start tracking']
            ];
        }

        $gapMinutes = now()->diffInMinutes($lastSeen['timestamp']);
        $severity = $this->determineGapSeverity($gapMinutes);
        
        return [
            'has_gap' => $gapMinutes > self::RECENT_THRESHOLD_MINUTES,
            'gap_duration_minutes' => $gapMinutes,
            'gap_severity' => $severity,
            'last_seen_timestamp' => $lastSeen['timestamp'],
            'message' => $this->getGapMessage($gapMinutes, $severity),
            'recommendations' => $this->getGapRecommendations($severity, $gapMinutes)
        ];
    }

    /**
     * Update last seen data when new location is received
     *
     * @param string $busId Bus identifier
     * @param array $locationData New location data
     * @return void
     */
    public function updateLastSeen(string $busId, array $locationData): void
    {
        // Clear cache to force refresh
        $cacheKey = "bus_last_seen_{$busId}";
        Cache::forget($cacheKey);
        
        // Log the update for monitoring
        Log::info('Last seen updated', [
            'bus_id' => $busId,
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'timestamp' => $locationData['timestamp'] ?? now()
        ]);
    }

    /**
     * Private helper methods
     */

    private function isRecentEnough(Carbon $timestamp): bool
    {
        return $timestamp->isAfter(now()->subMinutes(self::RECENT_THRESHOLD_MINUTES));
    }

    private function getRecentLocationData(string $busId): ?BusLocation
    {
        return BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subHours(2))
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function getHistoricalLastSeen(string $busId): ?array
    {
        // Look for any location data within the historical search window
        $historicalLocation = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subHours(self::HISTORICAL_SEARCH_HOURS))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$historicalLocation) {
            return null;
        }

        return [
            'latitude' => $historicalLocation->latitude,
            'longitude' => $historicalLocation->longitude,
            'timestamp' => $historicalLocation->created_at,
            'confidence_level' => max(0.1, $historicalLocation->reputation_weight * 0.5), // Reduced confidence for old data
            'source' => 'historical',
            'age_minutes' => now()->diffInMinutes($historicalLocation->created_at),
            'is_recent' => false
        ];
    }

    private function formatLastSeenFromCurrentPosition(BusCurrentPosition $position): array
    {
        return [
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'timestamp' => $position->last_updated,
            'confidence_level' => $position->confidence_level,
            'source' => 'current_position',
            'age_minutes' => now()->diffInMinutes($position->last_updated),
            'is_recent' => true,
            'active_trackers' => $position->active_trackers ?? 0
        ];
    }

    private function formatLastSeenFromLocation(BusLocation $location): array
    {
        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'timestamp' => $location->created_at,
            'confidence_level' => $location->reputation_weight,
            'source' => 'location_data',
            'age_minutes' => now()->diffInMinutes($location->created_at),
            'is_recent' => $this->isRecentEnough($location->created_at),
            'accuracy' => $location->accuracy
        ];
    }

    private function getLocationContext(float $latitude, float $longitude): array
    {
        // Validate against bus stops
        $stoppageValidation = $this->stoppageValidator->validateStoppageRadius($latitude, $longitude);
        
        $context = [
            'type' => 'unknown',
            'name' => 'Unknown location',
            'description' => 'Location not recognized',
            'confidence' => 0.0
        ];

        if ($stoppageValidation['within_radius']) {
            $context = [
                'type' => 'bus_stop',
                'name' => $stoppageValidation['closest_stop'],
                'description' => 'At bus stop',
                'confidence' => 0.9,
                'distance_to_stop' => $stoppageValidation['distance_to_closest']
            ];
        } elseif ($stoppageValidation['closest_stop']) {
            $distance = $stoppageValidation['distance_to_closest'];
            $context = [
                'type' => 'near_stop',
                'name' => $stoppageValidation['closest_stop'],
                'description' => "Near {$stoppageValidation['closest_stop']}",
                'confidence' => max(0.3, 1 - ($distance / 1000)), // Confidence decreases with distance
                'distance_to_stop' => $distance
            ];
        }

        // Add general area information
        $context['area'] = $this->getGeneralArea($latitude, $longitude);
        
        return $context;
    }

    private function generateDisplayLocation(array $locationContext): string
    {
        switch ($locationContext['type']) {
            case 'bus_stop':
                return $locationContext['name'];
                
            case 'near_stop':
                $distance = round($locationContext['distance_to_stop']);
                return "near {$locationContext['name']} ({$distance}m away)";
                
            default:
                return $locationContext['area'] ?? 'unknown location';
        }
    }

    private function getGeneralArea(float $latitude, float $longitude): string
    {
        // Simple area detection based on coordinates
        // This could be enhanced with a proper geocoding service
        
        if ($latitude >= 23.8 && $latitude <= 23.85 && $longitude >= 90.35 && $longitude <= 90.37) {
            return 'Mirpur area';
        } elseif ($latitude >= 23.76 && $latitude <= 23.78 && $longitude >= 90.36 && $longitude <= 90.38) {
            return 'Shyamoli area';
        } elseif ($latitude >= 23.76 && $latitude <= 23.77 && $longitude >= 90.365 && $longitude <= 90.37) {
            return 'Asad Gate area';
        } else {
            return 'Dhaka area';
        }
    }

    private function getConfidenceDescription(float $confidence): string
    {
        if ($confidence > 0.8) return 'Very reliable';
        if ($confidence > 0.6) return 'Reliable';
        if ($confidence > 0.4) return 'Moderate reliability';
        if ($confidence > 0.2) return 'Low reliability';
        return 'Very low reliability';
    }

    private function getAgeDescription(Carbon $timestamp): string
    {
        $minutes = now()->diffInMinutes($timestamp);
        
        if ($minutes < 5) return 'Very recent';
        if ($minutes < 15) return 'Recent';
        if ($minutes < 60) return 'Somewhat old';
        if ($minutes < 180) return 'Old';
        return 'Very old';
    }

    private function formatTimeAgo(Carbon $timestamp): string
    {
        $diff = now()->diffInMinutes($timestamp);
        
        if ($diff < 1) return 'just now';
        if ($diff < 60) return "{$diff} minutes ago";
        
        $hours = floor($diff / 60);
        if ($hours < 24) return "{$hours} hours ago";
        
        $days = floor($hours / 24);
        return "{$days} days ago";
    }

    private function determineGapSeverity(int $gapMinutes): string
    {
        if ($gapMinutes <= 15) return 'none';
        if ($gapMinutes <= 30) return 'minor';
        if ($gapMinutes <= 60) return 'moderate';
        if ($gapMinutes <= 180) return 'major';
        return 'critical';
    }

    private function getGapMessage(int $gapMinutes, string $severity): string
    {
        switch ($severity) {
            case 'none':
                return 'Recently tracked';
                
            case 'minor':
                return "No tracking for {$gapMinutes} minutes";
                
            case 'moderate':
                $hours = floor($gapMinutes / 60);
                $mins = $gapMinutes % 60;
                return "No tracking for {$hours}h {$mins}m";
                
            case 'major':
                $hours = floor($gapMinutes / 60);
                return "No tracking for {$hours} hours";
                
            case 'critical':
                $hours = floor($gapMinutes / 60);
                return "No tracking for {$hours} hours - may not be running";
                
            default:
                return 'Tracking status unknown';
        }
    }

    private function getGapRecommendations(string $severity, int $gapMinutes): array
    {
        switch ($severity) {
            case 'none':
                return ['Continue normal tracking'];
                
            case 'minor':
                return [
                    'Check if passengers are still on the bus',
                    'Location data should update soon'
                ];
                
            case 'moderate':
                return [
                    'Bus may be between stops or in poor signal area',
                    'Check schedule to see if bus should be running',
                    'Consider alternative transportation if urgent'
                ];
                
            case 'major':
                return [
                    'Bus may have completed its route',
                    'Check bus schedule for next departure',
                    'Consider alternative transportation'
                ];
                
            case 'critical':
                return [
                    'Bus likely not running or route completed',
                    'Check official schedule',
                    'Use alternative transportation'
                ];
                
            default:
                return ['Check bus schedule and consider alternatives'];
        }
    }
}