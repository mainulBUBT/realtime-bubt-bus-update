<?php

namespace App\Console\Commands;

use App\Services\TripCompletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectTripCompletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus:detect-completion 
                            {--bus-id= : Check completion for specific bus only}
                            {--force-complete= : Force complete trip for specific bus}
                            {--transition= : Handle trip transition for specific bus}
                            {--stats : Show completion detection statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect completed bus trips and handle trip transitions';

    /**
     * Execute the console command.
     */
    public function handle(TripCompletionService $completionService)
    {
        $this->info('Starting trip completion detection...');
        
        $busId = $this->option('bus-id');
        $forceComplete = $this->option('force-complete');
        $transition = $this->option('transition');
        $showStats = $this->option('stats');
        
        try {
            // Handle force completion
            if ($forceComplete) {
                return $this->handleForceCompletion($completionService, $forceComplete);
            }
            
            // Handle trip transition
            if ($transition) {
                return $this->handleTripTransition($completionService, $transition);
            }
            
            // Show statistics
            if ($showStats) {
                $this->displayCompletionStats($completionService);
                return Command::SUCCESS;
            }
            
            // Run normal completion detection
            $results = $completionService->detectCompletedTrips();
            
            $this->displayResults($results);
            
            if (!empty($results['errors'])) {
                $this->warn('Some errors occurred during detection:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
                return Command::FAILURE;
            }
            
            $this->info('Trip completion detection completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Trip completion detection failed: ' . $e->getMessage());
            Log::error('Trip completion detection command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Handle force completion for specific bus
     */
    private function handleForceCompletion(TripCompletionService $completionService, string $busId): int
    {
        $this->info("Force completing trip for bus: {$busId}");
        
        try {
            // Stop GPS data collection
            $stoppedSessions = $completionService->stopGPSDataCollection($busId);
            $this->info("Stopped {$stoppedSessions} tracking sessions");
            
            // Handle transition
            $transitionResult = $completionService->handleTripTransition($busId);
            
            if ($transitionResult['success']) {
                $this->info("Trip transition completed successfully");
                $this->info("Cleaned {$transitionResult['cleaned_locations']} old location records");
            } else {
                $this->error("Trip transition failed: " . $transitionResult['error']);
                return Command::FAILURE;
            }
            
            $this->info("Force completion for bus {$busId} completed successfully!");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Force completion failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Handle trip transition for specific bus
     */
    private function handleTripTransition(TripCompletionService $completionService, string $busId): int
    {
        $this->info("Handling trip transition for bus: {$busId}");
        
        try {
            $result = $completionService->handleTripTransition($busId);
            
            if ($result['success']) {
                $this->info("Trip transition completed successfully");
                $this->info("Cleaned {$result['cleaned_locations']} old location records");
                return Command::SUCCESS;
            } else {
                $this->error("Trip transition failed: " . $result['error']);
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Trip transition failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display completion detection statistics
     */
    private function displayCompletionStats(TripCompletionService $completionService): void
    {
        $this->info('Trip Completion Detection Statistics:');
        $this->line('');
        
        // This would require additional methods in TripCompletionService
        // For now, we'll show basic info
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Schedules', \App\Models\BusSchedule::active()->count()],
                ['Currently Running', \App\Models\BusSchedule::active()->get()->filter(fn($s) => $s->isCurrentlyActive())->count()],
                ['Active Sessions', \App\Models\UserTrackingSession::where('is_active', true)->count()],
                ['Current Positions', \App\Models\BusCurrentPosition::count()],
                ['Recent Locations (1h)', \App\Models\BusLocation::where('created_at', '>=', now()->subHour())->count()]
            ]
        );
        
        $this->line('');
    }
    
    /**
     * Display detection results
     */
    private function displayResults(array $results): void
    {
        $this->line('');
        $this->info('Trip Completion Detection Results:');
        
        if (empty($results['completed_trips'])) {
            $this->info('No completed trips detected.');
        } else {
            $this->info('Completed Trips:');
            
            foreach ($results['completed_trips'] as $trip) {
                $this->line("  Bus {$trip['bus_id']}:");
                $this->line("    - Direction: {$trip['trip_direction']}");
                $this->line("    - Completion Reason: {$trip['completion_reason']}");
                $this->line("    - Completion Time: {$trip['completion_time']}");
                
                if ($trip['final_destination']) {
                    $this->line("    - Final Destination: {$trip['final_destination']}");
                }
                
                $this->line('');
            }
        }
        
        // Summary table
        $this->table(
            ['Operation', 'Count'],
            [
                ['Completed Trips', count($results['completed_trips'])],
                ['Stopped Sessions', $results['stopped_sessions']],
                ['Archived Records', $results['archived_data']],
                ['Errors', count($results['errors'])]
            ]
        );
        
        $this->line('');
    }
}