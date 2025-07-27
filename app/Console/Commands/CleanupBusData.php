<?php

namespace App\Console\Commands;

use App\Services\LocationBatchProcessor;
use App\Services\HistoricalDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupBusData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus:cleanup {--sessions : Clean up old tracking sessions} {--archive : Archive old location data} {--all : Run all cleanup operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old bus tracking data and inactive sessions (Legacy - use bus:archive for new functionality)';

    /**
     * Execute the console command.
     */
    public function handle(LocationBatchProcessor $batchProcessor, HistoricalDataService $historicalService)
    {
        $this->warn('This command is deprecated. Please use "php artisan bus:archive" for enhanced archiving functionality.');
        $this->info('Starting bus data cleanup...');
        
        $cleanupSessions = $this->option('sessions') || $this->option('all');
        $archiveData = $this->option('archive') || $this->option('all');
        
        $results = [];
        
        try {
            if ($cleanupSessions) {
                $this->info('Cleaning up old tracking sessions...');
                // Use new service for consistency
                $deletedSessions = $historicalService->cleanupOldSessions();
                $results['deleted_sessions'] = $deletedSessions;
                $this->info("Deleted {$deletedSessions} old tracking sessions");
            }
            
            if ($archiveData) {
                $this->info('Archiving old location data...');
                // Use legacy method for backward compatibility
                $archivedLocations = $batchProcessor->archiveOldLocations();
                $results['archived_locations'] = $archivedLocations;
                $this->info("Archived {$archivedLocations} old location records");
            }
            
            if (!$cleanupSessions && !$archiveData) {
                $this->warn('No cleanup operations specified. Use --sessions, --archive, or --all');
                $this->info('For enhanced functionality, use: php artisan bus:archive --help');
                return Command::INVALID;
            }
            
            // Display summary
            $this->table(
                ['Operation', 'Records Processed'],
                [
                    ['Deleted Sessions', $results['deleted_sessions'] ?? 'N/A'],
                    ['Archived Locations', $results['archived_locations'] ?? 'N/A'],
                ]
            );
            
            $this->info('Cleanup completed successfully!');
            $this->info('Consider using "php artisan bus:archive" for more comprehensive archiving options.');
            
            Log::info('Bus data cleanup completed', $results);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('Bus data cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
