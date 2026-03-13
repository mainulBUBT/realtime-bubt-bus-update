<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\Location;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo data for BUBT Bus Tracker...');

        // 1. Ensure we have basic data (period, routes, stops, buses)
        $this->ensureBasicData();

        // 2. Create additional users
        $this->createDemoUsers();

        // 3. Create active trips with locations
        $this->createActiveTrips();

        // 4. Create completed trips
        $this->createCompletedTrips();

        $this->command->info('Demo data created successfully!');
        $this->command->newLine();
        $this->command->info('=================================');
        $this->command->info('Demo Credentials:');
        $this->command->info('=================================');
        $this->command->info('Admin:   admin@bustracker.com / password');
        $this->command->info('Driver:  driver1@bustracker.com / password');
        $this->command->info('Driver:  driver2@bustracker.com / password');
        $this->command->info('Driver:  driver3@bustracker.com / password');
        $this->command->info('Student: student@bustracker.com / password');
        $this->command->info('=================================');
    }

    /**
     * Ensure basic data exists (buses, routes, stops, schedules)
     */
    private function ensureBasicData(): void
    {
        $this->command->info('Ensuring basic data exists...');

        // Create schedule period if not exists
        $period = SchedulePeriod::firstOrCreate(
            ['name' => 'Regular Schedule'],
            [
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear()->addYear(),
                'is_active' => true,
            ]
        );

        // Define bus data with real GPS coordinates around BUBT
        $busRoutes = [
            [
                'bus_plate' => 'DHAKA-METRO-15-2843',
                'bus_name' => 'Buriganga Express',
                'route_name' => 'Mirpur Road to BUBT',
                'direction' => 'up',
                'stops' => [
                    ['name' => 'Asad Gate', 'lat' => 23.7654, 'lng' => 90.3685, 'sequence' => 1],
                    ['name' => 'Shyamoli', 'lat' => 23.7688, 'lng' => 90.3686, 'sequence' => 2],
                    ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537, 'sequence' => 3],
                    ['name' => 'Pallabi', 'lat' => 23.8220, 'lng' => 90.3658, 'sequence' => 4],
                    ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 5],
                ],
            ],
            [
                'bus_plate' => 'DHAKA-METRO-11-1925',
                'bus_name' => 'Brahmaputra Service',
                'route_name' => 'Uttara to BUBT',
                'direction' => 'up',
                'stops' => [
                    ['name' => 'Hemayetpur', 'lat' => 23.7947, 'lng' => 90.2358, 'sequence' => 1],
                    ['name' => 'Amin Bazar', 'lat' => 23.7856, 'lng' => 90.3289, 'sequence' => 2],
                    ['name' => 'Gabtoli', 'lat' => 23.7781, 'lng' => 90.3497, 'sequence' => 3],
                    ['name' => 'Mazar Road', 'lat' => 23.7889, 'lng' => 90.3598, 'sequence' => 4],
                    ['name' => 'Mirpur-10', 'lat' => 23.8156, 'lng' => 90.3625, 'sequence' => 5],
                    ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 6],
                ],
            ],
            [
                'bus_plate' => 'DHAKA-METRO-13-2208',
                'bus_name' => 'Padma Deluxe',
                'route_name' => 'Shyamoli to BUBT',
                'direction' => 'up',
                'stops' => [
                    ['name' => 'Shyamoli Shishu Mela', 'lat' => 23.7688, 'lng' => 90.3686, 'sequence' => 1],
                    ['name' => 'Agargaon', 'lat' => 23.7778, 'lng' => 90.3822, 'sequence' => 2],
                    ['name' => 'Kazipara', 'lat' => 23.7958, 'lng' => 90.3698, 'sequence' => 3],
                    ['name' => 'Mirpur-10', 'lat' => 23.8156, 'lng' => 90.3625, 'sequence' => 4],
                    ['name' => 'Proshikha', 'lat' => 23.8589, 'lng' => 90.3725, 'sequence' => 5],
                    ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 6],
                ],
            ],
        ];

        foreach ($busRoutes as $data) {
            // Create or get bus
            $bus = Bus::firstOrCreate(
                ['plate_number' => $data['bus_plate']],
                [
                    'device_id' => 'DEVICE-' . strtoupper(substr(md5($data['bus_plate']), 0, 8)),
                    'capacity' => 40,
                    'status' => 'active',
                ]
            );

            // Create route
            $route = Route::firstOrCreate(
                [
                    'name' => $data['route_name'],
                    'direction' => $data['direction'],
                    'schedule_period_id' => $period->id,
                ],
                [
                    'origin_name' => $data['stops'][0]['name'],
                    'destination_name' => end($data['stops'])['name'],
                    'polyline' => json_encode(array_map(fn($s) => ['lat' => $s['lat'], 'lng' => $s['lng']], $data['stops'])),
                    'is_active' => true,
                ]
            );

            // Create stops
            foreach ($data['stops'] as $stop) {
                RouteStop::firstOrCreate(
                    [
                        'route_id' => $route->id,
                        'name' => $stop['name'],
                        'sequence' => $stop['sequence'],
                    ],
                    [
                        'lat' => $stop['lat'],
                        'lng' => $stop['lng'],
                    ]
                );
            }

            // Create schedule
            Schedule::firstOrCreate(
                [
                    'route_id' => $route->id,
                    'departure_time' => '08:00:00',
                ],
                [
                    'weekdays' => json_encode(['mon' => true, 'tue' => true, 'wed' => true, 'thu' => true, 'fri' => true, 'sat' => false, 'sun' => false]),
                    'effective_from' => now()->toDateString(),
                    'effective_to' => null,
                    'schedule_type' => 'regular',
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Basic data ensured.');
    }

    /**
     * Create additional demo users
     */
    private function createDemoUsers(): void
    {
        $this->command->info('Creating demo users...');

        // Create additional drivers
        for ($i = 1; $i <= 3; $i++) {
            User::firstOrCreate(
                ['email' => "driver{$i}@bustracker.com"],
                [
                    'name' => "Driver {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'driver',
                    'phone' => "+8801712345" . str_pad($i, 2, '0', STR_PAD_LEFT),
                ]
            );
        }

        // Create additional students
        for ($i = 1; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "student{$i}@bustracker.com"],
                [
                    'name' => "Student {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'phone' => "+8801812345" . str_pad($i, 2, '0', STR_PAD_LEFT),
                ]
            );
        }

        $this->command->info('Demo users created.');
    }

    /**
     * Create active trips with real-time locations
     */
    private function createActiveTrips(): void
    {
        $this->command->info('Creating active trips with locations...');

        $buses = Bus::take(3)->get();
        $drivers = User::where('role', 'driver')->take(3)->get();

        $now = now();
        $tripData = [
            [
                'status' => 'ongoing',
                'delay_minutes' => 0,
                'location_progress' => 0.7, // 70% along route
            ],
            [
                'status' => 'ongoing',
                'delay_minutes' => 15, // Delayed
                'location_progress' => 0.4, // 40% along route (behind)
            ],
            [
                'status' => 'ongoing',
                'delay_minutes' => 0,
                'location_progress' => 0.9, // 90% along route (near campus)
            ],
        ];

        foreach ($buses as $index => $bus) {
            if (!isset($drivers[$index]) || !isset($tripData[$index])) {
                break;
            }

            $data = $tripData[$index];
            $driver = $drivers[$index];
            $route = Route::where('is_active', true)->skip($index)->first();

            if (!$route) {
                continue;
            }

            // Create trip
            $trip = Trip::create([
                'bus_id' => $bus->id,
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'schedule_id' => $route->schedules()->first()?->id,
                'trip_date' => $now->toDateString(),
                'status' => $data['status'],
                'started_at' => $now->copy()->subMinutes(30 + $data['delay_minutes']),
            ]);

            // Create location history showing bus movement
            $stops = $route->stops()->orderBy('sequence')->get();
            $totalStops = $stops->count();
            $targetStopIndex = min((int)($totalStops * $data['location_progress']), $totalStops - 1);
            $targetStop = $stops->get($targetStopIndex);

            // Generate multiple locations leading to current position
            $numLocations = 5;
            for ($i = 0; $i < $numLocations; $i++) {
                $progressOffset = ($i / $numLocations) * $data['location_progress'];
                $stopIndex = min((int)($totalStops * $progressOffset), $totalStops - 1);
                $stop = $stops->get($stopIndex);

                if (!$stop) {
                    continue;
                }

                // Add slight randomness to position
                $latOffset = (rand(-50, 50) / 100000); // ±0.0005 degrees
                $lngOffset = (rand(-50, 50) / 100000);

                $minutesAgo = ($numLocations - $i) * 3; // Each location 3 mins apart
                $recordedAt = $now->copy()->subMinutes($minutesAgo);

                Location::create([
                    'trip_id' => $trip->id,
                    'bus_id' => $bus->id,
                    'lat' => $stop->lat + $latOffset,
                    'lng' => $stop->lng + $lngOffset,
                    'speed' => rand(20, 35), // 20-35 km/h
                    'recorded_at' => $recordedAt,
                ]);
            }

            // Create latest location at current position
            if ($targetStop) {
                $latOffset = (rand(-30, 30) / 100000);
                $lngOffset = (rand(-30, 30) / 100000);

                Location::create([
                    'trip_id' => $trip->id,
                    'bus_id' => $bus->id,
                    'lat' => $targetStop->lat + $latOffset,
                    'lng' => $targetStop->lng + $lngOffset,
                    'speed' => rand(25, 40),
                    'recorded_at' => $now->copy()->subSeconds(rand(10, 120)),
                ]);
            }
        }

        $this->command->info('Active trips created.');
    }

    /**
     * Create completed trips for historical data
     */
    private function createCompletedTrips(): void
    {
        $this->command->info('Creating completed trips...');

        $buses = Bus::skip(3)->take(2)->get();
        $drivers = User::where('role', 'driver')->skip(3)->take(2)->get();

        for ($i = 0; $i < 2; $i++) {
            if (!isset($buses[$i]) || !isset($drivers[$i])) {
                break;
            }

            $bus = $buses[$i];
            $driver = $drivers[$i];
            $route = Route::where('is_active', true)->skip($i)->first();

            if (!$route) {
                continue;
            }

            $endedAt = now()->subHours(rand(2, 6));
            $startedAt = $endedAt->copy()->subMinutes(rand(40, 60));

            $trip = Trip::create([
                'bus_id' => $bus->id,
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'schedule_id' => $route->schedules()->first()?->id,
                'trip_date' => $endedAt->toDateString(),
                'status' => 'completed',
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);

            // Add a few locations for the completed trip
            $stops = $route->stops()->orderBy('sequence')->get();
            foreach ($stops as $index => $stop) {
                if ($index % 2 !== 0) continue; // Only every other stop

                $elapsedMinutes = ($index / $stops->count()) * 45; // 45 min trip
                $recordedAt = $startedAt->copy()->addMinutes($elapsedMinutes);

                Location::create([
                    'trip_id' => $trip->id,
                    'bus_id' => $bus->id,
                    'lat' => $stop->lat,
                    'lng' => $stop->lng,
                    'speed' => rand(25, 40),
                    'recorded_at' => $recordedAt,
                ]);
            }
        }

        $this->command->info('Completed trips created.');
    }
}
