<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $route = Route::first();

        if (!$route) {
            $this->command->warn('No routes found. Please create a route first using DatabaseSeeder.');
            return;
        }

        $today = Carbon::today();

        // Up direction (Campus to City) - Morning departure at 8 AM
        Schedule::create([
            'route_id' => $route->id,
            'direction' => 'up',
            'departure_time' => '08:00',
            'weekdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'effective_from' => $today->toDateString(),
            'effective_to' => null,
            'schedule_type' => 'regular',
            'is_active' => true,
        ]);

        // Down direction (City to Campus) - Afternoon departure at 5 PM
        Schedule::create([
            'route_id' => $route->id,
            'direction' => 'down',
            'departure_time' => '17:00',
            'weekdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'effective_from' => $today->toDateString(),
            'effective_to' => null,
            'schedule_type' => 'regular',
            'is_active' => true,
        ]);

        // Weekend schedule example
        Schedule::create([
            'route_id' => $route->id,
            'direction' => 'up',
            'departure_time' => '09:00',
            'weekdays' => ['saturday', 'sunday'],
            'effective_from' => $today->toDateString(),
            'effective_to' => null,
            'schedule_type' => 'regular',
            'is_active' => true,
        ]);

        // Exam schedule example - future dated, limited time
        $examStart = $today->copy()->addMonths(2);
        $examEnd = $examStart->copy()->addDays(14);

        Schedule::create([
            'route_id' => $route->id,
            'direction' => 'up',
            'departure_time' => '07:00',
            'weekdays' => ['sunday', 'saturday'],
            'effective_from' => $examStart->toDateString(),
            'effective_to' => $examEnd->toDateString(),
            'schedule_type' => 'exam',
            'is_active' => true,
        ]);

        $this->command->info('Schedules seeded successfully!');
        $this->command->info('Created 4 schedules:');
        $this->command->info('  - Up (Campus→City): Weekdays 8:00 AM');
        $this->command->info('  - Down (City→Campus): Weekdays 5:00 PM');
        $this->command->info('  - Up (Weekend): Sat/Sun 9:00 AM');
        $this->command->info('  - Exam Schedule: Starting ' . $examStart->toDateString());
    }
}
