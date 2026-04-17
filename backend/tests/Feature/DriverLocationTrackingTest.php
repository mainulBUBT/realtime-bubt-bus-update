<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Location;
use App\Models\Route;
use App\Models\RouteStop;
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
        $route = $this->createRouteWithStops();
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
        $this->assertNotNull($trip->tracking_status);
    }

    public function test_batch_location_update_persists_multiple_points_and_trip_cache_uses_latest_recorded_at(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
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

    public function test_active_trip_without_gps_returns_no_gps_tracking_state(): void
    {
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
        $driver = $this->createUser('driver');

        $trip = $this->createTrip($bus, $route, $driver);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/trips/active');

        $response->assertOk();
        $response->assertJsonPath('0.id', $trip->id);
        $response->assertJsonPath('0.tracking_status', 'no_gps');
        $response->assertJsonPath('0.stop_states.0.state', 'upcoming');
    }

    public function test_active_trip_bootstraps_tracking_state_from_latest_location_when_trip_fields_are_empty(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
        $trip = $this->createTrip($bus, $route, $driver);

        $location = Location::create([
            'trip_id' => $trip->id,
            'bus_id' => $bus->id,
            'lat' => 23.7800000,
            'lng' => 90.4197000,
            'recorded_at' => Carbon::parse('2026-04-04T09:08:00+06:00'),
        ]);

        $trip->update([
            'current_lat' => $location->lat,
            'current_lng' => $location->lng,
            'last_location_at' => $location->recorded_at,
            'progress_distance_m' => null,
            'previous_progress_distance_m' => null,
            'progress_segment_index' => null,
            'current_stop_id' => null,
            'next_stop_id' => null,
            'tracking_status' => null,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/trips/active');

        $response->assertOk();
        $response->assertJsonPath('0.id', $trip->id);
        $response->assertJsonPath('0.tracking_status', 'on_route');
        $response->assertJsonPath('0.next_stop_id', $route->stops()->where('sequence', 4)->value('id'));
        $response->assertJsonPath('0.stop_states.0.state', 'passed');
        $response->assertJsonPath('0.stop_states.1.state', 'passed');
        $response->assertJsonPath('0.stop_states.2.state', 'passed');
        $response->assertJsonPath('0.stop_states.3.state', 'approaching');

        $trip->refresh();
        $this->assertNotNull($trip->progress_distance_m);
        $this->assertSame('on_route', $trip->tracking_status);
    }

    public function test_progress_engine_tracks_forward_progress_and_near_destination_correctly(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
        $trip = $this->createTrip($bus, $route, $driver);

        Sanctum::actingAs($driver);

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4100500,
            'recorded_at' => '2026-04-04T09:00:00+06:00',
        ])->assertOk();

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4197000,
            'recorded_at' => '2026-04-04T09:08:00+06:00',
        ])->assertOk();

        $trip->refresh();
        $this->assertEquals('on_route', $trip->tracking_status);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/trips/active');

        $response->assertOk();
        $response->assertJsonPath('0.tracking_status', 'on_route');
        $response->assertJsonPath('0.next_stop_id', $route->stops()->where('sequence', 4)->value('id'));
        $response->assertJsonPath('0.distance_to_next_stop_m', 234.03);
        $response->assertJsonPath('0.stop_states.0.state', 'passed');
        $response->assertJsonPath('0.stop_states.1.state', 'passed');
        $response->assertJsonPath('0.stop_states.2.state', 'passed');
        $response->assertJsonPath('0.stop_states.3.state', 'approaching');

        Sanctum::actingAs($driver);

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4219800,
            'recorded_at' => '2026-04-04T09:10:00+06:00',
        ])->assertOk();

        Sanctum::actingAs($student);

        $destinationResponse = $this->getJson('/api/student/trips/active');
        $destinationResponse->assertOk();
        $destinationResponse->assertJsonPath('0.current_stop_id', $route->stops()->where('sequence', 4)->value('id'));
        $destinationResponse->assertJsonPath('0.stop_states.3.state', 'current');
    }

    public function test_skip_stop_backward_off_route_and_rejoin_behaviors_are_stable(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $student = $this->createUser('student');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
        $trip = $this->createTrip($bus, $route, $driver);

        Sanctum::actingAs($driver);

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4100500,
            'recorded_at' => '2026-04-04T09:00:00+06:00',
        ])->assertOk();

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4197000,
            'recorded_at' => '2026-04-04T09:06:00+06:00',
        ])->assertOk();

        $trip->refresh();
        $forwardProgress = (float) $trip->progress_distance_m;

        Sanctum::actingAs($student);
        $skipResponse = $this->getJson('/api/student/trips/active');
        $skipResponse->assertOk();
        $skipResponse->assertJsonPath('0.stop_states.1.state', 'passed');
        $skipResponse->assertJsonPath('0.stop_states.2.state', 'passed');
        $skipResponse->assertJsonPath('0.stop_states.3.state', 'approaching');

        Sanctum::actingAs($driver);
        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4141000,
            'recorded_at' => '2026-04-04T09:20:00+06:00',
        ])->assertOk();

        $trip->refresh();
        $this->assertSame('backward', $trip->tracking_status);
        $this->assertGreaterThanOrEqual($forwardProgress, (float) $trip->progress_distance_m);

        foreach ([1, 2, 3] as $minuteOffset) {
            $this->postJson('/api/driver/location', [
                'trip_id' => $trip->id,
                'lat' => 23.7825000,
                'lng' => 90.4160000 + ($minuteOffset * 0.0001),
                'recorded_at' => Carbon::parse('2026-04-04T09:30:00+06:00')->addMinutes($minuteOffset)->toIso8601String(),
            ])->assertOk();
        }

        $trip->refresh();
        $this->assertTrue($trip->is_off_route);
        $frozenProgress = (float) $trip->progress_distance_m;

        Sanctum::actingAs($student);
        $offRouteResponse = $this->getJson('/api/student/trips/active');
        $offRouteResponse->assertOk();
        $offRouteResponse->assertJsonPath('0.tracking_status', 'off_route');
        $offRouteResponse->assertJsonPath('0.distance_to_next_stop_m', 234.03);

        Sanctum::actingAs($driver);
        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4218000,
            'recorded_at' => '2026-04-04T09:40:00+06:00',
        ])->assertOk();

        $trip->refresh();
        $this->assertFalse($trip->is_off_route);
        $this->assertGreaterThanOrEqual($frozenProgress, (float) $trip->progress_distance_m);
    }

    public function test_noise_and_impossible_speed_points_do_not_advance_progress(): void
    {
        Event::fake();

        $driver = $this->createUser('driver');
        $bus = $this->createBus();
        $route = $this->createRouteWithStops();
        $trip = $this->createTrip($bus, $route, $driver);

        Sanctum::actingAs($driver);

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4100500,
            'recorded_at' => '2026-04-04T09:00:00+06:00',
        ])->assertOk();

        $trip->refresh();
        $firstAcceptedAt = $trip->last_gps_at?->timestamp;
        $firstProgress = (float) $trip->progress_distance_m;

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4100800,
            'recorded_at' => '2026-04-04T09:00:10+06:00',
        ])->assertOk()
            ->assertJsonPath('ignored', true);

        $trip->refresh();
        $this->assertSame(1, Location::query()->count());
        $this->assertSame($firstAcceptedAt, $trip->last_gps_at?->timestamp);
        $this->assertSame($firstProgress, (float) $trip->progress_distance_m);
        $this->assertEquals('23.7800000', (string) $trip->current_lat);
        $this->assertEquals('90.4100500', (string) $trip->current_lng);

        $this->postJson('/api/driver/location', [
            'trip_id' => $trip->id,
            'lat' => 23.7800000,
            'lng' => 90.4220000,
            'recorded_at' => '2026-04-04T09:00:20+06:00',
        ])->assertOk()
            ->assertJsonPath('ignored', true);

        $trip->refresh();
        $this->assertSame(1, Location::query()->count());
        $this->assertSame($firstAcceptedAt, $trip->last_gps_at?->timestamp);
        $this->assertSame($firstProgress, (float) $trip->progress_distance_m);
        $this->assertEquals('23.7800000', (string) $trip->current_lat);
        $this->assertEquals('90.4100500', (string) $trip->current_lng);
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

    private function createRouteWithStops(array $routeAttributes = []): Route
    {
        $route = $this->createRoute($routeAttributes);

        $stops = [
            ['name' => 'A', 'sequence' => 1, 'lat' => 23.7800000, 'lng' => 90.4100000],
            ['name' => 'B', 'sequence' => 2, 'lat' => 23.7800000, 'lng' => 90.4140000],
            ['name' => 'C', 'sequence' => 3, 'lat' => 23.7800000, 'lng' => 90.4180000],
            ['name' => 'D', 'sequence' => 4, 'lat' => 23.7800000, 'lng' => 90.4220000],
        ];

        foreach ($stops as $stop) {
            RouteStop::create([
                'route_id' => $route->id,
                'name' => $stop['name'],
                'sequence' => $stop['sequence'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
            ]);
        }

        return $route->fresh(['stops']);
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
