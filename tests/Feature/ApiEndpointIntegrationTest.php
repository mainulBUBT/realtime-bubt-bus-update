<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ApiEndpointIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    public function test_polling_endpoint_returns_current_bus_locations()
    {
        // Create current position data
        BusCurrentPosition::create([
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'confidence_level' => 0.9,
            'last_updated' => now(),
            'active_trackers' => 3,
            'trusted_trackers' => 2,
            'average_trust_score' => 0.8,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/polling/bus-locations');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'bus_id',
                            'latitude',
                            'longitude',
                            'confidence_level',
                            'last_updated',
                            'status',
                            'active_trackers'
                        ]
                    ],
                    'timestamp'
                ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('B1', $data[0]['bus_id']);
        $this->assertEquals(23.7937, $data[0]['latitude']);
    }

    public function test_polling_endpoint_filters_by_bus_id()
    {
        // Create multiple bus positions
        BusCurrentPosition::create([
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'confidence_level' => 0.9,
            'last_updated' => now(),
            'active_trackers' => 3,
            'status' => 'active'
        ]);

        BusCurrentPosition::create([
            'bus_id' => 'B2',
            'latitude' => 23.8000,
            'longitude' => 90.3700,
            'confidence_level' => 0.8,
            'last_updated' => now(),
            'active_trackers' => 2,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/polling/bus-locations?bus_id=B1');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('B1', $data[0]['bus_id']);
    }

    public function test_polling_endpoint_handles_no_active_buses()
    {
        $response = $this->getJson('/api/polling/bus-locations');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active buses currently'
                ]);
    }

    public function test_gps_location_submission_endpoint_accepts_valid_data()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'api_test_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $locationData = [
            'device_token' => 'api_test_device',
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'timestamp' => now()->timestamp * 1000
        ];

        $response = $this->postJson('/api/gps/submit-location', $locationData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Location data processed successfully'
                ]);

        // Verify data was stored
        $this->assertDatabaseHas('bus_locations', [
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7937,
            'longitude' => 90.3629
        ]);
    }

    public function test_gps_location_submission_endpoint_rejects_invalid_data()
    {
        $invalidLocationData = [
            'device_token' => 'invalid_device',
            'bus_id' => 'B1',
            'latitude' => 'invalid',
            'longitude' => 'invalid',
            'accuracy' => -1,
            'timestamp' => 'invalid'
        ];

        $response = $this->postJson('/api/gps/submit-location', $invalidLocationData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['latitude', 'longitude', 'accuracy', 'timestamp']);
    }

    public function test_gps_location_batch_submission_endpoint()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'batch_api_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $batchData = [
            'device_token' => 'batch_api_device',
            'bus_id' => 'B1',
            'locations' => [
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
                    'timestamp' => (now()->addSeconds(30))->timestamp * 1000
                ]
            ]
        ];

        $response = $this->postJson('/api/gps/submit-batch', $batchData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 2,
                    'valid' => 2,
                    'invalid' => 0
                ]);

        // Verify both locations were stored
        $this->assertDatabaseCount('bus_locations', 2);
    }

    public function test_device_token_registration_endpoint()
    {
        $fingerprintData = [
            'screen' => [
                'width' => 1920,
                'height' => 1080,
                'colorDepth' => 24
            ],
            'navigator' => [
                'userAgent' => 'Mozilla/5.0 Test Browser',
                'platform' => 'MacIntel',
                'language' => 'en-US'
            ],
            'timezone' => [
                'timezone' => 'America/New_York',
                'offset' => -300
            ]
        ];

        $response = $this->postJson('/api/device/register-token', [
            'fingerprint' => $fingerprintData
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'token',
                    'device_id',
                    'reputation_score',
                    'trust_score',
                    'is_trusted'
                ]);

        // Verify device token was created
        $responseData = $response->json();
        $this->assertDatabaseHas('device_tokens', [
            'token_hash' => hash('sha256', $responseData['token'])
        ]);
    }

    public function test_tracking_session_start_endpoint()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'session_test_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $response = $this->postJson('/api/tracking/start-session', [
            'device_token' => 'session_test_device',
            'bus_id' => 'B1'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'bus_id' => 'B1'
                ])
                ->assertJsonStructure([
                    'success',
                    'session_id',
                    'bus_id',
                    'started_at'
                ]);

        // Verify tracking session was created
        $this->assertDatabaseHas('user_tracking_sessions', [
            'device_token' => 'session_test_device',
            'bus_id' => 'B1',
            'is_active' => true
        ]);
    }

    public function test_tracking_session_end_endpoint()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'end_session_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $session = UserTrackingSession::create([
            'device_token' => 'end_session_device',
            'bus_id' => 'B1',
            'started_at' => now(),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/tracking/end-session', [
            'session_id' => $session->id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Tracking session ended successfully'
                ]);

        // Verify session was ended
        $this->assertDatabaseHas('user_tracking_sessions', [
            'id' => $session->id,
            'is_active' => false
        ]);
    }

    public function test_bus_schedule_api_endpoint()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM
        Carbon::setTestNow($currentTime);

        $response = $this->getJson('/api/schedules/active-buses');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'bus_id',
                            'route_name',
                            'trip_direction',
                            'departure_time',
                            'return_time'
                        ]
                    ]
                ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        $this->assertEquals('B1', $data[0]['bus_id']);
    }

    public function test_bus_route_timeline_api_endpoint()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        $response = $this->getJson('/api/routes/timeline/B1');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'bus_id',
                    'trip_direction',
                    'timeline' => [
                        '*' => [
                            'stop_name',
                            'stop_order',
                            'status',
                            'latitude',
                            'longitude'
                        ]
                    ]
                ]);

        $responseData = $response->json();
        $this->assertEquals('B1', $responseData['bus_id']);
        $this->assertNotEmpty($responseData['timeline']);
    }

    public function test_api_rate_limiting_prevents_abuse()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'rate_limit_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $locationData = [
            'device_token' => 'rate_limit_device',
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'timestamp' => now()->timestamp * 1000
        ];

        // Send many rapid requests
        $successCount = 0;
        $rateLimitedCount = 0;

        for ($i = 0; $i < 20; $i++) {
            $response = $this->postJson('/api/gps/submit-location', $locationData);
            
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) { // Too Many Requests
                $rateLimitedCount++;
            }
        }

        // Should have rate limited some requests
        $this->assertGreaterThan(0, $rateLimitedCount);
        $this->assertLessThan(20, $successCount);
    }

    public function test_api_authentication_validates_device_tokens()
    {
        // Test with invalid device token
        $locationData = [
            'device_token' => 'invalid_token',
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'timestamp' => now()->timestamp * 1000
        ];

        $response = $this->postJson('/api/gps/submit-location', $locationData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'Invalid device token'
                ]);
    }

    public function test_api_error_handling_returns_consistent_format()
    {
        // Test various error scenarios
        
        // 1. Validation error
        $response = $this->postJson('/api/gps/submit-location', []);
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);

        // 2. Not found error
        $response = $this->getJson('/api/routes/timeline/NONEXISTENT');
        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        // 3. Method not allowed
        $response = $this->putJson('/api/gps/submit-location', []);
        $response->assertStatus(405)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    public function test_api_cors_headers_are_set_correctly()
    {
        $response = $this->getJson('/api/polling/bus-locations');

        $response->assertHeader('Access-Control-Allow-Origin', '*')
                ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }

    public function test_api_response_caching_improves_performance()
    {
        // First request should hit the database
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/polling/bus-locations');
        $firstRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);

        // Second request should use cache (if implemented)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/polling/bus-locations');
        $secondRequestTime = microtime(true) - $startTime;

        $response2->assertStatus(200);

        // Verify responses are identical
        $this->assertEquals($response1->json(), $response2->json());

        // Second request should be faster (if caching is implemented)
        // This assertion might need adjustment based on actual caching implementation
        $this->assertLessThanOrEqual($firstRequestTime, $secondRequestTime);
    }

    public function test_api_pagination_works_correctly()
    {
        // Create many location records
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'pagination_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        for ($i = 0; $i < 50; $i++) {
            BusLocation::create([
                'bus_id' => 'B1',
                'device_token' => $deviceToken->token_hash,
                'latitude' => 23.7937 + ($i * 0.0001),
                'longitude' => 90.3629 + ($i * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true,
                'created_at' => now()->subMinutes($i)
            ]);
        }

        // Test first page
        $response = $this->getJson('/api/locations/history/B1?page=1&per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ]);

        $data = $response->json();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(50, $data['pagination']['total']);
    }

    public function test_api_data_consistency_across_endpoints()
    {
        // Create current position
        BusCurrentPosition::create([
            'bus_id' => 'B1',
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'confidence_level' => 0.9,
            'last_updated' => now(),
            'active_trackers' => 3,
            'status' => 'active'
        ]);

        // Get data from polling endpoint
        $pollingResponse = $this->getJson('/api/polling/bus-locations?bus_id=B1');
        $pollingData = $pollingResponse->json('data')[0];

        // Get data from timeline endpoint
        $timelineResponse = $this->getJson('/api/routes/timeline/B1');
        $timelineData = $timelineResponse->json();

        // Verify consistency
        $this->assertEquals($pollingData['bus_id'], $timelineData['bus_id']);
        $this->assertEquals('B1', $pollingData['bus_id']);
        $this->assertEquals('B1', $timelineData['bus_id']);
    }

    /**
     * Helper method to create test data
     */
    private function createTestData()
    {
        // Create test schedule
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // Create test routes
        $stops = ['Campus', 'Stop 1', 'Stop 2', 'City Center'];
        foreach ($stops as $index => $stopName) {
            BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => $stopName,
                'stop_order' => $index + 1,
                'latitude' => 23.7937 + ($index * 0.01),
                'longitude' => 90.3629 + ($index * 0.01),
                'coverage_radius' => 100,
                'estimated_time' => '07:' . str_pad(($index * 15), 2, '0', STR_PAD_LEFT) . ':00'
            ]);
        }
    }
}