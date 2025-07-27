<?php

namespace App\Services;

use App\Models\BusLocation;
use App\Models\BusLocationHistory;
use App\Models\UserTrackingSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HistoricalDataService
{
    private const BATCH_SIZE = 100;
    private const DEFAULT_RETENTION_DAYS = 90;
    
    /**
     * Archive completed trip data to history tables
     */
    public function archiveCompletedTrips(Carbon $beforeDate = null): array
    {
        $beforeDate = $beforeDate ?? now()->subHours(24);
        
        $results = [
            'archived_trips' => 0,
            'archived_locations' => 0,
            'errors' => []
        ];
        
        try {
            DB::beginTransaction();
            
            // Get locations to archive grouped by bus and date
            $locationGroups = BusLocation::where('created_at', '<', $beforeDate)
                ->orderBy('created_at')
                ->get()
                ->groupBy(function ($location) {
                    return $location->bus_id . '_' . $location->created_at->format('Y-m-d');
                });
            
            foreach ($locationGroups as $groupKey => $locations) {
                [$busId, $date] = explode('_', $groupKey);
                
                $archiveResult = $this->archiveTripData($busId, $date, $locations);
                
                if ($archiveResult['success']) {
                    $results['archived_trips']++;
                    $results['archived_locations'] += $archiveResult['location_count'];
                } else {
                    $results['errors'][] = $archiveResult['error'];
                }
            }
            
            DB::commit();
            
            Log::info('Trip archiving completed', $results);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('Trip archiving failed', [
                'error' => $e->getMessage(),
                'before_date' => $beforeDate->toDateTimeString()
            ]);
        }
        
        return $results;
    }
    
    /**
     * Archive a single trip's data
     */
    private function archiveTripData(string $busId, string $date, $locations): array
    {
        try {
            $locationArray = $locations->toArray();
            $tripSummary = $this->generateTripSummary($locations);
            
            // Create or update historical record
            BusLocationHistory::updateOrCreate(
                [
                    'bus_id' => $busId,
                    'trip_date' => $date
                ],
                [
                    'location_data' => $locationArray,
                    'trip_summary' => $tripSummary
                ]
            );
            
            // Delete archived locations from real-time table
            BusLocation::whereIn('id', $locations->pluck('id'))->delete();
            
            return [
                'success' => true,
                'location_count' => $locations->count()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to archive trip {$busId} on {$date}: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate trip summary from location data
     */
    private function generateTripSummary($locations): array
    {
        $validatedLocations = $locations->where('is_validated', true);
        $speeds = $locations->whereNotNull('speed')->pluck('speed');
        
        return [
            'total_locations' => $locations->count(),
            'trusted_locations' => $validatedLocations->count(),
            'average_trust' => round($locations->avg('reputation_weight'), 3),
            'first_location' => $locations->first()->created_at->toDateTimeString(),
            'last_location' => $locations->last()->created_at->toDateTimeString(),
            'trip_duration_minutes' => $locations->first()->created_at->diffInMinutes($locations->last()->created_at),
            'unique_devices' => $locations->unique('device_token')->count(),
            'speed_stats' => [
                'average' => $speeds->isNotEmpty() ? round($speeds->avg(), 2) : null,
                'max' => $speeds->isNotEmpty() ? $speeds->max() : null,
                'min' => $speeds->isNotEmpty() ? $speeds->min() : null
            ],
            'trust_distribution' => [
                'high_trust' => $locations->where('reputation_weight', '>=', 0.7)->count(),
                'medium_trust' => $locations->whereBetween('reputation_weight', [0.3, 0.7])->count(),
                'low_trust' => $locations->where('reputation_weight', '<', 0.3)->count()
            ]
        ];
    }
    
    /**
     * Clean up real-time location tables
     */
    public function cleanupRealtimeData(Carbon $beforeDate = null): int
    {
        $beforeDate = $beforeDate ?? now()->subHours(6);
        $deletedCount = 0;
        
        try {
            do {
                $deleted = BusLocation::where('created_at', '<', $beforeDate)
                    ->limit(self::BATCH_SIZE)
                    ->delete();
                
                $deletedCount += $deleted;
                
                if ($deleted > 0) {
                    usleep(100000); // 100ms delay
                }
                
            } while ($deleted > 0);
            
            Log::info('Real-time data cleanup completed', [
                'deleted_count' => $deletedCount,
                'before_date' => $beforeDate->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Real-time data cleanup failed', [
                'error' => $e->getMessage(),
                'deleted_count' => $deletedCount
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Archive old historical data beyond retention period
     */
    public function archiveOldHistoricalData(int $retentionDays = null): array
    {
        $retentionDays = $retentionDays ?? self::DEFAULT_RETENTION_DAYS;
        $cutoffDate = now()->subDays($retentionDays);
        
        $results = [
            'archived_records' => 0,
            'deleted_records' => 0,
            'errors' => []
        ];
        
        try {
            // Get old historical records
            $oldRecords = BusLocationHistory::where('trip_date', '<', $cutoffDate->format('Y-m-d'))
                ->orderBy('trip_date')
                ->get();
            
            if ($oldRecords->isEmpty()) {
                return $results;
            }
            
            // Create compressed archive (optional - could be file-based or separate table)
            $archiveData = $this->createCompressedArchive($oldRecords);
            
            // Store compressed archive (implement based on requirements)
            $this->storeCompressedArchive($archiveData, $cutoffDate);
            
            // Delete old records
            $results['deleted_records'] = BusLocationHistory::where('trip_date', '<', $cutoffDate->format('Y-m-d'))
                ->delete();
            
            $results['archived_records'] = $oldRecords->count();
            
            Log::info('Old historical data archived', $results);
            
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Old historical data archiving failed', [
                'error' => $e->getMessage(),
                'retention_days' => $retentionDays
            ]);
        }
        
        return $results;
    }
    
    /**
     * Create compressed archive of old data
     */
    private function createCompressedArchive($records): array
    {
        $archive = [
            'archive_date' => now()->toDateTimeString(),
            'record_count' => $records->count(),
            'date_range' => [
                'start' => $records->min('trip_date'),
                'end' => $records->max('trip_date')
            ],
            'summary_stats' => [
                'total_trips' => $records->count(),
                'buses_tracked' => $records->unique('bus_id')->count(),
                'total_locations' => $records->sum(fn($r) => $r->trip_summary['total_locations'] ?? 0),
                'trusted_locations' => $records->sum(fn($r) => $r->trip_summary['trusted_locations'] ?? 0)
            ],
            'compressed_data' => gzcompress(json_encode($records->toArray()), 9)
        ];
        
        return $archive;
    }
    
    /**
     * Store compressed archive (implement based on storage requirements)
     */
    private function storeCompressedArchive(array $archiveData, Carbon $cutoffDate): void
    {
        // This could be implemented to store in:
        // 1. File system
        // 2. Separate archive database
        // 3. Cloud storage
        // 4. Separate archive table
        
        // For now, we'll log the archive creation
        Log::info('Compressed archive created', [
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
            'record_count' => $archiveData['record_count'],
            'compressed_size' => strlen($archiveData['compressed_data'])
        ]);
    }
    
    /**
     * Retrieve historical data for analysis
     */
    public function getHistoricalData(string $busId, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $records = BusLocationHistory::forBus($busId)
                ->dateRange($startDate, $endDate)
                ->orderBy('trip_date')
                ->get();
            
            $analysis = [
                'trip_count' => $records->count(),
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ],
                'trips' => [],
                'summary_stats' => [
                    'total_locations' => 0,
                    'trusted_locations' => 0,
                    'average_trust' => 0,
                    'total_duration_minutes' => 0
                ]
            ];
            
            foreach ($records as $record) {
                $tripStats = $record->getTripStats();
                $routeAnalysis = $record->getRouteAnalysis();
                
                $analysis['trips'][] = [
                    'date' => $record->trip_date->format('Y-m-d'),
                    'stats' => $tripStats,
                    'route_analysis' => $routeAnalysis
                ];
                
                // Accumulate summary stats
                $analysis['summary_stats']['total_locations'] += $tripStats['total_locations'];
                $analysis['summary_stats']['trusted_locations'] += $tripStats['trusted_locations'];
                $analysis['summary_stats']['total_duration_minutes'] += $tripStats['trip_duration'] ?? 0;
            }
            
            // Calculate averages
            if ($records->count() > 0) {
                $analysis['summary_stats']['average_trust'] = round(
                    $records->avg(fn($r) => $r->trip_summary['average_trust'] ?? 0), 3
                );
            }
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error('Historical data retrieval failed', [
                'bus_id' => $busId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => $e->getMessage(),
                'trip_count' => 0,
                'trips' => []
            ];
        }
    }
    
    /**
     * Clean up old tracking sessions
     */
    public function cleanupOldSessions(Carbon $beforeDate = null): int
    {
        $beforeDate = $beforeDate ?? now()->subHours(6);
        $deletedCount = 0;
        
        try {
            do {
                $deleted = UserTrackingSession::where('is_active', false)
                    ->where('ended_at', '<', $beforeDate)
                    ->limit(self::BATCH_SIZE)
                    ->delete();
                
                $deletedCount += $deleted;
                
                if ($deleted > 0) {
                    usleep(100000); // 100ms delay
                }
                
            } while ($deleted > 0);
            
            Log::info('Old tracking sessions cleaned up', [
                'deleted_count' => $deletedCount,
                'before_date' => $beforeDate->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Session cleanup failed', [
                'error' => $e->getMessage(),
                'deleted_count' => $deletedCount
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Get archiving statistics
     */
    public function getArchivingStats(): array
    {
        try {
            $realtimeCount = BusLocation::count();
            $historicalCount = BusLocationHistory::count();
            $oldestRealtime = BusLocation::orderBy('created_at')->first();
            $newestHistorical = BusLocationHistory::orderBy('trip_date', 'desc')->first();
            
            return [
                'realtime_locations' => $realtimeCount,
                'historical_trips' => $historicalCount,
                'oldest_realtime' => $oldestRealtime?->created_at?->toDateTimeString(),
                'newest_historical' => $newestHistorical?->trip_date?->format('Y-m-d'),
                'archiving_needed' => BusLocation::where('created_at', '<', now()->subHours(24))->count(),
                'old_historical' => BusLocationHistory::where('trip_date', '<', now()->subDays(self::DEFAULT_RETENTION_DAYS)->format('Y-m-d'))->count()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get archiving stats', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}