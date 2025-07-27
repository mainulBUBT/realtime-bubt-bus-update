<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use App\Events\BusLocationUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SmartBroadcastingService
{
    private const TRUST_THRESHOLD = 0.7;
    private const MIN_CONFIDENCE_LEVEL = 0.5;
    private const LOCATION_TIMEOUT_MINUTES = 5;
    private const BATCH_SIZE = 50;
    
    /**
     * Update bus positions using trusted user data
     */
    public function updateBusPositions(): void
    {
        try {
            $activeBuses = $this->getActiveBuses();
            
            foreach ($activeBuses as $busId) {
                $this->updateSingleBusPosition($busId);
            }
            
            $this->cleanupOldData();
            
        } catch (\Exception $e) {
            Log::error('Smart broadcasting update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Update position for a single bus
     */
    private function updateSingleBusPosition(string $busId): void
    {
        // Get recent trusted locations for this bus
        $trustedLocations = $this->getTrustedLocations($busId);
        
        if ($trustedLocations->isEmpty()) {
            $this->handleNoTrustedData($busId);
            return;
        }
        
        // Calculate weighted average position
        $position = $this->calculateWeightedPosition($trustedLocations);
        
        // Update or create bus current position
        $this->updateBusCurrentPosition($busId, $position, $trustedLocations);
        
        // Broadcast the update
        $this->broadcastLocationUpdate($busId, $position);
    }
    
    /**
     * Get active buses that should be tracked
     */
    private function getActiveBuses(): array
    {
        // Get buses that have recent location data or are scheduled to be active
        return BusLocation::select('bus_id')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->distinct()
            ->pluck('bus_id')
            ->toArray();
    }
    
    /**
     * Get trusted locations for a bus
     */
    private function getTrustedLocations(string $busId)
    {
        return BusLocation::forBus($busId)
            ->recent(10) // Last 10 minutes
            ->validated()
            ->trusted(self::TRUST_THRESHOLD)
            ->with('deviceToken')
            ->latest()
            ->limit(20) // Max 20 locations to process
            ->get()
            ->filter(function ($location) {
                return $location->deviceToken && 
                       $location->deviceToken->isTrustedDevice() &&
                       $location->isValidCoordinates() &&
                       $location->isValidSpeed();
            });
    }
    
    /**
     * Calculate weighted average position from trusted locations
     */
    private function calculateWeightedPosition($locations): array
    {
        if ($locations->isEmpty()) {
            return [];
        }
        
        $totalWeight = 0;
        $weightedLat = 0;
        $weightedLng = 0;
        $totalTrustScore = 0;
        $movementConsistency = 0;
        
        foreach ($locations as $location) {
            $weight = $location->reputation_weight * $location->deviceToken->trust_score;
            
            $weightedLat += (float)$location->latitude * $weight;
            $weightedLng += (float)$location->longitude * $weight;
            $totalWeight += $weight;
            $totalTrustScore += $location->deviceToken->trust_score;
            $movementConsistency += $location->deviceToken->movement_consistency;
        }
        
        if ($totalWeight == 0) {
            return [];
        }
        
        return [
            'latitude' => $weightedLat / $totalWeight,
            'longitude' => $weightedLng / $totalWeight,
            'confidence_level' => min(1.0, $totalWeight / $locations->count()),
            'active_trackers' => $locations->count(),
            'trusted_trackers' => $locations->where('deviceToken.is_trusted', true)->count(),
            'average_trust_score' => $totalTrustScore / $locations->count(),
            'movement_consistency' => $movementConsistency / $locations->count(),
            'last_updated' => now(),
            'status' => 'active'
        ];
    }
    
    /**
     * Update bus current position in database
     */
    private function updateBusCurrentPosition(string $busId, array $position, $locations): void
    {
        if (empty($position)) {
            return;
        }
        
        // Store last known location for fallback
        $lastKnownLocation = [
            'latitude' => $position['latitude'],
            'longitude' => $position['longitude'],
            'timestamp' => now()->toISOString(),
            'source' => 'trusted_users',
            'tracker_count' => $position['active_trackers']
        ];
        
        BusCurrentPosition::updateOrCreate(
            ['bus_id' => $busId],
            array_merge($position, [
                'last_known_location' => $lastKnownLocation
            ])
        );
    }
    
    /**
     * Handle buses with no trusted data
     */
    private function handleNoTrustedData(string $busId): void
    {
        $currentPosition = BusCurrentPosition::find($busId);
        
        if (!$currentPosition) {
            // Create new record with no_data status
            BusCurrentPosition::create([
                'bus_id' => $busId,
                'status' => 'no_data',
                'active_trackers' => 0,
                'trusted_trackers' => 0,
                'confidence_level' => 0.0,
                'average_trust_score' => 0.0,
                'movement_consistency' => 0.0
            ]);
            return;
        }
        
        // Check if we should mark as inactive
        if ($currentPosition->last_updated && 
            $currentPosition->last_updated->diffInMinutes(now()) > self::LOCATION_TIMEOUT_MINUTES) {
            
            $currentPosition->update([
                'status' => 'inactive',
                'active_trackers' => 0,
                'trusted_trackers' => 0
            ]);
        }
    }
    
    /**
     * Broadcast location update to connected clients
     */
    private function broadcastLocationUpdate(string $busId, array $position): void
    {
        if (empty($position)) {
            return;
        }
        
        try {
            $locationData = [
                'latitude' => $position['latitude'],
                'longitude' => $position['longitude'],
                'confidence_level' => $position['confidence_level'],
                'trusted_trackers' => $position['trusted_trackers'],
                'status' => $position['status'],
                'timestamp' => $position['last_updated']->toISOString()
            ];
            
            broadcast(new BusLocationUpdated(
                $busId,
                $locationData,
                $position['active_trackers']
            ));
            
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast location update', [
                'bus_id' => $busId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clean up old location data and inactive sessions
     */
    private function cleanupOldData(): void
    {
        try {
            // Clean up old location data (older than 24 hours)
            $deletedLocations = BusLocation::where('created_at', '<', now()->subHours(24))
                ->limit(self::BATCH_SIZE)
                ->delete();
            
            // Clean up inactive tracking sessions (older than 2 hours)
            $deletedSessions = UserTrackingSession::where('is_active', false)
                ->where('ended_at', '<', now()->subHours(2))
                ->limit(self::BATCH_SIZE)
                ->delete();
            
            // Update inactive bus positions
            BusCurrentPosition::where('last_updated', '<', now()->subMinutes(self::LOCATION_TIMEOUT_MINUTES))
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
            
            if ($deletedLocations > 0 || $deletedSessions > 0) {
                Log::info('Cleanup completed', [
                    'deleted_locations' => $deletedLocations,
                    'deleted_sessions' => $deletedSessions
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get current bus positions for API/display
     */
    public function getCurrentBusPositions(): array
    {
        return Cache::remember('bus_positions', 30, function () {
            return BusCurrentPosition::with('busSchedule')
                ->get()
                ->map(function ($position) {
                    return [
                        'bus_id' => $position->bus_id,
                        'latitude' => $position->latitude,
                        'longitude' => $position->longitude,
                        'status' => $position->status,
                        'confidence_level' => $position->confidence_level,
                        'active_trackers' => $position->active_trackers,
                        'trusted_trackers' => $position->trusted_trackers,
                        'last_updated' => $position->last_updated?->toISOString(),
                        'last_seen' => $position->last_seen,
                        'is_reliable' => $position->hasReliableTracking(),
                        'last_known_location' => $position->last_known_location
                    ];
                })
                ->toArray();
        });
    }
    
    /**
     * Force update positions (for manual triggers)
     */
    public function forceUpdatePositions(): void
    {
        Cache::forget('bus_positions');
        $this->updateBusPositions();
    }
    
    /**
     * Get statistics for monitoring
     */
    public function getStatistics(): array
    {
        return [
            'total_buses' => BusCurrentPosition::count(),
            'active_buses' => BusCurrentPosition::active()->count(),
            'reliable_buses' => BusCurrentPosition::reliable()->count(),
            'total_locations_today' => BusLocation::whereDate('created_at', today())->count(),
            'trusted_locations_today' => BusLocation::whereDate('created_at', today())
                ->trusted(self::TRUST_THRESHOLD)->count(),
            'active_sessions' => UserTrackingSession::where('is_active', true)->count(),
            'last_cleanup' => Cache::get('last_cleanup_time', 'Never')
        ];
    }
}