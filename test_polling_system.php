<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\Api\PollingController;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\DeviceToken;
use App\Services\LocationService;
use App\Services\BusTrackingReliabilityService;
use Illuminate\Http\Request;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing AJAX Polling Fallback System\n";
echo "====================================\n\n";

// Test 1: Health Check
echo "1. Testing Health Check Endpoint\n";
$controller = new PollingController(
    app(LocationService::class),
    app(BusTrackingReliabilityService::class)
);

$healthResponse = $controller->healthCheck();
$healthData = json_decode($healthResponse->getContent(), true);

if ($healthData['success'] && $healthData['status'] === 'healthy') {
    echo "✓ Health check passed\n";
} else {
    echo "✗ Health check failed\n";
}

// Test 2: Get Bus Locations (empty)
echo "\n2. Testing Get Bus Locations (should be empty)\n";
$request = new Request();
$locationsResponse = $controller->getBusLocations($request);
$locationsData = json_decode($locationsResponse->getContent(), true);

if ($locationsData['success'] && is_array($locationsData['locations'])) {
    echo "✓ Get bus locations endpoint working (found " . count($locationsData['locations']) . " locations)\n";
} else {
    echo "✗ Get bus locations endpoint failed\n";
}

// Test 3: Get Tracking Status
echo "\n3. Testing Get Tracking Status\n";
$request = new Request(['bus_id' => 'B1']);
$statusResponse = $controller->getTrackingStatus($request);
$statusData = json_decode($statusResponse->getContent(), true);

if ($statusData['success']) {
    echo "✓ Get tracking status endpoint working\n";
    if (isset($statusData['data']['bus_tracking'])) {
        $busTracking = $statusData['data']['bus_tracking'];
        echo "  - Bus ID: " . $busTracking['bus_id'] . "\n";
        echo "  - Active trackers: " . $busTracking['active_trackers'] . "\n";
        echo "  - Status: " . $busTracking['status'] . "\n";
    }
} else {
    echo "✗ Get tracking status endpoint failed\n";
}

// Test 4: Submit Location (should fail without active schedule)
echo "\n4. Testing Submit Location (should fail - no active schedule)\n";
$request = new Request();
$request->merge([
    'bus_id' => 'B1',
    'device_token' => 'test-token-123',
    'latitude' => 23.7937,
    'longitude' => 90.3629,
    'accuracy' => 10,
    'speed' => 25
]);

$submitResponse = $controller->submitLocation($request);
$submitData = json_decode($submitResponse->getContent(), true);

if (!$submitData['success'] && strpos($submitData['message'], 'not currently scheduled') !== false) {
    echo "✓ Submit location correctly rejected (no active schedule)\n";
} else {
    echo "✗ Submit location validation failed\n";
}

// Test 5: Connection Manager JavaScript Test
echo "\n5. Testing Connection Manager JavaScript Structure\n";
$connectionManagerPath = 'resources/js/connection-manager.js';
if (file_exists($connectionManagerPath)) {
    $jsContent = file_get_contents($connectionManagerPath);
    
    $requiredMethods = [
        'connectWebSocket',
        'startPolling',
        'poll',
        'submitLocation',
        'subscribe',
        'unsubscribe',
        'healthCheck'
    ];
    
    $allMethodsFound = true;
    foreach ($requiredMethods as $method) {
        if (strpos($jsContent, $method) === false) {
            echo "✗ Missing method: $method\n";
            $allMethodsFound = false;
        }
    }
    
    if ($allMethodsFound) {
        echo "✓ Connection Manager JavaScript has all required methods\n";
    }
} else {
    echo "✗ Connection Manager JavaScript file not found\n";
}

// Test 6: Connection Status Component
echo "\n6. Testing Connection Status Component\n";
$connectionStatusPath = 'app/Livewire/ConnectionStatus.php';
if (file_exists($connectionStatusPath)) {
    echo "✓ Connection Status Livewire component exists\n";
} else {
    echo "✗ Connection Status Livewire component not found\n";
}

$connectionStatusViewPath = 'resources/views/livewire/connection-status.blade.php';
if (file_exists($connectionStatusViewPath)) {
    echo "✓ Connection Status view exists\n";
} else {
    echo "✗ Connection Status view not found\n";
}

// Test 7: Routes Registration
echo "\n7. Testing API Routes Registration\n";
$routesPath = 'routes/api.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    
    $requiredRoutes = [
        '/health',
        '/locations',
        '/location',
        '/tracking-status'
    ];
    
    $allRoutesFound = true;
    foreach ($requiredRoutes as $route) {
        if (strpos($routesContent, $route) === false) {
            echo "✗ Missing route: $route\n";
            $allRoutesFound = false;
        }
    }
    
    if ($allRoutesFound) {
        echo "✓ All polling routes are registered\n";
    }
} else {
    echo "✗ API routes file not found\n";
}

echo "\n====================================\n";
echo "AJAX Polling Fallback System Test Complete\n";
echo "====================================\n";