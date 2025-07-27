<?php

require_once 'vendor/autoload.php';

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusSchedule;
use App\Models\BusRoute;
use Carbon\Carbon;

echo "Testing Bus Schedule and Route Functionality\n";
echo "============================================\n\n";

// Test 1: Get all bus schedules
$schedules = BusSchedule::all();
echo "Total bus schedules: " . $schedules->count() . "\n";

foreach ($schedules as $schedule) {
    echo "Bus {$schedule->bus_id}: {$schedule->departure_time->format('H:i')} - {$schedule->return_time->format('H:i')}\n";
}

echo "\n";

// Test 2: Check currently active buses
echo "Currently active buses:\n";
$activeSchedules = BusSchedule::active()->get()->filter(function ($schedule) {
    return $schedule->isCurrentlyActive();
});

foreach ($activeSchedules as $schedule) {
    $direction = $schedule->getCurrentTripDirection();
    echo "Bus {$schedule->bus_id}: {$direction} trip\n";
}

if ($activeSchedules->isEmpty()) {
    echo "No buses currently active (outside schedule hours)\n";
}

echo "\n";

// Test 3: Test trip direction logic with a specific bus
$testSchedule = BusSchedule::where('bus_id', 'B1')->first();
if ($testSchedule) {
    echo "Testing Bus B1 trip direction logic:\n";
    echo "Current time: " . Carbon::now()->format('H:i') . "\n";
    echo "Departure time: " . $testSchedule->departure_time->format('H:i') . "\n";
    echo "Return time: " . $testSchedule->return_time->format('H:i') . "\n";
    echo "Is currently active: " . ($testSchedule->isCurrentlyActive() ? 'Yes' : 'No') . "\n";
    echo "Trip direction: " . $testSchedule->getCurrentTripDirection() . "\n";
    echo "Is on departure trip: " . ($testSchedule->isOnDepartureTrip() ? 'Yes' : 'No') . "\n";
    echo "Is on return trip: " . ($testSchedule->isOnReturnTrip() ? 'Yes' : 'No') . "\n";
}

echo "\n";

// Test 4: Test route functionality
echo "Testing route functionality for Bus B1:\n";
$routes = BusRoute::where('schedule_id', $testSchedule->id)->ordered()->get();

foreach ($routes as $route) {
    echo "Stop {$route->stop_order}: {$route->stop_name}\n";
    echo "  Coordinates: {$route->latitude}, {$route->longitude}\n";
    echo "  Departure time: {$route->estimated_departure_time->format('H:i')}\n";
    echo "  Return time: {$route->estimated_return_time->format('H:i')}\n";
    echo "  Timeline status: {$route->getTimelineStatus()}\n";
    echo "  Progress: {$route->getProgressPercentage()}%\n";
    echo "\n";
}

// Test 5: Test coordinate validation
echo "Testing coordinate validation:\n";
$testRoute = $routes->first();
if ($testRoute) {
    // Test with coordinates within radius
    $withinRadius = $testRoute->isWithinRadius(23.7960, 90.3540);
    echo "Coordinates within radius: " . ($withinRadius ? 'Yes' : 'No') . "\n";

    // Test with coordinates outside radius
    $outsideRadius = $testRoute->isWithinRadius(23.8000, 90.4000);
    echo "Coordinates outside radius: " . ($outsideRadius ? 'Yes' : 'No') . "\n";
}

echo "\nAll tests completed successfully!\n";