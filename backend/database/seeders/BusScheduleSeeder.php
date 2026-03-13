<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\BusStop;
use App\Models\BusSchedule;
use App\Models\SchedulePeriod;
use Illuminate\Support\Facades\DB;

class BusScheduleSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data to prevent duplicates if run multiple times
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BusSchedule::truncate();
        BusStop::truncate();
        BusRoute::truncate();
        Bus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create Schedule Period (Regular)
        $period = SchedulePeriod::firstOrCreate(
            ['slug' => 'regular-schedule'],
            [
                'name' => 'Regular Schedule',
                'description' => 'Default regular schedule',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear()->addYear(),
                'is_default' => true,
                'is_active' => true
            ]
        );

        // 2. Define Bus Data with REAL GPS Coordinates from Dhaka
        $buses = [
            [
                'code' => 'B1',
                'name' => 'Buriganga',
                'stops' => [
                    ['name' => 'Asad Gate', 'lat' => 23.7654, 'lng' => 90.3685],
                    ['name' => 'Shyamoli', 'lat' => 23.7688, 'lng' => 90.3686],
                    ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537],
                    ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698],
                    ['name' => 'BUBT', 'lat' => 23.8103, 'lng' => 90.4125], // BUBT Campus
                ],
                'times_up' => ['07:00:00', '17:00:00'],
                'times_down' => ['16:10:00', '21:25:00'],
            ],
            [
                'code' => 'B2',
                'name' => 'Brahmaputra',
                'stops' => [
                    ['name' => 'Hemayetpur', 'lat' => 23.7947, 'lng' => 90.2358],
                    ['name' => 'Amin Bazar', 'lat' => 23.7856, 'lng' => 90.3289],
                    ['name' => 'Gabtoli', 'lat' => 23.7781, 'lng' => 90.3497],
                    ['name' => 'Mazar Road', 'lat' => 23.7889, 'lng' => 90.3598],
                    ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537],
                    ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698],
                    ['name' => 'BUBT', 'lat' => 23.8103, 'lng' => 90.4125],
                ],
                'times_up' => ['07:00:00', '17:00:00'],
                'times_down' => ['16:10:00', '21:25:00'],
            ],
            [
                'code' => 'B3',
                'name' => 'Padma',
                'stops' => [
                    ['name' => 'Shyamoli (Shishu Mela)', 'lat' => 23.7688, 'lng' => 90.3686],
                    ['name' => 'Agargaon', 'lat' => 23.7778, 'lng' => 90.3822],
                    ['name' => 'Kazipara', 'lat' => 23.7958, 'lng' => 90.3698],
                    ['name' => 'Mirpur-10', 'lat' => 23.8068, 'lng' => 90.3685],
                    ['name' => 'Proshikha', 'lat' => 23.8089, 'lng' => 90.3889],
                    ['name' => 'BUBT', 'lat' => 23.8103, 'lng' => 90.4125],
                ],
                'times_up' => ['07:00:00', '17:00:00'],
                'times_down' => ['16:10:00', '21:25:00'],
            ],
            [
                'code' => 'B4',
                'name' => 'Meghna',
                'stops' => [
                    ['name' => 'Mirpur-14', 'lat' => 23.8289, 'lng' => 90.3598],
                    ['name' => 'Mirpur-10 (Original)', 'lat' => 23.8068, 'lng' => 90.3685],
                    ['name' => 'Mirpur-11', 'lat' => 23.8156, 'lng' => 90.3789],
                    ['name' => 'Proshikha', 'lat' => 23.8089, 'lng' => 90.3889],
                    ['name' => 'BUBT', 'lat' => 23.8103, 'lng' => 90.4125],
                ],
                'times_up' => ['07:00:00', '17:00:00'],
                'times_down' => ['16:10:00', '21:25:00'],
            ],
            [
                'code' => 'B5',
                'name' => 'Jamuna',
                'stops' => [
                    ['name' => 'ECB Chattar', 'lat' => 23.8256, 'lng' => 90.4189],
                    ['name' => 'Kalshi Bridge', 'lat' => 23.8198, 'lng' => 90.4098],
                    ['name' => 'Mirpur-12', 'lat' => 23.8189, 'lng' => 90.3898],
                    ['name' => 'Duaripara', 'lat' => 23.8145, 'lng' => 90.4025],
                    ['name' => 'BUBT', 'lat' => 23.8103, 'lng' => 90.4125],
                ],
                'times_up' => ['07:00:00', '17:00:00'],
                'times_down' => ['16:10:00', '21:25:00'],
            ],
        ];

        foreach ($buses as $data) {
            // Create Bus
            $bus = Bus::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'direction' => 'A_TO_Z', // Campus Bound
                'start_location' => $data['stops'][0]['name'],
                'end_location' => 'BUBT Campus',
                'capacity' => 40,
                'is_active' => true,
            ]);

            // --- UP TRIP (To Campus) ---
            // Generate polyline from stops
            $polylineUp = $this->generatePolyline($data['stops']);
            
            $routeUp = BusRoute::create([
                'bus_id' => $bus->id,
                'schedule_period_id' => $period->id,
                'name' => $data['stops'][0]['name'] . ' to BUBT',
                'polyline' => json_encode($polylineUp),
                'is_active' => true,
            ]);

            // Create Stops for Up Trip
            $baseTimeUp = $data['times_up'][0]; // First departure time
            foreach ($data['stops'] as $index => $stop) {
                // Assume 5 minutes between stops for demo (you can customize this)
                $etaMinutes = $index * 5;
                $arrivalTime = $this->calculateArrivalTime($baseTimeUp, $etaMinutes);

                BusStop::create([
                    'bus_id' => $bus->id,
                    'bus_route_id' => $routeUp->id,
                    'name' => $stop['name'],
                    'sequence' => $index + 1,
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'eta_from_start_minutes' => $etaMinutes,
                    'arrival_time' => $arrivalTime,
                    'is_major_stop' => $index === 0 || $index === count($data['stops']) - 1,
                ]);
            }

            // Create Schedules for Up Trip
            foreach ($data['times_up'] as $time) {
                BusSchedule::create([
                    'bus_id' => $bus->id,
                    'schedule_period_id' => $period->id,
                    'bus_route_id' => $routeUp->id,
                    'departure_time' => $time,
                    'weekdays' => json_encode(['mon' => true, 'tue' => true, 'wed' => true, 'thu' => true, 'fri' => true, 'sat' => true, 'sun' => true]),
                ]);
            }

            // --- DOWN TRIP (From Campus) ---
            $reversedStops = array_reverse($data['stops']);
            $polylineDown = $this->generatePolyline($reversedStops);
            
            $routeDown = BusRoute::create([
                'bus_id' => $bus->id,
                'schedule_period_id' => $period->id,
                'name' => 'BUBT to ' . $data['stops'][0]['name'],
                'polyline' => json_encode($polylineDown),
                'is_active' => true,
            ]);

            // Create Stops for Down Trip (Reverse Order)
            $baseTimeDown = $data['times_down'][0]; // First departure time
            foreach ($reversedStops as $index => $stop) {
                // Assume 5 minutes between stops for demo
                $etaMinutes = $index * 5;
                $arrivalTime = $this->calculateArrivalTime($baseTimeDown, $etaMinutes);

                BusStop::create([
                    'bus_id' => $bus->id,
                    'bus_route_id' => $routeDown->id,
                    'name' => $stop['name'],
                    'sequence' => $index + 1,
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'eta_from_start_minutes' => $etaMinutes,
                    'arrival_time' => $arrivalTime,
                    'is_major_stop' => $index === 0 || $index === \count($reversedStops) - 1,
                ]);
            }

            // Create Schedules for Down Trip
            foreach ($data['times_down'] as $time) {
                BusSchedule::create([
                    'bus_id' => $bus->id,
                    'schedule_period_id' => $period->id,
                    'bus_route_id' => $routeDown->id,
                    'departure_time' => $time,
                    'weekdays' => json_encode(['mon' => true, 'tue' => true, 'wed' => true, 'thu' => true, 'fri' => true, 'sat' => true, 'sun' => true]),
                ]);
            }
        }
    }

    /**
     * Generate polyline from array of stops
     * This creates a simple polyline connecting all stops
     * In production, you would use Google Directions API to get actual road routes
     */
    private function generatePolyline(array $stops): array
    {
        $polyline = [];
        foreach ($stops as $stop) {
            $polyline[] = [
                'lat' => $stop['lat'],
                'lng' => $stop['lng']
            ];
        }
        return $polyline;
    }

    /**
     * Calculate arrival time for a stop based on departure time and ETA from start
     */
    private function calculateArrivalTime($departureTime, $etaFromStartMinutes): string
    {
        if (!$etaFromStartMinutes) {
            return $departureTime; // Return departure time if no ETA
        }

        // Parse departure time (HH:MM:SS)
        [$hours, $minutes] = explode(':', $departureTime);

        // Create Carbon object and add minutes
        $arrivalTime = now()->setHour((int)$hours)->setMinute((int)$minutes)->setSecond(0);
        $arrivalTime->addMinutes($etaFromStartMinutes);

        return $arrivalTime->format('H:i:s');
    }
}
