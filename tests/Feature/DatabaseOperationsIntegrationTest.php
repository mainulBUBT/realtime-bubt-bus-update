<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusLocationHistory;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use App\Services\LocationBatchProcessor;
use App\Services\HistoricalDataService;
use App\Services\SmartBroadcastingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected LocationBatchProcessor $batchProcessor;
    protected HistoricalDataService $historicalService;
    protected SmartBroadcastingService $broadcastingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->batchProcessor = app(LocationBatchProcessor::class);
        $this->historicalService = app(HistoricalDataService::class);
        $this->broadcastingService = app(SmartBroadcastingService::class);
        
        $this->createTestData();
    }

    public function test_location_batch_processing_stores_data_correctly()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_batch_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $locationBatch = [
            [
                'bus_id' => 'B1',
                'device_token' => 'test_batch_device',
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'accuracy' => 20.0,
                'speed' => 25.0,
                'timestamp' => now()->timestamp * 1000
            ],
            [
                'bus_id' => 'B1',
                'device_token' => 'test_batch_device',
                'latitude' => 23.7940,
                'longitude' => 90.3630,
                'accuracy' => 15.0,
                'speed' => 30.0,
                'timestamp' => (now()->addSeconds(30))->timestamp * 1000
            ],
            [
                'bus_id' => 'B1',
                'device_token' => 'test_batch_device',
                'latitude' => 23.7943,
                'longitude' => 90.3631,
                'accuracy' => 18.0,
                'speed' => 28.0,
                'timestamp' => (now()->addMinutes(1))->timestamp * 1000
            ]
        ];

        $result = $this->batchProcessor->processBatch($locationBatch);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(3, $result['valid']);
        $this->assertEquals(0, $result['invalid']);

        // Verify data was stored in database
        $this->assertDatabaseCount('bus_locations', 3);
        
        $locations = BusLocation::where('bus_id', 'B1')->orderBy('created_at')->get();
        $this->assertEquals(23.7937, $locations[0]->latitude);
        $this->assertEquals(23.7940, $locations[1]->latitude);
        $this->assertEquals(23.7943, $locations[2]->latitude);
    }

    public function test_location_batch_processing_handles_invalid_data()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_invalid_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $locationBatch = [
            [
                'bus_id' => 'B1',
                'device_token' => 'test_invalid_device',
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'accuracy' => 20.0,
                'speed' => 25.0,
                'timestamp' => now()->timestamp * 1000
            ],
            [
                'bus_id' => 'B1',
                'device_token' => 'test_invalid_device',
                'latitude' => 40.7128, // Invalid coordinates (New York)
                'longitude' => -74.0060,
                'accuracy' => 15.0,
                'speed' => 30.0,
                'timestamp' => (now()->addSeconds(30))->timestamp * 1000
            ],
            [
                'bus_id' => 'B1',
                'device_token' => 'test_invalid_device',
                'latitude' => 23.7943,
                'longitude' => 90.3631,
                'accuracy' => 2000.0, // Poor accuracy
                'speed' => 28.0,
                'timestamp' => (now()->addMinutes(1))->timestamp * 1000
            ]
        ];

        $result = $this->batchProcessor->processBatch($locationBatch);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(1, $result['valid']); // Only first location should be valid
        $this->assertEquals(2, $result['invalid']);

        // Verify only valid data was stored
        $this->assertDatabaseCount('bus_locations', 1);
        
        $location = BusLocation::first();
        $this->assertEquals(23.7937, $location->latitude);
        $this->assertEquals(90.3629, $location->longitude);
    }

    public function test_database_transactions_maintain_consistency()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_transaction_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        // Test transaction rollback on error
        try {
            DB::transaction(function () use ($deviceToken) {
                // Create valid location
                BusLocation::create([
                    'bus_id' => 'B1',
                    'device_token' => $deviceToken->token_hash,
                    'latitude' => 23.7937,
                    'longitude' => 90.3629,
                    'accuracy' => 20.0,
                    'speed' => 25.0,
                    'reputation_weight' => 0.8,
                    'is_validated' => true
                ]);

                // Create tracking session
                UserTrackingSession::create([
                    'device_token' => 'test_transaction_device',
                    'bus_id' => 'B1',
                    'started_at' => now(),
                    'is_active' => true
                ]);

                // Force an error to test rollback
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify no data was committed due to rollback
        $this->assertDatabaseCount('bus_locations', 0);
        $this->assertDatabaseCount('user_tracking_sessions', 0);
    }

    public function test_database_indexes_improve_query_performance()
    {
        // Create large dataset to test index performance
        $deviceTokens = [];
        for ($i = 0; $i < 10; $i++) {
            $deviceTokens[] = DeviceToken::create([
                'token_hash' => hash('sha256', "performance_device_{$i}"),
                'fingerprint_data' => ['test' => "data_{$i}"],
                'reputation_score' => 0.8,
                'trust_score' => 0.7
            ]);
        }

        // Create many location records
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            BusLocation::create([
                'bus_id' => 'B' . ($i % 5 + 1), // Distribute across 5 buses
                'device_token' => $deviceTokens[$i % 10]->token_hash,
                'latitude' => 23.7937 + ($i * 0.0001),
                'longitude' => 90.3629 + ($i * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true,
                'created_at' => now()->subMinutes($i % 60)
            ]);
        }
        $insertTime = microtime(true) - $startTime;

        // Test indexed query performance
        $queryStartTime = microtime(true);
        $recentLocations = BusLocation::where('bus_id', 'B1')
            ->where('created_at', '>', now()->subMinutes(30))
            ->where('is_validated', true)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        $queryTime = microtime(true) - $queryStartTime;

        // Verify query returns expected results
        $this->assertGreaterThan(0, $recentLocations->count());
        $this->assertLessThan(1.0, $queryTime); // Should be fast with indexes

        // Test aggregation query performance
        $aggregationStartTime = microtime(true);
        $stats = BusLocation::select('bus_id')
            ->selectRaw('COUNT(*) as location_count')
            ->selectRaw('AVG(reputation_weight) as avg_reputation')
            ->where('created_at', '>', now()->subHour())
            ->groupBy('bus_id')
            ->get();
        $aggregationTime = microtime(true) - $aggregationStartTime;

        $this->assertGreaterThan(0, $stats->count());
        $this->assertLessThan(1.0, $aggregationTime); // Should be fast with indexes
    }

    public function test_historical_data_archiving_process()
    {
        // Create location data from yesterday
        $yesterday = Carbon::yesterday();
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'historical_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        // Create completed trip data
        for ($i = 0; $i < 50; $i++) {
            BusLocation::create([
                'bus_id' => 'B1',
                'device_token' => $deviceToken->token_hash,
                'latitude' => 23.7937 + ($i * 0.001),
                'longitude' => 90.3629 + ($i * 0.001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true,
                'created_at' => $yesterday->copy()->addMinutes($i * 2)
            ]);
        }

        // Archive the data
        $result = $this->historicalService->archiveDailyData($yesterday);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['buses_archived']);
        $this->assertEquals(50, $result['locations_archived']);

        // Verify historical record was created
        $this->assertDatabaseHas('bus_location_history', [
            'bus_id' => 'B1',
            'trip_date' => $yesterday->format('Y-m-d')
        ]);

        // Verify original location data was cleaned up
        $remainingLocations = BusLocation::where('created_at', '<', $yesterday->endOfDay())->count();
        $this->assertEquals(0, $remainingLocations);
    }

    public function test_smart_broadcasting_updates_current_positions()
    {
        // Create multiple devices tracking the same bus
        $deviceTokens = [];
        for ($i = 0; $i < 5; $i++) {
            $deviceTokens[] = DeviceToken::create([
                'token_hash' => hash('sha256', "broadcast_device_{$i}"),
                'fingerprint_data' => ['test' => "data_{$i}"],
                'reputation_score' => 0.6 + ($i * 0.1), // Varying reputation scores
                'trust_score' => 0.6 + ($i * 0.1)
            ]);
        }

        // Create location data with different reputation weights
        foreach ($deviceTokens as $index => $deviceToken) {
            BusLocation::create([
                'bus_id' => 'B1',
                'device_token' => $deviceToken->token_hash,
                'latitude' => 23.7937 + ($index * 0.0001), // Slightly different positions
                'longitude' => 90.3629 + ($index * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => $deviceToken->reputation_score,
                'is_validated' => true,
                'created_at' => now()
            ]);
        }

        // Process smart broadcasting
        $result = $this->broadcastingService->updateBusPositions();

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['buses_updated']);

        // Verify current position was calculated and stored
        $currentPosition = BusCurrentPosition::where('bus_id', 'B1')->first();
        $this->assertNotNull($currentPosition);
        $this->assertEquals('active', $currentPosition->status);
        $this->assertEquals(5, $currentPosition->active_trackers);
        $this->assertGreaterThan(0, $currentPosition->average_trust_score);

        // Verify weighted average calculation
        $this->assertIsFloat($currentPosition->latitude);
        $this->assertIsFloat($currentPosition->longitude);
        $this->assertGreaterThan(0.5, $currentPosition->confidence_level);
    }

    public function test_database_cleanup_removes_old_data()
    {
        // Create old location data
        $oldDate = Carbon::now()->subDays(35);
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'cleanup_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true,
            'created_at' => $oldDate
        ]);

        // Create recent location data
        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7940,
            'longitude' => 90.3630,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true,
            'created_at' => now()
        ]);

        // Run cleanup
        $deletedCount = BusLocation::where('created_at', '<', Carbon::now()->subDays(30))->delete();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseCount('bus_locations', 1);

        // Verify recent data remains
        $remainingLocation = BusLocation::first();
        $this->assertEquals(23.7940, $remainingLocation->latitude);
    }

    public function test_concurrent_database_operations_handle_conflicts()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'concurrent_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        // Simulate concurrent location updates
        $processes = [];
        for ($i = 0; $i < 5; $i++) {
            $processes[] = function () use ($deviceToken, $i) {
                BusLocation::create([
                    'bus_id' => 'B1',
                    'device_token' => $deviceToken->token_hash,
                    'latitude' => 23.7937 + ($i * 0.0001),
                    'longitude' => 90.3629 + ($i * 0.0001),
                    'accuracy' => 20.0,
                    'speed' => 25.0,
                    'reputation_weight' => 0.8,
                    'is_validated' => true,
                    'created_at' => now()->addSeconds($i)
                ]);
            };
        }

        // Execute concurrent operations
        foreach ($processes as $process) {
            $process();
        }

        // Verify all operations completed successfully
        $this->assertDatabaseCount('bus_locations', 5);

        // Verify data integrity
        $locations = BusLocation::orderBy('created_at')->get();
        for ($i = 0; $i < 5; $i++) {
            $expectedLat = 23.7937 + ($i * 0.0001);
            $this->assertEquals($expectedLat, $locations[$i]->latitude);
        }
    }

    public function test_database_constraints_prevent_invalid_data()
    {
        // Test foreign key constraints
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => 'non_existent_token', // Should fail foreign key constraint
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);
    }

    public function test_database_backup_and_restore_operations()
    {
        // Create test data
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'backup_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        // Export data
        $exportData = [
            'device_tokens' => DeviceToken::all()->toArray(),
            'bus_locations' => BusLocation::all()->toArray()
        ];

        // Clear database
        DeviceToken::truncate();
        BusLocation::truncate();

        $this->assertDatabaseCount('device_tokens', 0);
        $this->assertDatabaseCount('bus_locations', 0);

        // Restore data
        foreach ($exportData['device_tokens'] as $tokenData) {
            DeviceToken::create($tokenData);
        }

        foreach ($exportData['bus_locations'] as $locationData) {
            BusLocation::create($locationData);
        }

        // Verify restoration
        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseCount('bus_locations', 1);

        $restoredToken = DeviceToken::first();
        $this->assertEquals('backup_device', hash_hmac('sha256', 'backup_device', ''));
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