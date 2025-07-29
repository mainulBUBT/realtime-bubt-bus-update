<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GPSDataValidator;
use App\Services\StoppageCoordinateValidator;
use App\Services\RouteValidator;
use App\Services\BusScheduleService;
use App\Models\BusLocation;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class GPSDataValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected GPSDataValidator $validator;
    protected $stoppageValidator;
    protected $routeValidator;
    protected $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stoppageValidator = $this->createMock(StoppageCoordinateValidator::class);
        $this->routeValidator = $this->createMock(RouteValidator::class);
        $this->scheduleService = $this->createMock(BusScheduleService::class);
        
        $this->validator = new GPSDataValidator(
            $this->stoppageValidator,
            $this->routeValidator,
            $this->scheduleService
        );
    }

    public function test_validates_coordinates_within_bangladesh_boundaries()
    {
        $validLocationData = [
            'latitude' => 23.7937,  // Dhaka coordinates
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($validLocationData);

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['validation_results']['boundary']['valid']);
        $this->assertTrue($result['validation_results']['boundary']['within_bangladesh']);
        $this->assertGreaterThan(0.5, $result['confidence_score']);
    }

    public function test_rejects_coordinates_outside_bangladesh_boundaries()
    {
        $invalidLocationData = [
            'latitude' => 40.7128,  // New York coordinates
            'longitude' => -74.0060,
            'accuracy' => 20.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $result = $this->validator->validateGPSData($invalidLocationData);

        $this->assertFalse($result['valid']);
        $this->assertFalse($result['validation_results']['boundary']['valid']);
        $this->assertFalse($result['validation_results']['boundary']['within_bangladesh']);
        $this->assertContains('coordinates_outside_bangladesh', $result['flags']);
    }

    public function test_validates_speed_limits_with_previous_location()
    {
        // Create device token and previous location
        $deviceToken = 'test_device_token';
        $hashedToken = hash('sha256', $deviceToken);
        
        DeviceToken::create([
            'token_hash' => $hashedToken,
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8
        ]);

        // Create previous location
        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $hashedToken,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'created_at' => now()->subMinutes(2)
        ]);

        // Current location with reasonable speed
        $currentLocationData = [
            'latitude' => 23.7950,  // Small movement
            'longitude' => 90.3640,
            'accuracy' => 20.0,
            'speed' => 30.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => $deviceToken,
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($currentLocationData);

        $this->assertTrue($result['validation_results']['speed']['valid']);
        $this->assertTrue($result['validation_results']['speed']['speed_valid']);
        $this->assertLessThan(80, $result['validation_results']['speed']['calculated_speed_kmh']);
    }

    public function test_rejects_impossible_speed_movements()
    {
        // Create device token and previous location
        $deviceToken = 'test_device_token';
        $hashedToken = hash('sha256', $deviceToken);
        
        DeviceToken::create([
            'token_hash' => $hashedToken,
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8
        ]);

        // Create previous location
        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $hashedToken,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'created_at' => now()->subMinutes(1) // 1 minute ago
        ]);

        // Current location very far away (impossible speed)
        $currentLocationData = [
            'latitude' => 23.8500,  // Much further away
            'longitude' => 90.4500,
            'accuracy' => 20.0,
            'speed' => 30.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => $deviceToken,
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($currentLocationData);

        $this->assertFalse($result['validation_results']['speed']['valid']);
        $this->assertFalse($result['validation_results']['speed']['speed_valid']);
        $this->assertGreaterThan(80, $result['validation_results']['speed']['calculated_speed_kmh']);
    }

    public function test_validates_gps_accuracy_within_acceptable_range()
    {
        $locationDataGoodAccuracy = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 15.0, // Good accuracy
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationDataGoodAccuracy);

        $this->assertTrue($result['validation_results']['accuracy']['valid']);
        $this->assertEquals('very_good', $result['validation_results']['accuracy']['quality_level']);
        $this->assertGreaterThan(0.8, $result['validation_results']['accuracy']['quality_score']);
    }

    public function test_rejects_poor_gps_accuracy()
    {
        $locationDataPoorAccuracy = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 1500.0, // Very poor accuracy
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationDataPoorAccuracy);

        $this->assertFalse($result['validation_results']['accuracy']['valid']);
        $this->assertEquals('poor', $result['validation_results']['accuracy']['quality_level']);
        $this->assertLessThan(0.3, $result['validation_results']['accuracy']['quality_score']);
    }

    public function test_validates_timestamp_within_tolerance()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'timestamp' => now()->timestamp * 1000, // Current time
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertTrue($result['validation_results']['timestamp']['valid']);
        $this->assertFalse($result['validation_results']['timestamp']['obviously_wrong']);
        $this->assertLessThan(300, $result['validation_results']['timestamp']['time_diff_seconds']);
    }

    public function test_rejects_obviously_wrong_timestamps()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'timestamp' => Carbon::create(2010, 1, 1)->timestamp * 1000, // Very old timestamp
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertFalse($result['validation_results']['timestamp']['valid']);
        $this->assertTrue($result['validation_results']['timestamp']['obviously_wrong']);
    }

    public function test_validates_route_adherence_with_mocked_services()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        // Mock stoppage validation
        $this->stoppageValidator->method('validateStoppageRadius')
            ->willReturn([
                'within_radius' => true,
                'closest_stop' => 'Asad Gate',
                'distance' => 50
            ]);

        // Mock route progression validation
        $this->routeValidator->method('validateRouteProgression')
            ->willReturn([
                'valid' => true,
                'confidence' => 0.9
            ]);

        // Mock route validation
        $this->stoppageValidator->method('validateAgainstBusRoute')
            ->willReturn([
                'on_expected_route' => true,
                'route_adherence_score' => 0.8
            ]);

        $this->mockScheduleService();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertTrue($result['validation_results']['route']['valid']);
        $this->assertGreaterThan(0.4, $result['validation_results']['route']['adherence_score']);
    }

    public function test_validates_schedule_based_timing()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        // Mock schedule service to return active bus
        $this->scheduleService->method('isBusCurrentlyActive')
            ->willReturn(true);

        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'trip_type' => 'campus_to_city'
            ]);

        $this->mockOtherValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertTrue($result['validation_results']['schedule']['valid']);
        $this->assertTrue($result['validation_results']['schedule']['bus_active']);
        $this->assertEquals('departure', $result['validation_results']['schedule']['trip_direction']['direction']);
    }

    public function test_rejects_gps_data_for_inactive_bus_schedule()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        // Mock schedule service to return inactive bus
        $this->scheduleService->method('isBusCurrentlyActive')
            ->willReturn(false);

        $this->mockOtherValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertFalse($result['validation_results']['schedule']['valid']);
        $this->assertFalse($result['validation_results']['schedule']['bus_active']);
    }

    public function test_calculates_overall_confidence_score_correctly()
    {
        $locationData = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 15.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationData);

        $this->assertIsFloat($result['confidence_score']);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence_score']);
        $this->assertLessThanOrEqual(1.0, $result['confidence_score']);
        
        // With all validations passing, confidence should be high
        $this->assertGreaterThan(0.6, $result['confidence_score']);
    }

    public function test_generates_appropriate_validation_flags()
    {
        $invalidLocationData = [
            'latitude' => 40.7128,  // Outside Bangladesh
            'longitude' => -74.0060,
            'accuracy' => 2000.0,   // Poor accuracy
            'timestamp' => Carbon::create(2010, 1, 1)->timestamp * 1000, // Old timestamp
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $result = $this->validator->validateGPSData($invalidLocationData);

        $this->assertContains('coordinates_outside_bangladesh', $result['flags']);
        $this->assertNotEmpty($result['flags']);
    }

    public function test_generates_helpful_recommendations()
    {
        $locationDataPoorAccuracy = [
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 500.0, // Poor accuracy
            'timestamp' => now()->timestamp * 1000,
            'device_token' => 'test_token',
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($locationDataPoorAccuracy);

        $this->assertNotEmpty($result['recommendations']);
        $this->assertContains('Improve GPS signal by moving to open area', $result['recommendations']);
    }

    public function test_handles_missing_required_fields_gracefully()
    {
        $incompleteLocationData = [
            'latitude' => 23.7937,
            // Missing longitude, accuracy, timestamp, etc.
        ];

        $result = $this->validator->validateGPSData($incompleteLocationData);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['flags']);
    }

    public function test_validates_movement_pattern_consistency()
    {
        // Create device token and multiple previous locations showing consistent movement
        $deviceToken = 'test_device_token';
        $hashedToken = hash('sha256', $deviceToken);
        
        DeviceToken::create([
            'token_hash' => $hashedToken,
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8
        ]);

        // Create consistent movement pattern
        $baseTime = now()->subMinutes(10);
        for ($i = 0; $i < 4; $i++) {
            BusLocation::create([
                'bus_id' => 'B1',
                'device_token' => $hashedToken,
                'latitude' => 23.7937 + ($i * 0.001), // Gradual movement
                'longitude' => 90.3629 + ($i * 0.001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'created_at' => $baseTime->copy()->addMinutes($i * 2)
            ]);
        }

        $currentLocationData = [
            'latitude' => 23.7937 + (4 * 0.001),
            'longitude' => 90.3629 + (4 * 0.001),
            'accuracy' => 20.0,
            'speed' => 25.0,
            'timestamp' => now()->timestamp * 1000,
            'device_token' => $deviceToken,
            'bus_id' => 'B1'
        ];

        $this->mockValidationDependencies();
        
        $result = $this->validator->validateGPSData($currentLocationData);

        $this->assertTrue($result['validation_results']['movement']['valid']);
        $this->assertTrue($result['validation_results']['movement']['movement_consistency']['consistent']);
        $this->assertFalse($result['validation_results']['movement']['stationary_analysis']['is_stationary']);
    }

    /**
     * Helper methods to mock dependencies
     */
    private function mockValidationDependencies()
    {
        $this->mockStoppageValidator();
        $this->mockRouteValidator();
        $this->mockScheduleService();
    }

    private function mockStoppageValidator()
    {
        $this->stoppageValidator->method('validateStoppageRadius')
            ->willReturn([
                'within_radius' => true,
                'closest_stop' => 'Test Stop',
                'distance' => 50
            ]);

        $this->stoppageValidator->method('validateAgainstBusRoute')
            ->willReturn([
                'on_expected_route' => true,
                'route_adherence_score' => 0.8
            ]);
    }

    private function mockRouteValidator()
    {
        $this->routeValidator->method('validateRouteProgression')
            ->willReturn([
                'valid' => true,
                'confidence' => 0.9
            ]);
    }

    private function mockScheduleService()
    {
        $this->scheduleService->method('isBusCurrentlyActive')
            ->willReturn(true);

        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'trip_type' => 'campus_to_city'
            ]);
    }

    private function mockOtherValidationDependencies()
    {
        $this->stoppageValidator->method('validateStoppageRadius')
            ->willReturn(['within_radius' => false]);

        $this->routeValidator->method('validateRouteProgression')
            ->willReturn(['valid' => false]);

        $this->stoppageValidator->method('validateAgainstBusRoute')
            ->willReturn(['on_expected_route' => false]);
    }
}