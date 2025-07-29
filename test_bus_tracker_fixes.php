<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bus Tracker Fixes Test ===\n\n";

// Test 1: Check if user_tracking_sessions table has required columns
echo "1. Testing user_tracking_sessions table structure:\n";
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('user_tracking_sessions');
    echo "   Table columns: " . implode(', ', $columns) . "\n";
    
    $requiredColumns = ['device_token', 'device_token_hash', 'bus_id', 'session_id'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "   ✓ All required columns present\n";
    } else {
        echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check bus positions
echo "2. Testing bus positions:\n";
try {
    $positions = \App\Models\BusCurrentPosition::all();
    echo "   Found " . $positions->count() . " bus positions:\n";
    
    foreach ($positions as $position) {
        echo "   - Bus {$position->bus_id}: {$position->latitude}, {$position->longitude} (Status: {$position->status})\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test creating a tracking session
echo "3. Testing tracking session creation:\n";
try {
    $deviceToken = 'test_device_' . uniqid();
    $deviceTokenHash = hash('sha256', $deviceToken);
    
    $session = \App\Models\UserTrackingSession::create([
        'device_token' => $deviceToken,
        'device_token_hash' => $deviceTokenHash,
        'bus_id' => 'B1',
        'session_id' => uniqid('session_', true),
        'started_at' => now(),
        'is_active' => true,
        'trust_score_at_start' => 0.5
    ]);
    
    echo "   ✓ Successfully created tracking session with ID: {$session->id}\n";
    echo "   - Device Token: " . substr($deviceToken, 0, 12) . "...\n";
    echo "   - Bus ID: {$session->bus_id}\n";
    echo "   - Session ID: {$session->session_id}\n";
    
    // Clean up
    $session->delete();
    echo "   ✓ Test session cleaned up\n";
    
} catch (Exception $e) {
    echo "   ✗ Error creating tracking session: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test BusTracker component initialization
echo "4. Testing BusTracker component:\n";
try {
    // Check if we can instantiate the component
    $component = new \App\Livewire\BusTracker();
    echo "   ✓ BusTracker component can be instantiated\n";
    
    // Test mount method
    $component->mount('B1');
    echo "   ✓ Component mounted successfully for Bus B1\n";
    echo "   - Bus Name: {$component->busName}\n";
    echo "   - Trip Status: {$component->tripStatus}\n";
    echo "   - Current Location: " . ($component->currentLocation ? 'Available' : 'Not Available') . "\n";
    
    if ($component->currentLocation) {
        echo "     Lat: {$component->currentLocation['latitude']}, Lng: {$component->currentLocation['longitude']}\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error with BusTracker component: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check if bus schedules exist
echo "5. Testing bus schedules:\n";
try {
    $schedules = \App\Models\BusSchedule::with('routes')->get();
    echo "   Found " . $schedules->count() . " bus schedules:\n";
    
    foreach ($schedules->take(3) as $schedule) {
        echo "   - Bus {$schedule->bus_id}: {$schedule->route_name}\n";
        echo "     Routes: {$schedule->routes->count()} stops\n";
        echo "     Active: " . ($schedule->is_active ? 'Yes' : 'No') . "\n";
    }
    
    if ($schedules->count() > 3) {
        echo "   ... and " . ($schedules->count() - 3) . " more schedules\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";

// Summary
echo "\nSUMMARY:\n";
echo "- Database schema: Fixed user_tracking_sessions table\n";
echo "- Bus positions: Created sample data for map display\n";
echo "- Tracking sessions: Fixed device_token_hash requirement\n";
echo "- BusTracker component: Added proper location initialization\n";
echo "- Map display: Fixed JavaScript bus position initialization\n";

echo "\nNext steps:\n";
echo "1. Delete the duplicate migration file manually\n";
echo "2. Test the bus tracker page in browser\n";
echo "3. Verify map shows bus at correct location\n";
echo "4. Test 'I'm on this Bus' functionality\n";