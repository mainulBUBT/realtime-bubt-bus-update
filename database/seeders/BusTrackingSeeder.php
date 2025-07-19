<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bus;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\Setting;

class BusTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // Create buses based on the image provided
        $buses = [
            ['name' => 'B1', 'route_name' => 'Buriganga'],
            ['name' => 'B2', 'route_name' => 'Brahmaputra'],
            ['name' => 'B3', 'route_name' => 'Padma'],
            ['name' => 'B4', 'route_name' => 'Meghna'],
            ['name' => 'B5', 'route_name' => 'Jamuna'],
        ];

        foreach ($buses as $busData) {
            $bus = Bus::create([
                'name' => $busData['name'],
                'route_name' => $busData['route_name'],
                'is_active' => true,
            ]);

            // Create stops for each bus based on the routes shown in image
            $this->createStopsForBus($bus);
            
            // Create today's trips
            $this->createTripsForBus($bus);
        }

        // Create default settings
        $this->createSettings();
    }

    private function createStopsForBus(Bus $bus): void
    {
        $routes = [
            'B1' => [
                ['name' => 'Asad Gate', 'lat' => 23.7550, 'lng' => 90.3700],
                ['name' => 'Shyamoli', 'lat' => 23.7600, 'lng' => 90.3650],
                ['name' => 'Mirpur-1', 'lat' => 23.7950, 'lng' => 90.3550],
                ['name' => 'Rainkhola', 'lat' => 23.8200, 'lng' => 90.3400],
                ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
            ],
            'B2' => [
                ['name' => 'Hemayetpur', 'lat' => 23.7900, 'lng' => 90.2800],
                ['name' => 'Amin Bazar', 'lat' => 23.7950, 'lng' => 90.3100],
                ['name' => 'Gabtoli', 'lat' => 23.7800, 'lng' => 90.3300],
                ['name' => 'Mazar Road', 'lat' => 23.8000, 'lng' => 90.3450],
                ['name' => 'Mirpur-1', 'lat' => 23.7950, 'lng' => 90.3550],
                ['name' => 'Rainkhola', 'lat' => 23.8200, 'lng' => 90.3400],
                ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
            ],
            'B3' => [
                ['name' => 'Shyamoli (Shishu Mela)', 'lat' => 23.7580, 'lng' => 90.3680],
                ['name' => 'Agargaon', 'lat' => 23.7750, 'lng' => 90.3600],
                ['name' => 'Kazipara', 'lat' => 23.7850, 'lng' => 90.3650],
                ['name' => 'Mirpur-10', 'lat' => 23.8050, 'lng' => 90.3700],
                ['name' => 'Proshikha', 'lat' => 23.8150, 'lng' => 90.3500],
                ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
            ],
            'B4' => [
                ['name' => 'Mirpur-14', 'lat' => 23.8250, 'lng' => 90.3800],
                ['name' => 'Mirpur-10 (Original)', 'lat' => 23.8050, 'lng' => 90.3700],
                ['name' => 'Mirpur-11', 'lat' => 23.8100, 'lng' => 90.3600],
                ['name' => 'Proshikha', 'lat' => 23.8150, 'lng' => 90.3500],
                ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
            ],
            'B5' => [
                ['name' => 'ECB Chattar', 'lat' => 23.7400, 'lng' => 90.3900],
                ['name' => 'Kalshi Bridge', 'lat' => 23.7600, 'lng' => 90.3800],
                ['name' => 'Mirpur-12', 'lat' => 23.8000, 'lng' => 90.3750],
                ['name' => 'Duaripara', 'lat' => 23.8200, 'lng' => 90.3600],
                ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
            ],
        ];

        $busStops = $routes[$bus->name] ?? [];
        
        foreach ($busStops as $index => $stop) {
            Stop::create([
                'name' => $stop['name'],
                'latitude' => $stop['lat'],
                'longitude' => $stop['lng'],
                'order_index' => $index + 1,
                'bus_id' => $bus->id,
            ]);
        }
    }

    private function createTripsForBus(Bus $bus): void
    {
        // Morning trip (7:00 AM departure, 4:10 PM return)
        Trip::create([
            'bus_id' => $bus->id,
            'trip_date' => today(),
            'departure_time' => '07:00:00',
            'return_time' => '16:10:00',
            'direction' => 'outbound',
            'status' => 'scheduled',
        ]);

        // Evening trip (5:00 PM departure, 9:25 PM return)
        Trip::create([
            'bus_id' => $bus->id,
            'trip_date' => today(),
            'departure_time' => '17:00:00',
            'return_time' => '21:25:00',
            'direction' => 'outbound',
            'status' => 'scheduled',
        ]);

        // Create trips for next few days
        for ($i = 1; $i <= 7; $i++) {
            $date = today()->addDays($i);
            
            Trip::create([
                'bus_id' => $bus->id,
                'trip_date' => $date,
                'departure_time' => '07:00:00',
                'return_time' => '16:10:00',
                'direction' => 'outbound',
                'status' => 'scheduled',
            ]);

            Trip::create([
                'bus_id' => $bus->id,
                'trip_date' => $date,
                'departure_time' => '17:00:00',
                'return_time' => '21:25:00',
                'direction' => 'outbound',
                'status' => 'scheduled',
            ]);
        }
    }

    private function createSettings(): void
    {
        $settings = [
            'app_name' => 'BUBT Bus Tracker',
            'refresh_interval' => '30',
            'max_location_age' => '10',
            'clustering_radius' => '60',
            'university_name' => 'Bangladesh University of Business and Technology',
            'university_lat' => '23.8300',
            'university_lng' => '90.3200',
            'contact_email' => 'transport@bubt.edu.bd',
            'contact_phone' => '+880-2-9138234',
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}