<?php

require_once 'vendor/autoload.php';

use App\Services\SmartBroadcastingService;
use App\Services\LocationBatchProcessor;
use App\Models\BusLocation;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Smart Broadcasting System Test ===\n\n";

try {
    $smartBroadcasting = new SmartBroadcastingService();
    $batchProcessor = new LocationBatchProcessor();
    
    // Test 1: Create some test device tokens
    echo "1. Creating test device tokens...\n";
    
    $testTokens = [];
    for ($i = 1; $i <= 3; $i++) {
        $token = DeviceToken::create([
            'token_hash' => 'test_token_' . $i,
            'fingerprint_data' => ['test' => 'data_' . $i],
            'trust_score' => 0.8 + ($i * 0.05), // 0.85, 0.90, 0.95
            'is_trusted' => true,
            'total_contributions' => 10 * $i,
            'accurate_contributions' => 8 * $i,
            'last_activity' => now()
        ]);
        $testTokens[] = $token;
        echo "   Created token: {$token->token_hash} (trust: {$token->trust_score})\n";
    }
    
    // Test 2: Create test location data
    echo "\n2. Creating test location data for bus B1...\n";
    
    $busId = 'B1';
    $baseLatitude = 23.7808875; // Dhaka coordinates
    $baseLongitude = 90.2792371;
    
    $locationData = [];
    foreach ($testTokens as $index => $token) {
        // Create slightly different coordinates to simulate multiple users
        $lat = $baseLatitude + (($index - 1) * 0.0001); // Small variations
        $lng = $baseLongitude + (($index - 1) * 0.0001);
        
        $locationData[] = [
            'bus_id' => $busId,
            'device_token' => $token->token_hash,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => 10.0,
            'speed' => 15.0,
            'reputation_weight' => $token->trust_score,
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        echo "   Location {$index}: lat={$lat}, lng={$lng}, trust={$token->trust_score}\n";
    }
    
    // Test 3: Process batch location data
    echo "\n3. Processing location batch...\n";
    $batchResults = $batchProcessor->processBatch($locationData);
    echo "   Processed: {$batchResults['processed']}\n";
    echo "   Validated: {$batchResults['validated']}\n";
    echo "   Rejected: {$batchResults['rejected']}\n";
    if (!empty($batchResults['errors'])) {
        echo "   Errors: " . implode(', ', $batchResults['errors']) . "\n";
    }
    
    // Test 4: Create tracking sessions
    echo "\n4. Creating tracking sessions...\n";
    foreach ($testTokens as $token) {
        UserTrackingSession::create([
            'device_token' => $token->token_hash,
            'device_token_hash' => $token->token_hash,
            'bus_id' => $busId,
            'session_id' => 'session_' . $token->id,
            'started_at' => now()->subMinutes(5),
            'is_active' => true,
            'locations_contributed' => 5,
            'valid_locations' => 4
        ]);
        echo "   Created session for token: {$token->token_hash}\n";
    }
    
    // Test 5: Update bus positions using smart broadcasting
    echo "\n5. Running smart broadcasting update...\n";
    $smartBroadcasting->updateBusPositions();
    echo "   Smart broadcasting update completed\n";
    
    // Test 6: Check current bus positions
    echo "\n6. Checking current bus positions...\n";
    $currentPositions = $smartBroadcasting->getCurrentBusPositions();
    
    foreach ($currentPositions as $position) {
        echo "   Bus {$position['bus_id']}:\n";
        echo "     Status: {$position['status']}\n";
        echo "     Location: {$position['latitude']}, {$position['longitude']}\n";
        echo "     Confidence: {$position['confidence_level']}\n";
        echo "     Active trackers: {$position['active_trackers']}\n";
        echo "     Trusted trackers: {$position['trusted_trackers']}\n";
        echo "     Last updated: {$position['last_updated']}\n";
        echo "     Is reliable: " . ($position['is_reliable'] ? 'Yes' : 'No') . "\n";
    }
    
    // Test 7: Check database records
    echo "\n7. Checking database records...\n";
    
    $locationCount = BusLocation::where('bus_id', $busId)->count();
    echo "   Total locations for {$busId}: {$locationCount}\n";
    
    $currentPosition = BusCurrentPosition::find($busId);
    if ($currentPosition) {
        echo "   Current position record exists:\n";
        echo "     Status: {$currentPosition->status}\n";
        echo "     Active trackers: {$currentPosition->active_trackers}\n";
        echo "     Trusted trackers: {$currentPosition->trusted_trackers}\n";
        echo "     Confidence level: {$currentPosition->confidence_level}\n";
        echo "     Average trust score: {$currentPosition->average_trust_score}\n";
    } else {
        echo "   No current position record found\n";
    }
    
    // Test 8: Get system statistics
    echo "\n8. System statistics...\n";
    $stats = $smartBroadcasting->getStatistics();
    foreach ($stats as $key => $value) {
        echo "   {$key}: {$value}\n";
    }
    
    // Test 9: Test cleanup operations
    echo "\n9. Testing cleanup operations...\n";
    
    // Create some old data for cleanup testing
    BusLocation::create([
        'bus_id' => 'B2',
        'device_token' => 'old_token',
        'latitude' => 23.7808875,
        'longitude' => 90.2792371,
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2)
    ]);
    
    UserTrackingSession::create([
        'device_token' => 'old_token',
        'device_token_hash' => 'old_token',
        'bus_id' => 'B2',
        'session_id' => 'old_session',
        'started_at' => now()->subHours(8),
        'ended_at' => now()->subHours(7),
        'is_active' => false
    ]);
    
    echo "   Created old test data\n";
    
    $deletedSessions = $batchProcessor->cleanupOldSessions();
    echo "   Cleaned up {$deletedSessions} old sessions\n";
    
    $archivedLocations = $batchProcessor->archiveOldLocations();
    echo "   Archived {$archivedLocations} old locations\n";
    
    echo "\n=== All tests completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Cleanup test data
    echo "\nCleaning up test data...\n";
    
    try {
        BusLocation::where('device_token', 'LIKE', 'test_token_%')->delete();
        BusCurrentPosition::where('bus_id', 'B1')->delete();
        UserTrackingSession::where('device_token', 'LIKE', 'test_token_%')->delete();
        DeviceToken::where('token_hash', 'LIKE', 'test_token_%')->delete();
        
        // Clean up old test data
        BusLocation::where('device_token', 'old_token')->delete();
        UserTrackingSession::where('device_token', 'old_token')->delete();
        
        echo "Test data cleaned up\n";
    } catch (Exception $e) {
        echo "Cleanup error: " . $e->getMessage() . "\n";
    }
}