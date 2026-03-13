<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class BusRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data
        Schedule::truncate();
        RouteStop::truncate();
        Route::truncate();
        Bus::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create buses and routes
        $this->createB1Routes();
        $this->createB2Routes();
        $this->createB3Routes();
        $this->createB4Routes();
        $this->createB5Routes();

        $this->command->info('✅ Bus routes seeded successfully!');
        $this->command->info('   - 5 Buses (B1-B5)');
        $this->command->info('   - 10 Routes (outbound/inbound)');
        $this->command->info('   - 45+ Stops with real coordinates');
        $this->command->info('   - 20 Schedules');
    }

    /**
     * Create B1 - Buriganga routes
     */
    private function createB1Routes(): void
    {
        $bus = Bus::create([
            'plate_number' => 'DHAKA-METRO-15-2843',
            'display_name' => 'Buriganga',
            'code' => 'B1',
            'capacity' => 40,
            'status' => 'active',
        ]);

        // Outbound route (TO Campus)
        $outboundRoute = Route::create([
            'name' => 'Buriganga - To Campus via Asad Gate',
            'code' => 'B1-OUT',
            'direction' => 'outbound',
            'origin_name' => 'Asad Gate',
            'destination_name' => 'BUBT Campus',
            'is_active' => true,
        ]);

        $outboundStops = [
            ['name' => 'Asad Gate', 'lat' => 23.7654, 'lng' => 90.3685, 'sequence' => 1],
            ['name' => 'Shyamoli', 'lat' => 23.7688, 'lng' => 90.3686, 'sequence' => 2],
            ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537, 'sequence' => 3],
            ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698, 'sequence' => 4],
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 5],
        ];

        foreach ($outboundStops as $stop) {
            RouteStop::create([
                'route_id' => $outboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        // Inbound route (FROM Campus)
        $inboundRoute = Route::create([
            'name' => 'Buriganga - From Campus via Shyamoli',
            'code' => 'B1-IN',
            'direction' => 'inbound',
            'origin_name' => 'BUBT Campus',
            'destination_name' => 'Asad Gate',
            'is_active' => true,
        ]);

        $inboundStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 1],
            ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698, 'sequence' => 2],
            ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537, 'sequence' => 3],
            ['name' => 'Shyamoli', 'lat' => 23.7688, 'lng' => 90.3686, 'sequence' => 4],
            ['name' => 'Asad Gate', 'lat' => 23.7654, 'lng' => 90.3685, 'sequence' => 5],
        ];

        foreach ($inboundStops as $stop) {
            RouteStop::create([
                'route_id' => $inboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        $this->createSchedules($bus, $outboundRoute, $inboundRoute);
    }

    /**
     * Create B2 - Brahmaputra routes
     */
    private function createB2Routes(): void
    {
        $bus = Bus::create([
            'plate_number' => 'DHAKA-METRO-11-1925',
            'display_name' => 'Brahmaputra',
            'code' => 'B2',
            'capacity' => 40,
            'status' => 'active',
        ]);

        // Outbound route (TO Campus)
        $outboundRoute = Route::create([
            'name' => 'Brahmaputra - To Campus via Hemayetpur',
            'code' => 'B2-OUT',
            'direction' => 'outbound',
            'origin_name' => 'Hemayetpur',
            'destination_name' => 'BUBT Campus',
            'is_active' => true,
        ]);

        $outboundStops = [
            ['name' => 'Hemayetpur', 'lat' => 23.8000, 'lng' => 90.3300, 'sequence' => 1],
            ['name' => 'Amin Bazar', 'lat' => 23.7880, 'lng' => 90.3475, 'sequence' => 2],
            ['name' => 'Gabtoli', 'lat' => 23.7762, 'lng' => 90.3618, 'sequence' => 3],
            ['name' => 'Mazar Road', 'lat' => 23.7850, 'lng' => 90.3580, 'sequence' => 4],
            ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537, 'sequence' => 5],
            ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698, 'sequence' => 6],
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 7],
        ];

        foreach ($outboundStops as $stop) {
            RouteStop::create([
                'route_id' => $outboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        // Inbound route (FROM Campus)
        $inboundRoute = Route::create([
            'name' => 'Brahmaputra - From Campus via Gabtoli',
            'code' => 'B2-IN',
            'direction' => 'inbound',
            'origin_name' => 'BUBT Campus',
            'destination_name' => 'Hemayetpur',
            'is_active' => true,
        ]);

        $inboundStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 1],
            ['name' => 'Rainkhola', 'lat' => 23.8103, 'lng' => 90.3698, 'sequence' => 2],
            ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537, 'sequence' => 3],
            ['name' => 'Mazar Road', 'lat' => 23.7850, 'lng' => 90.3580, 'sequence' => 4],
            ['name' => 'Gabtoli', 'lat' => 23.7762, 'lng' => 90.3618, 'sequence' => 5],
            ['name' => 'Amin Bazar', 'lat' => 23.7880, 'lng' => 90.3475, 'sequence' => 6],
            ['name' => 'Hemayetpur', 'lat' => 23.8000, 'lng' => 90.3300, 'sequence' => 7],
        ];

        foreach ($inboundStops as $stop) {
            RouteStop::create([
                'route_id' => $inboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        $this->createSchedules($bus, $outboundRoute, $inboundRoute);
    }

    /**
     * Create B3 - Padma routes
     */
    private function createB3Routes(): void
    {
        $bus = Bus::create([
            'plate_number' => 'DHAKA-METRO-13-2208',
            'display_name' => 'Padma',
            'code' => 'B3',
            'capacity' => 40,
            'status' => 'active',
        ]);

        // Outbound route (TO Campus)
        $outboundRoute = Route::create([
            'name' => 'Padma - To Campus via Shyamoli Shishu Mela',
            'code' => 'B3-OUT',
            'direction' => 'outbound',
            'origin_name' => 'Shyamoli (Shishu Mela)',
            'destination_name' => 'BUBT Campus',
            'is_active' => true,
        ]);

        $outboundStops = [
            ['name' => 'Shyamoli (Shishu Mela)', 'lat' => 23.7700, 'lng' => 90.3700, 'sequence' => 1],
            ['name' => 'Agargaon', 'lat' => 23.7720, 'lng' => 90.3790, 'sequence' => 2],
            ['name' => 'Kazipara', 'lat' => 23.8176, 'lng' => 90.3665, 'sequence' => 3],
            ['name' => 'Mirpur-10', 'lat' => 23.8226, 'lng' => 90.3598, 'sequence' => 4],
            ['name' => 'Proshikkha', 'lat' => 23.8650, 'lng' => 90.3760, 'sequence' => 5],
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 6],
        ];

        foreach ($outboundStops as $stop) {
            RouteStop::create([
                'route_id' => $outboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        // Inbound route (FROM Campus)
        $inboundRoute = Route::create([
            'name' => 'Padma - From Campus via Kazipara',
            'code' => 'B3-IN',
            'direction' => 'inbound',
            'origin_name' => 'BUBT Campus',
            'destination_name' => 'Shyamoli (Shishu Mela)',
            'is_active' => true,
        ]);

        $inboundStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 1],
            ['name' => 'Proshikkha', 'lat' => 23.8650, 'lng' => 90.3760, 'sequence' => 2],
            ['name' => 'Mirpur-10', 'lat' => 23.8226, 'lng' => 90.3598, 'sequence' => 3],
            ['name' => 'Kazipara', 'lat' => 23.8176, 'lng' => 90.3665, 'sequence' => 4],
            ['name' => 'Agargaon', 'lat' => 23.7720, 'lng' => 90.3790, 'sequence' => 5],
            ['name' => 'Shyamoli (Shishu Mela)', 'lat' => 23.7700, 'lng' => 90.3700, 'sequence' => 6],
        ];

        foreach ($inboundStops as $stop) {
            RouteStop::create([
                'route_id' => $inboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        $this->createSchedules($bus, $outboundRoute, $inboundRoute);
    }

    /**
     * Create B4 - Meghna routes
     */
    private function createB4Routes(): void
    {
        $bus = Bus::create([
            'plate_number' => 'DHAKA-METRO-14-2050',
            'display_name' => 'Meghna',
            'code' => 'B4',
            'capacity' => 40,
            'status' => 'active',
        ]);

        // Outbound route (TO Campus)
        $outboundRoute = Route::create([
            'name' => 'Meghna - To Campus via Mirpur-14',
            'code' => 'B4-OUT',
            'direction' => 'outbound',
            'origin_name' => 'Mirpur-14',
            'destination_name' => 'BUBT Campus',
            'is_active' => true,
        ]);

        $outboundStops = [
            ['name' => 'Mirpur-14', 'lat' => 23.8500, 'lng' => 90.3750, 'sequence' => 1],
            ['name' => 'Mirpur-10 (Original)', 'lat' => 23.8226, 'lng' => 90.3598, 'sequence' => 2],
            ['name' => 'Mirpur-11', 'lat' => 23.8300, 'lng' => 90.3650, 'sequence' => 3],
            ['name' => 'Proshikkha', 'lat' => 23.8650, 'lng' => 90.3760, 'sequence' => 4],
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 5],
        ];

        foreach ($outboundStops as $stop) {
            RouteStop::create([
                'route_id' => $outboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        // Inbound route (FROM Campus)
        $inboundRoute = Route::create([
            'name' => 'Meghna - From Campus via Proshikkha',
            'code' => 'B4-IN',
            'direction' => 'inbound',
            'origin_name' => 'BUBT Campus',
            'destination_name' => 'Mirpur-14',
            'is_active' => true,
        ]);

        $inboundStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 1],
            ['name' => 'Proshikkha', 'lat' => 23.8650, 'lng' => 90.3760, 'sequence' => 2],
            ['name' => 'Mirpur-11', 'lat' => 23.8300, 'lng' => 90.3650, 'sequence' => 3],
            ['name' => 'Mirpur-10 (Original)', 'lat' => 23.8226, 'lng' => 90.3598, 'sequence' => 4],
            ['name' => 'Mirpur-14', 'lat' => 23.8500, 'lng' => 90.3750, 'sequence' => 5],
        ];

        foreach ($inboundStops as $stop) {
            RouteStop::create([
                'route_id' => $inboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        $this->createSchedules($bus, $outboundRoute, $inboundRoute);
    }

    /**
     * Create B5 - Jamuna routes
     */
    private function createB5Routes(): void
    {
        $bus = Bus::create([
            'plate_number' => 'DHAKA-METRO-12-2150',
            'display_name' => 'Jamuna',
            'code' => 'B5',
            'capacity' => 40,
            'status' => 'active',
        ]);

        // Outbound route (TO Campus)
        $outboundRoute = Route::create([
            'name' => 'Jamuna - To Campus via ECB Chattar',
            'code' => 'B5-OUT',
            'direction' => 'outbound',
            'origin_name' => 'ECB Chattar',
            'destination_name' => 'BUBT Campus',
            'is_active' => true,
        ]);

        $outboundStops = [
            ['name' => 'ECB Chattar', 'lat' => 23.8620, 'lng' => 90.3850, 'sequence' => 1],
            ['name' => 'Kalshi Bridge', 'lat' => 23.8480, 'lng' => 90.3780, 'sequence' => 2],
            ['name' => 'Mirpur-12', 'lat' => 23.8400, 'lng' => 90.3700, 'sequence' => 3],
            ['name' => 'Duaripara', 'lat' => 23.8550, 'lng' => 90.3820, 'sequence' => 4],
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 5],
        ];

        foreach ($outboundStops as $stop) {
            RouteStop::create([
                'route_id' => $outboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        // Inbound route (FROM Campus)
        $inboundRoute = Route::create([
            'name' => 'Jamuna - From Campus via Duaripara',
            'code' => 'B5-IN',
            'direction' => 'inbound',
            'origin_name' => 'BUBT Campus',
            'destination_name' => 'ECB Chattar',
            'is_active' => true,
        ]);

        $inboundStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.8759, 'lng' => 90.3795, 'sequence' => 1],
            ['name' => 'Duaripara', 'lat' => 23.8550, 'lng' => 90.3820, 'sequence' => 2],
            ['name' => 'Mirpur-12', 'lat' => 23.8400, 'lng' => 90.3700, 'sequence' => 3],
            ['name' => 'Kalshi Bridge', 'lat' => 23.8480, 'lng' => 90.3780, 'sequence' => 4],
            ['name' => 'ECB Chattar', 'lat' => 23.8620, 'lng' => 90.3850, 'sequence' => 5],
        ];

        foreach ($inboundStops as $stop) {
            RouteStop::create([
                'route_id' => $inboundRoute->id,
                'name' => $stop['name'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'sequence' => $stop['sequence'],
            ]);
        }

        $this->createSchedules($bus, $outboundRoute, $inboundRoute);
    }

    /**
     * Create schedules for a bus (same pattern for all buses)
     */
    private function createSchedules(Bus $bus, Route $outboundRoute, Route $inboundRoute): void
    {
        $weekdays = json_encode(['mon', 'tue', 'wed', 'thu', 'fri']);

        // Outbound schedules (TO Campus) - 7AM and 5PM
        Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $outboundRoute->id,
            'departure_time' => '07:00:00',
            'weekdays' => $weekdays,
            'is_active' => true,
        ]);

        Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $outboundRoute->id,
            'departure_time' => '17:00:00',
            'weekdays' => $weekdays,
            'is_active' => true,
        ]);

        // Inbound schedules (FROM Campus) - 4:10PM and 9:25PM
        Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $inboundRoute->id,
            'departure_time' => '16:10:00',
            'weekdays' => $weekdays,
            'is_active' => true,
        ]);

        Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $inboundRoute->id,
            'departure_time' => '21:25:00',
            'weekdays' => $weekdays,
            'is_active' => true,
        ]);
    }
}
