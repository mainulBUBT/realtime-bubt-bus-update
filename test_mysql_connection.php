<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\BusTimelineProgression;
use Illuminate\Support\Facades\DB;

echo "Testing MySQL connection and tables...\n\n";

try {
    // Test database connection
    DB::connection()->getPdo();
    echo "✓ MySQL connection successful\n";
    
    // Check if tables exist
    $tables = [
        'bus_schedules',
        'bus_routes', 
        'bus_timeline_progression',
        'bus_locations'
    ];
    
    foreach ($tables as $table) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            echo "✓ Table '{$table}' exists\n";
        } else {
            echo "✗ Table '{$table}' missing\n";
        }
    }
    
    // Test creating a sample schedule
    echo "\nTesting data creation...\n";
    
    $schedule = BusSchedule::create([
        'bus_id' => 'TEST001',
        'route_name' => 'Test Route',
        'departure_time' => '07:00:00',
        'return_time' => '17:00:00',
        'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true
    ]);
    
    echo "✓ BusSchedule created with ID: {$schedule->id}\n";
    
    // Test creating a route
    $route = BusRoute::create([
        'schedule_id' => $schedule->id,
        'stop_name' => 'Test Stop',
        'stop_order' => 1,
        'latitude' => 23.7500,
        'longitude' => 90.3667,
        'coverage_radius' => 200,
        'estimated_departure_time' => '07:15:00',
        'estimated_return_time' => '16:45:00'
    ]);
    
    echo "✓ BusRoute created with ID: {$route->id}\n";
    
    // Test creating timeline progression
    $progression = BusTimelineProgression::create([
        'bus_id' => 'TEST001',
        'schedule_id' => $schedule->id,
        'route_id' => $route->id,
        'trip_direction' => 'departure',
        'status' => 'upcoming',
        'progress_percentage' => 0,
        'confidence_score' => 0.5,
        'is_active_trip' => true
    ]);
    
    echo "✓ BusTimelineProgression created with ID: {$progression->id}\n";
    
    // Clean up test data
    $progression->delete();
    $route->delete();
    $schedule->delete();
    
    echo "✓ Test data cleaned up\n";
    
    echo "\n🎉 All MySQL database operations successful!\n";
    echo "The bus schedule management system is ready to use with MySQL.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}