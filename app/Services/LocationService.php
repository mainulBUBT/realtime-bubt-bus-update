<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Services\StoppageCoordinateValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Location Service for GPS Data Processing
 * Handles GPS coordinate validation, aggregation, and processing
 */
class LocationService
{
    private StoppageCoordinateValidator $validator;
    private const MAX_SPEED_KMH = 80; // Maximum realistic bus speed
    private const MIN_ACCURACY_METERS = 100; // Minimum acceptable GPS accuracy
    private const LOCATION_TIMEOUT_MINUTES = 5; // Consider location stale after 5 minutes
    private const AGGREGATION_RADIUS_METERS = 50; // Group locations within 50m

    public function __construct(StoppageCoordinateValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Process incoming GPS location data
     *
     * @param array $locationData GPS data from client
     * @return array Processing result
     */
    public function processLocationData(array $locationData): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'validation_results' => [],
            'location_id' => null,
            'aggregated_position' => null
        ];

        try {
            // Validate required fields
            $validationResult = $this->validateLocationData($locationData);
            if (!$validationResult['valid']) {
                $result['message'] = $validationResult['message'];
                return $result;
            }

            // Extract and sanitize data
            $cleanData = $this->sanitizeLocationData($locationData);
            
            // Validate GPS coordinates
            $coordinateValidation = $this->validateCoordinates($cleanData);
            $result['validation_results']['coordinates'] = $coordinateValidation;
            
            if (!$coordinateValidation['valid']) {
                $result['message'] = 'Invalid GPS coordinates';
                return $result;
            }

            // Validate against bus stops and routes
            $routeValidation = $this->validateAgainstRoute($cleanData);
            $result['validation_results']['route'] = $routeValidation;

            // Validate speed (if previous location exists)
            $speedValidation = $this->validateSpeed($cleanData);
            $result['validation_results']['speed'] = $speedValidation;

            // Calculate reputation weight for this location
            $reputationWeight = $this->calculateReputationWeight($cleanData, $result['validation_results']);
            $cleanData['reputation_weight'] = $reputationWeight;

            // Store location data
            $location = $this->storeLocationData($cleanData);
            $result['location_id'] = $location->id;

            // Aggregate with other users' locations for the same bus
            $aggregatedPosition = $this->aggregateLocationData($cleanData['bus_id']);
            $result['aggregated_position'] = $aggregatedPosition;

            // Update bus current position cache
            $this->updateBusCurrentPosition($cleanData['bus_id'], $aggregatedPosition);

            $result['success'] = true;
            $result['message'] = 'Location processed successfully';

            Log::info('Location processed', [
                'device_token' => substr($cleanData['device_token'], 0, 8) . '...',
                'bus_id' => $cleanData['bus_id'],
                'reputation_weight' => $reputationWeight,
                'validation_results' => $result['validation_results']
            ]);

        } catch (\Exception $e) {
            Log::error('Location processing failed', [
                'error' => $e->getMessage(),
                'location_data' => $locationData
            ]);
            
            $result['message'] = 'Location processing failed';
        }

        return $result;
    }

    /**
     * Validate incoming location data structure
     */
    private function validateLocationData(array $data): array
    {
        $required = ['device_token', 'bus_id', 'latitude', 'longitude', 'accuracy', 'timestamp'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }

        // Validate data types
        if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
            return [
                'valid' => false,
                'message' => 'Latitude and longitude must be numeric'
            ];
        }

        if (!is_numeric($data['accuracy']) || $data['accuracy'] <= 0) {
            return [
                'valid' => false,
                'message' => 'Accuracy must be a positive number'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Sanitize and normalize location data
     */
    private function sanitizeLocationData(array $data): array
    {
        return [
            'device_token' => trim($data['device_token']),
            'bus_id' => strtoupper(trim($data['bus_id'])),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'accuracy' => (float) $data['accuracy'],
            'speed' => isset($data['speed']) ? (float) $data['speed'] : null,
            'heading' => isset($data['heading']) ? (float) $data['heading'] : null,
            'timestamp' => isset($data['timestamp']) ? Carbon::createFromTimestamp($data['timestamp'] / 1000) : now(),
            'session_id' => $data['session_id'] ?? null
        ];
    }

    /**
     * Validate GPS coordinates
     */
    private function validateCoordinates(array $data): array
    {
        $lat = $data['latitude'];
        $lng = $data['longitude'];
        $accuracy = $data['accuracy'];

        // Check if coordinates are within Bangladesh bounds
        if ($lat < 20.5 || $lat > 26.5 || $lng < 88.0 || $lng > 92.7) {
            return [
                'valid' => false,
                'message' => 'Coordinates outside Bangladesh region',
                'details' => ['lat' => $lat, 'lng' => $lng]
            ];
        }

        // Check GPS accuracy
        if ($accuracy > self::MIN_ACCURACY_METERS) {
            return [
                'valid' => false,
                'message' => "GPS accuracy too low: {$accuracy}m (minimum: " . self::MIN_ACCURACY_METERS . "m)",
                'details' => ['accuracy' => $accuracy]
            ];
        }

        // Check for obviously invalid coordinates (0,0 or other common errors)
        if (($lat == 0 && $lng == 0) || abs($lat) < 0.001 || abs($lng) < 0.001) {
            return [
                'valid' => false,
                'message' => 'Invalid or default coordinates detected',
                'details' => ['lat' => $lat, 'lng' => $lng]
            ];
        }

        return [
            'valid' => true,
            'message' => 'Coordinates valid',
            'details' => [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $accuracy
            ]
        ];
    }

    /**
     * Validate location against expected bus route
     */
    private function validateAgainstRoute(array $data): array
    {
        $lat = $data['latitude'];
        $lng = $data['longitude'];
        $busId = $data['bus_id'];

        // Validate against bus stops
        $stoppageValidation = $this->validator->validateStoppageRadius($lat, $lng);
        
        // Validate against bus route
        $routeValidation = $this->validator->validateAgainstBusRoute($lat, $lng, $busId);

        return [
            'valid' => $stoppageValidation['is_valid'] || $routeValidation['is_valid'],
            'stoppage_validation' => $stoppageValidation,
            'route_validation' => $routeValidation,
            'message' => $stoppageValidation['is_valid'] ? 
                'Location valid (at bus stop)' : 
                ($routeValidation['is_valid'] ? 'Location valid (on route)' : 'Location outside expected area')
        ];
    }

    /**
     * Validate speed against previous location
     */
    private function validateSpeed(array $data): array
    {
        $deviceToken = $data['device_token'];
        $currentTime = $data['timestamp'];
        $currentLat = $data['latitude'];
        $currentLng = $data['longitude'];

        // Get previous location for this device
        $previousLocation = BusLocation::where('device_token', $deviceToken)
            ->where('created_at', '>', $currentTime->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousLocation) {
            return [
                'valid' => true,
                'message' => 'No previous location for speed validation',
                'calculated_speed' => null
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
        
        if ($timeDiff <= 0) {
            return [
                'valid' => false,
                'message' => 'Invalid timestamp sequence',
                'calculated_speed' => null
            ];
        }

        // Calculate speed in km/h
        $speedKmh = ($distance / $timeDiff) * 3.6;

        // Validate against maximum realistic speed
        $isValid = $speedKmh <= self::MAX_SPEED_KMH;

        return [
            'valid' => $isValid,
            'message' => $isValid ? 
                'Speed validation passed' : 
                "Speed too high: {$speedKmh} km/h (max: " . self::MAX_SPEED_KMH . " km/h)",
            'calculated_speed' => round($speedKmh, 2),
            'distance_meters' => round($distance, 2),
            'time_seconds' => $timeDiff
        ];
    }

    /**
     * Calculate reputation weight for location data
     */
    private function calculateReputationWeight(array $data, array $validationResults): float
    {
        $deviceToken = $data['device_token'];
        $accuracy = $data['accuracy'];

        // Get device reputation score
        $deviceReputation = $this->getDeviceReputation($deviceToken);

        // Base weight from device reputation (0.1 to 1.0)
        $weight = max(0.1, $deviceReputation);

        // Adjust based on GPS accuracy (better accuracy = higher weight)
        $accuracyFactor = max(0.5, min(1.0, (50 / $accuracy))); // 50m = 1.0, 100m = 0.5
        $weight *= $accuracyFactor;

        // Adjust based on validation results
        if ($validationResults['coordinates']['valid']) {
            $weight *= 1.0; // No penalty for valid coordinates
        } else {
            $weight *= 0.1; // Heavy penalty for invalid coordinates
        }

        if ($validationResults['route']['valid']) {
            $weight *= 1.2; // Bonus for being on expected route
        } else {
            $weight *= 0.7; // Penalty for being off route
        }

        if (isset($validationResults['speed']) && $validationResults['speed']['valid']) {
            $weight *= 1.1; // Bonus for realistic speed
        } elseif (isset($validationResults['speed']) && !$validationResults['speed']['valid']) {
            $weight *= 0.3; // Heavy penalty for unrealistic speed
        }

        // Ensure weight is within bounds
        return max(0.01, min(1.0, $weight));
    }

    /**
     * Get device reputation score
     */
    private function getDeviceReputation(string $deviceToken): float
    {
        $device = DeviceToken::where('token_hash', hash('sha256', $deviceToken))->first();
        
        if (!$device) {
            // New device gets neutral reputation
            return 0.5;
        }

        return $device->reputation_score ?? 0.5;
    }

    /**
     * Store location data in database
     */
    private function storeLocationData(array $data): BusLocation
    {
        return BusLocation::create([
            'bus_id' => $data['bus_id'],
            'device_token' => hash('sha256', $data['device_token']), // Hash for privacy
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'],
            'speed' => $data['speed'],
            'reputation_weight' => $data['reputation_weight'],
            'is_validated' => true, // Mark as validated since we processed it
            'session_id' => $data['session_id']
        ]);
    }

    /**
     * Aggregate location data from multiple users for the same bus
     */
    public function aggregateLocationData(string $busId): ?array
    {
        // Get recent locations for this bus (last 2 minutes)
        $recentLocations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>', now()->subMinutes(2))
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recentLocations->isEmpty()) {
            return null;
        }

        // Group locations by proximity (within aggregation radius)
        $locationGroups = $this->groupLocationsByProximity($recentLocations);

        // Find the group with highest total reputation weight
        $bestGroup = null;
        $highestWeight = 0;

        foreach ($locationGroups as $group) {
            $totalWeight = $group->sum('reputation_weight');
            if ($totalWeight > $highestWeight) {
                $highestWeight = $totalWeight;
                $bestGroup = $group;
            }
        }

        if (!$bestGroup || $bestGroup->isEmpty()) {
            return null;
        }

        // Calculate weighted average position
        $totalWeight = $bestGroup->sum('reputation_weight');
        $weightedLat = $bestGroup->sum(function ($location) {
            return $location->latitude * $location->reputation_weight;
        }) / $totalWeight;
        
        $weightedLng = $bestGroup->sum(function ($location) {
            return $location->longitude * $location->reputation_weight;
        }) / $totalWeight;

        // Calculate confidence metrics
        $averageAccuracy = $bestGroup->avg('accuracy');
        $locationCount = $bestGroup->count();
        $trustedCount = $bestGroup->where('reputation_weight', '>', 0.7)->count();

        return [
            'latitude' => round($weightedLat, 8),
            'longitude' => round($weightedLng, 8),
            'confidence_level' => min(1.0, $totalWeight / $locationCount),
            'location_count' => $locationCount,
            'trusted_count' => $trustedCount,
            'average_accuracy' => round($averageAccuracy, 2),
            'total_weight' => round($totalWeight, 3),
            'last_updated' => now()
        ];
    }

    /**
     * Group locations by proximity
     */
    private function groupLocationsByProximity($locations): array
    {
        $groups = [];
        
        foreach ($locations as $location) {
            $addedToGroup = false;
            
            // Try to add to existing group
            foreach ($groups as &$group) {
                $groupCenter = $this->calculateGroupCenter($group);
                $distance = $this->calculateDistance(
                    $location->latitude,
                    $location->longitude,
                    $groupCenter['lat'],
                    $groupCenter['lng']
                );
                
                if ($distance <= self::AGGREGATION_RADIUS_METERS) {
                    $group->push($location);
                    $addedToGroup = true;
                    break;
                }
            }
            
            // Create new group if not added to existing one
            if (!$addedToGroup) {
                $groups[] = collect([$location]);
            }
        }
        
        return $groups;
    }

    /**
     * Calculate center of a location group
     */
    private function calculateGroupCenter($group): array
    {
        return [
            'lat' => $group->avg('latitude'),
            'lng' => $group->avg('longitude')
        ];
    }

    /**
     * Update bus current position cache
     */
    private function updateBusCurrentPosition(string $busId, ?array $aggregatedPosition): void
    {
        if (!$aggregatedPosition) {
            return;
        }

        $cacheKey = "bus_position_{$busId}";
        $cacheData = [
            'bus_id' => $busId,
            'latitude' => $aggregatedPosition['latitude'],
            'longitude' => $aggregatedPosition['longitude'],
            'confidence_level' => $aggregatedPosition['confidence_level'],
            'location_count' => $aggregatedPosition['location_count'],
            'trusted_count' => $aggregatedPosition['trusted_count'],
            'last_updated' => $aggregatedPosition['last_updated']->toISOString(),
            'status' => 'active'
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $cacheData, now()->addMinutes(5));

        // Also update database cache table if it exists
        try {
            \DB::table('bus_current_positions')->updateOrInsert(
                ['bus_id' => $busId],
                [
                    'latitude' => $aggregatedPosition['latitude'],
                    'longitude' => $aggregatedPosition['longitude'],
                    'confidence_level' => $aggregatedPosition['confidence_level'],
                    'last_updated' => now(),
                    'active_trackers' => $aggregatedPosition['location_count'],
                    'trusted_trackers' => $aggregatedPosition['trusted_count'],
                    'status' => 'active'
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to update bus_current_positions table', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);
        }
    }

    /**
     * Get current bus positions for all active buses
     */
    public function getCurrentBusPositions(): array
    {
        $positions = [];
        
        // Get all bus IDs that have recent location data
        $activeBuses = BusLocation::where('created_at', '>', now()->subMinutes(self::LOCATION_TIMEOUT_MINUTES))
            ->distinct()
            ->pluck('bus_id');

        foreach ($activeBuses as $busId) {
            $cacheKey = "bus_position_{$busId}";
            $cachedPosition = Cache::get($cacheKey);
            
            if ($cachedPosition) {
                $positions[$busId] = $cachedPosition;
            } else {
                // Recalculate if not in cache
                $aggregatedPosition = $this->aggregateLocationData($busId);
                if ($aggregatedPosition) {
                    $this->updateBusCurrentPosition($busId, $aggregatedPosition);
                    $positions[$busId] = Cache::get($cacheKey);
                }
            }
        }

        return $positions;
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
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
     * Clean up old location data
     */
    public function cleanupOldLocations(): int
    {
        // Delete locations older than 24 hours
        $deleted = BusLocation::where('created_at', '<', now()->subHours(24))->delete();
        
        Log::info("Cleaned up {$deleted} old location records");
        
        return $deleted;
    }

    /**
     * Get location statistics for monitoring
     */
    public function getLocationStatistics(): array
    {
        $stats = [
            'total_locations_today' => BusLocation::whereDate('created_at', today())->count(),
            'active_buses' => BusLocation::where('created_at', '>', now()->subMinutes(self::LOCATION_TIMEOUT_MINUTES))
                ->distinct('bus_id')->count(),
            'active_devices' => BusLocation::where('created_at', '>', now()->subMinutes(self::LOCATION_TIMEOUT_MINUTES))
                ->distinct('device_token')->count(),
            'average_accuracy' => BusLocation::where('created_at', '>', now()->subHour())
                ->avg('accuracy'),
            'high_reputation_percentage' => BusLocation::where('created_at', '>', now()->subHour())
                ->where('reputation_weight', '>', 0.7)->count() / 
                max(1, BusLocation::where('created_at', '>', now()->subHour())->count()) * 100
        ];

        return array_map(function ($value) {
            return is_numeric($value) ? round($value, 2) : $value;
        }, $stats);
    }
}