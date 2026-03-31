<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Route;
use App\Models\SchedulePeriod;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TripAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_active_trips_only_returns_todays_ongoing_trips(): void
    {
        $student = $this->createUser('student');
        $driver = $this->createUser('driver');

        $visibleBus = $this->createBus([
            'code' => 'B1',
            'display_name' => 'Buriganga',
            'plate_number' => 'DHAKA-METRO-15-2843',
        ]);
        $visibleRoute = $this->createRoute([
            'code' => 'B1-OUT',
            'name' => 'Buriganga - To Campus via Asad Gate',
        ]);

        $hiddenBus = $this->createBus([
            'code' => 'B2',
            'display_name' => 'Brahmaputra',
            'plate_number' => 'DHAKA-METRO-11-1925',
        ]);
        $hiddenRoute = $this->createRoute([
            'code' => 'B2-OUT',
            'name' => 'Brahmaputra - To Campus via Hemayetpur',
        ]);

        $visibleTrip = $this->createTrip($visibleBus, $visibleRoute, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
        ]);

        $this->createTrip($hiddenBus, $hiddenRoute, $driver, [
            'status' => 'completed',
            'trip_date' => today()->toDateString(),
        ]);

        $this->createTrip($hiddenBus, $hiddenRoute, $driver, [
            'status' => 'cancelled',
            'trip_date' => today()->toDateString(),
        ]);

        $staleTrip = $this->createTrip($hiddenBus, $hiddenRoute, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->subDay()->toDateString(),
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/trips/active');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $visibleTrip->id,
            'display_name' => 'Buriganga',
            'name' => 'Buriganga - To Campus via Asad Gate',
        ]);
        $response->assertJsonMissing(['id' => $staleTrip->id]);
    }

    public function test_driver_available_buses_hides_buses_with_active_trips_today(): void
    {
        $driver = $this->createUser('driver');

        $activeBus = $this->createBus([
            'code' => 'B1',
            'display_name' => 'Buriganga',
            'plate_number' => 'DHAKA-METRO-15-2843',
        ]);
        $completedOnlyBus = $this->createBus([
            'code' => 'B2',
            'display_name' => 'Brahmaputra',
            'plate_number' => 'DHAKA-METRO-11-1925',
        ]);
        $staleBus = $this->createBus([
            'code' => 'B3',
            'display_name' => 'Padma',
            'plate_number' => 'DHAKA-METRO-13-2208',
        ]);

        $route = $this->createRoute();

        $this->createTrip($activeBus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
        ]);

        $this->createTrip($completedOnlyBus, $route, $driver, [
            'status' => 'completed',
            'trip_date' => today()->toDateString(),
        ]);

        $this->createTrip($staleBus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->subDay()->toDateString(),
        ]);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/driver/buses');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $activeBus->id]);
        $response->assertJsonFragment([
            'id' => $completedOnlyBus->id,
            'display_name' => 'Brahmaputra',
        ]);
        $response->assertJsonFragment([
            'id' => $staleBus->id,
            'display_name' => 'Padma',
        ]);
    }

    public function test_driver_cannot_start_trip_for_bus_already_active_today(): void
    {
        $driver = $this->createUser('driver');
        $otherDriver = $this->createUser('driver');
        $bus = $this->createBus([
            'code' => 'B1',
            'display_name' => 'Buriganga',
            'plate_number' => 'DHAKA-METRO-15-2843',
        ]);
        $route = $this->createRoute([
            'code' => 'B1-OUT',
            'name' => 'Buriganga - To Campus via Asad Gate',
        ]);

        $existingTrip = $this->createTrip($bus, $route, $otherDriver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/driver/trips/start', [
            'bus_id' => $bus->id,
            'route_id' => $route->id,
        ]);

        $response->assertStatus(409);
        $response->assertJsonFragment([
            'message' => 'This bus already has an ongoing trip',
            'existing_trip_id' => $existingTrip->id,
        ]);
    }

    public function test_driver_can_start_trip_when_only_stale_previous_day_trip_exists(): void
    {
        $driver = $this->createUser('driver');
        $otherDriver = $this->createUser('driver');
        $bus = $this->createBus([
            'code' => 'B1',
            'display_name' => 'Buriganga',
            'plate_number' => 'DHAKA-METRO-15-2843',
        ]);
        $route = $this->createRoute([
            'code' => 'B1-OUT',
            'name' => 'Buriganga - To Campus via Asad Gate',
        ]);

        $this->createTrip($bus, $route, $otherDriver, [
            'status' => 'ongoing',
            'trip_date' => today()->subDay()->toDateString(),
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/driver/trips/start', [
            'bus_id' => $bus->id,
            'route_id' => $route->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('trip.bus.id', $bus->id);
        $response->assertJsonPath('trip.route.id', $route->id);
        $this->assertTrue(
            Trip::query()
                ->where('bus_id', $bus->id)
                ->where('driver_id', $driver->id)
                ->where('status', 'ongoing')
                ->whereDate('trip_date', today())
                ->exists()
        );
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
            'display_name' => fake()->unique()->word(),
            'code' => strtoupper(fake()->unique()->bothify('B#??')),
            'capacity' => 40,
            'status' => 'active',
        ], $attributes));
    }

    private function createRoute(array $attributes = []): Route
    {
        $period = SchedulePeriod::create([
            'name' => 'Regular Schedule ' . fake()->unique()->word(),
            'start_date' => today()->startOfMonth()->toDateString(),
            'end_date' => today()->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        return Route::create(array_merge([
            'name' => 'Route ' . fake()->unique()->word(),
            'code' => strtoupper(fake()->unique()->bothify('R#??')),
            'direction' => 'outbound',
            'origin_name' => 'Origin',
            'destination_name' => 'Destination',
            'schedule_period_id' => $period->id,
            'is_active' => true,
        ], $attributes));
    }

    private function createTrip(Bus $bus, Route $route, User $driver, array $attributes = []): Trip
    {
        return Trip::create(array_merge([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'schedule_id' => null,
            'trip_date' => today()->toDateString(),
            'status' => 'ongoing',
            'started_at' => now(),
        ], $attributes));
    }
}
