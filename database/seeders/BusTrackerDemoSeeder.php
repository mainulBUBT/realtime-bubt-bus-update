<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusTimelineProgression;
use Carbon\Carbon;

class BusTrackerDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚌 Seeding BUBT Bus Tracker Demo Data...');

        // Clear existing data
        $this->command->info('Clearing existing data...');
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BusTimelineProgression::truncate();
        BusLocation::truncate();
        BusRoute::truncate();
        BusSchedule::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Bus Schedules
        $this->command->info('Creating bus schedules...');
        $schedules = $this->createBusSchedules();

        // Create Routes for each schedule
        $this->command->info('Creating bus routes...');
        foreach ($schedules as $schedule) {
            $this->createRoutesForSchedule($schedule);
        }

        // Create sample location data
        $this->command->info('Creating sample location data...');
        $this->createSampleLocations();

        // Create timeline progression data
        $this->command->info('Creating timeline progression data...');
        $this->createTimelineProgression();

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - Bus Schedules: ' . BusSchedule::count());
        $this->command->info('   - Bus Routes: ' . BusRoute::count());
        $this->command->info('   - Location Records: ' . BusLocation::count());
        $this->command->info('   - Timeline Records: ' . BusTimelineProgression::count());
    }

    /**
     * Create bus schedules
     */
    private function createBusSchedules(): array
    {
        $schedules = [
            [
                'bus_id' => 'B1',
                'route_name' => 'BUBT Campus - Asad Gate',
                'departure_time' => '07:00:00',
                'return_time' => '17:00:00',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true,
                'description' => 'Main route from BUBT Campus to Asad Gate via Shyamoli and Mirpur'
            ],
            [
                'bus_id' => 'B2',
                'route_name' => 'BUBT Campus - Mirpur Circuit',
                'departure_time' => '07:30:00',
                'return_time' => '17:30:00',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true,
                'description' => 'Circuit route covering Mirpur area'
            ],
            [
                'bus_id' => 'B3',
                'route_name' => 'BUBT Campus - Dhanmondi',
                'departure_time' => '08:00:00',
                'return_time' => '18:00:00',
                'days_of_week' => ['monday', 'wednesday', 'friday'],
                'is_active' => true,
                'description' => 'Route to Dhanmondi area via New Market'
            ],
            [
                'bus_id' => 'B4',
                'route_name' => 'BUBT Campus - Uttara',
                'departure_time' => '06:30:00',
                'return_time' => '16:30:00',
                'days_of_week' => ['tuesday', 'thursday'],
                'is_active' => true,
                'description' => 'Route to Uttara via Airport Road'
            ],
            [
                'bus_id' => 'B5',
                'route_name' => 'BUBT Campus - Old Dhaka',
                'departure_time' => '08:30:00',
                'return_time' => '18:30:00',
                'days_of_week' => ['saturday'],
                'is_active' => false,
                'description' => 'Weekend route to Old Dhaka (Currently inactive)'
            ]
        ];

        $createdSchedules = [];
        foreach ($schedules as $scheduleData) {
            $schedule = BusSchedule::create($scheduleData);
            $createdSchedules[] = $schedule;
            $this->command->info("   ✓ Created schedule for {$schedule->bus_id}: {$schedule->route_name}");
        }

        return $createdSchedules;
    }

    /**
     * Create routes for a schedule
     */
    private function createRoutesForSchedule(BusSchedule $schedule): void
    {
        $routeConfigs = [
            'B1' => [ // BUBT Campus - Asad Gate
                ['BUBT Campus', 23.8213, 90.3541, 1, '07:00', '16:45', 150],
                ['Rainkhola', 23.8069, 90.3554, 2, '07:15', '16:30', 200],
                ['Mirpur-1', 23.7937, 90.3629, 3, '07:30', '16:15', 300],
                ['Shyamoli', 23.7746, 90.3657, 4, '07:45', '16:00', 250],
                ['Asad Gate', 23.7651, 90.3668, 5, '08:00', '15:45', 200],
            ],
            'B2' => [ // BUBT Campus - Mirpur Circuit
                ['BUBT Campus', 23.8213, 90.3541, 1, '07:30', '17:15', 150],
                ['Mirpur-2', 23.8150, 90.3580, 2, '07:40', '17:05', 200],
                ['Mirpur-10', 23.8050, 90.3650, 3, '07:55', '16:50', 250],
                ['Mirpur-1', 23.7937, 90.3629, 4, '08:10', '16:35', 300],
                ['Kazipara', 23.7850, 90.3700, 5, '08:25', '16:20', 200],
            ],
            'B3' => [ // BUBT Campus - Dhanmondi
                ['BUBT Campus', 23.8213, 90.3541, 1, '08:00', '17:45', 150],
                ['Mirpur Road', 23.7800, 90.3600, 2, '08:20', '17:25', 200],
                ['New Market', 23.7350, 90.3850, 3, '08:45', '17:00', 250],
                ['Dhanmondi 27', 23.7450, 90.3750, 4, '09:00', '16:45', 200],
                ['Dhanmondi 32', 23.7500, 90.3800, 5, '09:15', '16:30', 200],
            ],
            'B4' => [ // BUBT Campus - Uttara
                ['BUBT Campus', 23.8213, 90.3541, 1, '06:30', '16:15', 150],
                ['Pallabi', 23.8300, 90.3650, 2, '06:45', '16:00', 200],
                ['Airport Road', 23.8500, 90.3900, 3, '07:10', '15:35', 300],
                ['Uttara Sector 3', 23.8750, 90.3950, 4, '07:30', '15:15', 250],
                ['Uttara Sector 7', 23.8800, 90.4000, 5, '07:45', '15:00', 200],
            ],
            'B5' => [ // BUBT Campus - Old Dhaka
                ['BUBT Campus', 23.8213, 90.3541, 1, '08:30', '18:15', 150],
                ['Gabtoli', 23.7950, 90.3450, 2, '08:50', '17:55', 200],
                ['Mohammadpur', 23.7650, 90.3550, 3, '09:15', '17:30', 250],
                ['Newmarket', 23.7350, 90.3850, 4, '09:40', '17:05', 200],
                ['Sadarghat', 23.7050, 90.4100, 5, '10:00', '16:45', 300],
            ]
        ];

        $routes = $routeConfigs[$schedule->bus_id] ?? [];
        
        foreach ($routes as $routeData) {
            BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => $routeData[0],
                'latitude' => $routeData[1],
                'longitude' => $routeData[2],
                'stop_order' => $routeData[3],
                'estimated_departure_time' => $routeData[4] . ':00',
                'estimated_return_time' => $routeData[5] . ':00',
                'coverage_radius' => $routeData[6],
                'departure_duration_minutes' => $routeData[3] * 15, // 15 min between stops
                'return_duration_minutes' => (6 - $routeData[3]) * 15
            ]);
        }
    }

    /**
     * Create sample location data
     */
    private function createSampleLocations(): void
    {
        $activeBuses = ['B1', 'B2', 'B3'];
        $now = Carbon::now();

        foreach ($activeBuses as $busId) {
            // Create recent location history (last 30 minutes)
            for ($i = 30; $i >= 0; $i -= 5) {
                $timestamp = $now->copy()->subMinutes($i);
                
                // Get a route for this bus to simulate movement
                $schedule = BusSchedule::where('bus_id', $busId)->first();
                if (!$schedule) continue;
                
                $routes = $schedule->routes()->orderBy('stop_order')->get();
                if ($routes->isEmpty()) continue;

                // Simulate bus movement along the route
                $routeIndex = ($i / 5) % $routes->count();
                $currentRoute = $routes[$routeIndex];
                
                // Add some random variation to simulate real movement
                $latVariation = (rand(-50, 50) / 100000); // ±0.0005 degrees
                $lngVariation = (rand(-50, 50) / 100000);

                BusLocation::create([
                    'bus_id' => $busId,
                    'device_token' => 'demo_token_' . $busId . '_' . $i,
                    'latitude' => $currentRoute->latitude + $latVariation,
                    'longitude' => $currentRoute->longitude + $lngVariation,
                    'accuracy' => rand(5, 20),
                    'speed' => rand(0, 40),
                    'reputation_weight' => rand(70, 100) / 100,
                    'is_validated' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ]);
            }

            $this->command->info("   ✓ Created location history for bus {$busId}");
        }
    }

    /**
     * Create timeline progression data
     */
    private function createTimelineProgression(): void
    {
        $activeBuses = ['B1', 'B2'];
        
        foreach ($activeBuses as $busId) {
            $schedule = BusSchedule::where('bus_id', $busId)->first();
            if (!$schedule) continue;

            $routes = $schedule->routes()->orderBy('stop_order')->get();
            if ($routes->isEmpty()) continue;

            // Determine current trip direction based on time
            $now = Carbon::now();
            $currentTime = $now->format('H:i:s');
            $departureTime = $schedule->departure_time;
            $returnTime = $schedule->return_time;
            
            $isReturnTrip = $currentTime > '12:00:00'; // Assume return trip after noon
            $direction = $isReturnTrip ? 'return' : 'departure';

            // Create progression for each stop
            foreach ($routes as $index => $route) {
                $status = 'upcoming';
                $reachedAt = null;
                $progressPercentage = 0;
                $etaMinutes = null;

                // Simulate current progress
                if ($index === 0) {
                    $status = 'completed';
                    $reachedAt = $now->copy()->subMinutes(30);
                    $progressPercentage = 100;
                } elseif ($index === 1) {
                    $status = 'completed';
                    $reachedAt = $now->copy()->subMinutes(15);
                    $progressPercentage = 100;
                } elseif ($index === 2) {
                    $status = 'current';
                    $progressPercentage = rand(30, 80);
                    $etaMinutes = rand(5, 15);
                } else {
                    $status = 'upcoming';
                    $etaMinutes = ($index - 2) * 15 + rand(5, 10);
                }

                BusTimelineProgression::create([
                    'bus_id' => $busId,
                    'schedule_id' => $schedule->id,
                    'route_id' => $route->id,
                    'trip_direction' => $direction,
                    'status' => $status,
                    'reached_at' => $reachedAt,
                    'estimated_arrival' => $etaMinutes ? $now->copy()->addMinutes($etaMinutes) : null,
                    'progress_percentage' => $progressPercentage,
                    'distance_from_previous' => $index > 0 ? rand(500, 2000) : 0,
                    'eta_minutes' => $etaMinutes,
                    'confidence_score' => rand(70, 95) / 100,
                    'location_data' => json_encode([
                        'last_update' => $now->toISOString(),
                        'accuracy' => rand(5, 15),
                        'speed' => rand(10, 35)
                    ]),
                    'is_active_trip' => true
                ]);
            }

            $this->command->info("   ✓ Created timeline progression for bus {$busId} ({$direction} trip)");
        }
    }
}