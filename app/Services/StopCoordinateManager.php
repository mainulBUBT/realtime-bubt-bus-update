<?php

namespace App\Services;

use App\Models\BusRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Stop Coordinate Manager
 * Manages bus stop coordinates and coverage radius for route validation
 */
class StopCoordinateManager
{
    // Predefined stop coordinates for Dhaka bus routes
    private const STOP_COORDINATES = [
        'Asad Gate' => [
            'latitude' => 23.7651,
            'longitude' => 90.3668,
            'default_radius' => 200,
            'aliases' => ['asad_gate', 'asadgate', 'asad'],
            'landmarks' => ['Asad Gate Circle', 'Dhanmondi Road']
        ],
        'Shyamoli' => [
            'latitude' => 23.7746,
            'longitude' => 90.3657,
            'default_radius' => 250,
            'aliases' => ['shyamoli_square', 'shyamoli', 'shamoli'],
            'landmarks' => ['Shyamoli Square', 'Ring Road']
        ],
        'Mirpur-1' => [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'default_radius' => 300,
            'aliases' => ['mirpur_1', 'mirpur1', 'mirpur_one', 'mirpur-1'],
            'landmarks' => ['Mirpur-1 Circle', 'Mirpur Road']
        ],
        'Rainkhola' => [
            'latitude' => 23.8069,
            'longitude' => 90.3554,
            'default_radius' => 200,
            'aliases' => ['rain_khola', 'rainkhola_bridge', 'rainkhola'],
            'landmarks' => ['Rainkhola Bridge', 'Turag River']
        ],
        'BUBT' => [
            'latitude' => 23.8213,
            'longitude' => 90.3541,
            'default_radius' => 150,
            'aliases' => ['bubt_campus', 'bangladesh_university', 'bubt'],
            'landmarks' => ['BUBT Campus', 'Mirpur-2']
        ]
    ];

    /**
     * Get coordinates for a specific stop
     *
     * @param string $stopName Stop name
     * @return array|null Stop coordinates and details
     */
    public function getStopCoordinates(string $stopName): ?array
    {
        $normalizedName = $this->normalizeStopName($stopName);
        
        // Direct match
        if (isset(self::STOP_COORDINATES[$normalizedName])) {
            return array_merge(self::STOP_COORDINATES[$normalizedName], [
                'stop_name' => $normalizedName,
                'source' => 'predefined'
            ]);
        }

        // Alias match
        foreach (self::STOP_COORDINATES as $name => $data) {
            if (in_array(strtolower($stopName), array_map('strtolower', $data['aliases']))) {
                return array_merge($data, [
                    'stop_name' => $name,
                    'matched_alias' => $stopName,
                    'source' => 'alias'
                ]);
            }
        }

        // Database lookup
        $dbStop = BusRoute::where('stop_name', 'LIKE', "%{$stopName}%")->first();
        if ($dbStop) {
            return [
                'stop_name' => $dbStop->stop_name,
                'latitude' => $dbStop->latitude,
                'longitude' => $dbStop->longitude,
                'default_radius' => $dbStop->coverage_radius ?? 200,
                'source' => 'database',
                'schedule_id' => $dbStop->schedule_id
            ];
        }

        return null;
    }

    /**
     * Get all available stop coordinates
     *
     * @return array All stop coordinates
     */
    public function getAllStopCoordinates(): array
    {
        $stops = [];
        
        foreach (self::STOP_COORDINATES as $name => $data) {
            $stops[$name] = array_merge($data, [
                'stop_name' => $name,
                'source' => 'predefined'
            ]);
        }

        // Add database stops that aren't in predefined list
        $dbStops = BusRoute::whereNotIn('stop_name', array_keys(self::STOP_COORDINATES))->get();
        
        foreach ($dbStops as $stop) {
            $stops[$stop->stop_name] = [
                'stop_name' => $stop->stop_name,
                'latitude' => $stop->latitude,
                'longitude' => $stop->longitude,
                'default_radius' => $stop->coverage_radius ?? 200,
                'source' => 'database',
                'schedule_id' => $stop->schedule_id,
                'stop_order' => $stop->stop_order
            ];
        }

        return $stops;
    }

    /**
     * Calculate distance between two stops
     *
     * @param string $stop1Name First stop name
     * @param string $stop2Name Second stop name
     * @return array Distance calculation result
     */
    public function calculateDistanceBetweenStops(string $stop1Name, string $stop2Name): array
    {
        $stop1 = $this->getStopCoordinates($stop1Name);
        $stop2 = $this->getStopCoordinates($stop2Name);

        if (!$stop1 || !$stop2) {
            return [
                'success' => false,
                'message' => 'One or both stops not found',
                'distance' => null
            ];
        }

        $distance = $this->calculateDistance(
            $stop1['latitude'], $stop1['longitude'],
            $stop2['latitude'], $stop2['longitude']
        );

        return [
            'success' => true,
            'distance_meters' => round($distance, 2),
            'distance_km' => round($distance / 1000, 3),
            'from_stop' => $stop1['stop_name'],
            'to_stop' => $stop2['stop_name'],
            'coordinates' => [
                'from' => ['lat' => $stop1['latitude'], 'lng' => $stop1['longitude']],
                'to' => ['lat' => $stop2['latitude'], 'lng' => $stop2['longitude']]
            ]
        ];
    }

    /**
     * Validate if coordinates are within stop coverage radius
     *
     * @param float $latitude GPS latitude
     * @param float $longitude GPS longitude
     * @param string $stopName Stop name to check against
     * @param int|null $customRadius Custom radius override
     * @return array Validation result
     */
    public function validateCoordinatesWithinStopRadius(
        float $latitude, 
        float $longitude, 
        string $stopName, 
        ?int $customRadius = null
    ): array {
        $stop = $this->getStopCoordinates($stopName);
        
        if (!$stop) {
            return [
                'valid' => false,
                'reason' => 'stop_not_found',
                'message' => "Stop '{$stopName}' not found",
                'distance' => null
            ];
        }

        $distance = $this->calculateDistance(
            $latitude, $longitude,
            $stop['latitude'], $stop['longitude']
        );

        $radius = $customRadius ?? $stop['default_radius'];
        $isValid = $distance <= $radius;

        return [
            'valid' => $isValid,
            'distance' => round($distance, 2),
            'radius' => $radius,
            'stop_name' => $stop['stop_name'],
            'coverage_percentage' => min(100, (($radius - $distance) / $radius) * 100),
            'message' => $isValid ? 
                "Within {$stop['stop_name']} coverage area" : 
                "Outside {$stop['stop_name']} coverage area by " . round($distance - $radius, 2) . "m"
        ];
    }

    /**
     * Find nearest stop to given coordinates
     *
     * @param float $latitude GPS latitude
     * @param float $longitude GPS longitude
     * @param int $maxResults Maximum number of results
     * @return array Nearest stops with distances
     */
    public function findNearestStops(float $latitude, float $longitude, int $maxResults = 5): array
    {
        $allStops = $this->getAllStopCoordinates();
        $distances = [];

        foreach ($allStops as $stop) {
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                $stop['latitude'], $stop['longitude']
            );

            $distances[] = [
                'stop' => $stop,
                'distance' => $distance,
                'within_radius' => $distance <= $stop['default_radius']
            ];
        }

        // Sort by distance
        usort($distances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return array_slice($distances, 0, $maxResults);
    }

    /**
     * Get route corridor coordinates between stops
     *
     * @param string $fromStop Starting stop name
     * @param string $toStop Ending stop name
     * @param int $corridorWidth Corridor width in meters
     * @return array Corridor coordinates and details
     */
    public function getRouteCorridorCoordinates(string $fromStop, string $toStop, int $corridorWidth = 500): array
    {
        $from = $this->getStopCoordinates($fromStop);
        $to = $this->getStopCoordinates($toStop);

        if (!$from || !$to) {
            return [
                'success' => false,
                'message' => 'One or both stops not found',
                'corridor' => null
            ];
        }

        // Calculate corridor boundaries
        $distance = $this->calculateDistance(
            $from['latitude'], $from['longitude'],
            $to['latitude'], $to['longitude']
        );

        // Calculate bearing
        $bearing = $this->calculateBearing(
            $from['latitude'], $from['longitude'],
            $to['latitude'], $to['longitude']
        );

        // Calculate perpendicular bearings for corridor boundaries
        $leftBearing = fmod($bearing - 90 + 360, 360);
        $rightBearing = fmod($bearing + 90, 360);

        // Calculate corridor boundary points
        $corridorPoints = $this->generateCorridorBoundary(
            $from, $to, $corridorWidth, $leftBearing, $rightBearing
        );

        return [
            'success' => true,
            'from_stop' => $from['stop_name'],
            'to_stop' => $to['stop_name'],
            'corridor_width' => $corridorWidth,
            'distance' => round($distance, 2),
            'bearing' => round($bearing, 2),
            'corridor_points' => $corridorPoints,
            'center_line' => [
                'start' => ['lat' => $from['latitude'], 'lng' => $from['longitude']],
                'end' => ['lat' => $to['latitude'], 'lng' => $to['longitude']]
            ]
        ];
    }

    /**
     * Update stop coordinates in database
     *
     * @param string $stopName Stop name
     * @param float $latitude New latitude
     * @param float $longitude New longitude
     * @param int $radius Coverage radius
     * @return array Update result
     */
    public function updateStopCoordinates(string $stopName, float $latitude, float $longitude, int $radius): array
    {
        try {
            // Validate coordinates
            if (!$this->isValidCoordinate($latitude, $longitude)) {
                return [
                    'success' => false,
                    'message' => 'Invalid coordinates provided'
                ];
            }

            // Update database records
            $updated = BusRoute::where('stop_name', $stopName)
                ->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'coverage_radius' => $radius
                ]);

            if ($updated > 0) {
                // Clear related caches
                $this->clearStopCache($stopName);
                
                Log::info('Stop coordinates updated', [
                    'stop_name' => $stopName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $radius,
                    'records_updated' => $updated
                ]);

                return [
                    'success' => true,
                    'message' => "Updated {$updated} records for stop '{$stopName}'",
                    'records_updated' => $updated
                ];
            }

            return [
                'success' => false,
                'message' => "No records found for stop '{$stopName}'"
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update stop coordinates', [
                'error' => $e->getMessage(),
                'stop_name' => $stopName
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update stop coordinates'
            ];
        }
    }

    /**
     * Generate visualization data for admin panel
     *
     * @return array Visualization data
     */
    public function generateVisualizationData(): array
    {
        $allStops = $this->getAllStopCoordinates();
        $visualizationData = [
            'stops' => [],
            'corridors' => [],
            'coverage_areas' => []
        ];

        // Add stop markers
        foreach ($allStops as $stop) {
            $visualizationData['stops'][] = [
                'name' => $stop['stop_name'],
                'coordinates' => [
                    'lat' => $stop['latitude'],
                    'lng' => $stop['longitude']
                ],
                'radius' => $stop['default_radius'],
                'source' => $stop['source'],
                'aliases' => $stop['aliases'] ?? [],
                'landmarks' => $stop['landmarks'] ?? []
            ];

            // Add coverage area circles
            $visualizationData['coverage_areas'][] = [
                'center' => [
                    'lat' => $stop['latitude'],
                    'lng' => $stop['longitude']
                ],
                'radius' => $stop['default_radius'],
                'stop_name' => $stop['stop_name'],
                'style' => [
                    'color' => '#1a73e8',
                    'fillColor' => 'rgba(26, 115, 232, 0.2)',
                    'weight' => 2
                ]
            ];
        }

        // Add route corridors between sequential stops
        $stopNames = array_keys(self::STOP_COORDINATES);
        for ($i = 0; $i < count($stopNames) - 1; $i++) {
            $corridor = $this->getRouteCorridorCoordinates($stopNames[$i], $stopNames[$i + 1]);
            
            if ($corridor['success']) {
                $visualizationData['corridors'][] = [
                    'from' => $corridor['from_stop'],
                    'to' => $corridor['to_stop'],
                    'center_line' => $corridor['center_line'],
                    'corridor_points' => $corridor['corridor_points'],
                    'width' => $corridor['corridor_width'],
                    'style' => [
                        'color' => '#ff6b35',
                        'fillColor' => 'rgba(255, 107, 53, 0.1)',
                        'weight' => 1,
                        'dashArray' => '5, 5'
                    ]
                ];
            }
        }

        return $visualizationData;
    }

    /**
     * Private helper methods
     */

    /**
     * Normalize stop name for consistent matching
     */
    private function normalizeStopName(string $stopName): string
    {
        // Convert to title case and handle common variations
        $normalized = ucwords(strtolower(trim($stopName)));
        
        // Handle specific cases
        $replacements = [
            'Mirpur 1' => 'Mirpur-1',
            'Mirpur1' => 'Mirpur-1',
            'Bubt' => 'BUBT',
            'Rain Khola' => 'Rainkhola'
        ];

        return $replacements[$normalized] ?? $normalized;
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
     * Generate corridor boundary points
     */
    private function generateCorridorBoundary(array $from, array $to, int $width, float $leftBearing, float $rightBearing): array
    {
        $halfWidth = $width / 2;
        
        // Calculate offset points
        $fromLeft = $this->calculateDestinationPoint($from['latitude'], $from['longitude'], $leftBearing, $halfWidth);
        $fromRight = $this->calculateDestinationPoint($from['latitude'], $from['longitude'], $rightBearing, $halfWidth);
        $toLeft = $this->calculateDestinationPoint($to['latitude'], $to['longitude'], $leftBearing, $halfWidth);
        $toRight = $this->calculateDestinationPoint($to['latitude'], $to['longitude'], $rightBearing, $halfWidth);

        return [
            'left_boundary' => [
                'start' => $fromLeft,
                'end' => $toLeft
            ],
            'right_boundary' => [
                'start' => $fromRight,
                'end' => $toRight
            ],
            'polygon' => [
                $fromLeft,
                $toLeft,
                $toRight,
                $fromRight,
                $fromLeft // Close the polygon
            ]
        ];
    }

    /**
     * Calculate destination point given start point, bearing, and distance
     */
    private function calculateDestinationPoint(float $lat, float $lng, float $bearing, float $distance): array
    {
        $earthRadius = 6371000; // meters
        
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $bearingRad = deg2rad($bearing);
        
        $angularDistance = $distance / $earthRadius;
        
        $destLatRad = asin(sin($latRad) * cos($angularDistance) + 
                          cos($latRad) * sin($angularDistance) * cos($bearingRad));
        
        $destLngRad = $lngRad + atan2(sin($bearingRad) * sin($angularDistance) * cos($latRad),
                                     cos($angularDistance) - sin($latRad) * sin($destLatRad));
        
        return [
            'lat' => rad2deg($destLatRad),
            'lng' => rad2deg($destLngRad)
        ];
    }

    /**
     * Validate coordinate bounds
     */
    private function isValidCoordinate(float $lat, float $lng): bool
    {
        // Basic coordinate bounds
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }
        
        // Check if within Bangladesh bounds
        if ($lat < 20.5 || $lat > 26.5 || $lng < 88.0 || $lng > 92.7) {
            return false;
        }
        
        return true;
    }

    /**
     * Clear stop-related caches
     */
    private function clearStopCache(string $stopName): void
    {
        $patterns = [
            "stop_coordinates_{$stopName}",
            "nearest_stops_*",
            "route_corridor_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}