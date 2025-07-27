<?php

namespace App\Console\Commands;

use App\Services\SmartBroadcastingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateBusPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus:update-positions {--force : Force update ignoring cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bus positions using trusted user data and broadcast updates';

    /**
     * Execute the console command.
     */
    public function handle(SmartBroadcastingService $broadcastingService)
    {
        $this->info('Starting bus position update...');
        
        try {
            if ($this->option('force')) {
                $broadcastingService->forceUpdatePositions();
                $this->info('Forced position update completed.');
            } else {
                $broadcastingService->updateBusPositions();
                $this->info('Position update completed.');
            }
            
            // Display statistics
            $stats = $broadcastingService->getStatistics();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Buses', $stats['total_buses']],
                    ['Active Buses', $stats['active_buses']],
                    ['Reliable Buses', $stats['reliable_buses']],
                    ['Locations Today', $stats['total_locations_today']],
                    ['Trusted Locations Today', $stats['trusted_locations_today']],
                    ['Active Sessions', $stats['active_sessions']],
                ]
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Bus position update failed: ' . $e->getMessage());
            Log::error('Bus position update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
