<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_student_schedules_today_and_all_filters_follow_current_period_rules(): void
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

        $todayWeekday = strtolower(now()->englishDayOfWeek);
        $otherWeekday = $this->differentWeekdayFrom($todayWeekday);

        $currentSchedule = $this->createSchedule($bus, $currentRoute, $currentPeriod, [
            'departure_time' => '08:00',
            'weekdays' => [$todayWeekday],
        ]);

        $currentOtherDaySchedule = $this->createSchedule($bus, $currentRoute, $currentPeriod, [
            'departure_time' => '08:30',
            'weekdays' => [$otherWeekday],
        ]);

        $futureSchedule = $this->createSchedule($bus, $futureRoute, $futurePeriod, [
            'departure_time' => '08:00',
            'weekdays' => [$todayWeekday],
        ]);

        $legacySchedule = Schedule::create([
            'bus_id' => $bus->id,
            'route_id' => $legacyRoute->id,
            'schedule_period_id' => null,
            'departure_time' => '09:00',
            'weekdays' => [$todayWeekday],
            'effective_date' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $todayResponse = $this->getJson('/api/student/schedules');

        $todayResponse->assertOk();
        $todayResponse->assertJsonCount(1, 'data');
        $todayResponse->assertJsonPath('meta.current_page', 1);
        $todayResponse->assertJsonPath('meta.has_more', false);
        $todayResponseIds = collect($todayResponse->json('data'))->pluck('id')->all();
        $this->assertContains($currentSchedule->id, $todayResponseIds);
        $this->assertNotContains($currentOtherDaySchedule->id, $todayResponseIds);
        $this->assertNotContains($futureSchedule->id, $todayResponseIds);
        $this->assertNotContains($legacySchedule->id, $todayResponseIds);

        $allResponse = $this->getJson('/api/student/schedules?filter=all');

        $allResponse->assertOk();
        $allResponse->assertJsonCount(2, 'data');
        $allResponse->assertJsonPath('meta.current_page', 1);
        $allResponse->assertJsonPath('meta.total', 2);
        $allResponse->assertJsonPath('meta.has_more', false);
        $allResponseIds = collect($allResponse->json('data'))->pluck('id')->all();
        $this->assertContains($currentSchedule->id, $allResponseIds);
        $this->assertContains($currentOtherDaySchedule->id, $allResponseIds);
        $this->assertNotContains($futureSchedule->id, $allResponseIds);
        $this->assertNotContains($legacySchedule->id, $allResponseIds);

        $firstSchedule = collect($allResponse->json('data'))->firstWhere('id', $currentSchedule->id);
        $this->assertNotNull($firstSchedule);
        $this->assertArrayHasKey('route', $firstSchedule);
        $this->assertArrayHasKey('stops_count', $firstSchedule['route']);
        $this->assertArrayNotHasKey('stops', $firstSchedule['route']);
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

    public function test_student_schedules_filter_all_handles_legacy_weekdays_formats_without_type_error(): void
    {
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $period = $this->createPeriod('Current', today()->startOfMonth(), today()->endOfMonth());
        $route = $this->createRoute(['schedule_period_id' => $period->id]);
        $today = strtolower(now()->englishDayOfWeek);
        $todayShort = $this->toShortWeekday($today);

        $jsonStringScheduleId = DB::table('schedules')->insertGetId([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $period->id,
            'departure_time' => '10:30',
            'weekdays' => json_encode($todayShort . ',fri'),
            'effective_date' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jsonObjectScheduleId = DB::table('schedules')->insertGetId([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_period_id' => $period->id,
            'departure_time' => '11:30',
            'weekdays' => json_encode([$todayShort => true, 'sun' => false, 'tue' => '1']),
            'effective_date' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/schedules?filter=all');

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $payload = collect($response->json('data'));

        $jsonStringSchedule = $payload->firstWhere('id', $jsonStringScheduleId);
        $this->assertNotNull($jsonStringSchedule);
        $this->assertContains($today, $jsonStringSchedule['weekdays']);
        $this->assertContains('friday', $jsonStringSchedule['weekdays']);
        $this->assertTrue($jsonStringSchedule['is_today']);

        $jsonObjectSchedule = $payload->firstWhere('id', $jsonObjectScheduleId);
        $this->assertNotNull($jsonObjectSchedule);
        $this->assertContains($today, $jsonObjectSchedule['weekdays']);
        $this->assertContains('tuesday', $jsonObjectSchedule['weekdays']);
        $this->assertNotContains('sunday', $jsonObjectSchedule['weekdays']);
    }

    public function test_student_routes_are_paginated_searchable_and_list_payload_is_lightweight(): void
    {
        $student = $this->createUser('student');
        $period = $this->createPeriod('Current', today()->startOfMonth(), today()->endOfMonth());

        $libraryRoute = $this->createRoute([
            'name' => 'Library Express',
            'code' => 'LIB-01',
            'origin_name' => 'Campus Gate',
            'destination_name' => 'Library',
            'schedule_period_id' => $period->id,
        ]);
        $cityRoute = $this->createRoute([
            'name' => 'City Shuttle',
            'code' => 'CITY-02',
            'origin_name' => 'Main Road',
            'destination_name' => 'City Center',
            'schedule_period_id' => $period->id,
        ]);
        $hallRoute = $this->createRoute([
            'name' => 'Hall Circular',
            'code' => 'HALL-03',
            'origin_name' => 'North Hall',
            'destination_name' => 'South Hall',
            'schedule_period_id' => $period->id,
        ]);

        RouteStop::create([
            'route_id' => $libraryRoute->id,
            'name' => 'Central Library',
            'lat' => 23.8103000,
            'lng' => 90.4125000,
            'sequence' => 1,
        ]);
        RouteStop::create([
            'route_id' => $cityRoute->id,
            'name' => 'City Bus Stand',
            'lat' => 23.8203000,
            'lng' => 90.4225000,
            'sequence' => 1,
        ]);

        Sanctum::actingAs($student);

        $pagedResponse = $this->getJson('/api/student/routes?page=1&per_page=1');
        $pagedResponse->assertOk();
        $pagedResponse->assertJsonCount(1, 'data');
        $pagedResponse->assertJsonPath('meta.current_page', 1);
        $pagedResponse->assertJsonPath('meta.per_page', 1);
        $pagedResponse->assertJsonPath('meta.total', 3);
        $pagedResponse->assertJsonPath('meta.last_page', 3);
        $pagedResponse->assertJsonPath('meta.has_more', true);

        $firstItem = $pagedResponse->json('data.0');
        $this->assertNotNull($firstItem);
        $this->assertArrayHasKey('stops_count', $firstItem);
        $this->assertArrayNotHasKey('stops', $firstItem);

        $searchResponse = $this->getJson('/api/student/routes?q=library');
        $searchResponse->assertOk();
        $searchResponse->assertJsonPath('meta.total', 1);
        $searchPayload = collect($searchResponse->json('data'));
        $this->assertCount(1, $searchPayload);
        $this->assertSame($libraryRoute->id, $searchPayload->first()['id']);

        $detailResponse = $this->getJson("/api/student/routes/{$libraryRoute->id}");
        $detailResponse->assertOk();
        $this->assertNotEmpty($detailResponse->json('stops'));
        $detailStops = collect($detailResponse->json('stops'))->pluck('name')->all();
        $this->assertContains('Central Library', $detailStops);
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

    private function toShortWeekday(string $weekday): string
    {
        return match ($weekday) {
            'monday' => 'mon',
            'tuesday' => 'tue',
            'wednesday' => 'wed',
            'thursday' => 'thu',
            'friday' => 'fri',
            'saturday' => 'sat',
            default => 'sun',
        };
    }

    private function differentWeekdayFrom(string $weekday): string
    {
        return $weekday === 'monday' ? 'tuesday' : 'monday';
    }
}
