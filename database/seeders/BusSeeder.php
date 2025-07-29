<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buses = [
            [
                'bus_id' => 'B1',
                'name' => 'Buriganga',
                'capacity' => 40,
                'vehicle_number' => 'DHK-GA-1234',
                'model' => 'Toyota Coaster',
                'year' => 2020,
                'status' => 'active',
                'is_active' => true,
                'driver_name' => 'Md. Rahman',
                'driver_phone' => '+880 1712-345678',
                'last_maintenance_date' => now()->subDays(30),
                'next_maintenance_date' => now()->addDays(60),
            ],
            [
                'bus_id' => 'B2',
                'name' => 'Brahmaputra',
                'capacity' => 45,
                'vehicle_number' => 'DHK-GA-5678',
                'model' => 'Ashok Leyland',
                'year' => 2019,
                'status' => 'active',
                'is_active' => true,
                'driver_name' => 'Md. Karim',
                'driver_phone' => '+880 1812-345678',
                'last_maintenance_date' => now()->subDays(15),
                'next_maintenance_date' => now()->addDays(75),
            ],
            [
                'bus_id' => 'B3',
                'name' => 'Padma',
                'capacity' => 42,
                'vehicle_number' => 'DHK-GA-9012',
                'model' => 'Toyota Coaster',
                'year' => 2021,
                'status' => 'active',
                'is_active' => true,
                'driver_name' => 'Md. Hasan',
                'driver_phone' => '+880 1912-345678',
                'last_maintenance_date' => now()->subDays(45),
                'next_maintenance_date' => now()->addDays(45),
            ],
            [
                'bus_id' => 'B4',
                'name' => 'Meghna',
                'capacity' => 38,
                'vehicle_number' => 'DHK-GA-3456',
                'model' => 'Tata Starbus',
                'year' => 2018,
                'status' => 'maintenance',
                'is_active' => false,
                'driver_name' => 'Md. Ali',
                'driver_phone' => '+880 1612-345678',
                'maintenance_notes' => 'Engine overhaul in progress. Expected completion in 3 days.',
                'last_maintenance_date' => now()->subDays(5),
                'next_maintenance_date' => now()->addDays(85),
            ],
            [
                'bus_id' => 'B5',
                'name' => 'Jamuna',
                'capacity' => 40,
                'vehicle_number' => 'DHK-GA-7890',
                'model' => 'Toyota Coaster',
                'year' => 2020,
                'status' => 'inactive',
                'is_active' => false,
                'driver_name' => 'Md. Rahim',
                'driver_phone' => '+880 1512-345678',
                'last_maintenance_date' => now()->subDays(60),
                'next_maintenance_date' => now()->subDays(10), // Overdue maintenance
            ],
        ];

        foreach ($buses as $busData) {
            \App\Models\Bus::updateOrCreate(
                ['bus_id' => $busData['bus_id']],
                $busData
            );
        }

        $this->command->info('Bus data seeded successfully!');
        $this->command->info('Created buses: B1 (Buriganga), B2 (Brahmaputra), B3 (Padma), B4 (Meghna), B5 (Jamuna)');
    }
}
