<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Broadcasting\BusLocationBroadcaster;
use App\Events\BusLocationUpdated;
use App\Events\BusTrackingStatusChanged;
use App\Services\WebSocketConnectionManager;
use Illuminate\Support\Facades\Log;

echo "Testing Laravel Reverb WebSocket Setup...\n\n";

try {
    // Test 1: Check if broadcasting is configured
    echo "1. Testing broadcasting configuration...\n";
    $broadcastConnection = config('broadcasting.default');
    echo "   ✓ Broadcast connection: {$broadcastConnection}\n";
    
    if ($broadcastConnection !== 'reverb') {
        echo "   ⚠ Warning: Broadcasting is not set to 'reverb'\n";
    }

    // Test 2: Check Reverb configuration
    echo "\n2. Testing Reverb configuration...\n";
    $reverbConfig = config('broadcasting.connections.reverb');
    echo "   ✓ Reverb host: " . ($reverbConfig['options']['host'] ?? 'not set') . "\n";
    echo "   ✓ Reverb port: " . ($reverbConfig['options']['port'] ?? 'not set') . "\n";
    echo "   ✓ Reverb scheme: " . ($reverbConfig['options']['scheme'] ?? 'not set') . "\n";

    // Test 3: Test WebSocket Connection Manager
    echo "\n3. Testing WebSocket Connection Manager...\n";
    $connectionManager = new WebSocketConnectionManager();
    
    // Register a test connection
    $testConnectionId = 'test_connection_' . uniqid();
    $registered = $connectionManager->registerConnection($testConnectionId, 'B1', [
        'user_agent' => 'Test Client',
        'ip_address' => '127.0.0.1'
    ]);
    
    if ($registered) {
        echo "   ✓ Connection registered successfully\n";
        
        // Test heartbeat
        $heartbeatUpdated = $connectionManager->updateHeartbeat($testConnectionId);
        echo "   ✓ Heartbeat updated: " . ($heartbeatUpdated ? 'Yes' : 'No') . "\n";
        
        // Test connection count
        $connectionCount = $connectionManager->getConnectionCount('B1');
        echo "   ✓ Connection count for B1: {$connectionCount}\n";
        
        // Test statistics
        $stats = $connectionManager->getConnectionStatistics();
        echo "   ✓ Total connections: " . ($stats['current_connections'] ?? 0) . "\n";
        
        // Cleanup test connection
        $unregistered = $connectionManager->unregisterConnection($testConnectionId);
        echo "   ✓ Connection unregistered: " . ($unregistered ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ✗ Failed to register connection\n";
    }

    // Test 4: Test Broadcasting Events
    echo "\n4. Testing broadcasting events...\n";
    
    // Test BusLocationUpdated event
    $locationData = [
        'latitude' => 23.7500,
        'longitude' => 90.3667,
        'accuracy' => 10.5,
        'speed' => 25.0,
        'heading' => 180.0,
        'trust_score' => 0.8
    ];
    
    echo "   ✓ Creating BusLocationUpdated event...\n";
    $locationEvent = new BusLocationUpdated('B1', $locationData, 3);
    echo "   ✓ Event created for bus: {$locationEvent->busId}\n";
    echo "   ✓ Active trackers: {$locationEvent->activeTrackers}\n";
    
    // Test BusTrackingStatusChanged event
    echo "   ✓ Creating BusTrackingStatusChanged event...\n";
    $statusEvent = new BusTrackingStatusChanged('B1', 'active', 3, [
        'last_location' => $locationData,
        'confidence' => 0.9
    ]);
    echo "   ✓ Status event created for bus: {$statusEvent->busId}\n";
    echo "   ✓ Status: {$statusEvent->status}\n";

    // Test 5: Test BusLocationBroadcaster
    echo "\n5. Testing BusLocationBroadcaster...\n";
    
    // Test validation
    $validData = [
        'latitude' => 23.7500,
        'longitude' => 90.3667,
        'accuracy' => 10.5,
        'trust_score' => 0.8
    ];
    
    echo "   ✓ Testing location data validation...\n";
    // Note: We can't directly test the private method, but we can test the broadcast method
    
    try {
        BusLocationBroadcaster::broadcast('B1', $validData, 2);
        echo "   ✓ Broadcast method executed successfully\n";
    } catch (Exception $e) {
        echo "   ⚠ Broadcast method failed (expected without Reverb server): " . $e->getMessage() . "\n";
    }

    // Test 6: Check required JavaScript packages
    echo "\n6. Checking JavaScript dependencies...\n";
    
    $packageJson = json_decode(file_get_contents('package.json'), true);
    $dependencies = array_merge(
        $packageJson['dependencies'] ?? [],
        $packageJson['devDependencies'] ?? []
    );
    
    $requiredPackages = ['laravel-echo', 'pusher-js'];
    foreach ($requiredPackages as $package) {
        if (isset($dependencies[$package])) {
            echo "   ✓ {$package}: " . $dependencies[$package] . "\n";
        } else {
            echo "   ✗ {$package}: Not installed\n";
        }
    }

    // Test 7: Check if WebSocket client file exists
    echo "\n7. Checking WebSocket client files...\n";
    
    $clientFile = 'resources/js/websocket-client.js';
    if (file_exists($clientFile)) {
        echo "   ✓ WebSocket client file exists\n";
        $fileSize = filesize($clientFile);
        echo "   ✓ File size: " . number_format($fileSize) . " bytes\n";
    } else {
        echo "   ✗ WebSocket client file missing\n";
    }

    // Test 8: Check Vite configuration
    echo "\n8. Checking Vite configuration...\n";
    
    $viteConfig = file_get_contents('vite.config.js');
    if (strpos($viteConfig, 'websocket-client.js') !== false) {
        echo "   ✓ WebSocket client included in Vite config\n";
    } else {
        echo "   ✗ WebSocket client not found in Vite config\n";
    }

    echo "\n✅ WebSocket setup test completed!\n";
    echo "\nNext steps:\n";
    echo "1. Start the Reverb server: php artisan reverb:start\n";
    echo "2. Build assets: npm run build\n";
    echo "3. Test WebSocket connections in browser\n";
    echo "4. Monitor connections with: php artisan tinker\n";
    echo "   > app(App\\Services\\WebSocketConnectionManager::class)->getConnectionStatistics()\n";

} catch (Exception $e) {
    echo "\n❌ Error during WebSocket setup test:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}