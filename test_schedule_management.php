<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Schedule Management System Test ===\n\n";

// Test 1: Check existing schedules
echo "1. Testing existing schedules:\n";
try {
    $schedules = \App\Models\BusSchedule::with(['routes', 'history'])->get();
    echo "   Found " . $schedules->count() . " schedules:\n";
    foreach ($schedules as $schedule) {
        echo "   - {$schedule->bus_id}: {$schedule->route_name}\n";
        echo "     Times: {$schedule->departure_time->format('H:i')} - {$schedule->return_time->format('H:i')}\n";
        echo "     Days: " . implode(', ', $schedule->days_of_week ?? []) . "\n";
        echo "     Routes: {$schedule->routes->count()} stops\n";
        echo "     History: {$schedule->history->count()} records\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test schedule templates
echo "2. Testing schedule templates:\n";
try {
    // Create a test template
    $template = \App\Models\ScheduleTemplate::create([
        'name' => 'Standard Morning Route',
        'description' => 'Standard morning departure schedule',
        'template_data' => [
            'route_name' => 'BUBT Campus - City Center',
            'departure_time' => '08:00',
            'return_time' => '16:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
            'description' => 'Standard weekday schedule'
        ],
        'is_active' => true,
        'created_by' => 'System Test'
    ]);
    
    echo "   Created template: {$template->name}\n";
    echo "   Template ID: {$template->id}\n";
    echo "   Usage count: {$template->usage_count}\n";
    
    $templates = \App\Models\ScheduleTemplate::active()->get();
    echo "   Total active templates: {$templates->count()}\n";
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test schedule history
echo "3. Testing schedule history:\n";
try {
    $schedule = \App\Models\BusSchedule::first();
    if ($schedule) {
        // Create a test history record
        $history = \App\Models\ScheduleHistory::createRecord(
            $schedule->id,
            'updated',
            ['route_name' => 'Old Route Name'],
            ['route_name' => 'New Route Name'],
            'System Test',
            'Test history record'
        );
        
        echo "   Created history record for schedule {$schedule->id}\n";
        echo "   Action: {$history->action_display}\n";
        echo "   Icon: {$history->action_icon}\n";
        echo "   Badge class: {$history->action_badge_class}\n";
        
        $changes = $history->getChangesSummary();
        echo "   Changes: " . json_encode($changes) . "\n";
        
        $totalHistory = \App\Models\ScheduleHistory::count();
        echo "   Total history records: {$totalHistory}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test conflict detection
echo "4. Testing schedule conflict detection:\n";
try {
    $schedule = \App\Models\BusSchedule::first();
    if ($schedule) {
        // Test for conflicts with same bus
        $conflicts = \App\Models\BusSchedule::where('bus_id', $schedule->bus_id)
            ->where('id', '!=', $schedule->id)
            ->where(function($q) use ($schedule) {
                $departureTime = $schedule->departure_time->format('H:i');
                $returnTime = $schedule->return_time->format('H:i');
                
                $q->whereBetween('departure_time', [$departureTime, $returnTime])
                  ->orWhereBetween('return_time', [$departureTime, $returnTime])
                  ->orWhere(function($subQ) use ($departureTime, $returnTime) {
                      $subQ->where('departure_time', '<=', $departureTime)
                           ->where('return_time', '>=', $returnTime);
                  });
            })->get();
            
        echo "   Checking conflicts for {$schedule->bus_id} ({$schedule->departure_time->format('H:i')} - {$schedule->return_time->format('H:i')})\n";
        echo "   Found {$conflicts->count()} potential conflicts\n";
        
        foreach ($conflicts as $conflict) {
            $overlappingDays = array_intersect($schedule->days_of_week ?? [], $conflict->days_of_week ?? []);
            if (!empty($overlappingDays)) {
                echo "   - Conflict with schedule {$conflict->id}: " . implode(', ', $overlappingDays) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Test route management
echo "5. Testing route management:\n";
try {
    $schedule = \App\Models\BusSchedule::first();
    if ($schedule) {
        $routes = $schedule->routes()->ordered()->get();
        echo "   Schedule {$schedule->id} has {$routes->count()} route stops:\n";
        
        foreach ($routes as $route) {
            echo "   - Stop {$route->stop_order}: {$route->stop_name}\n";
            echo "     Location: {$route->latitude}, {$route->longitude}\n";
            echo "     Coverage: {$route->coverage_radius}m\n";
            echo "     Timeline status: {$route->getTimelineStatus()}\n";
            
            // Test distance calculation
            $testLat = $route->latitude + 0.001; // ~100m away
            $testLng = $route->longitude + 0.001;
            $distance = $route->calculateDistance($testLat, $testLng, $route->latitude, $route->longitude);
            echo "     Distance to test point: " . round($distance) . "m\n";
            echo "     Within radius: " . ($route->isWithinRadius($testLat, $testLng) ? 'Yes' : 'No') . "\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";