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
use Illuminate\Foundation\Testing\RefreshDatabase;

class RouteTimelineServiceBasicTest extends TestCase
{
    use RefreshDatabase;

    private RouteTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $scheduleService = $this->createMock(BusScheduleService::class);
        $routeValidator = $this->createMock(RouteValidator::class);
        $stopManager = $this->createMock(StopCoordinateManager::class);
        
        $this->service = new RouteTimelineService(
            $scheduleService,
            $routeValidator,
            $stopManager
        );
    }

    public function test_timeline_progression_model_can_be_created()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday'],
            'is_active' => true
        ]);

        $route = BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Test Stop',
            'stop_order' => 1,
            'latitude' => 23.7500,
            'longitude' => 90.3667,
            'coverage_radius' => 100,
            'estimated_departure_time' => '07:10:00',
            'estimated_return_time' => '17:10:00'
        ]);

        $progression = BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        $this->assertDatabaseHas('bus_timeline_progression', [
            'bus_id' => 'B1',
            'status' => 'current',
            'trip_direction' => 'departure'
        ]);

        $this->assertTrue($progression->isCurrent());
        $this->assertFalse($progression->isCompleted());
        $this->assertFalse($progression->isUpcoming());
    }

    public function test_timeline_progression_can_be_marked_as_completed()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday'],
            'is_active' => true
        ]);

        $route = BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Test Stop',
            'stop_order' => 1,
            'latitude' => 23.7500,
            'longitude' => 90.3667,
            'coverage_radius' => 100,
            'estimated_departure_time' => '07:10:00',
            'estimated_return_time' => '17:10:00'
        ]);

        $progression = BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        // Mark as completed
        $progression->markAsCompleted();

        $this->assertTrue($progression->isCompleted());
        $this->assertEquals(100, $progression->progress_percentage);
        $this->assertNotNull($progression->reached_at);
    }

    public function test_timeline_progression_can_update_progress()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday'],
            'is_active' => true
        ]);

        $route = BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Test Stop',
            'stop_order' => 1,
            'latitude' => 23.7500,
            'longitude' => 90.3667,
            'coverage_radius' => 100,
            'estimated_departure_time' => '07:10:00',
            'estimated_return_time' => '17:10:00'
        ]);

        $progression = BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        // Update progress
        $progression->updateProgress(75, 5);

        $this->assertEquals(75, $progression->progress_percentage);
        $this->assertEquals(5, $progression->eta_minutes);
    }

    public function test_timeline_progression_scopes_work()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Test Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday'],
            'is_active' => true
        ]);

        $route1 = BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Stop 1',
            'stop_order' => 1,
            'latitude' => 23.7500,
            'longitude' => 90.3667,
            'coverage_radius' => 100,
            'estimated_departure_time' => '07:10:00',
            'estimated_return_time' => '17:10:00'
        ]);

        $route2 = BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Stop 2',
            'stop_order' => 2,
            'latitude' => 23.7600,
            'longitude' => 90.3767,
            'coverage_radius' => 100,
            'estimated_departure_time' => '07:20:00',
            'estimated_return_time' => '17:20:00'
        ]);

        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route1->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_COMPLETED,
            'is_active_trip' => true
        ]);

        BusTimelineProgression::create([
            'bus_id' => 'B1',
            'schedule_id' => $schedule->id,
            'route_id' => $route2->id,
            'trip_direction' => 'departure',
            'status' => BusTimelineProgression::STATUS_CURRENT,
            'is_active_trip' => true
        ]);

        // Test scopes
        $forBus = BusTimelineProgression::forBus('B1')->get();
        $this->assertCount(2, $forBus);

        $activeTrip = BusTimelineProgression::activeTrip()->get();
        $this->assertCount(2, $activeTrip);

        $completed = BusTimelineProgression::completed()->get();
        $this->assertCount(1, $completed);

        $current = BusTimelineProgression::current()->get();
        $this->assertCount(1, $current);

        $forDirection = BusTimelineProgression::forDirection('departure')->get();
        $this->assertCount(2, $forDirection);
    }
}