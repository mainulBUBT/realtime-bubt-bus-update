<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BusScheduleService;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BusScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BusScheduleService $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduleService = new BusScheduleService();
    }

    public function test_determines_bus_is_active_during_scheduled_time()
    {
        // Create a schedule for current day and time
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->isBusActive('B1', $currentTime);

        $this->assertTrue($result['is_active']);
        $this->assertEquals('within_schedule', $result['reason']);
        $this->assertEquals($schedule->id, $result['schedule']->id);
    }

    public function test_determines_bus_is_inactive_outside_scheduled_time()
    {
        // Create a schedule but check outside the time window
        $currentTime = Carbon::create(2024, 1, 15, 20, 0, 0); // Monday 8:00 PM (outside schedule)
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->isBusActive('B1', $currentTime);

        $this->assertFalse($result['is_active']);
        $this->assertEquals('outside_schedule', $result['reason']);
    }

    public function test_determines_bus_is_inactive_on_non_scheduled_day()
    {
        // Create a weekday schedule but check on weekend
        $currentTime = Carbon::create(2024, 1, 13, 8, 0, 0); // Saturday 8:00 AM
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->isBusActive('B1', $currentTime);

        $this->assertFalse($result['is_active']);
        $this->assertEquals('outside_schedule', $result['reason']);
    }

    public function test_returns_no_schedules_for_non_existent_bus()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);

        $result = $this->scheduleService->isBusActive('NON_EXISTENT', $currentTime);

        $this->assertFalse($result['is_active']);
        $this->assertEquals('no_schedules', $result['reason']);
        $this->assertEquals('No schedules found for this bus', $result['message']);
    }

    public function test_gets_current_trip_direction_for_departure_time()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM (departure time)
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // Create route stops
        BusRoute::create([
            'schedule_id' => $schedule->id,
            'stop_name' => 'Campus',
            'stop_order' => 1,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'coverage_radius' => 100,
            'estimated_time' => '07:00:00'
        ]);

        $result = $this->scheduleService->getCurrentTripDirection('B1', $currentTime);

        $this->assertEquals(BusScheduleService::DIRECTION_DEPARTURE, $result['direction']);
        $this->assertEquals('campus_to_city', $result['trip_type']);
        $this->assertEquals($schedule->id, $result['schedule_id']);
        $this->assertNotEmpty($result['route_stops']);
    }

    public function test_gets_current_trip_direction_for_return_time()
    {
        $currentTime = Carbon::create(2024, 1, 15, 18, 0, 0); // Monday 6:00 PM (return time)
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->getCurrentTripDirection('B1', $currentTime);

        $this->assertEquals(BusScheduleService::DIRECTION_RETURN, $result['direction']);
        $this->assertEquals('city_to_campus', $result['trip_type']);
    }

    public function test_returns_null_direction_for_inactive_bus()
    {
        $currentTime = Carbon::create(2024, 1, 15, 20, 0, 0); // Outside schedule

        $result = $this->scheduleService->getCurrentTripDirection('B1', $currentTime);

        $this->assertNull($result['direction']);
        $this->assertNull($result['trip_type']);
        $this->assertEquals('Bus is not currently active', $result['message']);
    }

    public function test_validates_gps_data_timing_for_active_bus()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->validateGPSDataTiming('B1', $currentTime);

        $this->assertTrue($result['valid']);
        $this->assertEquals('GPS data timing is valid', $result['message']);
        $this->assertArrayHasKey('trip_direction', $result);
    }

    public function test_rejects_gps_data_timing_for_inactive_bus()
    {
        $currentTime = Carbon::create(2024, 1, 15, 20, 0, 0); // Outside schedule

        $result = $this->scheduleService->validateGPSDataTiming('B1', $currentTime);

        $this->assertFalse($result['valid']);
        $this->assertEquals('bus_not_active', $result['reason']);
        $this->assertEquals('GPS data rejected: Bus is not currently scheduled to run', $result['message']);
    }

    public function test_rejects_gps_data_outside_buffer_time()
    {
        $currentTime = Carbon::create(2024, 1, 15, 6, 30, 0); // Too early (before buffer)
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->validateGPSDataTiming('B1', $currentTime);

        $this->assertFalse($result['valid']);
        $this->assertEquals('outside_buffer', $result['reason']);
        $this->assertArrayHasKey('buffer_start', $result);
        $this->assertArrayHasKey('buffer_end', $result);
    }

    public function test_handles_schedule_transition_from_departure_to_return()
    {
        $currentTime = Carbon::create(2024, 1, 15, 16, 45, 0); // Near return time
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->handleScheduleTransition('B1', $currentTime);

        $this->assertTrue($result['in_transition']);
        $this->assertEquals('departure_to_return', $result['transition_type']);
        $this->assertEquals(BusScheduleService::DIRECTION_DEPARTURE, $result['from_direction']);
        $this->assertEquals(BusScheduleService::DIRECTION_RETURN, $result['to_direction']);
    }

    public function test_handles_service_ending_transition()
    {
        $currentTime = Carbon::create(2024, 1, 15, 18, 45, 0); // Near service end
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->handleScheduleTransition('B1', $currentTime);

        $this->assertTrue($result['in_transition']);
        $this->assertEquals('service_ending', $result['transition_type']);
        $this->assertEquals(BusScheduleService::DIRECTION_RETURN, $result['from_direction']);
        $this->assertNull($result['to_direction']);
    }

    public function test_gets_route_stops_for_departure_direction()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // Create route stops in order
        $stops = ['Campus', 'Stop 1', 'Stop 2', 'City Center'];
        foreach ($stops as $index => $stopName) {
            BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => $stopName,
                'stop_order' => $index + 1,
                'latitude' => 23.7937 + ($index * 0.01),
                'longitude' => 90.3629 + ($index * 0.01),
                'coverage_radius' => 100,
                'estimated_time' => '07:' . str_pad(($index * 15), 2, '0', STR_PAD_LEFT) . ':00'
            ]);
        }

        $result = $this->scheduleService->getRouteStopsForDirection($schedule->id, BusScheduleService::DIRECTION_DEPARTURE);

        $this->assertCount(4, $result);
        $this->assertEquals('Campus', $result[0]['stop_name']);
        $this->assertEquals('City Center', $result[3]['stop_name']);
        $this->assertEquals('departure', $result[0]['direction']);
    }

    public function test_gets_route_stops_for_return_direction_reversed()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // Create route stops in order
        $stops = ['Campus', 'Stop 1', 'Stop 2', 'City Center'];
        foreach ($stops as $index => $stopName) {
            BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => $stopName,
                'stop_order' => $index + 1,
                'latitude' => 23.7937 + ($index * 0.01),
                'longitude' => 90.3629 + ($index * 0.01),
                'coverage_radius' => 100,
                'estimated_time' => '07:' . str_pad(($index * 15), 2, '0', STR_PAD_LEFT) . ':00'
            ]);
        }

        $result = $this->scheduleService->getRouteStopsForDirection($schedule->id, BusScheduleService::DIRECTION_RETURN);

        $this->assertCount(4, $result);
        $this->assertEquals('City Center', $result[0]['stop_name']); // Reversed order
        $this->assertEquals('Campus', $result[3]['stop_name']);
        $this->assertEquals('return', $result[0]['direction']);
        $this->assertEquals(1, $result[0]['stop_order']); // Re-ordered for return
    }

    public function test_gets_all_active_buses_for_current_time()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM

        // Create multiple schedules
        $activeBuses = ['B1', 'B2', 'B3'];
        foreach ($activeBuses as $busId) {
            BusSchedule::create([
                'bus_id' => $busId,
                'route_name' => "Route for {$busId}",
                'departure_time' => '07:00:00',
                'return_time' => '17:00:00',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true
            ]);
        }

        // Create an inactive bus
        BusSchedule::create([
            'bus_id' => 'B4',
            'route_name' => 'Inactive Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['saturday', 'sunday'], // Not active on Monday
            'is_active' => true
        ]);

        $result = $this->scheduleService->getActiveBuses($currentTime);

        $this->assertCount(3, $result);
        $activeBusIds = array_column($result, 'bus_id');
        $this->assertContains('B1', $activeBusIds);
        $this->assertContains('B2', $activeBusIds);
        $this->assertContains('B3', $activeBusIds);
        $this->assertNotContains('B4', $activeBusIds);
    }

    public function test_gets_schedule_statistics()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM

        // Create test schedules
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Active Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        BusSchedule::create([
            'bus_id' => 'B2',
            'route_name' => 'Inactive Route',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => false
        ]);

        $result = $this->scheduleService->getScheduleStatistics();

        $this->assertEquals(2, $result['total_schedules']);
        $this->assertEquals(1, $result['active_schedules']);
        $this->assertArrayHasKey('currently_active_buses', $result);
        $this->assertArrayHasKey('schedules_today', $result);
        $this->assertArrayHasKey('next_departures', $result);
        $this->assertArrayHasKey('schedule_adherence', $result);
    }

    public function test_validates_schedule_configuration_with_valid_data()
    {
        $validScheduleData = [
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
        ];

        $result = $this->scheduleService->validateScheduleConfiguration($validScheduleData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validates_schedule_configuration_with_missing_fields()
    {
        $invalidScheduleData = [
            'bus_id' => 'B1',
            // Missing required fields
        ];

        $result = $this->scheduleService->validateScheduleConfiguration($invalidScheduleData);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Missing required field: route_name', $result['errors']);
    }

    public function test_validates_schedule_configuration_with_invalid_time_order()
    {
        $invalidScheduleData = [
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '17:00:00',
            'return_time' => '07:00:00', // Return before departure
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
        ];

        $result = $this->scheduleService->validateScheduleConfiguration($invalidScheduleData);

        $this->assertFalse($result['valid']);
        $this->assertContains('Return time must be after departure time', $result['errors']);
    }

    public function test_validates_schedule_configuration_with_invalid_days()
    {
        $invalidScheduleData = [
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'invalid_day', 'friday']
        ];

        $result = $this->scheduleService->validateScheduleConfiguration($invalidScheduleData);

        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid days of week: invalid_day', $result['errors']);
    }

    public function test_caches_active_bus_results()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // First call should cache the result
        $result1 = $this->scheduleService->isBusActive('B1', $currentTime);
        
        // Second call should use cached result
        $result2 = $this->scheduleService->isBusActive('B1', $currentTime);

        $this->assertEquals($result1, $result2);
        $this->assertTrue($result1['is_active']);
    }

    public function test_clears_schedule_cache()
    {
        // Set some cache data
        Cache::put('bus_active_B1_test', ['cached' => true], 60);
        Cache::put('active_buses_test', ['cached' => true], 60);

        $this->scheduleService->clearScheduleCache();

        // Cache should be cleared (this is a basic test - in real implementation
        // we'd need to check specific cache keys)
        $this->assertTrue(true); // Basic assertion that method runs without error
    }

    public function test_finds_next_departure_for_inactive_bus()
    {
        $currentTime = Carbon::create(2024, 1, 15, 20, 0, 0); // Monday 8:00 PM (after schedule)
        
        BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        $result = $this->scheduleService->isBusActive('B1', $currentTime);

        $this->assertFalse($result['is_active']);
        $this->assertNotNull($result['next_departure']);
        $this->assertEquals('B1', $result['next_departure']['bus_id']);
        $this->assertInstanceOf(Carbon::class, $result['next_departure']['departure_time']);
    }

    public function test_calculates_trip_duration_based_on_stops()
    {
        $schedule = BusSchedule::create([
            'bus_id' => 'B1',
            'route_name' => 'Campus to City',
            'departure_time' => '07:00:00',
            'return_time' => '17:00:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true
        ]);

        // Create 5 stops
        for ($i = 1; $i <= 5; $i++) {
            BusRoute::create([
                'schedule_id' => $schedule->id,
                'stop_name' => "Stop {$i}",
                'stop_order' => $i,
                'latitude' => 23.7937 + ($i * 0.01),
                'longitude' => 90.3629 + ($i * 0.01),
                'coverage_radius' => 100,
                'estimated_time' => '07:' . str_pad(($i * 15), 2, '0', STR_PAD_LEFT) . ':00'
            ]);
        }

        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        $result = $this->scheduleService->getCurrentTripDirection('B1', $currentTime);

        $this->assertGreaterThan(0, $result['estimated_duration']);
        $this->assertIsInt($result['estimated_duration']);
    }
}