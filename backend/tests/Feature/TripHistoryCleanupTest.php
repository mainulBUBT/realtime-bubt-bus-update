<?php

namespace Tests\Feature;

use App\Jobs\CompleteExpiredTripsJob;
use App\Models\Bus;
use App\Models\Location;
use App\Models\Route;
use App\Models\SchedulePeriod;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TripHistoryCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_ending_trip_keeps_trip_and_locations_for_history(): void
    {
        $driver = $this->createUser('driver');
        $bus = $this->createBus();
        $route = $this->createRoute();
        $trip = $this->createTrip($bus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
        ]);
        $location = $this->createLocation($trip, $bus);

        Sanctum::actingAs($driver);

        $response = $this->postJson("/api/driver/trips/{$trip->id}/end");

        $response->assertOk();
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'trip_id' => $trip->id,
            'bus_id' => $bus->id,
        ]);
        $this->assertNotNull($trip->fresh()->ended_at);
    }

    public function test_driver_history_returns_only_completed_and_cancelled_trips_in_descending_order(): void
    {
        $driver = $this->createUser('driver');
        $otherDriver = $this->createUser('driver');
        $bus = $this->createBus();
        $route = $this->createRoute();

        $yesterdayTrip = $this->createTrip($bus, $route, $driver, [
            'status' => 'completed',
            'trip_date' => today()->subDay()->toDateString(),
            'started_at' => today()->subDay()->setTime(9, 0),
            'ended_at' => today()->subDay()->setTime(10, 0),
        ]);

        $cancelledTrip = $this->createTrip($bus, $route, $driver, [
            'status' => 'cancelled',
            'trip_date' => today()->toDateString(),
            'started_at' => today()->setTime(8, 0),
            'ended_at' => today()->setTime(8, 30),
        ]);

        $latestCompletedTrip = $this->createTrip($bus, $route, $driver, [
            'status' => 'completed',
            'trip_date' => today()->toDateString(),
            'started_at' => today()->setTime(10, 0),
            'ended_at' => today()->setTime(11, 0),
        ]);

        $this->createTrip($bus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
            'started_at' => now(),
        ]);

        $this->createTrip($bus, $route, $otherDriver, [
            'status' => 'completed',
            'trip_date' => today()->toDateString(),
        ]);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/driver/trips/history');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.id', $latestCompletedTrip->id);
        $response->assertJsonPath('data.1.id', $cancelledTrip->id);
        $response->assertJsonPath('data.2.id', $yesterdayTrip->id);
    }

    public function test_stale_trip_cleanup_completes_previous_day_trips_and_keeps_todays_trip_active(): void
    {
        $driver = $this->createUser('driver');
        $bus = $this->createBus();
        $route = $this->createRoute();

        $staleWithLastLocation = $this->createTrip($bus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->subDay()->toDateString(),
            'last_location_at' => today()->subDay()->setTime(22, 15),
        ]);

        $staleWithoutLastLocation = $this->createTrip($bus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->subDays(2)->toDateString(),
            'last_location_at' => null,
        ]);

        $todayTrip = $this->createTrip($bus, $route, $driver, [
            'status' => 'ongoing',
            'trip_date' => today()->toDateString(),
        ]);

        (new CompleteExpiredTripsJob)->handle();

        $staleWithLastLocation->refresh();
        $staleWithoutLastLocation->refresh();
        $todayTrip->refresh();

        $this->assertSame('completed', $staleWithLastLocation->status);
        $this->assertSame(
            $staleWithLastLocation->last_location_at->toDateTimeString(),
            $staleWithLastLocation->ended_at->toDateTimeString()
        );

        $this->assertSame('completed', $staleWithoutLastLocation->status);
        $this->assertSame(
            $staleWithoutLastLocation->trip_date->copy()->endOfDay()->toDateTimeString(),
            $staleWithoutLastLocation->ended_at->toDateTimeString()
        );

        $this->assertSame('ongoing', $todayTrip->status);

        Sanctum::actingAs($driver);

        $historyResponse = $this->getJson('/api/driver/trips/history');

        $historyResponse->assertOk();
        $historyResponse->assertJsonFragment(['id' => $staleWithLastLocation->id]);
        $historyResponse->assertJsonFragment(['id' => $staleWithoutLastLocation->id]);
        $historyResponse->assertJsonMissing(['id' => $todayTrip->id]);
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

    private function createLocation(Trip $trip, Bus $bus): Location
    {
        return Location::create([
            'trip_id' => $trip->id,
            'bus_id' => $bus->id,
            'lat' => 23.8103000,
            'lng' => 90.3698000,
            'speed' => 25,
            'recorded_at' => now(),
        ]);
    }
}
