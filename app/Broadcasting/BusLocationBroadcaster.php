<?php

namespace App\Broadcasting;

use App\Models\BusLocation;
use App\Models\BusSchedule;
use App\Services\BusScheduleService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Bus Location Broadcaster
 * Handles real-time broadcasting of bus location updates
 */
class BusLocationBroadcaster implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $busId;
    public $locationData;
    public $timestamp;
    public $connectionCount;
    public $broadcastQueue = 'high';

    /**
     * Create a new broadcaster instance
     *
     * @param string $busId
     * @param array $locationData
     * @param int $connectionCount
     */
    public function __construct(string $busId, array $locationData, int $connectionCount = 0)
    {
        $this->busId = $busId;
        $this->locationData = $locationData;
        $this->timestamp = now()->toISOString();
        $this->connectionCount = $connectionCount;

        Log::info('BusLocationBroadcaster created', [
            'bus_id' => $busId,
            'location_data' => $locationData,
            'connection_count' => $connectionCount
        ]);
    }

    /**
     * Get the channels the event should broadcast on
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("bus.{$this->busId}"),
            new Channel('bus.all'),
            new PresenceChannel("bus.{$this->busId}.tracking")
        ];
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Get the data to broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'bus_id' => $this->busId,
            'location' => $this->locationData,
            'timestamp' => $this->timestamp,
            'connection_count' => $this->connectionCount,
            'server_time' => now()->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if there are active connections
        return $this->connectionCount > 0;
    }

    /**
     * Create and broadcast a bus location update
     *
     * @param string $busId
     * @param array $locationData
     * @param int $connectionCount
     * @return void
     */
    public static function broadcast(string $busId, array $locationData, int $connectionCount = 0): void
    {
        try {
            // Validate location data before broadcasting
            if (!self::validateLocationData($locationData)) {
                Log::warning('Invalid location data for broadcasting', [
                    'bus_id' => $busId,
                    'location_data' => $locationData
                ]);
                return;
            }

            // Check if bus is currently active
            $scheduleService = app(BusScheduleService::class);
            $activeStatus = $scheduleService->isBusActive($busId);
            
            if (!$activeStatus['is_active']) {
                Log::info('Skipping broadcast for inactive bus', [
                    'bus_id' => $busId,
                    'reason' => $activeStatus['reason']
                ]);
                return;
            }

            // Add additional context to location data
            $enrichedLocationData = self::enrichLocationData($locationData, $busId);

            // Create and dispatch the broadcaster
            $broadcaster = new self($busId, $enrichedLocationData, $connectionCount);
            broadcast($broadcaster);

            // Update cache for polling fallback
            self::updateLocationCache($busId, $enrichedLocationData);

            Log::info('Bus location broadcasted successfully', [
                'bus_id' => $busId,
                'connection_count' => $connectionCount,
                'channels' => ["bus.{$busId}", 'bus.all', "bus.{$busId}.tracking"]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast bus location', [
                'error' => $e->getMessage(),
                'bus_id' => $busId,
                'location_data' => $locationData
            ]);
        }
    }

    /**
     * Validate location data structure
     *
     * @param array $locationData
     * @return bool
     */
    private static function validateLocationData(array $locationData): bool
    {
        $required = ['latitude', 'longitude', 'accuracy'];
        
        foreach ($required as $field) {
            if (!isset($locationData[$field]) || !is_numeric($locationData[$field])) {
                return false;
            }
        }

        // Validate coordinate bounds (Bangladesh region)
        if ($locationData['latitude'] < 20.5 || $locationData['latitude'] > 26.5 ||
            $locationData['longitude'] < 88.0 || $locationData['longitude'] > 92.7) {
            return false;
        }

        return true;
    }

    /**
     * Enrich location data with additional context
     *
     * @param array $locationData
     * @param string $busId
     * @return array
     */
    private static function enrichLocationData(array $locationData, string $busId): array
    {
        $scheduleService = app(BusScheduleService::class);
        $tripDirection = $scheduleService->getCurrentTripDirection($busId);

        return array_merge($locationData, [
            'trip_direction' => $tripDirection['direction'] ?? null,
            'route_name' => $tripDirection['route_name'] ?? null,
            'active_trackers' => self::getActiveTrackerCount($busId),
            'trust_score' => $locationData['trust_score'] ?? 0.5,
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Get count of active trackers for a bus
     *
     * @param string $busId
     * @return int
     */
    private static function getActiveTrackerCount(string $busId): int
    {
        return Cache::remember("active_trackers_{$busId}", now()->addMinutes(1), function () use ($busId) {
            return BusLocation::where('bus_id', $busId)
                ->where('is_validated', true)
                ->where('created_at', '>', now()->subMinutes(5))
                ->distinct('device_token')
                ->count();
        });
    }

    /**
     * Update location cache for polling fallback
     *
     * @param string $busId
     * @param array $locationData
     * @return void
     */
    private static function updateLocationCache(string $busId, array $locationData): void
    {
        $cacheKey = "bus_location_{$busId}";
        $cacheData = [
            'location' => $locationData,
            'updated_at' => now()->toISOString(),
            'cache_source' => 'websocket_broadcast'
        ];

        Cache::put($cacheKey, $cacheData, now()->addMinutes(10));
    }

    /**
     * Broadcast to multiple buses at once
     *
     * @param array $busUpdates Array of ['bus_id' => $busId, 'location_data' => $data]
     * @return void
     */
    public static function broadcastMultiple(array $busUpdates): void
    {
        foreach ($busUpdates as $update) {
            if (isset($update['bus_id']) && isset($update['location_data'])) {
                self::broadcast(
                    $update['bus_id'],
                    $update['location_data'],
                    $update['connection_count'] ?? 0
                );
            }
        }
    }

    /**
     * Broadcast connection status update
     *
     * @param string $busId
     * @param string $status
     * @param array $metadata
     * @return void
     */
    public static function broadcastConnectionStatus(string $busId, string $status, array $metadata = []): void
    {
        try {
            $statusData = [
                'bus_id' => $busId,
                'status' => $status,
                'timestamp' => now()->toISOString(),
                'metadata' => $metadata
            ];

            broadcast(new class($busId, $statusData) implements ShouldBroadcast {
                use Dispatchable, InteractsWithSockets, SerializesModels;

                public $busId;
                public $statusData;

                public function __construct($busId, $statusData)
                {
                    $this->busId = $busId;
                    $this->statusData = $statusData;
                }

                public function broadcastOn(): array
                {
                    return [
                        new Channel("bus.{$this->busId}"),
                        new Channel('bus.all')
                    ];
                }

                public function broadcastAs(): string
                {
                    return 'connection.status';
                }

                public function broadcastWith(): array
                {
                    return $this->statusData;
                }
            });

            Log::info('Connection status broadcasted', [
                'bus_id' => $busId,
                'status' => $status,
                'metadata' => $metadata
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast connection status', [
                'error' => $e->getMessage(),
                'bus_id' => $busId,
                'status' => $status
            ]);
        }
    }

    /**
     * Get broadcasting statistics
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total_broadcasts_today' => Cache::get('broadcast_count_today', 0),
            'active_channels' => Cache::get('active_channels', []),
            'connection_counts' => Cache::get('connection_counts', []),
            'last_broadcast' => Cache::get('last_broadcast_time'),
            'failed_broadcasts_today' => Cache::get('failed_broadcast_count_today', 0)
        ];
    }

    /**
     * Clear broadcasting caches
     *
     * @return void
     */
    public static function clearCaches(): void
    {
        $patterns = [
            'bus_location_*',
            'active_trackers_*',
            'broadcast_count_*',
            'connection_counts',
            'active_channels'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Broadcasting caches cleared');
    }
}