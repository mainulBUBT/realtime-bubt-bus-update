<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Services\TripCompletionService;
use App\Services\HistoricalDataService;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;
use App\Models\DeviceToken;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bus Trip Completion Detection Test ===\n\n";

try {
    $historicalService = new HistoricalDataService();
    $completionService = new TripCompletionService($historicalService);
    
    // Test 1: Create test schedule and routes
    echo "1. Creating test schedule and routes...\n";
    
    $schedule = BusSchedule::create([
        'bus_id' => 'B1',
        'route_name' => 'BUBT - Asad Gate',
        'departure_time' => '07:00',
        'return_time' => '17:00',
        'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true
    ]);
    
    // Create route stops
    $stops = [
        ['stop_name' => 'BUBT Campus', 'stop_order' => 1, 'latitude' => 23.7500, 'longitude' => 90.3750, 'coverage_radius' => 100],
        ['stop_name' => 'Rainkhola', 'stop_order' => 2, 'latitude' => 23.7600, 'longitude' => 90.3800, 'coverage_radius' => 150],
        ['stop_name' => 'Mirpur-1', 'stop_order' => 3, 'latitude' => 23.7700, 'longitude' => 90.3850, 'coverage_radius' => 200],
        ['stop_name' => 'Shyamoli', 'stop_order' => 4, 'latitude' => 23.7800, 'longitude' => 90.3900, 'coverage_radius' => 150],
        ['stop_name' => 'Asad Gate', 'stop_order' => 5, 'latitude' => 23.7900, 'longitude' => 90.3950, 'coverage_radius' => 100]
    ];
    
    foreach ($stops as $stop) {
        BusRoute::create(array_merge($stop, [
            'schedule_id' => $schedule->id,
            'estimated_departure_time' => '08:00',
            'estimated_return_time' => '16:00'
        ]));
    }
    
    echo "Created schedule for bus {$schedule->bus_id} with " . count($stops) . " stops\n";
    
    // Test 2: Create device token and tracking session
    echo "\n2. Creating test device token and tracking session...\n";
    
    $deviceToken = DeviceToken::create([
        'token_hash' => 'test_device_' . uniqid(),
        'fingerprint_data' => ['test' => 'data'],
        'reputation_score' => 0.8,
        'trust_score' => 0.8,
        'is_trusted' => true,
        'total_contributions' => 100,
        'accurate_contributions' => 85
    ]);
    
    $trackingSession = UserTrackingSession::create([
        'device_token' => $deviceToken->token_hash,
        'device_token_hash' => $deviceToken->token_hash,
        'session_id' => 'test_session_' . uniqid(),
        'bus_id' => 'B1',
        'started_at' => now()->subHours(2),
        'is_active' => true
    ]);
    
    echo "Created device token and active tracking session\n";
    
    // Test 3: Create location data simulating a trip
    echo "\n3. Creating location data simulating a complete trip...\n";
    
    $locations = [];
    $startTime = now()->subHours(2);
    
    // Simulate movement through all stops
    foreach ($stops as $index => $stop) {
        for ($i = 0; $i < 5; $i++) {
            $locations[] = [
                'bus_id' => 'B1',
                'device_token' => $deviceToken->token_hash,
                'latitude' => $stop['latitude'] + ($i * 0.0001), // Small variations
                'longitude' => $stop['longitude'] + ($i * 0.0001),
                'accuracy' => 10.0,
                'speed' => $index === count($stops) - 1 ? 1.0 : 15.0, // Slow down at final stop
                'reputation_weight' => 0.8,
                'is_validated' => true,
                'created_at' => $startTime->copy()->addMinutes(($index * 20) + ($i * 2)),
                'updated_at' => $startTime->copy()->addMinutes(($index * 20) + ($i * 2))
            ];
        }
    }
    
    BusLocation::insert($locations);
    echo "Created " . count($locations) . " location records simulating trip through all stops\n";
    
    // Test 4: Create current position
    echo "\n4. Creating current position at final destination...\n";
    
    $finalStop = end($stops);
    BusCurrentPosition::create([
        'bus_id' => 'B1',
        'latitude' => $finalStop['latitude'],
        'longitude' => $finalStop['longitude'],
        'confidence_level' => 0.9,
        'last_updated' => now(),
        'active_trackers' => 1,
        'trusted_trackers' => 1,
        'average_trust_score' => 0.8,
        'status' => 'active'
    ]);
    
    echo "Created current position at final destination: {$finalStop['stop_name']}\n";
    
    // Test 5: Test trip completion detection
    echo "\n5. Testing trip completion detection...\n";
    
    $completionResult = $completionService->checkTripCompletion($schedule);
    
    echo "Trip completion check results:\n";
    echo "  Bus ID: {$completionResult['bus_id']}\n";
    echo "  Completed: " . ($completionResult['completed'] ? 'Yes' : 'No') . "\n";
    echo "  Trip Direction: {$completionResult['trip_direction']}\n";
    
    if ($completionResult['completed']) {
        echo "  Completion Reason: {$completionResult['completion_reason']}\n";
        echo "  Completion Time: {$completionResult['completion_time']}\n";
        echo "  Final Destination: {$completionResult['final_destination']}\n";
    }
    
    // Test 6: Test full completion detection
    echo "\n6. Running full completion detection...\n";
    
    $detectionResults = $completionService->detectCompletedTrips();
    
    echo "Detection results:\n";
    echo "  Completed trips: " . count($detectionResults['completed_trips']) . "\n";
    echo "  Stopped sessions: {$detectionResults['stopped_sessions']}\n";
    echo "  Archived data: {$detectionResults['archived_data']}\n";
    echo "  Errors: " . count($detectionResults['errors']) . "\n";
    
    if (!empty($detectionResults['completed_trips'])) {
        foreach ($detectionResults['completed_trips'] as $trip) {
            echo "\n  Completed Trip Details:\n";
            echo "    Bus: {$trip['bus_id']}\n";
            echo "    Direction: {$trip['trip_direction']}\n";
            echo "    Reason: {$trip['completion_reason']}\n";
            echo "    Time: {$trip['completion_time']}\n";
        }
    }
    
    if (!empty($detectionResults['errors'])) {
        echo "\n  Errors:\n";
        foreach ($detectionResults['errors'] as $error) {
            echo "    - $error\n";
        }
    }
    
    // Test 7: Test GPS data collection stopping
    echo "\n7. Testing GPS data collection stopping...\n";
    
    $stoppedSessions = $completionService->stopGPSDataCollection('B1');
    echo "Stopped {$stoppedSessions} tracking sessions\n";
    
    // Verify session was stopped
    $activeSession = UserTrackingSession::where('bus_id', 'B1')
        ->where('is_active', true)
        ->first();
    
    echo "Active sessions remaining: " . ($activeSession ? '1' : '0') . "\n";
    
    // Test 8: Test trip transition handling
    echo "\n8. Testing trip transition handling...\n";
    
    $transitionResult = $completionService->handleTripTransition('B1');
    
    if ($transitionResult['success']) {
        echo "Trip transition successful\n";
        echo "Cleaned locations: {$transitionResult['cleaned_locations']}\n";
    } else {
        echo "Trip transition failed: {$transitionResult['error']}\n";
    }
    
    // Test 9: Verify cleanup
    echo "\n9. Verifying cleanup after transition...\n";
    
    $remainingPositions = BusCurrentPosition::where('bus_id', 'B1')->count();
    $remainingActiveSessions = UserTrackingSession::where('bus_id', 'B1')
        ->where('is_active', true)
        ->count();
    
    echo "Remaining current positions: {$remainingPositions}\n";
    echo "Remaining active sessions: {$remainingActiveSessions}\n";
    
    // Test 10: Test time-based completion
    echo "\n10. Testing time-based completion detection...\n";
    
    // Create a schedule that should have ended
    $expiredSchedule = BusSchedule::create([
        'bus_id' => 'B2',
        'route_name' => 'Test Expired Route',
        'departure_time' => now()->subHours(3)->format('H:i'),
        'return_time' => now()->subHours(1)->format('H:i'),
        'days_of_week' => [strtolower(now()->format('l'))],
        'is_active' => true
    ]);
    
    $timeBasedResult = $completionService->checkTripCompletion($expiredSchedule);
    
    echo "Time-based completion check:\n";
    echo "  Bus ID: {$timeBasedResult['bus_id']}\n";
    echo "  Completed: " . ($timeBasedResult['completed'] ? 'Yes' : 'No') . "\n";
    echo "  Reason: {$timeBasedResult['completion_reason']}\n";
    
    echo "\n=== Trip Completion Detection Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}