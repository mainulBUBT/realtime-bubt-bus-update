<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RouteValidator;
use App\Services\BusScheduleService;
use App\Models\BusLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RouteValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected RouteValidator $routeValidator;
    protected $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scheduleService = $this->createMock(BusScheduleService::class);
        $this->routeValidator = new RouteValidator($this->scheduleService);
    }

    public function test_validates_route_progression_for_active_bus()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';
        $timestamp = now();

        // Mock schedule service to return active trip
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100
                    ],
                    [
                        'id' => 2,
                        'stop_name' => 'City Center',
                        'stop_order' => 2,
                        'latitude' => 23.8000,
                        'longitude' => 90.3700,
                        'coverage_radius' => 100
                    ]
                ]
            ]);

        $result = $this->routeValidator->validateRouteProgression($latitude, $longitude, $busId, $timestamp);

        $this->assertTrue($result['valid']);
        $this->assertEquals('departure', $result['trip_direction']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertArrayHasKey('closest_stop', $result);
        $this->assertArrayHasKey('corridor_validation', $result);
    }

    public function test_rejects_route_progression_for_inactive_bus()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service to return inactive bus
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn(['direction' => null]);

        $result = $this->routeValidator->validateRouteProgression($latitude, $longitude, $busId);

        $this->assertFalse($result['valid']);
        $this->assertEquals('bus_not_active', $result['reason']);
        $this->assertEquals('Bus is not currently active', $result['message']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    public function test_rejects_route_progression_with_no_route_data()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service to return active bus but no route data
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => []
            ]);

        $result = $this->routeValidator->validateRouteProgression($latitude, $longitude, $busId);

        $this->assertFalse($result['valid']);
        $this->assertEquals('no_route_data', $result['reason']);
        $this->assertEquals('No route data available for this bus', $result['message']);
    }

    public function test_validates_route_corridor_between_stops()
    {
        $latitude = 23.7968; // Midpoint between stops
        $longitude = 90.3664;
        $busId = 'B1';
        $fromStopOrder = 1;
        $toStopOrder = 2;

        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100
                    ],
                    [
                        'id' => 2,
                        'stop_name' => 'City Center',
                        'stop_order' => 2,
                        'latitude' => 23.8000,
                        'longitude' => 90.3700,
                        'coverage_radius' => 100
                    ]
                ]
            ]);

        $result = $this->routeValidator->validateRouteCorridorBetweenStops(
            $latitude, $longitude, $busId, $fromStopOrder, $toStopOrder
        );

        $this->assertTrue($result['valid']);
        $this->assertLessThan(500, $result['distance_from_corridor']); // Within corridor width
        $this->assertEquals('Campus', $result['from_stop']);
        $this->assertEquals('City Center', $result['to_stop']);
        $this->assertGreaterThan(50, $result['adherence_percentage']);
    }

    public function test_rejects_coordinates_far_from_route_corridor()
    {
        $latitude = 23.8500; // Far from route
        $longitude = 90.4500;
        $busId = 'B1';
        $fromStopOrder = 1;
        $toStopOrder = 2;

        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100
                    ],
                    [
                        'id' => 2,
                        'stop_name' => 'City Center',
                        'stop_order' => 2,
                        'latitude' => 23.8000,
                        'longitude' => 90.3700,
                        'coverage_radius' => 100
                    ]
                ]
            ]);

        $result = $this->routeValidator->validateRouteCorridorBetweenStops(
            $latitude, $longitude, $busId, $fromStopOrder, $toStopOrder
        );

        $this->assertFalse($result['valid']);
        $this->assertGreaterThan(500, $result['distance_from_corridor']); // Outside corridor width
        $this->assertLessThan(50, $result['adherence_percentage']);
    }

    public function test_validates_direction_aware_coordinates_for_departure()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';
        $expectedDirection = BusScheduleService::DIRECTION_DEPARTURE;

        // Mock schedule service to return matching direction
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => BusScheduleService::DIRECTION_DEPARTURE,
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100
                    ]
                ]
            ]);

        $result = $this->routeValidator->validateDirectionAwareCoordinates(
            $latitude, $longitude, $busId, $expectedDirection
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals($expectedDirection, $result['validated_direction']);
        $this->assertArrayHasKey('direction_bonus', $result);
        $this->assertGreaterThanOrEqual(0.8, $result['direction_bonus']);
    }

    public function test_rejects_direction_aware_coordinates_for_wrong_direction()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';
        $expectedDirection = BusScheduleService::DIRECTION_DEPARTURE;

        // Mock schedule service to return different direction
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => BusScheduleService::DIRECTION_RETURN
            ]);

        $result = $this->routeValidator->validateDirectionAwareCoordinates(
            $latitude, $longitude, $busId, $expectedDirection
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('direction_mismatch', $result['reason']);
        $this->assertEquals($expectedDirection, $result['expected_direction']);
        $this->assertEquals(BusScheduleService::DIRECTION_RETURN, $result['actual_direction']);
    }

    public function test_gets_expected_next_stops_based_on_current_location()
    {
        $latitude = 23.7937; // At first stop
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service with multiple stops
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100,
                        'estimated_time' => '07:00:00'
                    ],
                    [
                        'id' => 2,
                        'stop_name' => 'Stop 1',
                        'stop_order' => 2,
                        'latitude' => 23.8000,
                        'longitude' => 90.3700,
                        'coverage_radius' => 100,
                        'estimated_time' => '07:15:00'
                    ],
                    [
                        'id' => 3,
                        'stop_name' => 'Stop 2',
                        'stop_order' => 3,
                        'latitude' => 23.8100,
                        'longitude' => 90.3800,
                        'coverage_radius' => 100,
                        'estimated_time' => '07:30:00'
                    ],
                    [
                        'id' => 4,
                        'stop_name' => 'City Center',
                        'stop_order' => 4,
                        'latitude' => 23.8200,
                        'longitude' => 90.3900,
                        'coverage_radius' => 100,
                        'estimated_time' => '07:45:00'
                    ]
                ]
            ]);

        $result = $this->routeValidator->getExpectedNextStops($latitude, $longitude, $busId);

        $this->assertNotEmpty($result['next_stops']);
        $this->assertLessThanOrEqual(3, count($result['next_stops'])); // Maximum 3 next stops
        $this->assertEquals('Campus', $result['current_stop']['stop_name']);
        $this->assertEquals('Stop 1', $result['next_stops'][0]['stop_name']);
        $this->assertEquals('departure', $result['trip_direction']);
        
        // Check that distances are calculated
        foreach ($result['next_stops'] as $stop) {
            $this->assertArrayHasKey('distance_meters', $stop);
            $this->assertIsFloat($stop['distance_meters']);
        }
    }

    public function test_returns_empty_next_stops_for_inactive_bus()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service to return inactive bus
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn(['direction' => null]);

        $result = $this->routeValidator->getExpectedNextStops($latitude, $longitude, $busId);

        $this->assertEmpty($result['next_stops']);
        $this->assertEquals('Bus is not currently active', $result['message']);
    }

    public function test_analyzes_stop_progression_with_location_history()
    {
        $busId = 'B1';
        $currentStopOrder = 2;
        
        // Create location history showing progression
        $baseTime = now()->subMinutes(8);
        $locations = [
            ['lat' => 23.7937, 'lng' => 90.3629, 'time' => $baseTime], // Stop 1
            ['lat' => 23.7950, 'lng' => 90.3640, 'time' => $baseTime->copy()->addMinutes(2)], // Between stops
            ['lat' => 23.7970, 'lng' => 90.3660, 'time' => $baseTime->copy()->addMinutes(4)], // Closer to stop 2
            ['lat' => 23.8000, 'lng' => 90.3700, 'time' => $baseTime->copy()->addMinutes(6)] // Stop 2
        ];

        foreach ($locations as $location) {
            BusLocation::create([
                'bus_id' => $busId,
                'device_token' => hash('sha256', 'test_token'),
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'accuracy' => 20.0,
                'created_at' => $location['time']
            ]);
        }

        $routeStops = [
            [
                'id' => 1,
                'stop_name' => 'Campus',
                'stop_order' => 1,
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'coverage_radius' => 100
            ],
            [
                'id' => 2,
                'stop_name' => 'City Center',
                'stop_order' => 2,
                'latitude' => 23.8000,
                'longitude' => 90.3700,
                'coverage_radius' => 100
            ]
        ];

        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => $routeStops
            ]);

        $result = $this->routeValidator->validateRouteProgression(23.8000, 90.3700, $busId);

        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('progression_analysis', $result);
    }

    public function test_calculates_distance_between_coordinates_accurately()
    {
        // Test with known coordinates (approximately 1km apart)
        $lat1 = 23.7937;
        $lng1 = 90.3629;
        $lat2 = 23.8000; // Slightly north
        $lng2 = 90.3700; // Slightly east

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('calculateDistance');
        $method->setAccessible(true);

        $distance = $method->invokeArgs($this->routeValidator, [$lat1, $lng1, $lat2, $lng2]);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(500, $distance); // Should be more than 500m
        $this->assertLessThan(2000, $distance); // Should be less than 2km
    }

    public function test_calculates_distance_from_route_corridor_accurately()
    {
        // Test point on the line between two coordinates
        $pointLat = 23.7968; // Midpoint
        $pointLng = 90.3664;
        $line1Lat = 23.7937;
        $line1Lng = 90.3629;
        $line2Lat = 23.8000;
        $line2Lng = 90.3700;

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('calculateDistanceFromRouteCorridor');
        $method->setAccessible(true);

        $distance = $method->invokeArgs($this->routeValidator, [
            $pointLat, $pointLng, $line1Lat, $line1Lng, $line2Lat, $line2Lng
        ]);

        $this->assertIsFloat($distance);
        $this->assertLessThan(100, $distance); // Point should be close to the line
    }

    public function test_detects_progressing_sequence()
    {
        $stopOrders = [1, 2, 2, 3, 3, 4]; // Progressing sequence

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('isSequenceProgressing');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->routeValidator, [$stopOrders]);

        $this->assertTrue($result);
    }

    public function test_detects_backtracking_sequence()
    {
        $stopOrders = [4, 3, 2, 2, 1]; // Backtracking sequence

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('isSequenceBacktracking');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->routeValidator, [$stopOrders]);

        $this->assertTrue($result);
    }

    public function test_calculates_progression_confidence_correctly()
    {
        $consistentStopOrders = [1, 1, 2, 2, 3]; // Consistent progression
        $inconsistentStopOrders = [1, 3, 1, 4, 2]; // Inconsistent

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('calculateProgressionConfidence');
        $method->setAccessible(true);

        $consistentConfidence = $method->invokeArgs($this->routeValidator, [$consistentStopOrders]);
        $inconsistentConfidence = $method->invokeArgs($this->routeValidator, [$inconsistentStopOrders]);

        $this->assertGreaterThan($inconsistentConfidence, $consistentConfidence);
        $this->assertGreaterThan(0.5, $consistentConfidence);
        $this->assertLessThan(0.5, $inconsistentConfidence);
    }

    public function test_calculates_bearing_between_coordinates()
    {
        $lat1 = 23.7937;
        $lng1 = 90.3629;
        $lat2 = 23.8000; // North
        $lng2 = 90.3629; // Same longitude

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('calculateBearing');
        $method->setAccessible(true);

        $bearing = $method->invokeArgs($this->routeValidator, [$lat1, $lng1, $lat2, $lng2]);

        $this->assertIsFloat($bearing);
        $this->assertGreaterThanOrEqual(0, $bearing);
        $this->assertLessThan(360, $bearing);
        // Going north should be close to 0 degrees
        $this->assertLessThan(10, $bearing);
    }

    public function test_finds_closest_stop_to_coordinates()
    {
        $latitude = 23.7950; // Closer to first stop
        $longitude = 90.3640;
        
        $routeStops = [
            [
                'id' => 1,
                'stop_name' => 'Campus',
                'stop_order' => 1,
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'coverage_radius' => 100
            ],
            [
                'id' => 2,
                'stop_name' => 'City Center',
                'stop_order' => 2,
                'latitude' => 23.8100,
                'longitude' => 90.3800,
                'coverage_radius' => 100
            ]
        ];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('findClosestStopToCoordinates');
        $method->setAccessible(true);

        $closestStop = $method->invokeArgs($this->routeValidator, [$latitude, $longitude, $routeStops]);

        $this->assertEquals('Campus', $closestStop['stop_name']);
        $this->assertEquals(1, $closestStop['stop_order']);
    }

    public function test_calculates_direction_consistency()
    {
        $consistentDirections = [45, 50, 48, 52, 47]; // Similar directions
        $inconsistentDirections = [45, 135, 225, 315, 90]; // Very different directions

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->routeValidator);
        $method = $reflection->getMethod('calculateDirectionConsistency');
        $method->setAccessible(true);

        $consistentScore = $method->invokeArgs($this->routeValidator, [$consistentDirections]);
        $inconsistentScore = $method->invokeArgs($this->routeValidator, [$inconsistentDirections]);

        $this->assertGreaterThan($inconsistentScore, $consistentScore);
        $this->assertGreaterThan(0.8, $consistentScore);
        $this->assertLessThan(0.3, $inconsistentScore);
    }

    public function test_handles_empty_route_stops_gracefully()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service with empty route stops
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => []
            ]);

        $result = $this->routeValidator->validateRouteProgression($latitude, $longitude, $busId);

        $this->assertFalse($result['valid']);
        $this->assertEquals('no_route_data', $result['reason']);
    }

    public function test_handles_single_stop_route()
    {
        $latitude = 23.7937;
        $longitude = 90.3629;
        $busId = 'B1';

        // Mock schedule service with single stop
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Campus',
                        'stop_order' => 1,
                        'latitude' => 23.7937,
                        'longitude' => 90.3629,
                        'coverage_radius' => 100
                    ]
                ]
            ]);

        $result = $this->routeValidator->validateRouteProgression($latitude, $longitude, $busId);

        $this->assertTrue($result['valid']); // Should be valid if at the single stop
        $this->assertArrayHasKey('closest_stop', $result);
    }
}