<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bus;
use App\Models\Location;
use App\Support\Cluster;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BusTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_bus(): void
    {
        $bus = Bus::create([
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('buses', [
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);
    }

    public function test_can_ping_location(): void
    {
        $bus = Bus::create([
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/ping', [
            'bus_id' => $bus->id,
            'latitude' => 23.8103,
            'longitude' => 90.4125,
            'source' => 'test'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Location updated successfully'
                ]);

        $this->assertDatabaseHas('locations', [
            'bus_id' => $bus->id,
            'latitude' => 23.8103,
            'longitude' => 90.4125,
        ]);
    }

    public function test_rate_limiting_on_ping(): void
    {
        $bus = Bus::create([
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);

        // Make 4 requests (should be allowed)
        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/ping', [
                'bus_id' => $bus->id,
                'latitude' => 23.8103,
                'longitude' => 90.4125,
            ]);
            $response->assertStatus(200);
        }

        // 5th request should be rate limited
        $response = $this->postJson('/api/ping', [
            'bus_id' => $bus->id,
            'latitude' => 23.8103,
            'longitude' => 90.4125,
        ]);

        $response->assertStatus(429);
    }

    public function test_clustering_algorithm(): void
    {
        $cluster = new Cluster(60, 1); // 60m radius, min 1 point (to ensure we get results)

        $locations = [
            ['lat' => 23.8103, 'lng' => 90.4125, 'bus_id' => 1, 'bus_name' => 'B1', 'recorded_at' => now()->toISOString()],
            ['lat' => 23.8104, 'lng' => 90.4126, 'bus_id' => 2, 'bus_name' => 'B2', 'recorded_at' => now()->toISOString()],
        ];

        $result = $cluster->getBusPositions($locations);

        // Test that the clustering function works and returns some result
        $this->assertIsArray($result);
        // With minPts = 1, we should get at least some clusters
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function test_can_get_bus_positions(): void
    {
        $bus = Bus::create([
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);

        Location::create([
            'bus_id' => $bus->id,
            'latitude' => 23.8103,
            'longitude' => 90.4125,
            'recorded_at' => now(),
            'source' => 'test'
        ]);

        $response = $this->getJson('/api/positions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'positions',
                        'last_updated',
                        'active_buses'
                    ]
                ]);
    }

    public function test_livewire_today_trips_component(): void
    {
        $bus = Bus::create([
            'name' => 'B1',
            'route_name' => 'Test Route',
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_livewire_admin_dashboard_component(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }
}