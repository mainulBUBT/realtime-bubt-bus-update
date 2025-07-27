<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\BusTrackerDemoSeeder;

class SeedBusTrackerDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus-tracker:seed-demo {--fresh : Clear existing data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo data for BUBT Bus Tracker application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚌 BUBT Bus Tracker Demo Data Seeder');
        $this->info('=====================================');

        if ($this->option('fresh')) {
            $this->warn('⚠️  This will clear all existing bus tracker data!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting demo data seeding...');
        
        try {
            $seeder = new BusTrackerDemoSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            
            $this->newLine();
            $this->info('🎉 Demo data seeded successfully!');
            $this->info('You can now test the bus tracker application with realistic data.');
            
            $this->newLine();
            $this->info('💡 Quick test commands:');
            $this->line('   • Visit: http://localhost:8000');
            $this->line('   • API Health: curl http://localhost:8000/api/polling/health');
            $this->line('   • Bus Locations: curl http://localhost:8000/api/polling/locations');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error seeding demo data: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}