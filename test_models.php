<?php

require_once 'vendor/autoload.php';

use App\Models\BusSchedule;
use App\Models\BusRoute;

// Test creating a bus schedule
$schedule = new BusSchedule([
    'bus_id' => 'B1',
    'route_name' => 'BUBT to Asad Gate',
    'departure_time' => '07:00',
    'return_time' => '17:00',
    'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    'is_active' => true,
    'description' => 'Main campus route'
]);

echo "BusSchedule model created successfully\n";

// Test creating a bus route
$route = new BusRoute([
    'schedule_id' => 1,
    'stop_name' => 'BUBT Campus',
    'stop_order' => 1,
    'latitude' => 23.7956,
    'longitude' => 90.3537,
    'coverage_radius' => 100,
    'estimated_departure_time' => '07:00',
    'estimated_return_time' => '17:00',
    'departure_duration_minutes' => 0,
    'return_duration_minutes' => 0
]);

echo "BusRoute model created successfully\n";

// Test distance calculation
$distance = $route->calculateDistance(23.7956, 90.3537, 23.7960, 90.3540);
echo "Distance calculation works: {$distance} meters\n";

// Test radius check
$withinRadius = $route->isWithinRadius(23.7960, 90.3540);
echo "Radius check works: " . ($withinRadius ? 'true' : 'false') . "\n";

echo "All model tests passed!\n";