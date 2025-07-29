<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Livewire\BusTracker;
use App\Livewire\BusList;
use App\Livewire\LocationSharing;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class LivewireComponentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test schedule and routes
        $this->createTestScheduleAndRoutes();
    }

    public function test_bus_list_component_displays_active_buses()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0); // Monday 8:00 AM
        Carbon::setTestNow($currentTime);

        Livewire::test(BusList::class)
            ->assertSee('B1')
            ->assertSee('B2')
            ->assertSee('Campus to City Route')
            ->assertSee('Campus to Mall Route')
            ->assertDontSee('B3'); // Weekend bus should not be visible
    }

    public function test_bus_list_component_filters_buses_correctly()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        Livewire::test(BusList::class)
            ->set('selectedBusId', 'B1')
            ->assertSee('B1')
            ->assertDontSee('B2')
            ->set('selectedBusId', '')
            ->assertSee('B1')
            ->assertSee('B2');
    }

    public function test_bus_list_component_shows_bus_status_correctly()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create location data for B1 to make it active
        $this->createTestLocationData('B1');

        Livewire::test(BusList::class)
            ->assertSee('Active') // B1 should show as active
            ->assertSee('No tracking'); // B2 should show no tracking
    }

    public function test_bus_tracker_component_displays_route_timeline()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        Livewire::test(BusTracker::class, ['busId' => 'B1'])
            ->assertSee('Campus')
            ->assertSee('Stop 1')
            ->assertSee('Stop 2')
            ->assertSee('City Center')
            ->assertSee('I\'m on this Bus');
    }

    public function test_bus_tracker_component_starts_tracking_session()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create device token
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        // Mock session to simulate device token
        session(['device_token' => 'test_device_token']);

        Livewire::test(BusTracker::class, ['busId' => 'B1'])
            ->call('startTracking')
            ->assertSet('isTracking', true)
            ->assertSee('Stop Tracking');

        // Verify tracking session was created
        $this->assertDatabaseHas('user_tracking_sessions', [
            'device_token' => 'test_device_token',
            'bus_id' => 'B1',
            'is_active' => true
        ]);
    }

    public function test_bus_tracker_component_stops_tracking_session()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create device token and tracking session
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        $session = UserTrackingSession::create([
            'device_token' => 'test_device_token',
            'bus_id' => 'B1',
            'started_at' => now(),
            'is_active' => true
        ]);

        session(['device_token' => 'test_device_token']);

        Livewire::test(BusTracker::class, ['busId' => 'B1'])
            ->set('isTracking', true)
            ->call('stopTracking')
            ->assertSet('isTracking', false)
            ->assertSee('I\'m on this Bus');

        // Verify tracking session was ended
        $this->assertDatabaseHas('user_tracking_sessions', [
            'id' => $session->id,
            'is_active' => false
        ]);
    }

    public function test_bus_tracker_component_updates_with_real_time_location()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create location data
        $this->createTestLocationData('B1');

        $component = Livewire::test(BusTracker::class, ['busId' => 'B1']);

        // Simulate real-time location update
        $component->call('updateLocation', [
            'latitude' => 23.7950,
            'longitude' => 90.3640,
            'confidence' => 0.9,
            'active_trackers' => 3
        ]);

        $component->assertSet('currentLocation.latitude', 23.7950)
                 ->assertSet('currentLocation.longitude', 90.3640)
                 ->assertSet('currentLocation.confidence', 0.9);
    }

    public function test_location_sharing_component_handles_gps_permission()
    {
        Livewire::test(LocationSharing::class)
            ->assertSee('Enable Location Sharing')
            ->call('requestLocationPermission')
            ->assertEmitted('location-permission-requested');
    }

    public function test_location_sharing_component_starts_location_sharing()
    {
        // Create device token
        DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        session(['device_token' => 'test_device_token']);

        Livewire::test(LocationSharing::class)
            ->set('hasPermission', true)
            ->call('startSharing', 'B1')
            ->assertSet('isSharing', true)
            ->assertSee('Stop Sharing');
    }

    public function test_location_sharing_component_processes_location_batch()
    {
        // Create device token
        DeviceToken::create([
            'token_hash' => hash('sha256', 'test_device_token'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        session(['device_token' => 'test_device_token']);

        $locationBatch = [
            [
                'latitude' => 23.7937,
                'longitude' => 90.3629,
                'accuracy' => 20.0,
                'speed' => 25.0,
                'timestamp' => now()->timestamp * 1000
            ],
            [
                'latitude' => 23.7940,
                'longitude' => 90.3630,
                'accuracy' => 15.0,
                'speed' => 30.0,
                'timestamp' => (now()->addSeconds(30))->timestamp * 1000
            ]
        ];

        Livewire::test(LocationSharing::class)
            ->set('isSharing', true)
            ->call('processBatchLocationData', $locationBatch)
            ->assertEmitted('location-batch-processed');

        // Verify locations were stored
        $this->assertDatabaseCount('bus_locations', 2);
    }

    public function test_components_handle_inactive_bus_gracefully()
    {
        $currentTime = Carbon::create(2024, 1, 15, 20, 0, 0); // Outside schedule
        Carbon::setTestNow($currentTime);

        // Bus List should show no active buses
        Livewire::test(BusList::class)
            ->assertSee('No active buses')
            ->assertDontSee('B1')
            ->assertDontSee('B2');

        // Bus Tracker should show inactive message
        Livewire::test(BusTracker::class, ['busId' => 'B1'])
            ->assertSee('Bus is not currently active')
            ->assertDontSee('I\'m on this Bus');
    }

    public function test_components_handle_database_errors_gracefully()
    {
        // Temporarily disable database connection to simulate error
        config(['database.default' => 'invalid']);

        Livewire::test(BusList::class)
            ->assertSee('Unable to load bus information');

        Livewire::test(BusTracker::class, ['busId' => 'B1'])
            ->assertSee('Unable to load bus tracking information');
    }

    public function test_components_emit_and_listen_to_events_correctly()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Test BusTracker emits location updates
        $busTracker = Livewire::test(BusTracker::class, ['busId' => 'B1']);
        
        $busTracker->call('updateLocation', [
            'latitude' => 23.7950,
            'longitude' => 90.3640,
            'confidence' => 0.9
        ]);

        $busTracker->assertEmitted('bus-location-updated');

        // Test LocationSharing listens to tracking events
        $locationSharing = Livewire::test(LocationSharing::class);
        
        $locationSharing->emit('tracking-started', 'B1');
        $locationSharing->assertSet('currentBusId', 'B1');
    }

    public function test_components_validate_user_input_properly()
    {
        // Test BusTracker with invalid bus ID
        Livewire::test(BusTracker::class, ['busId' => 'INVALID'])
            ->assertSee('Invalid bus ID');

        // Test LocationSharing with invalid coordinates
        Livewire::test(LocationSharing::class)
            ->call('processBatchLocationData', [
                [
                    'latitude' => 'invalid',
                    'longitude' => 'invalid',
                    'accuracy' => -1,
                    'timestamp' => 'invalid'
                ]
            ])
            ->assertHasErrors(['locationData']);
    }

    public function test_components_handle_concurrent_users_correctly()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create multiple device tokens
        for ($i = 1; $i <= 3; $i++) {
            DeviceToken::create([
                'token_hash' => hash('sha256', "test_device_token_{$i}"),
                'fingerprint_data' => ['test' => "data_{$i}"],
                'reputation_score' => 0.8,
                'trust_score' => 0.7
            ]);
        }

        // Simulate multiple users tracking the same bus
        $components = [];
        for ($i = 1; $i <= 3; $i++) {
            session(['device_token' => "test_device_token_{$i}"]);
            
            $component = Livewire::test(BusTracker::class, ['busId' => 'B1']);
            $component->call('startTracking');
            $components[] = $component;
        }

        // Verify all tracking sessions were created
        $this->assertDatabaseCount('user_tracking_sessions', 3);

        // Verify all sessions are for the same bus
        $sessions = UserTrackingSession::where('bus_id', 'B1')->where('is_active', true)->get();
        $this->assertCount(3, $sessions);
    }

    public function test_components_update_timeline_progression_correctly()
    {
        $currentTime = Carbon::create(2024, 1, 15, 8, 0, 0);
        Carbon::setTestNow($currentTime);

        // Create timeline progression data
        $schedule = BusSchedule::where('bus_id', 'B1')->first();
        $routes = BusRoute::where('schedule_id', $schedule->id)->orderBy('stop_order')->get();

        foreach ($routes as $route) {
            \App\Models\BusTimelineProgression::create([
                'bus_id' => 'B1',
                'schedule_id' => $schedule->id,
                'route_id' => $route->id,
                'trip_direction' => 'departure',
                'status' => $route->stop_order === 1 ? 'current' : 'upcoming',
                'is_active_trip' => true
            ]);
        }

        $component = Livewire::test(BusTracker::class, ['busId' => 'B1']);

        // Simulate bus reaching next stop
        $component->call('updateTimelineProgression', [
            'current_stop_id' => $routes[1]->id,
            'completed_stop_id' => $routes[0]->id
        ]);

        // Verify timeline was updated
        $this->assertDatabaseHas('bus_timeline_progression', [
            'route_id' => $routes[0]->id,
            'status' => 'completed'
        ]);

        $this->assertDatabaseHas('bus_timeline_progression', [
            'route_id' => $routes[1]->id,
            'status' => 'current'
        ]);
    }

    /**
     * Helper methods
     */
    private function createTestScheduleAndRoutes()
    {
        // Create weekday schedules
        $weekdaySchedules = [
            ['bus_id' => 'B1', 'route_name' => 'Campus to City Route'],
            ['bus_id' => 'B2', 'route_name' => 'Campus to Mall Route']
        ];

        foreach ($weekdaySchedules as $scheduleData) {
            $schedule = BusSchedule::create([
                'bus_id' => $scheduleData['bus_id'],
                'route_name' => $scheduleData['route_name'],
                'departure_time' => '07:00:00',
                'return_time' => '17:00:00',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true
            ]);

            // Create route stops
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
        }

        // Create weekend schedule (should not be active on weekdays)
        $weekendSchedule = BusSchedule::create([
            'bus_id' => 'B3',
            'route_name' => 'Weekend Special Route',
            'departure_time' => '09:00:00',
            'return_time' => '18:00:00',
            'days_of_week' => ['saturday', 'sunday'],
            'is_active' => true
        ]);
    }

    private function createTestLocationData(string $busId)
    {
        $deviceToken = DeviceToken::create([
            'token_hash' => hash('sha256', 'test_location_device'),
            'fingerprint_data' => ['test' => 'data'],
            'reputation_score' => 0.8,
            'trust_score' => 0.7
        ]);

        BusLocation::create([
            'bus_id' => $busId,
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7937,
            'longitude' => 90.3629,
            'accuracy' => 20.0,
            'speed' => 25.0,
            'reputation_weight' => 0.8,
            'is_validated' => true,
            'created_at' => now()
        ]);
    }
}