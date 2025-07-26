<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Stoppage Coordinate Validation System
 * Handles geofencing validation and route corridor checking for bus stops
 */
class StoppageCoordinateValidator
{
    /**
     * Precise coordinates and radius for each bus stop in Dhaka
     */
    private const BUS_STOPS = [
        'Asad Gate' => [
            'lat' => 23.7651,
            'lng' => 90.3668,
            'radius' => 200, // meters
            'aliases' => ['asad_gate', 'asadgate']
        ],
        'Shyamoli' => [
            'lat' => 23.7746,
            'lng' => 90.3657,
            'radius' => 250, // meters
            'aliases' => ['shyamoli_square', 'shyamoli']
        ],
        'Mirpur-1' => [
            'lat' => 23.7937,
            'lng' => 90.3629,
            'radius' => 300, // meters (larger area due to traffic circle)
            'aliases' => ['mirpur_1', 'mirpur1', 'mirpur_one']
        ],
        'Rainkhola' => [
            'lat' => 23.8069,
            'lng' => 90.3554,
            'radius' => 200, // meters
            'aliases' => ['rain_khola', 'rainkhola_bridge']
        ],
        'BUBT' => [
            'lat' => 23.8213,
            'lng' => 90.3541,
            'radius' => 150, // meters (campus area)
            'aliases' => ['bubt_campus', 'bangladesh_university']
        ]
    ];

    /**
     * Route corridors between stops for path validation
     */
    private const ROUTE_CORRIDORS = [
        'Asad Gate -> Shyamoli' => [
            'start' => 'Asad Gate',
            'end' => 'Shyamoli',
            'corridor_width' => 500, // meters on each side of the path
            'waypoints' => [
                ['lat' => 23.7696, 'lng' => 90.3662],
                ['lat' => 23.7721, 'lng' => 90.3660]
            ]
        ],
        'Shyamoli -> Mirpur-1' => [
            'start' => 'Shyamoli',
            'end' => 'Mirpur-1',
            'corridor_width' => 400,
            'waypoints' => [
                ['lat' => 23.7842, 'lng' => 90.3643],
                ['lat' => 23.7890, 'lng' => 90.3636]
            ]
        ],
        'Mirpur-1 -> Rainkhola' => [
            'start' => 'Mirpur-1',
            'end' => 'Rainkhola',
            'corridor_width' => 350,
            'waypoints' => [
                ['lat' => 23.8003, 'lng' => 90.3592],
                ['lat' => 23.8036, 'lng' => 90.3573]
            ]
        ],
        'Rainkhola -> BUBT' => [
            'start' => 'Rainkhola',
            'end' => 'BUBT',
            'corridor_width' => 300,
            'waypoints' => [
                ['lat' => 23.8141, 'lng' => 90.3548]
            ]
        ]
    ];

    /**
     * Earth's radius in meters for distance calculations
     */
    private const EARTH_RADIUS = 6371000;

    /**
     * Validate if GPS coordinates are within a bus stop radius
     *
     * @param float $lat User's latitude
     * @param float $lng User's longitude
     * @param string|null $expectedStop Expected stop name (optional)
     * @return array Validation result with details
     */
    public function validateStoppageRadius(float $lat, float $lng, ?string $expectedStop = null): array
    {
        $result = [
            'is_valid' => false,
            'closest_stop' => null,
            'distance_to_closest' => null,
            'within_radius' => false,
            'expected_stop_match' => false,
            'validation_details' => []
        ];

        $closestStop = null;
        $minDistance = PHP_FLOAT_MAX;

        // Check distance to all bus stops
        foreach (self::BUS_STOPS as $stopName => $stopData) {
            $distance = $this->calculateDistance($lat, $lng, $stopData['lat'], $stopData['lng']);
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $stopName;
            }

            // Check if within radius of this stop
            if ($distance <= $stopData['radius']) {
                $result['is_valid'] = true;
                $result['within_radius'] = true;
                $result['validation_details'][] = [
                    'stop' => $stopName,
                    'distance' => round($distance, 2),
                    'radius' => $stopData['radius'],
                    'within_radius' => true
                ];

                // Check if matches expected stop
                if ($expectedStop && $this->isStopMatch($stopName, $expectedStop)) {
                    $result['expected_stop_match'] = true;
                }
            } else {
                $result['validation_details'][] = [
                    'stop' => $stopName,
                    'distance' => round($distance, 2),
                    'radius' => $stopData['radius'],
                    'within_radius' => false
                ];
            }
        }

        $result['closest_stop'] = $closestStop;
        $result['distance_to_closest'] = round($minDistance, 2);

        // Log validation attempt for monitoring
        Log::info('Stoppage validation', [
            'lat' => $lat,
            'lng' => $lng,
            'expected_stop' => $expectedStop,
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Validate if user is within expected route corridor between stops
     *
     * @param float $lat User's latitude
     * @param float $lng User's longitude
     * @param string $fromStop Starting stop
     * @param string $toStop Destination stop
     * @return array Validation result
     */
    public function validateRouteCorridorPath(float $lat, float $lng, string $fromStop, string $toStop): array
    {
        $corridorKey = "$fromStop -> $toStop";
        
        $result = [
            'is_valid' => false,
            'within_corridor' => false,
            'distance_from_path' => null,
            'corridor_width' => null,
            'path_progress' => null
        ];

        // Check if corridor exists
        if (!isset(self::ROUTE_CORRIDORS[$corridorKey])) {
            $result['error'] = "Route corridor not defined for $corridorKey";
            return $result;
        }

        $corridor = self::ROUTE_CORRIDORS[$corridorKey];
        $result['corridor_width'] = $corridor['corridor_width'];

        // Build complete path including start, waypoints, and end
        $pathPoints = [];
        $pathPoints[] = self::BUS_STOPS[$fromStop];
        
        foreach ($corridor['waypoints'] as $waypoint) {
            $pathPoints[] = $waypoint;
        }
        
        $pathPoints[] = self::BUS_STOPS[$toStop];

        // Find closest point on the path
        $minDistanceToPath = PHP_FLOAT_MAX;
        $pathProgress = 0;

        for ($i = 0; $i < count($pathPoints) - 1; $i++) {
            $segmentStart = $pathPoints[$i];
            $segmentEnd = $pathPoints[$i + 1];
            
            $distanceToSegment = $this->distanceToLineSegment(
                $lat, $lng,
                $segmentStart['lat'], $segmentStart['lng'],
                $segmentEnd['lat'], $segmentEnd['lng']
            );

            if ($distanceToSegment < $minDistanceToPath) {
                $minDistanceToPath = $distanceToSegment;
                $pathProgress = ($i + 1) / count($pathPoints); // Rough progress calculation
            }
        }

        $result['distance_from_path'] = round($minDistanceToPath, 2);
        $result['path_progress'] = round($pathProgress * 100, 1); // Percentage

        // Check if within corridor width
        if ($minDistanceToPath <= $corridor['corridor_width']) {
            $result['is_valid'] = true;
            $result['within_corridor'] = true;
        }

        return $result;
    }

    /**
     * Get all bus stops with their coordinates and coverage areas
     *
     * @return array All bus stops data
     */
    public function getAllBusStops(): array
    {
        return self::BUS_STOPS;
    }

    /**
     * Get route corridors for admin panel visualization
     *
     * @return array All route corridors
     */
    public function getRouteCorriders(): array
    {
        return self::ROUTE_CORRIDORS;
    }

    /**
     * Validate GPS coordinates against expected bus route
     *
     * @param float $lat User's latitude
     * @param float $lng User's longitude
     * @param string $busId Bus identifier
     * @param string $direction Trip direction (departure/return)
     * @return array Comprehensive validation result
     */
    public function validateAgainstBusRoute(float $lat, float $lng, string $busId, string $direction = 'departure'): array
    {
        // Get expected route for this bus and direction
        $expectedRoute = $this->getBusRoute($busId, $direction);
        
        $result = [
            'is_valid' => false,
            'on_expected_route' => false,
            'current_segment' => null,
            'next_expected_stop' => null,
            'route_adherence_score' => 0,
            'flags' => []
        ];

        if (empty($expectedRoute)) {
            $result['flags'][] = 'Unknown bus route';
            return $result;
        }

        // Check against each route segment
        $bestMatch = null;
        $bestScore = 0;

        for ($i = 0; $i < count($expectedRoute) - 1; $i++) {
            $fromStop = $expectedRoute[$i];
            $toStop = $expectedRoute[$i + 1];
            
            $corridorValidation = $this->validateRouteCorridorPath($lat, $lng, $fromStop, $toStop);
            
            if ($corridorValidation['within_corridor']) {
                $score = 100 - ($corridorValidation['distance_from_path'] / $corridorValidation['corridor_width'] * 100);
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'from' => $fromStop,
                        'to' => $toStop,
                        'progress' => $corridorValidation['path_progress']
                    ];
                }
            }
        }

        if ($bestMatch) {
            $result['is_valid'] = true;
            $result['on_expected_route'] = true;
            $result['current_segment'] = $bestMatch;
            $result['next_expected_stop'] = $bestMatch['to'];
            $result['route_adherence_score'] = round($bestScore, 1);
        } else {
            // Check if at least near a valid stop
            $stoppageValidation = $this->validateStoppageRadius($lat, $lng);
            if ($stoppageValidation['within_radius']) {
                $result['is_valid'] = true;
                $result['flags'][] = 'At valid stop but not on expected route segment';
                $result['route_adherence_score'] = 50; // Partial score
            } else {
                $result['flags'][] = 'Outside expected route and stops';
            }
        }

        return $result;
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     *
     * @param float $lat1 First point latitude
     * @param float $lng1 First point longitude
     * @param float $lat2 Second point latitude
     * @param float $lng2 Second point longitude
     * @return float Distance in meters
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
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

        return self::EARTH_RADIUS * $c;
    }

    /**
     * Calculate distance from a point to a line segment
     *
     * @param float $px Point x (longitude)
     * @param float $py Point y (latitude)
     * @param float $x1 Line start x
     * @param float $y1 Line start y
     * @param float $x2 Line end x
     * @param float $y2 Line end y
     * @return float Distance in meters
     */
    private function distanceToLineSegment(float $py, float $px, float $y1, float $x1, float $y2, float $x2): float
    {
        // Convert to approximate meters for calculation
        $A = $px - $x1;
        $B = $py - $y1;
        $C = $x2 - $x1;
        $D = $y2 - $y1;

        $dot = $A * $C + $B * $D;
        $lenSq = $C * $C + $D * $D;
        
        if ($lenSq == 0) {
            // Line segment is actually a point
            return $this->calculateDistance($py, $px, $y1, $x1);
        }

        $param = $dot / $lenSq;

        if ($param < 0) {
            // Closest point is start of segment
            return $this->calculateDistance($py, $px, $y1, $x1);
        } elseif ($param > 1) {
            // Closest point is end of segment
            return $this->calculateDistance($py, $px, $y2, $x2);
        } else {
            // Closest point is on the segment
            $closestX = $x1 + $param * $C;
            $closestY = $y1 + $param * $D;
            return $this->calculateDistance($py, $px, $closestY, $closestX);
        }
    }

    /**
     * Check if stop name matches expected stop (handles aliases)
     *
     * @param string $stopName Actual stop name
     * @param string $expectedStop Expected stop name
     * @return bool True if matches
     */
    private function isStopMatch(string $stopName, string $expectedStop): bool
    {
        $expectedLower = strtolower(trim($expectedStop));
        $stopLower = strtolower(trim($stopName));

        if ($stopLower === $expectedLower) {
            return true;
        }

        // Check aliases
        if (isset(self::BUS_STOPS[$stopName]['aliases'])) {
            foreach (self::BUS_STOPS[$stopName]['aliases'] as $alias) {
                if (strtolower($alias) === $expectedLower) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get bus route based on bus ID and direction
     *
     * @param string $busId Bus identifier
     * @param string $direction Trip direction
     * @return array Route stops in order
     */
    private function getBusRoute(string $busId, string $direction): array
    {
        // Standard route for all buses (can be customized per bus later)
        $departureRoute = ['Asad Gate', 'Shyamoli', 'Mirpur-1', 'Rainkhola', 'BUBT'];
        $returnRoute = array_reverse($departureRoute);

        return $direction === 'return' ? $returnRoute : $departureRoute;
    }

    /**
     * Generate geofencing boundaries for admin panel visualization
     *
     * @return array GeoJSON-like structure for map display
     */
    public function generateGeofencingBoundaries(): array
    {
        $boundaries = [];

        foreach (self::BUS_STOPS as $stopName => $stopData) {
            $boundaries[] = [
                'type' => 'circle',
                'name' => $stopName,
                'center' => [
                    'lat' => $stopData['lat'],
                    'lng' => $stopData['lng']
                ],
                'radius' => $stopData['radius'],
                'style' => [
                    'color' => '#1a73e8',
                    'fillColor' => 'rgba(26, 115, 232, 0.2)',
                    'weight' => 2
                ]
            ];
        }

        // Add route corridors
        foreach (self::ROUTE_CORRIDORS as $corridorName => $corridorData) {
            $pathPoints = [];
            $pathPoints[] = self::BUS_STOPS[$corridorData['start']];
            
            foreach ($corridorData['waypoints'] as $waypoint) {
                $pathPoints[] = $waypoint;
            }
            
            $pathPoints[] = self::BUS_STOPS[$corridorData['end']];

            $boundaries[] = [
                'type' => 'corridor',
                'name' => $corridorName,
                'path' => $pathPoints,
                'width' => $corridorData['corridor_width'],
                'style' => [
                    'color' => '#ff6b35',
                    'fillColor' => 'rgba(255, 107, 53, 0.1)',
                    'weight' => 1,
                    'dashArray' => '5, 5'
                ]
            ];
        }

        return $boundaries;
    }
}