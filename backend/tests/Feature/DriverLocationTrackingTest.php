<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Location;
use App\Models\Route;
use App\Models\SchedulePeriod;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverLocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_location_update_creates_history_and_uses_client_timestamp(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $bus = $this->createBus();
        $route = $this->createRoute();
        $trip = $this->createTrip($bus, $route, $driver);
        $recordedAt = Carbon::parse('2026-04-04 09:15:00');

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7812345,
            'lng' => 90.4123456,
            'speed' => 28.7,
            'recorded_at' => $recordedAt->toIso8601String(),
        ]);

        $response->assertOk();
        $this->assertEquals(1, Location::query()->count());

        $location = Location::query()->firstOrFail();

        $this->assertSame($trip->id, $location->trip_id);
        $this->assertEquals('23.7812345', (string) $location->lat);
        $this->assertEquals('90.4123456', (string) $location->lng);
        $this->assertTrue($location->recorded_at->equalTo($recordedAt));

        $trip->refresh();

        $this->assertEquals('23.7812345', (string) $trip->current_lat);
        $this->assertEquals('90.4123456', (string) $trip->current_lng);
        $this->assertTrue($trip->last_location_at->equalTo($recordedAt));
    }

    public function test_batch_location_update_persists_multiple_points_and_trip_cache_uses_latest_recorded_at(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRoute();
        $trip = $this->createTrip($bus, $route, $driver);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/driver/location/batch', [
            'trip_id' => $trip->id,
            'locations' => [
                [
                    'lat' => 23.7800001,
                    'lng' => 90.4100001,
                    'speed' => 12.5,
                    'recorded_at' => '2026-04-04T09:00:00+06:00',
                ],
                [
                    'lat' => 23.7805001,
                    'lng' => 90.4105001,
                    'speed' => 18.0,
                    'recorded_at' => '2026-04-04T09:05:00+06:00',
                ],
                [
                    'lat' => 23.7810001,
                    'lng' => 90.4110001,
                    'speed' => 21.0,
                    'recorded_at' => '2026-04-04T09:02:00+06:00',
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals(3, Location::query()->count());

        $trip->refresh();

        $this->assertEquals('23.7805001', (string) $trip->current_lat);
        $this->assertEquals('90.4105001', (string) $trip->current_lng);
        $this->assertSame(
            Carbon::parse('2026-04-04T09:05:00+06:00')->timestamp,
            $trip->last_location_at->timestamp
        );

        Sanctum::actingAs($student);

        $historyResponse = $this->getJson("/api/student/trips/{$trip->id}/locations");

        $historyResponse->assertOk();
        $historyResponse->assertJsonCount(3, 'locations');
        $historyResponse->assertJsonPath('locations.0.lat', '23.7805001');
        $historyResponse->assertJsonPath('locations.0.lng', '90.4105001');
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
