<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use App\Services\GPSLocationCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GPSLocationCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected GPSLocationCollectionService $gpsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gpsService = app(GPSLocationCollectionService::class);
    }

    /** @test */
    public function it_can_start_a_gps_tracking_session()
    {
        // Create a device token
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        // Start tracking session
        $result = $this->gpsService->startTrackingSession('test_device_token', 'B1');

        $this->assertTrue($result['success']);
        $this->assertEquals('B1', $result['bus_id']);
        $this->assertNotEmpty($result['session_id']);

        // Verify session was created in database
        $this->assertDatabaseHas('user_tracking_sessions', [
            'device_token' => 'test_device_token',
            'bus_id' => 'B1',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_process_location_batch()
    {
        // Create device and session
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $sessionResult = $this->gpsService->startTrackingSession('test_device_token', 'B1');
        $sessionId = $sessionResult['session_id'];

        // Prepare location batch
        $locationBatch = [
            [
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'accuracy' => 20.0,
                'speed' => 25.0,
                'timestamp' => now()->timestamp * 1000
            ],
            [
                'latitude' => 23.7940,
                'longitude' => 90.3630,
                'accuracy' => 15.0,
                'speed' => 30.0,
                'timestamp' => (now()->addSeconds(20))->timestamp * 1000
            ]
        ];

        // Process batch
        $result = $this->gpsService->processBatchLocationData(
            $locationBatch,
            'test_device_token',
            $sessionId
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(2, $result['valid']);
        $this->assertEquals(0, $result['invalid']);

        // Verify locations were stored
        $this->assertDatabaseCount('bus_locations', 2);
    }

    /** @test */
    public function it_can_end_a_tracking_session()
    {
        // Create device and session
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $sessionResult = $this->gpsService->startTrackingSession('test_device_token', 'B1');
        $sessionId = $sessionResult['session_id'];

        // End session
        $result = $this->gpsService->endTrackingSession($sessionId);

        $this->assertTrue($result['success']);

        // Verify session was ended
        $this->assertDatabaseHas('user_tracking_sessions', [
            'session_id' => $sessionId,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_validates_invalid_coordinates()
    {
        // Create device and session
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $sessionResult = $this->gpsService->startTrackingSession('test_device_token', 'B1');
        $sessionId = $sessionResult['session_id'];

        // Prepare invalid location batch (outside Bangladesh)
        $locationBatch = [
            [
                'latitude' => 40.7128, // New York coordinates
                'longitude' => -74.0060,
                'accuracy' => 20.0,
                'timestamp' => now()->timestamp * 1000
            ]
        ];

        // Process batch
        $result = $this->gpsService->processBatchLocationData(
            $locationBatch,
            'test_device_token',
            $sessionId
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['valid']);
        $this->assertEquals(1, $result['invalid']);

        // Verify no locations were stored
        $this->assertDatabaseCount('bus_locations', 0);
    }

    /** @test */
    public function it_calculates_session_statistics()
    {
        $stats = $this->gpsService->getCollectionStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('active_sessions', $stats);
        $this->assertArrayHasKey('locations_today', $stats);
        $this->assertArrayHasKey('valid_locations_today', $stats);
    }
}