<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bus;
use App\Models\BusLocation;

class BusLocationSeeder extends Seeder
{
    public function run()
    {
        // Clear existing locations
        BusLocation::truncate();

        // Get all buses
        $buses = Bus::all();

        // Add demo locations for each bus (simulating buses at their first stop)
        $demoLocations = [
            'B1' => ['lat' => 23.7654, 'lng' => 90.3685], // Asad Gate
            'B2' => ['lat' => 23.7947, 'lng' => 90.2358], // Hemayetpur
            'B3' => ['lat' => 23.7688, 'lng' => 90.3686], // Shyamoli
            'B4' => ['lat' => 23.8289, 'lng' => 90.3598], // Mirpur-14
            'B5' => ['lat' => 23.8256, 'lng' => 90.4189], // ECB Chattar
        ];

        foreach ($buses as $bus) {
            if (isset($demoLocations[$bus->code])) {
                BusLocation::create([
                    'bus_id' => $bus->id,
                    'lat' => $demoLocations[$bus->code]['lat'],
                    'lng' => $demoLocations[$bus->code]['lng'],
                    'calculated_at' => now(),
                ]);
            }
        }

        $this->command->info('Bus locations seeded successfully!');
    }
}
