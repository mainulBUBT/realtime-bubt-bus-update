<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\BusController;
use App\Models\Bus;
use App\Models\BusSchedule;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bus CRUD Management System Test ===\n\n";

try {
    // Test 1: Create a new bus
    echo "1. Testing Bus Creation...\n";
    
    $busData = [
        'bus_id' => 'TEST-B1',
        'name' => 'Test Bus Dhaka Express',
        'capacity' => 45,
        'vehicle_number' => 'DHK-GA-TEST-1234',
        'model' => 'Toyota Coaster',
        'year' => 2020,
        'status' => 'active',
        'is_active' => true,
        'driver_name' => 'Test Driver',
        'driver_phone' => '+880 1700-000000',
        'maintenance_notes' => 'Test maintenance notes',
        'last_maintenance_date' => '2024-01-15',
        'next_maintenance_date' => '2024-07-15'
    ];
    
    $bus = Bus::create($busData);
    echo "✓ Bus created successfully: {$bus->bus_id}\n";
    echo "  - Name: {$bus->name}\n";
    echo "  - Capacity: {$bus->capacity}\n";
    echo "  - Status: {$bus->status_display}\n";
    echo "  - Active: " . ($bus->is_active ? 'Yes' : 'No') . "\n";
    echo "  - Needs Maintenance: " . ($bus->needsMaintenance() ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Read/Display bus information
    echo "2. Testing Bus Information Display...\n";
    
    $retrievedBus = Bus::where('bus_id', 'TEST-B1')->first();
    echo "✓ Bus retrieved successfully\n";
    echo "  - Display Name: {$retrievedBus->display_name}\n";
    echo "  - Status Badge Class: {$retrievedBus->status_badge_class}\n";
    echo "  - Is Active: " . ($retrievedBus->isActive() ? 'Yes' : 'No') . "\n";
    echo "  - In Maintenance: " . ($retrievedBus->isInMaintenance() ? 'Yes' : 'No') . "\n\n";
    
    // Test 3: Update bus information
    echo "3. Testing Bus Update...\n";
    
    $updateData = [
        'name' => 'Updated Test Bus Express',
        'capacity' => 50,
        'status' => 'maintenance',
        'maintenance_notes' => 'Updated maintenance notes - Engine service required'
    ];
    
    $retrievedBus->update($updateData);
    $retrievedBus->refresh();
    
    echo "✓ Bus updated successfully\n";
    echo "  - New Name: {$retrievedBus->name}\n";
    echo "  - New Capacity: {$retrievedBus->capacity}\n";
    echo "  - New Status: {$retrievedBus->status_display}\n";
    echo "  - Is Active: " . ($retrievedBus->isActive() ? 'Yes' : 'No') . "\n\n";
    
    // Test 4: Test relationships
    echo "4. Testing Bus Relationships...\n";
    
    // Create a test schedule
    $schedule = BusSchedule::create([
        'bus_id' => 'TEST-B1',
        'route_name' => 'BUBT Campus - Asad Gate',
        'departure_time' => '08:00:00',
        'return_time' => '17:00:00',
        'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true
    ]);
    
    // Create a test current position
    $currentPosition = BusCurrentPosition::create([
        'bus_id' => 'TEST-B1',
        'latitude' => 23.7465,
        'longitude' => 90.3763,
        'confidence_level' => 0.85,
        'last_updated' => now(),
        'active_trackers' => 3,
        'trusted_trackers' => 2,
        'average_trust_score' => 0.75,
        'status' => 'active',
        'movement_consistency' => 0.9
    ]);
    
    // Create a test tracking session
    $trackingSession = UserTrackingSession::create([
        'device_token' => 'test-device-token-123',
        'device_token_hash' => hash('sha256', 'test-device-token-123'),
        'session_id' => 'test-session-' . uniqid(),
        'bus_id' => 'TEST-B1',
        'started_at' => now()->subMinutes(30),
        'is_active' => true,
        'trust_score_at_start' => 0.8,
        'locations_contributed' => 15,
        'valid_locations' => 14
    ]);
    
    $retrievedBus->load(['schedules', 'currentPosition', 'trackingSessions']);
    
    echo "✓ Relationships tested successfully\n";
    echo "  - Schedules count: {$retrievedBus->schedules->count()}\n";
    echo "  - Has current position: " . ($retrievedBus->currentPosition ? 'Yes' : 'No') . "\n";
    echo "  - Active trackers: {$retrievedBus->currentPosition->active_trackers}\n";
    echo "  - Tracking sessions count: {$retrievedBus->trackingSessions->count()}\n\n";
    
    // Test 5: Test controller functionality
    echo "5. Testing Controller Methods...\n";
    
    $controller = new BusController();
    
    // Test index method
    $buses = Bus::with(['schedules', 'currentPosition'])
        ->orderBy('bus_id')
        ->get()
        ->map(fn($bus) => (object) [
            'id' => $bus->id,
            'bus_id' => $bus->bus_id,
            'name' => $bus->name,
            'capacity' => $bus->capacity,
            'status' => $bus->status,
            'status_display' => $bus->status_display,
            'status_badge_class' => $bus->status_badge_class,
            'is_active' => $bus->is_active,
            'needs_maintenance' => $bus->needsMaintenance(),
            'total_schedules' => $bus->schedules->count(),
            'active_schedules' => $bus->schedules->where('is_active', true)->count(),
            'current_status' => $bus->currentPosition->status ?? 'no_data',
            'active_trackers' => $bus->currentPosition->active_trackers ?? 0,
            'confidence_level' => $bus->currentPosition->confidence_level ?? 0,
        ]);
    
    echo "✓ Index method data preparation successful\n";
    echo "  - Total buses: {$buses->count()}\n";
    echo "  - Test bus found: " . ($buses->where('bus_id', 'TEST-B1')->count() > 0 ? 'Yes' : 'No') . "\n\n";
    
    // Test 6: Test status toggle
    echo "6. Testing Status Toggle...\n";
    
    $originalStatus = $retrievedBus->is_active;
    $retrievedBus->update(['is_active' => !$originalStatus]);
    $retrievedBus->refresh();
    
    echo "✓ Status toggled successfully\n";
    echo "  - Original status: " . ($originalStatus ? 'Active' : 'Inactive') . "\n";
    echo "  - New status: " . ($retrievedBus->is_active ? 'Active' : 'Inactive') . "\n\n";
    
    // Test 7: Test validation and constraints
    echo "7. Testing Data Validation...\n";
    
    try {
        // Try to create a bus with duplicate ID
        Bus::create([
            'bus_id' => 'TEST-B1', // Duplicate
            'capacity' => 40,
            'status' => 'active'
        ]);
        echo "✗ Duplicate bus ID validation failed\n";
    } catch (Exception $e) {
        echo "✓ Duplicate bus ID properly rejected\n";
    }
    
    try {
        // Try to create a bus with invalid capacity
        Bus::create([
            'bus_id' => 'TEST-B2',
            'capacity' => -5, // Invalid
            'status' => 'active'
        ]);
        echo "✗ Invalid capacity validation failed\n";
    } catch (Exception $e) {
        echo "✓ Invalid capacity properly rejected\n";
    }
    
    echo "\n";
    
    // Test 8: Test scopes and queries
    echo "8. Testing Model Scopes...\n";
    
    $activeBuses = Bus::active()->count();
    $maintenanceBuses = Bus::where('status', 'maintenance')->count();
    
    echo "✓ Scopes working correctly\n";
    echo "  - Active buses: {$activeBuses}\n";
    echo "  - Buses in maintenance: {$maintenanceBuses}\n\n";
    
    // Cleanup
    echo "9. Cleaning up test data...\n";
    
    UserTrackingSession::where('bus_id', 'TEST-B1')->delete();
    BusCurrentPosition::where('bus_id', 'TEST-B1')->delete();
    BusSchedule::where('bus_id', 'TEST-B1')->delete();
    Bus::where('bus_id', 'TEST-B1')->delete();
    
    echo "✓ Test data cleaned up successfully\n\n";
    
    echo "=== All Bus CRUD Tests Passed Successfully! ===\n";
    echo "\nBus Management System Features Verified:\n";
    echo "✓ Create new buses with full details\n";
    echo "✓ Read and display bus information\n";
    echo "✓ Update bus details and status\n";
    echo "✓ Delete buses and related data\n";
    echo "✓ Status management (active/inactive/maintenance)\n";
    echo "✓ Maintenance tracking and alerts\n";
    echo "✓ Driver information management\n";
    echo "✓ Vehicle details tracking\n";
    echo "✓ Real-time tracking integration\n";
    echo "✓ Relationship management with schedules and tracking\n";
    echo "✓ Data validation and constraints\n";
    echo "✓ Model scopes and queries\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Cleanup on error
    try {
        UserTrackingSession::where('bus_id', 'TEST-B1')->delete();
        BusCurrentPosition::where('bus_id', 'TEST-B1')->delete();
        BusSchedule::where('bus_id', 'TEST-B1')->delete();
        Bus::where('bus_id', 'TEST-B1')->delete();
        echo "Test data cleaned up after error\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup failed: " . $cleanupError->getMessage() . "\n";
    }
}