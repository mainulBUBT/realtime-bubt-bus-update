<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LocationBatchProcessor
{
    private const BATCH_SIZE = 100;
    private const VALIDATION_BATCH_SIZE = 50;
    
    /**
     * Process a batch of location updates
     */
    public function processBatch(array $locationData): array
    {
        $results = [
            'processed' => 0,
            'validated' => 0,
            'rejected' => 0,
            'errors' => []
        ];
        
        try {
            DB::beginTransaction();
            
            $batches = array_chunk($locationData, self::BATCH_SIZE);
            
            foreach ($batches as $batch) {
                $batchResults = $this->processSingleBatch($batch);
                $results['processed'] += $batchResults['processed'];
                $results['validated'] += $batchResults['validated'];
                $results['rejected'] += $batchResults['rejected'];
                $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch processing failed', [
                'error' => $e->getMessage(),
                'batch_size' => count($locationData)
            ]);
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Process a single batch of locations
     */
    private function processSingleBatch(array $batch): array
    {
        $results = [
            'processed' => 0,
            'validated' => 0,
            'rejected' => 0,
            'errors' => []
        ];
        
        $insertData = [];
        $deviceTokens = $this->getDeviceTokens(array_column($batch, 'device_token'));
        
        foreach ($batch as $locationData) {
            try {
                $validation = $this->validateLocationData($locationData, $deviceTokens);
                
                if ($validation['valid']) {
                    $insertData[] = array_merge($locationData, [
                        'reputation_weight' => $validation['reputation_weight'],
                        'is_validated' => $validation['is_validated'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    if ($validation['is_validated']) {
                        $results['validated']++;
                    }
                    $results['processed']++;
                } else {
                    $results['rejected']++;
                    $results['errors'][] = "Invalid location data: " . $validation['reason'];
                }
                
            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
                $results['rejected']++;
            }
        }
        
        // Bulk insert validated locations
        if (!empty($insertData)) {
            BusLocation::insert($insertData);
        }
        
        return $results;
    }
    
    /**
     * Get device tokens for batch processing
     */
    private function getDeviceTokens(array $tokenHashes): array
    {
        return DeviceToken::whereIn('token_hash', $tokenHashes)
            ->get()
            ->keyBy('token_hash')
            ->toArray();
    }
    
    /**
     * Validate location data
     */
    private function validateLocationData(array $data, array $deviceTokens): array
    {
        $result = [
            'valid' => false,
            'is_validated' => false,
            'reputation_weight' => 0.1,
            'reason' => ''
        ];
        
        // Check required fields
        if (!isset($data['bus_id'], $data['device_token'], $data['latitude'], $data['longitude'])) {
            $result['reason'] = 'Missing required fields';
            return $result;
        }
        
        // Validate coordinates
        if (!$this->isValidCoordinates($data['latitude'], $data['longitude'])) {
            $result['reason'] = 'Invalid coordinates';
            return $result;
        }
        
        // Check device token
        $deviceToken = $deviceTokens[$data['device_token']] ?? null;
        if (!$deviceToken) {
            $result['reason'] = 'Invalid device token';
            return $result;
        }
        
        // Validate speed if provided
        if (isset($data['speed']) && !$this->isValidSpeed($data['speed'])) {
            $result['reason'] = 'Invalid speed';
            return $result;
        }
        
        // Set reputation weight based on device trust
        $result['reputation_weight'] = $deviceToken['trust_score'] ?? 0.1;
        $result['is_validated'] = ($deviceToken['is_trusted'] ?? false) && 
                                 ($deviceToken['trust_score'] ?? 0) >= 0.7;
        $result['valid'] = true;
        
        return $result;
    }
    
    /**
     * Validate GPS coordinates
     */
    private function isValidCoordinates(float $lat, float $lng): bool
    {
        // Bangladesh approximate bounds
        $minLat = 20.670883;
        $maxLat = 26.446526;
        $minLng = 88.084422;
        $maxLng = 92.672721;
        
        return $lat >= $minLat && $lat <= $maxLat &&
               $lng >= $minLng && $lng <= $maxLng;
    }
    
    /**
     * Validate speed
     */
    private function isValidSpeed(?float $speed): bool
    {
        if ($speed === null) {
            return true;
        }
        
        // Max 80 km/h = 22.22 m/s
        return $speed >= 0 && $speed <= 22.22;
    }
    
    /**
     * Batch update device token statistics
     */
    public function updateDeviceTokenStats(array $tokenHashes): void
    {
        try {
            $batches = array_chunk($tokenHashes, self::VALIDATION_BATCH_SIZE);
            
            foreach ($batches as $batch) {
                $this->updateTokenStatsBatch($batch);
            }
            
        } catch (\Exception $e) {
            Log::error('Device token stats update failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update stats for a batch of device tokens
     */
    private function updateTokenStatsBatch(array $tokenHashes): void
    {
        $stats = DB::table('bus_locations')
            ->select([
                'device_token',
                DB::raw('COUNT(*) as total_contributions'),
                DB::raw('SUM(CASE WHEN is_validated = 1 THEN 1 ELSE 0 END) as accurate_contributions'),
                DB::raw('AVG(reputation_weight) as avg_reputation')
            ])
            ->whereIn('device_token', $tokenHashes)
            ->where('created_at', '>=', now()->subDays(7)) // Last 7 days
            ->groupBy('device_token')
            ->get();
        
        foreach ($stats as $stat) {
            DeviceToken::where('token_hash', $stat->device_token)
                ->update([
                    'total_contributions' => $stat->total_contributions,
                    'accurate_contributions' => $stat->accurate_contributions,
                    'last_activity' => now()
                ]);
        }
    }
    
    /**
     * Clean up old tracking sessions in batches
     */
    public function cleanupOldSessions(): int
    {
        $deletedCount = 0;
        
        try {
            do {
                $deleted = UserTrackingSession::where('is_active', false)
                    ->where('ended_at', '<', now()->subHours(6))
                    ->limit(self::BATCH_SIZE)
                    ->delete();
                
                $deletedCount += $deleted;
                
                // Small delay to prevent overwhelming the database
                if ($deleted > 0) {
                    usleep(100000); // 100ms
                }
                
            } while ($deleted > 0);
            
        } catch (\Exception $e) {
            Log::error('Session cleanup failed', [
                'error' => $e->getMessage(),
                'deleted_count' => $deletedCount
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Archive old location data in batches
     */
    public function archiveOldLocations(): int
    {
        $archivedCount = 0;
        
        try {
            do {
                $locations = BusLocation::where('created_at', '<', now()->subHours(24))
                    ->limit(self::BATCH_SIZE)
                    ->get();
                
                if ($locations->isEmpty()) {
                    break;
                }
                
                // Group by bus_id and date for archiving
                $groupedData = $locations->groupBy(function ($location) {
                    return $location->bus_id . '_' . $location->created_at->format('Y-m-d');
                });
                
                foreach ($groupedData as $key => $dayLocations) {
                    [$busId, $date] = explode('_', $key);
                    
                    // Create archive record
                    DB::table('bus_location_history')->updateOrInsert(
                        [
                            'bus_id' => $busId,
                            'trip_date' => $date
                        ],
                        [
                            'location_data' => json_encode($dayLocations->toArray()),
                            'trip_summary' => json_encode([
                                'total_locations' => $dayLocations->count(),
                                'trusted_locations' => $dayLocations->where('is_validated', true)->count(),
                                'average_trust' => $dayLocations->avg('reputation_weight'),
                                'first_location' => $dayLocations->first()->created_at,
                                'last_location' => $dayLocations->last()->created_at
                            ]),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
                
                // Delete archived locations
                BusLocation::whereIn('id', $locations->pluck('id'))->delete();
                $archivedCount += $locations->count();
                
                // Small delay
                usleep(200000); // 200ms
                
            } while (true);
            
        } catch (\Exception $e) {
            Log::error('Location archiving failed', [
                'error' => $e->getMessage(),
                'archived_count' => $archivedCount
            ]);
        }
        
        return $archivedCount;
    }
}