<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BusScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buses = [
            [
                'bus_id' => 'B1',
                'route_name' => 'BUBT - Asad Gate Route',
                'departure_time' => '07:00',
                'return_time' => '17:00',
            ],
            [
                'bus_id' => 'B2',
                'route_name' => 'BUBT - Asad Gate Route',
                'departure_time' => '07:30',
                'return_time' => '17:30',
            ],
            [
                'bus_id' => 'B3',
                'route_name' => 'BUBT - Asad Gate Route',
                'departure_time' => '08:00',
                'return_time' => '18:00',
            ],
            [
                'bus_id' => 'B4',
                'route_name' => 'BUBT - Asad Gate Route',
                'departure_time' => '08:30',
                'return_time' => '18:30',
            ],
            [
                'bus_id' => 'B5',
                'route_name' => 'BUBT - Asad Gate Route',
                'departure_time' => '09:00',
                'return_time' => '19:00',
            ],
        ];

        // Route stops with coordinates (Dhaka, Bangladesh)
        $routeStops = [
            [
                'stop_name' => 'BUBT Campus',
                'stop_order' => 1,
                'latitude' => 23.7956,
                'longitude' => 90.3537,
                'coverage_radius' => 150,
            ],
            [
                'stop_name' => 'Rainkhola',
                'stop_order' => 2,
                'latitude' => 23.7850,
                'longitude' => 90.3650,
                'coverage_radius' => 100,
            ],
            [
                'stop_name' => 'Mirpur-1',
                'stop_order' => 3,
                'latitude' => 23.7956,
                'longitude' => 90.3537,
                'coverage_radius' => 120,
            ],
            [
                'stop_name' => 'Shyamoli',
                'stop_order' => 4,
                'latitude' => 23.7687,
                'longitude' => 90.3682,
                'coverage_radius' => 100,
            ],
            [
                'stop_name' => 'Asad Gate',
                'stop_order' => 5,
                'latitude' => 23.7550,
                'longitude' => 90.3850,
                'coverage_radius' => 100,
            ],
        ];

        foreach ($buses as $busData) {
            $schedule = \App\Models\BusSchedule::create([
                'bus_id' => $busData['bus_id'],
                'route_name' => $busData['route_name'],
                'departure_time' => $busData['departure_time'],
                'return_time' => $busData['return_time'],
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true,
                'description' => "University bus {$busData['bus_id']} serving campus to city route"
            ]);

            // Create route stops for this schedule
            foreach ($routeStops as $index => $stop) {
                // Calculate estimated times based on departure time
                $departureTime = \Carbon\Carbon::parse($busData['departure_time']);
                $returnTime = \Carbon\Carbon::parse($busData['return_time']);
                
                // Add 15 minutes per stop for departure trip
                $estimatedDepartureTime = $departureTime->copy()->addMinutes($index * 15);
                
                // Subtract 15 minutes per stop from return time (reverse order)
                $estimatedReturnTime = $returnTime->copy()->subMinutes((count($routeStops) - $index - 1) * 15);

                \App\Models\BusRoute::create([
                    'schedule_id' => $schedule->id,
                    'stop_name' => $stop['stop_name'],
                    'stop_order' => $stop['stop_order'],
                    'latitude' => $stop['latitude'],
                    'longitude' => $stop['longitude'],
                    'coverage_radius' => $stop['coverage_radius'],
                    'estimated_departure_time' => $estimatedDepartureTime->format('H:i'),
                    'estimated_return_time' => $estimatedReturnTime->format('H:i'),
                    'departure_duration_minutes' => $index * 15,
                    'return_duration_minutes' => (count($routeStops) - $index - 1) * 15,
                ]);
            }
        }
    }
}
