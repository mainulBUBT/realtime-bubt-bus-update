<?php

namespace App\Console\Commands;

use App\Services\HistoricalDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArchiveBusData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus:archive 
                            {--trips : Archive completed trip data to history tables}
                            {--cleanup : Clean up old real-time location data}
                            {--sessions : Clean up old tracking sessions}
                            {--old-history : Archive old historical data beyond retention period}
                            {--retention-days=90 : Number of days to retain historical data}
                            {--before-date= : Archive data before this date (Y-m-d H:i:s format)}
                            {--stats : Show archiving statistics}
                            {--all : Run all archiving operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive bus tracking data and manage historical data retention';

    /**
     * Execute the console command.
     */
    public function handle(HistoricalDataService $historicalService)
    {
        $this->info('Starting bus data archiving...');
        
        $archiveTrips = $this->option('trips') || $this->option('all');
        $cleanupRealtime = $this->option('cleanup') || $this->option('all');
        $cleanupSessions = $this->option('sessions') || $this->option('all');
        $archiveOldHistory = $this->option('old-history') || $this->option('all');
        $showStats = $this->option('stats');
        
        $retentionDays = (int) $this->option('retention-days');
        $beforeDate = $this->option('before-date') ? Carbon::parse($this->option('before-date')) : null;
        
        $results = [];
        
        try {
            // Show current statistics
            if ($showStats || $this->option('all')) {
                $this->displayArchivingStats($historicalService);
            }
            
            // Archive completed trips
            if ($archiveTrips) {
                $this->info('Archiving completed trip data...');
                $tripResults = $historicalService->archiveCompletedTrips($beforeDate);
                $results['trip_archiving'] = $tripResults;
                
                $this->info("Archived {$tripResults['archived_trips']} trips with {$tripResults['archived_locations']} locations");
                
                if (!empty($tripResults['errors'])) {
                    $this->warn('Trip archiving errors:');
                    foreach ($tripResults['errors'] as $error) {
                        $this->error("  - {$error}");
                    }
                }
            }
            
            // Clean up real-time data
            if ($cleanupRealtime) {
                $this->info('Cleaning up old real-time location data...');
                $cleanedLocations = $historicalService->cleanupRealtimeData($beforeDate);
                $results['realtime_cleanup'] = $cleanedLocations;
                $this->info("Cleaned up {$cleanedLocations} old location records");
            }
            
            // Clean up old sessions
            if ($cleanupSessions) {
                $this->info('Cleaning up old tracking sessions...');
                $cleanedSessions = $historicalService->cleanupOldSessions($beforeDate);
                $results['session_cleanup'] = $cleanedSessions;
                $this->info("Cleaned up {$cleanedSessions} old tracking sessions");
            }
            
            // Archive old historical data
            if ($archiveOldHistory) {
                $this->info("Archiving historical data older than {$retentionDays} days...");
                $historyResults = $historicalService->archiveOldHistoricalData($retentionDays);
                $results['old_history_archiving'] = $historyResults;
                
                $this->info("Archived {$historyResults['archived_records']} old records, deleted {$historyResults['deleted_records']} records");
                
                if (!empty($historyResults['errors'])) {
                    $this->warn('Historical data archiving errors:');
                    foreach ($historyResults['errors'] as $error) {
                        $this->error("  - {$error}");
                    }
                }
            }
            
            // Show no operations message
            if (!$archiveTrips && !$cleanupRealtime && !$cleanupSessions && !$archiveOldHistory && !$showStats) {
                $this->warn('No archiving operations specified. Use --trips, --cleanup, --sessions, --old-history, --stats, or --all');
                return Command::INVALID;
            }
            
            // Display summary table
            if (!$showStats) {
                $this->displayResultsSummary($results);
            }
            
            $this->info('Archiving completed successfully!');
            
            Log::info('Bus data archiving completed', $results);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Archiving failed: ' . $e->getMessage());
            Log::error('Bus data archiving failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Display current archiving statistics
     */
    private function displayArchivingStats(HistoricalDataService $historicalService): void
    {
        $this->info('Current Archiving Statistics:');
        $this->line('');
        
        $stats = $historicalService->getArchivingStats();
        
        if (isset($stats['error'])) {
            $this->error('Failed to retrieve statistics: ' . $stats['error']);
            return;
        }
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Real-time Locations', number_format($stats['realtime_locations'])],
                ['Historical Trips', number_format($stats['historical_trips'])],
                ['Oldest Real-time Data', $stats['oldest_realtime'] ?? 'N/A'],
                ['Newest Historical Data', $stats['newest_historical'] ?? 'N/A'],
                ['Locations Needing Archive', number_format($stats['archiving_needed'])],
                ['Old Historical Records', number_format($stats['old_historical'])]
            ]
        );
        
        $this->line('');
    }
    
    /**
     * Display results summary table
     */
    private function displayResultsSummary(array $results): void
    {
        $this->line('');
        $this->info('Archiving Results Summary:');
        
        $summaryData = [];
        
        if (isset($results['trip_archiving'])) {
            $summaryData[] = ['Trip Archiving - Trips', $results['trip_archiving']['archived_trips']];
            $summaryData[] = ['Trip Archiving - Locations', $results['trip_archiving']['archived_locations']];
            $summaryData[] = ['Trip Archiving - Errors', count($results['trip_archiving']['errors'])];
        }
        
        if (isset($results['realtime_cleanup'])) {
            $summaryData[] = ['Real-time Cleanup', $results['realtime_cleanup']];
        }
        
        if (isset($results['session_cleanup'])) {
            $summaryData[] = ['Session Cleanup', $results['session_cleanup']];
        }
        
        if (isset($results['old_history_archiving'])) {
            $summaryData[] = ['Old History - Archived', $results['old_history_archiving']['archived_records']];
            $summaryData[] = ['Old History - Deleted', $results['old_history_archiving']['deleted_records']];
            $summaryData[] = ['Old History - Errors', count($results['old_history_archiving']['errors'])];
        }
        
        if (!empty($summaryData)) {
            $this->table(['Operation', 'Records Processed'], $summaryData);
        }
        
        $this->line('');
    }
}