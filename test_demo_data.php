<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusLocation;
use App\Models\BusTimelineProgression;
use App\Services\BusScheduleService;
use App\Services\RouteTimelineService;

echo "🚌 BUBT Bus Tracker - Demo Data Verification\n";
echo "=============================================\n\n";

// Test 1: Check data counts
echo "📊 Data Summary:\n";
echo "   • Bus Schedules: " . BusSchedule::count() . "\n";
echo "   • Bus Routes: " . BusRoute::count() . "\n";
echo "   • Bus Locations: " . BusLocation::count() . "\n";
echo "   • Timeline Records: " . BusTimelineProgression::count() . "\n\n";

// Test 2: Show active buses
echo "🚍 Active Bus Schedules:\n";
$activeSchedules = BusSchedule::where('is_active', true)->get();
foreach ($activeSchedules as $schedule) {
    $routeCount = $schedule->routes()->count();
    echo "   • {$schedule->bus_id}: {$schedule->route_name}\n";
    echo "     Schedule: {$schedule->departure_time} - {$schedule->return_time}\n";
    echo "     Routes: {$routeCount} stops\n";
    echo "     Days: " . implode(', ', $schedule->days_of_week) . "\n\n";
}

// Test 3: Show recent locations
echo "📍 Recent Bus Locations (last 5):\n";
$recentLocations = BusLocation::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($recentLocations as $location) {
    echo "   • Bus {$location->bus_id}: ({$location->latitude}, {$location->longitude})\n";
    echo "     Accuracy: {$location->accuracy}m, Speed: {$location->speed}km/h\n";
    echo "     Time: {$location->created_at}\n\n";
}

// Test 4: Test BusScheduleService
echo "🔧 Testing BusScheduleService:\n";
try {
    $scheduleService = app(BusScheduleService::class);
    
    // Test active bus detection
    $busStatus = $scheduleService->isBusActive('B1');
    echo "   • Bus B1 Status: " . ($busStatus['is_active'] ? 'Active' : 'Inactive') . "\n";
    echo "     Reason: {$busStatus['reason']}\n";
    
    if ($busStatus['is_active']) {
        $tripDirection = $scheduleService->getCurrentTripDirection('B1');
        echo "     Direction: {$tripDirection['direction']}\n";
        echo "     Route Stops: " . count($tripDirection['route_stops']) . "\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error testing BusScheduleService: " . $e->getMessage() . "\n\n";
}

// Test 5: Show timeline progression
echo "⏱️ Timeline Progression:\n";
$progressions = BusTimelineProgression::with(['route', 'schedule'])
    ->where('is_active_trip', true)
    ->orderBy('bus_id')
    ->get();

$currentBus = null;
foreach ($progressions as $progression) {
    if ($currentBus !== $progression->bus_id) {
        $currentBus = $progression->bus_id;
        echo "   🚌 Bus {$progression->bus_id} ({$progression->trip_direction} trip):\n";
    }
    
    $statusIcon = match($progression->status) {
        'completed' => '✅',
        'current' => '🔄',
        'upcoming' => '⏳',
        default => '❓'
    };
    
    echo "     {$statusIcon} {$progression->route->stop_name} ({$progression->status})";
    if ($progression->progress_percentage > 0) {
        echo " - {$progression->progress_percentage}%";
    }
    if ($progression->eta_minutes) {
        echo " - ETA: {$progression->eta_minutes}min";
    }
    echo "\n";
}

echo "\n✅ Demo data verification complete!\n";
echo "🌐 You can now visit http://localhost:8000 to see the bus tracker in action.\n";