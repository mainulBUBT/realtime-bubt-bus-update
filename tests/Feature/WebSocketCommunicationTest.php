<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Events\BusLocationUpdated;
use App\Events\BusTrackingStatusChanged;
use App\Broadcasting\BusLocationBroadcaster;
use App\Services\WebSocketConnectionManager;
use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;

class WebSocketCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected WebSocketConnectionManager $connectionManager;
    protected BusLocationBroadcaster $broadcaster;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connectionManager = app(WebSocketConnectionManager::class);
        $this->broadcaster = app(BusLocationBroadcaster::class);
        
        // Fake events and broadcasting for testing
        Event::fake();
        Broadcast::fake();
        Queue::fake();
    }

    public function test_bus_location_updated_event_is_dispatched()
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'websocket_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $location = BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        // Dispatch the event
        event(new BusLocationUpdated($location));

        // Assert event was dispatched
        Event::assertDispatched(BusLocationUpdated::class, function ($event) use ($location) {
            return $event->location->id === $location->id;
        });
    }

    public function test_bus_tracking_status_changed_event_is_dispatched()
    {
        $statusData = [
            'bus_id' => 'B1',
            'status' => 'active',
            'active_trackers' => 3,
            'confidence_level' => 0.9,
            'last_updated' => now()
        ];

        // Dispatch the event
        event(new BusTrackingStatusChanged($statusData));

        // Assert event was dispatched
        Event::assertDispatched(BusTrackingStatusChanged::class, function ($event) use ($statusData) {
            return $event->statusData['bus_id'] === $statusData['bus_id'] &&
                   $event->statusData['status'] === $statusData['status'];
        });
    }

    public function test_websocket_broadcasting_sends_location_updates()
    {
        $location = BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => hash('sha256', 'broadcast_device'),
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        // Broadcast location update
        $this->broadcaster->broadcastLocationUpdate($location);

        // Assert broadcast was sent
        Broadcast::assertPushed(BusLocationUpdated::class, function ($event) use ($location) {
            return $event->location->id === $location->id;
        });
    }

    public function test_websocket_broadcasting_sends_status_updates()
    {
        $statusData = [
            'bus_id' => 'B1',
            'status' => 'active',
            'active_trackers' => 2,
            'confidence_level' => 0.8,
            'last_updated' => now()
        ];

        // Broadcast status update
        $this->broadcaster->broadcastStatusUpdate($statusData);

        // Assert broadcast was sent
        Broadcast::assertPushed(BusTrackingStatusChanged::class, function ($event) use ($statusData) {
            return $event->statusData['bus_id'] === $statusData['bus_id'];
        });
    }

    public function test_websocket_connection_manager_handles_connections()
    {
        $connectionId = 'test_connection_123';
        $userId = 'user_456';
        $busId = 'B1';

        // Register connection
        $result = $this->connectionManager->registerConnection($connectionId, $userId, $busId);

        $this->assertTrue($result['success']);
        $this->assertEquals($connectionId, $result['connection_id']);

        // Verify connection is tracked
        $connections = $this->connectionManager->getActiveConnections($busId);
        $this->assertContains($connectionId, array_column($connections, 'connection_id'));
    }

    public function test_websocket_connection_manager_handles_disconnections()
    {
        $connectionId = 'test_connection_123';
        $userId = 'user_456';
        $busId = 'B1';

        // Register and then disconnect
        $this->connectionManager->registerConnection($connectionId, $userId, $busId);
        $result = $this->connectionManager->handleDisconnection($connectionId);

        $this->assertTrue($result['success']);

        // Verify connection is removed
        $connections = $this->connectionManager->getActiveConnections($busId);
        $this->assertNotContains($connectionId, array_column($connections, 'connection_id'));
    }

    public function test_websocket_connection_manager_handles_multiple_connections()
    {
        $busId = 'B1';
        $connections = [];

        // Register multiple connections
        for ($i = 1; $i <= 5; $i++) {
            $connectionId = "connection_{$i}";
            $userId = "user_{$i}";
            
            $result = $this->connectionManager->registerConnection($connectionId, $userId, $busId);
            $this->assertTrue($result['success']);
            
            $connections[] = $connectionId;
        }

        // Verify all connections are tracked
        $activeConnections = $this->connectionManager->getActiveConnections($busId);
        $this->assertCount(5, $activeConnections);

        foreach ($connections as $connectionId) {
            $this->assertContains($connectionId, array_column($activeConnections, 'connection_id'));
        }
    }

    public function test_websocket_connection_cleanup_removes_stale_connections()
    {
        $busId = 'B1';
        
        // Register connections with different timestamps
        $oldConnectionId = 'old_connection';
        $recentConnectionId = 'recent_connection';
        
        $this->connectionManager->registerConnection($oldConnectionId, 'user_1', $busId);
        $this->connectionManager->registerConnection($recentConnectionId, 'user_2', $busId);

        // Simulate old connection by manually updating timestamp
        $this->connectionManager->updateConnectionTimestamp($oldConnectionId, now()->subMinutes(10));

        // Run cleanup (remove connections older than 5 minutes)
        $cleanupResult = $this->connectionManager->cleanupStaleConnections(5);

        $this->assertTrue($cleanupResult['success']);
        $this->assertEquals(1, $cleanupResult['cleaned_up']);

        // Verify only recent connection remains
        $activeConnections = $this->connectionManager->getActiveConnections($busId);
        $this->assertCount(1, $activeConnections);
        $this->assertEquals($recentConnectionId, $activeConnections[0]['connection_id']);
    }

    public function test_websocket_broadcasting_handles_connection_failures()
    {
        $location = BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => hash('sha256', 'failure_device'),
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        // Register some connections
        $this->connectionManager->registerConnection('conn_1', 'user_1', 'B1');
        $this->connectionManager->registerConnection('conn_2', 'user_2', 'B1');

        // Simulate connection failure for one connection
        $this->connectionManager->markConnectionAsFailed('conn_1');

        // Broadcast should still work for remaining connections
        $result = $this->broadcaster->broadcastLocationUpdate($location);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['failed_connections']);
        $this->assertEquals(1, $result['successful_broadcasts']);
    }

    public function test_websocket_broadcasting_batches_updates_efficiently()
    {
        // Create multiple location updates
        $locations = [];
        for ($i = 0; $i < 10; $i++) {
            $locations[] = BusLocation::create([
                'bus_id' => 'B1',
                'device_token' => hash('sha256', "batch_device_{$i}"),
                'latitude' => 23.7937 + ($i * 0.0001),
                'longitude' => 90.3629 + ($i * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true
            ]);
        }

        // Broadcast batch update
        $result = $this->broadcaster->broadcastBatchLocationUpdates($locations);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['locations_broadcasted']);
        $this->assertLessThan(1.0, $result['processing_time']); // Should be efficient

        // Verify only one broadcast event was sent (batched)
        Broadcast::assertPushedTimes(BusLocationUpdated::class, 1);
    }

    public function test_websocket_connection_scaling_handles_high_load()
    {
        $busId = 'B1';
        $connectionCount = 100;

        $startTime = microtime(true);

        // Register many connections simultaneously
        for ($i = 1; $i <= $connectionCount; $i++) {
            $connectionId = "load_test_connection_{$i}";
            $userId = "load_test_user_{$i}";
            
            $result = $this->connectionManager->registerConnection($connectionId, $userId, $busId);
            $this->assertTrue($result['success']);
        }

        $registrationTime = microtime(true) - $startTime;

        // Verify all connections are registered
        $activeConnections = $this->connectionManager->getActiveConnections($busId);
        $this->assertCount($connectionCount, $activeConnections);

        // Test broadcasting to all connections
        $location = BusLocation::create([
            'bus_id' => $busId,
            'device_token' => hash('sha256', 'load_test_device'),
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        $broadcastStartTime = microtime(true);
        $broadcastResult = $this->broadcaster->broadcastLocationUpdate($location);
        $broadcastTime = microtime(true) - $broadcastStartTime;

        $this->assertTrue($broadcastResult['success']);
        $this->assertEquals($connectionCount, $broadcastResult['successful_broadcasts']);
        
        // Performance assertions (adjust thresholds as needed)
        $this->assertLessThan(5.0, $registrationTime); // Registration should be fast
        $this->assertLessThan(2.0, $broadcastTime); // Broadcasting should be fast
    }

    public function test_websocket_fallback_to_polling_when_connection_fails()
    {
        // Simulate WebSocket connection failure
        $this->connectionManager->simulateConnectionFailure();

        $location = BusLocation::create([
            'bus_id' => 'B1',
            'device_token' => hash('sha256', 'fallback_device'),
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true
        ]);

        // Attempt to broadcast
        $result = $this->broadcaster->broadcastLocationUpdate($location);

        // Should indicate fallback was used
        $this->assertTrue($result['fallback_used']);
        $this->assertEquals('polling', $result['fallback_method']);
        
        // Verify polling endpoint would be called
        $this->assertArrayHasKey('polling_data', $result);
    }

    public function test_websocket_authentication_validates_connections()
    {
        $validToken = 'valid_auth_token';
        $invalidToken = 'invalid_auth_token';

        // Test valid authentication
        $validResult = $this->connectionManager->authenticateConnection($validToken);
        $this->assertTrue($validResult['authenticated']);

        // Test invalid authentication
        $invalidResult = $this->connectionManager->authenticateConnection($invalidToken);
        $this->assertFalse($invalidResult['authenticated']);
        $this->assertArrayHasKey('error', $invalidResult);
    }

    public function test_websocket_rate_limiting_prevents_spam()
    {
        $connectionId = 'rate_limit_test';
        $userId = 'spam_user';
        $busId = 'B1';

        $this->connectionManager->registerConnection($connectionId, $userId, $busId);

        // Send many rapid updates
        $successCount = 0;
        $rateLimitedCount = 0;

        for ($i = 0; $i < 20; $i++) {
            $location = BusLocation::create([
                'bus_id' => $busId,
                'device_token' => hash('sha256', "spam_device_{$i}"),
                'latitude' => 23.7937 + ($i * 0.0001),
                'longitude' => 90.3629 + ($i * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true
            ]);

            $result = $this->broadcaster->broadcastLocationUpdate($location, $connectionId);

            if ($result['rate_limited']) {
                $rateLimitedCount++;
            } else {
                $successCount++;
            }
        }

        // Should have rate limited some requests
        $this->assertGreaterThan(0, $rateLimitedCount);
        $this->assertLessThan(20, $successCount);
    }

    public function test_websocket_connection_recovery_after_failure()
    {
        $connectionId = 'recovery_test';
        $userId = 'recovery_user';
        $busId = 'B1';

        // Register connection
        $this->connectionManager->registerConnection($connectionId, $userId, $busId);

        // Simulate connection failure
        $this->connectionManager->markConnectionAsFailed($connectionId);

        // Verify connection is marked as failed
        $connectionStatus = $this->connectionManager->getConnectionStatus($connectionId);
        $this->assertEquals('failed', $connectionStatus['status']);

        // Attempt recovery
        $recoveryResult = $this->connectionManager->attemptConnectionRecovery($connectionId);

        if ($recoveryResult['recovery_successful']) {
            $this->assertTrue($recoveryResult['recovery_successful']);
            
            // Verify connection is restored
            $newStatus = $this->connectionManager->getConnectionStatus($connectionId);
            $this->assertEquals('active', $newStatus['status']);
        } else {
            // If recovery fails, should provide fallback options
            $this->assertArrayHasKey('fallback_options', $recoveryResult);
            $this->assertContains('polling', $recoveryResult['fallback_options']);
        }
    }

    public function test_websocket_message_queuing_during_disconnection()
    {
        $connectionId = 'queue_test';
        $userId = 'queue_user';
        $busId = 'B1';

        // Register connection then simulate disconnection
        $this->connectionManager->registerConnection($connectionId, $userId, $busId);
        $this->connectionManager->simulateDisconnection($connectionId);

        // Send updates while disconnected (should be queued)
        $queuedMessages = [];
        for ($i = 0; $i < 5; $i++) {
            $location = BusLocation::create([
                'bus_id' => $busId,
                'device_token' => hash('sha256', "queue_device_{$i}"),
                'latitude' => 23.7937 + ($i * 0.0001),
                'longitude' => 90.3629 + ($i * 0.0001),
                'accuracy' => 20.0,
                'speed' => 25.0,
                'reputation_weight' => 0.8,
                'is_validated' => true
            ]);

            $result = $this->broadcaster->broadcastLocationUpdate($location, $connectionId);
            
            if ($result['queued']) {
                $queuedMessages[] = $result['message_id'];
            }
        }

        $this->assertCount(5, $queuedMessages);

        // Reconnect and verify queued messages are delivered
        $reconnectResult = $this->connectionManager->handleReconnection($connectionId);
        
        $this->assertTrue($reconnectResult['success']);
        $this->assertEquals(5, $reconnectResult['queued_messages_delivered']);
    }
}