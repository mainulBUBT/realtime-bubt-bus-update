<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bus Management System Test ===\n\n";

// Test 1: Check if buses exist
echo "1. Testing buses in database:\n";
try {
    $buses = \App\Models\Bus::all();
    echo "   Found " . $buses->count() . " buses:\n";
    foreach ($buses as $bus) {
        echo "   - {$bus->bus_id} ({$bus->name}) - Status: {$bus->status} - Capacity: {$bus->capacity} - Active: " . ($bus->is_active ? 'Yes' : 'No') . "\n";
        if ($bus->needsMaintenance()) {
            echo "     ⚠️  Maintenance overdue!\n";
        }
        if ($bus->driver_name) {
            echo "     Driver: {$bus->driver_name} ({$bus->driver_phone})\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test bus relationships
echo "2. Testing bus relationships:\n";
try {
    $bus = \App\Models\Bus::where('bus_id', 'B1')->first();
    if ($bus) {
        echo "   Bus B1 relationships:\n";
        echo "   - Schedules: " . $bus->schedules()->count() . "\n";
        echo "   - Active schedules: " . $bus->activeSchedules()->count() . "\n";
        echo "   - Tracking sessions: " . $bus->trackingSessions()->count() . "\n";
        echo "   - Current position: " . ($bus->currentPosition ? 'Yes' : 'No') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test bus status methods
echo "3. Testing bus status methods:\n";
try {
    $buses = \App\Models\Bus::all();
    foreach ($buses as $bus) {
        echo "   Bus {$bus->bus_id}:\n";
        echo "     - Is Active: " . ($bus->isActive() ? 'Yes' : 'No') . "\n";
        echo "     - In Maintenance: " . ($bus->isInMaintenance() ? 'Yes' : 'No') . "\n";
        echo "     - Needs Maintenance: " . ($bus->needsMaintenance() ? 'Yes' : 'No') . "\n";
        echo "     - Status Badge Class: {$bus->status_badge_class}\n";
        echo "     - Display Name: {$bus->display_name}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test bus scopes
echo "4. Testing bus scopes:\n";
try {
    $activeBuses = \App\Models\Bus::active()->count();
    $maintenanceBuses = \App\Models\Bus::needingMaintenance()->count();
    
    echo "   - Active buses: {$activeBuses}\n";
    echo "   - Buses needing maintenance: {$maintenanceBuses}\n";
    
    if ($maintenanceBuses > 0) {
        echo "   Buses needing maintenance:\n";
        $needsMaintenance = \App\Models\Bus::needingMaintenance()->get();
        foreach ($needsMaintenance as $bus) {
            echo "     - {$bus->bus_id} ({$bus->name}) - Due: {$bus->next_maintenance_date->format('Y-m-d')}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";