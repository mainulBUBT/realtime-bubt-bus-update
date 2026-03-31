<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchedulePeriodSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_same_bus_same_time_with_overlapping_weekdays_in_same_period(): void
    {
        $admin = $this->createUser('admin');
        $bus = $this->createBus();
        $period = $this->createPeriod('Regular', today()->startOfMonth(), today()->endOfMonth());
        $route = $this->createRoute(['schedule_period_id' => $period->id]);

        $this->createSchedule($bus, $route, $period, [
            'departure_time' => '07:30',
            'weekdays' => ['monday', 'tuesday'],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $period->id,
            'departure_time' => '07:30',
            'weekdays' => ['tuesday', 'wednesday'],
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['departure_time']);
    }

    public function test_admin_can_create_same_bus_same_time_in_different_periods(): void
    {
        $admin = $this->createUser('admin');
        $bus = $this->createBus();
        $regularPeriod = $this->createPeriod('Regular', today()->startOfMonth(), today()->endOfMonth());
        $examPeriod = $this->createPeriod('Exam', today()->addMonth()->startOfMonth(), today()->addMonth()->endOfMonth());
        $regularRoute = $this->createRoute([
            'code' => 'R-REG',
            'schedule_period_id' => $regularPeriod->id,
        ]);
        $examRoute = $this->createRoute([
            'code' => 'R-EXM',
            'schedule_period_id' => $examPeriod->id,
        ]);

        $this->createSchedule($bus, $regularRoute, $regularPeriod, [
            'departure_time' => '07:30',
            'weekdays' => ['monday', 'tuesday'],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'bus_id' => $bus->id,
            'route_id' => $examRoute->id,
            'schedule_period_id' => $examPeriod->id,
            'departure_time' => '07:30',
            'weekdays' => ['monday', 'tuesday'],
            'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('schedule_period_id', $examPeriod->id);
    }

    public function test_admin_can_create_same_bus_same_time_in_same_period_when_weekdays_do_not_overlap(): void
    {
        $admin = $this->createUser('admin');
        $bus = $this->createBus();
        $period = $this->createPeriod('Regular', today()->startOfMonth(), today()->endOfMonth());
        $route = $this->createRoute(['schedule_period_id' => $period->id]);

        $this->createSchedule($bus, $route, $period, [
            'departure_time' => '07:30',
            'weekdays' => ['monday', 'tuesday'],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $period->id,
            'departure_time' => '07:30',
            'weekdays' => ['wednesday', 'thursday'],
            'is_active' => true,
        ]);

        $response->assertCreated();
    }

    public function test_student_schedules_only_show_current_period_and_hide_legacy_without_period(): void
    {
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $currentPeriod = $this->createPeriod('Current', today()->startOfMonth(), today()->endOfMonth());
        $futurePeriod = $this->createPeriod('Exam', today()->addMonth()->startOfMonth(), today()->addMonth()->endOfMonth());
        $currentRoute = $this->createRoute([
            'code' => 'R-CUR',
            'name' => 'Current Route',
            'schedule_period_id' => $currentPeriod->id,
        ]);
        $futureRoute = $this->createRoute([
            'code' => 'R-FUT',
            'name' => 'Future Route',
            'schedule_period_id' => $futurePeriod->id,
        ]);
        $legacyRoute = $this->createRoute([
            'code' => 'R-LEG',
            'name' => 'Legacy Route',
            'schedule_period_id' => null,
        ]);

        $weekday = strtolower(now()->englishDayOfWeek);

        $currentSchedule = $this->createSchedule($bus, $currentRoute, $currentPeriod, [
            'departure_time' => '08:00',
            'weekdays' => [$weekday],
        ]);

        $futureSchedule = $this->createSchedule($bus, $futureRoute, $futurePeriod, [
            'departure_time' => '08:00',
            'weekdays' => [$weekday],
        ]);

        $legacySchedule = Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $legacyRoute->id,
            'schedule_period_id' => null,
            'departure_time' => '09:00',
            'weekdays' => [$weekday],
            'effective_date' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/schedules');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $currentSchedule->id,
            'name' => 'Current Route',
        ]);
        $response->assertJsonMissing(['id' => $futureSchedule->id]);
        $response->assertJsonMissing(['id' => $legacySchedule->id]);
    }

    public function test_admin_rejects_schedule_when_route_period_does_not_match_selected_period(): void
    {
        $admin = $this->createUser('admin');
        $bus = $this->createBus();
        $regularPeriod = $this->createPeriod('Regular', today()->startOfMonth(), today()->endOfMonth());
        $examPeriod = $this->createPeriod('Exam', today()->addMonth()->startOfMonth(), today()->addMonth()->endOfMonth());
        $route = $this->createRoute(['schedule_period_id' => $regularPeriod->id]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $examPeriod->id,
            'departure_time' => '07:30',
            'weekdays' => ['monday'],
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['schedule_period_id']);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    private function createBus(array $attributes = []): Bus
    {
        return Bus::create(array_merge([
            'plate_number' => fake()->unique()->numerify('DHAKA-METRO-##-####'),
            'display_name' => 'B4 Meghna',
            'code' => strtoupper(fake()->unique()->bothify('B#??')),
            'capacity' => 40,
            'status' => 'active',
        ], $attributes));
    }

    private function createPeriod(string $name, $startDate, $endDate): SchedulePeriod
    {
        return SchedulePeriod::create([
            'name' => $name,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'is_active' => true,
        ]);
    }

    private function createRoute(array $attributes = []): Route
    {
        return Route::create(array_merge([
            'name' => 'Route ' . fake()->unique()->word(),
            'code' => strtoupper(fake()->unique()->bothify('R#??')),
            'direction' => 'outbound',
            'origin_name' => 'Origin',
            'destination_name' => 'Destination',
            'schedule_period_id' => null,
            'is_active' => true,
        ], $attributes));
    }

    private function createSchedule(Bus $bus, Route $route, SchedulePeriod $period, array $attributes = []): Schedule
    {
        return Schedule::create(array_merge([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $period->id,
            'departure_time' => '07:30',
            'weekdays' => ['monday'],
            'effective_date' => null,
            'is_active' => true,
        ], $attributes));
    }
}
