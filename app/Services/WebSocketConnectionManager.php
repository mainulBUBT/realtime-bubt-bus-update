<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * WebSocket Connection Manager
 * Manages WebSocket connections and cleanup for inactive connections
 */
class WebSocketConnectionManager
{
    private const CONNECTION_TTL = 300; // 5 minutes
    private const CLEANUP_INTERVAL = 60; // 1 minute
    private const MAX_CONNECTIONS_PER_BUS = 100;
    private const HEARTBEAT_INTERVAL = 30; // 30 seconds

    /**
     * Register a new WebSocket connection
     *
     * @param string $connectionId
     * @param string $busId
     * @param array $metadata
     * @return bool
     */
    public function registerConnection(string $connectionId, string $busId, array $metadata = []): bool
    {
        try {
            $connectionData = [
                'connection_id' => $connectionId,
                'bus_id' => $busId,
                'connected_at' => now()->toISOString(),
                'last_heartbeat' => now()->toISOString(),
                'metadata' => $metadata,
                'is_active' => true
            ];

            // Store connection data
            $cacheKey = "ws_connection_{$connectionId}";
            Cache::put($cacheKey, $connectionData, now()->addSeconds(self::CONNECTION_TTL));

            // Add to bus connections list
            $this->addToBusConnections($busId, $connectionId);

            // Update connection statistics
            $this->updateConnectionStats($busId, 'connect');

            Log::info('WebSocket connection registered', [
                'connection_id' => $connectionId,
                'bus_id' => $busId,
                'metadata' => $metadata
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to register WebSocket connection', [
                'error' => $e->getMessage(),
                'connection_id' => $connectionId,
                'bus_id' => $busId
            ]);

            return false;
        }
    }

    /**
     * Unregister a WebSocket connection
     *
     * @param string $connectionId
     * @return bool
     */
    public function unregisterConnection(string $connectionId): bool
    {
        try {
            $cacheKey = "ws_connection_{$connectionId}";
            $connectionData = Cache::get($cacheKey);

            if ($connectionData) {
                $busId = $connectionData['bus_id'];

                // Remove from bus connections list
                $this->removeFromBusConnections($busId, $connectionId);

                // Update connection statistics
                $this->updateConnectionStats($busId, 'disconnect');

                // Remove connection data
                Cache::forget($cacheKey);

                Log::info('WebSocket connection unregistered', [
                    'connection_id' => $connectionId,
                    'bus_id' => $busId
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to unregister WebSocket connection', [
                'error' => $e->getMessage(),
                'connection_id' => $connectionId
            ]);

            return false;
        }
    }

    /**
     * Update heartbeat for a connection
     *
     * @param string $connectionId
     * @return bool
     */
    public function updateHeartbeat(string $connectionId): bool
    {
        try {
            $cacheKey = "ws_connection_{$connectionId}";
            $connectionData = Cache::get($cacheKey);

            if ($connectionData) {
                $connectionData['last_heartbeat'] = now()->toISOString();
                Cache::put($cacheKey, $connectionData, now()->addSeconds(self::CONNECTION_TTL));

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to update connection heartbeat', [
                'error' => $e->getMessage(),
                'connection_id' => $connectionId
            ]);

            return false;
        }
    }

    /**
     * Get active connections for a bus
     *
     * @param string $busId
     * @return array
     */
    public function getActiveConnections(string $busId): array
    {
        try {
            $cacheKey = "bus_connections_{$busId}";
            $connectionIds = Cache::get($cacheKey, []);
            $activeConnections = [];

            foreach ($connectionIds as $connectionId) {
                $connectionData = Cache::get("ws_connection_{$connectionId}");
                
                if ($connectionData && $this->isConnectionActive($connectionData)) {
                    $activeConnections[] = $connectionData;
                }
            }

            return $activeConnections;

        } catch (\Exception $e) {
            Log::error('Failed to get active connections', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);

            return [];
        }
    }

    /**
     * Get connection count for a bus
     *
     * @param string $busId
     * @return int
     */
    public function getConnectionCount(string $busId): int
    {
        return count($this->getActiveConnections($busId));
    }

    /**
     * Get total connection statistics
     *
     * @return array
     */
    public function getConnectionStatistics(): array
    {
        try {
            $stats = Cache::get('ws_connection_stats', [
                'total_connections' => 0,
                'connections_by_bus' => [],
                'peak_connections' => 0,
                'peak_time' => null,
                'total_connects_today' => 0,
                'total_disconnects_today' => 0
            ]);

            // Add current active connections
            $currentConnections = 0;
            $connectionsByBus = [];

            // Get all bus IDs that have connections
            $busIds = $this->getAllActiveBusIds();
            
            foreach ($busIds as $busId) {
                $count = $this->getConnectionCount($busId);
                $connectionsByBus[$busId] = $count;
                $currentConnections += $count;
            }

            $stats['current_connections'] = $currentConnections;
            $stats['connections_by_bus'] = $connectionsByBus;
            $stats['last_updated'] = now()->toISOString();

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get connection statistics', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Cleanup inactive connections
     *
     * @return int Number of connections cleaned up
     */
    public function cleanupInactiveConnections(): int
    {
        try {
            $cleanedUp = 0;
            $busIds = $this->getAllActiveBusIds();

            foreach ($busIds as $busId) {
                $cacheKey = "bus_connections_{$busId}";
                $connectionIds = Cache::get($cacheKey, []);
                $activeConnectionIds = [];

                foreach ($connectionIds as $connectionId) {
                    $connectionData = Cache::get("ws_connection_{$connectionId}");
                    
                    if ($connectionData && $this->isConnectionActive($connectionData)) {
                        $activeConnectionIds[] = $connectionId;
                    } else {
                        // Connection is inactive, clean it up
                        Cache::forget("ws_connection_{$connectionId}");
                        $cleanedUp++;
                        
                        Log::debug('Cleaned up inactive connection', [
                            'connection_id' => $connectionId,
                            'bus_id' => $busId
                        ]);
                    }
                }

                // Update the bus connections list with only active connections
                if (empty($activeConnectionIds)) {
                    Cache::forget($cacheKey);
                } else {
                    Cache::put($cacheKey, $activeConnectionIds, now()->addHours(1));
                }
            }

            if ($cleanedUp > 0) {
                Log::info('WebSocket connections cleaned up', [
                    'cleaned_up_count' => $cleanedUp
                ]);
            }

            return $cleanedUp;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup inactive connections', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Schedule automatic cleanup
     *
     * @return void
     */
    public function scheduleCleanup(): void
    {
        // This would typically be called from a scheduled job
        $this->cleanupInactiveConnections();
    }

    /**
     * Check if connection can be rate limited
     *
     * @param string $connectionId
     * @param int $maxRequestsPerMinute
     * @return bool
     */
    public function checkRateLimit(string $connectionId, int $maxRequestsPerMinute = 60): bool
    {
        try {
            $rateLimitKey = "ws_rate_limit_{$connectionId}";
            $currentRequests = Cache::get($rateLimitKey, 0);

            if ($currentRequests >= $maxRequestsPerMinute) {
                return false;
            }

            Cache::put($rateLimitKey, $currentRequests + 1, now()->addMinute());
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to check rate limit', [
                'error' => $e->getMessage(),
                'connection_id' => $connectionId
            ]);

            return true; // Allow on error
        }
    }

    /**
     * Broadcast to all connections of a bus
     *
     * @param string $busId
     * @param array $data
     * @return int Number of connections broadcasted to
     */
    public function broadcastToBus(string $busId, array $data): int
    {
        try {
            $connections = $this->getActiveConnections($busId);
            $broadcastCount = 0;

            foreach ($connections as $connection) {
                // In a real implementation, this would send data to the WebSocket connection
                // For now, we'll just log it
                Log::debug('Broadcasting to connection', [
                    'connection_id' => $connection['connection_id'],
                    'bus_id' => $busId,
                    'data' => $data
                ]);

                $broadcastCount++;
            }

            return $broadcastCount;

        } catch (\Exception $e) {
            Log::error('Failed to broadcast to bus connections', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);

            return 0;
        }
    }

    /**
     * Private helper methods
     */

    /**
     * Add connection to bus connections list
     */
    private function addToBusConnections(string $busId, string $connectionId): void
    {
        $cacheKey = "bus_connections_{$busId}";
        $connectionIds = Cache::get($cacheKey, []);
        
        if (!in_array($connectionId, $connectionIds)) {
            $connectionIds[] = $connectionId;
            
            // Limit connections per bus
            if (count($connectionIds) > self::MAX_CONNECTIONS_PER_BUS) {
                array_shift($connectionIds); // Remove oldest connection
            }
            
            Cache::put($cacheKey, $connectionIds, now()->addHours(1));
            
            // Add to active bus IDs list
            $activeBusIds = Cache::get('active_bus_ids', []);
            if (!in_array($busId, $activeBusIds)) {
                $activeBusIds[] = $busId;
                Cache::put('active_bus_ids', $activeBusIds, now()->addHours(1));
            }
        }
    }

    /**
     * Remove connection from bus connections list
     */
    private function removeFromBusConnections(string $busId, string $connectionId): void
    {
        $cacheKey = "bus_connections_{$busId}";
        $connectionIds = Cache::get($cacheKey, []);
        
        $connectionIds = array_filter($connectionIds, function($id) use ($connectionId) {
            return $id !== $connectionId;
        });
        
        if (empty($connectionIds)) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, array_values($connectionIds), now()->addHours(1));
        }
    }

    /**
     * Check if connection is still active
     */
    private function isConnectionActive(array $connectionData): bool
    {
        $lastHeartbeat = Carbon::parse($connectionData['last_heartbeat']);
        $heartbeatThreshold = now()->subSeconds(self::HEARTBEAT_INTERVAL * 2);
        
        return $lastHeartbeat->greaterThan($heartbeatThreshold);
    }

    /**
     * Update connection statistics
     */
    private function updateConnectionStats(string $busId, string $action): void
    {
        $stats = Cache::get('ws_connection_stats', [
            'total_connections' => 0,
            'connections_by_bus' => [],
            'peak_connections' => 0,
            'peak_time' => null,
            'total_connects_today' => 0,
            'total_disconnects_today' => 0
        ]);

        if ($action === 'connect') {
            $stats['total_connects_today']++;
        } elseif ($action === 'disconnect') {
            $stats['total_disconnects_today']++;
        }

        // Update peak connections
        $currentTotal = array_sum($stats['connections_by_bus']);
        if ($currentTotal > $stats['peak_connections']) {
            $stats['peak_connections'] = $currentTotal;
            $stats['peak_time'] = now()->toISOString();
        }

        Cache::put('ws_connection_stats', $stats, now()->addDay());
    }

    /**
     * Get all bus IDs that have active connections
     */
    private function getAllActiveBusIds(): array
    {
        // Since we're using database cache, we'll maintain a separate list of active bus IDs
        $activeBusIds = Cache::get('active_bus_ids', []);
        
        // Filter out bus IDs that no longer have connections
        $validBusIds = [];
        foreach ($activeBusIds as $busId) {
            $cacheKey = "bus_connections_{$busId}";
            if (Cache::has($cacheKey)) {
                $validBusIds[] = $busId;
            }
        }
        
        // Update the active bus IDs list
        if (count($validBusIds) !== count($activeBusIds)) {
            Cache::put('active_bus_ids', $validBusIds, now()->addHours(1));
        }

        return $validBusIds;
    }
}