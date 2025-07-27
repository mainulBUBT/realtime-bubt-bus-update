<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RouteTimelineService;
use App\Services\BusScheduleService;
use App\Services\RouteValidator;
use App\Services\StopCoordinateManager;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusTimelineProgression;
use App\Models\BusLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RouteTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private RouteTimelineService $service;
    private BusScheduleService $scheduleService;
    private RouteValidator $routeValidator;
    private StopCoordinateManager $stopManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scheduleService = $this->createMock(BusScheduleService::class);
        $this->routeValidator = $this->createMock(RouteValidator::class);
        $this->stopManager = $this->createMock(StopCoordinateManager::class);
        
        $this->service = new RouteTimelineService(
            $this->scheduleService,
            $this->routeValidator,
            $this->stopManager
        );
    }

    public function test_initialize_timeline_progression_creates_records()
    {
        // Create test schedule and routes
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $routes = [];
        for ($i = 1; $i <= 3; $i++) {
            $routes[] = BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => "Stop {$i}",
                'stop_order' => $i,
                'latitude' => 23.7500 + ($i * 0.01),
                'longitude' => 90.3667 + ($i * 0.01),
                'coverage_radius' => 100,
                'estimated_departure_time' => '07:' . str_pad($i * 10, 2, '0', STR_PAD_LEFT) . ':00',
                'estimated_return_time' => '17:' . str_pad($i * 10, 2, '0', STR_PAD_LEFT) . ':00'
            ]);
        }

        $tripDirection = [
            'direction' => 'departure',
            'schedule_id' => $schedule->id,
            'route_stops' => collect($routes)->map(function ($route) {
                return [
                    'id' => $route->id,
                    'stop_name' => $route->stop_name,
                    'stop_order' => $route->stop_order,
                    'latitude' => $route->latitude,
                    'longitude' => $route->longitude
                ];
            })->toArray()
        ];

        // Initialize timeline progression
        $this->service->initializeTimelineProgression('B1', $tripDirection);

        // Assert progression records were created
        $this->assertDatabaseCount('bus_timeline_progression', 3);
        
        $progression = BusTimelineProgression::forBus('B1')->activeTrip()->get();
        $this->assertCount(3, $progression);
        
        // First stop should be current, others upcoming
        $firstStop = $progression->sortBy('route.stop_order')->first();
        $this->assertEquals(BusTimelineProgression::STATUS_CURRENT, $firstStop->status);
        
        $otherStops = $progression->sortBy('route.stop_order')->skip(1);
        foreach ($otherStops as $stop) {
            $this->assertEquals(BusTimelineProgression::STATUS_UPCOMING, $stop->status);
        }
    }

    public function test_get_route_timeline_returns_success_with_active_bus()
    {
        // Mock schedule service to return active trip
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'schedule_id' => 1,
                'route_stops' => [
                    [
                        'id' => 1,
                        'stop_name' => 'Stop 1',
                        'stop_order' => 1,
                        'latitude' => 23.7500,
                        'longitude' => 90.3667
                    ]
                ]
            ]);

        // Create test data
        $schedule = BusSchedule::factory()->create(['id' => 1]);
        $route = BusRoute::factory()->create(['id' => 1, 'schedule_id' => 1]);
        
        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => 1,
            'route_id' => 1,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        $result = $this->service->getRouteTimeline('B1');

        $this->assertTrue($result['success']);
        $this->assertEquals('B1', $result['bus_id']);
        $this->assertEquals('departure', $result['trip_direction']);
        $this->assertNotEmpty($result['timeline']);
    }

    public function test_get_route_timeline_returns_failure_with_inactive_bus()
    {
        // Mock schedule service to return inactive bus
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn(['direction' => null]);

        $result = $this->service->getRouteTimeline('B1');

        $this->assertFalse($result['success']);
        $this->assertEquals('Bus is not currently active', $result['message']);
        $this->assertEmpty($result['timeline']);
    }

    public function test_update_timeline_progression_marks_stop_as_completed()
    {
        // Create test data
        $schedule = BusSchedule::factory()->create();
        $route = BusRoute::factory()->create([
            'schedule_id' => $schedule->id,
            'latitude' => 23.7500,
            'longitude' => 90.3667,
            'coverage_radius' => 100
        ]);
        
        $progression = BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'schedule_id' => $schedule->id
            ]);

        // Update progression with coordinates very close to the stop
        $result = $this->service->updateTimelineProgression('B1', 23.7501, 90.3668);

        $this->assertTrue($result['updated']);
        $this->assertArrayHasKey('stop_completed', $result);
        
        // Check that progression was updated
        $progression->refresh();
        $this->assertEquals(BusTimelineProgression::STATUS_COMPLETED, $progression->status);
        $this->assertNotNull($progression->reached_at);
    }

    public function test_calculate_current_stop_eta_with_sufficient_data()
    {
        // Create test location data
        BusLocation::factory()->count(5)->create([
            'bus_id' => 'B1',
            'is_validated' => true,
            'created_at' => now()->subMinutes(5)
        ]);

        $targetStop = [
            'id' => 1,
            'latitude' => 23.7600,
            'longitude' => 90.3700
        ];

        $result = $this->service->calculateCurrentStopETA('B1', $targetStop);

        $this->assertTrue($result['eta_available']);
        $this->assertIsInt($result['estimated_minutes']);
        $this->assertGreaterThan(0, $result['estimated_minutes']);
        $this->assertEquals('real_time_gps', $result['calculation_method']);
    }

    public function test_calculate_stop_progress_percentage()
    {
        // Create test location data
        BusLocation::factory()->create([
            'bus_id' => 'B1',
            'latitude' => 23.7550, // Halfway between stops
            'longitude' => 90.3650,
            'created_at' => now()->subMinute()
        ]);

        $currentStop = [
            'latitude' => 23.7500,
            'longitude' => 90.3600
        ];

        $nextStop = [
            'latitude' => 23.7600,
            'longitude' => 90.3700
        ];

        $result = $this->service->calculateStopProgressPercentage('B1', $currentStop, $nextStop);

        $this->assertTrue($result['progress_available']);
        $this->assertIsFloat($result['percentage']);
        $this->assertGreaterThanOrEqual(0, $result['percentage']);
        $this->assertLessThanOrEqual(100, $result['percentage']);
    }

    public function test_handle_route_reversal_changes_direction()
    {
        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'schedule_id' => 1
            ]);

        $this->scheduleService->method('getRouteStopsForDirection')
            ->willReturn([
                ['id' => 1, 'stop_name' => 'Stop 1', 'stop_order' => 1]
            ]);

        // Create existing progression
        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => 1,
            'route_id' => 1,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        $result = $this->service->handleRouteReversal('B1', 'return');

        $this->assertTrue($result['reversed']);
        $this->assertEquals('return', $result['direction']);
        
        // Check that old progression was deactivated
        $oldProgression = BusTimelineProgression::forBus('B1')
            ->forDirection('departure')
            ->first();
        $this->assertFalse($oldProgression->is_active_trip);
    }

    public function test_get_timeline_status_management_returns_summary()
    {
        // Create test data
        $schedule = BusSchedule::factory()->create();
        $routes = BusRoute::factory()->count(3)->create(['schedule_id' => $schedule->id]);
        
        // Create progression with mixed statuses
        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $routes[0]->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_COMPLETED,
            'is_active_trip' => true
        ]);
        
        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $routes[1]->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);
        
        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $routes[2]->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_UPCOMING,
            'is_active_trip' => true
        ]);

        // Mock schedule service
        $this->scheduleService->method('getCurrentTripDirection')
            ->willReturn([
                'direction' => 'departure',
                'schedule_id' => $schedule->id
            ]);

        $result = $this->service->getTimelineStatusManagement('B1');

        $this->assertTrue($result['active']);
        $this->assertEquals('B1', $result['bus_id']);
        $this->assertEquals('departure', $result['trip_direction']);
        $this->assertEquals(1, $result['status_counts']['completed']);
        $this->assertEquals(1, $result['status_counts']['current']);
        $this->assertEquals(1, $result['status_counts']['upcoming']);
        $this->assertNotNull($result['current_stop']);
        $this->assertNotNull($result['next_stop']);
    }
}