<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Services\HistoricalDataService;
use App\Models\BusLocation;
use App\Models\BusLocationHistory;
use App\Models\DeviceToken;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bus Historical Data Archiving Test ===\n\n";

try {
    $historicalService = new HistoricalDataService();
    
    // Test 1: Create sample location data for archiving
    echo "1. Creating sample location data for archiving...\n";
    
    // Create a device token for testing
    $deviceToken = DeviceToken::firstOrCreate([
        'token_hash' => 'test_device_' . uniqid()
    ], [
        'fingerprint_data' => ['test' => 'data'],
        'reputation_score' => 0.8,
        'trust_score' => 0.8,
        'is_trusted' => true,
        'total_contributions' => 100,
        'accurate_contributions' => 85
    ]);
    
    // Create old location data (2 days ago)
    $oldDate = now()->subDays(2);
    $locations = [];
    
    for ($i = 0; $i < 20; $i++) {
        $locations[] = [
            'bus_id' => 'B1',
            'device_token' => $deviceToken->token_hash,
            'latitude' => 23.7500 + ($i * 0.001), // Simulate movement
            'longitude' => 90.3750 + ($i * 0.001),
            'accuracy' => 10.0,
            'speed' => 15.0,
            'reputation_weight' => 0.8,
            'is_validated' => true,
            'created_at' => $oldDate->copy()->addMinutes($i * 5),
            'updated_at' => $oldDate->copy()->addMinutes($i * 5)
        ];
    }
    
    BusLocation::insert($locations);
    echo "Created " . count($locations) . " sample location records\n";
    
    // Test 2: Check archiving statistics before
    echo "\n2. Checking archiving statistics before archiving...\n";
    $statsBefore = $historicalService->getArchivingStats();
    echo "Real-time locations: " . $statsBefore['realtime_locations'] . "\n";
    echo "Historical trips: " . $statsBefore['historical_trips'] . "\n";
    echo "Locations needing archive: " . $statsBefore['archiving_needed'] . "\n";
    
    // Test 3: Archive completed trips
    echo "\n3. Archiving completed trip data...\n";
    $archiveResults = $historicalService->archiveCompletedTrips(now()->subHours(12));
    
    echo "Archived trips: " . $archiveResults['archived_trips'] . "\n";
    echo "Archived locations: " . $archiveResults['archived_locations'] . "\n";
    
    if (!empty($archiveResults['errors'])) {
        echo "Errors:\n";
        foreach ($archiveResults['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    // Test 4: Check archiving statistics after
    echo "\n4. Checking archiving statistics after archiving...\n";
    $statsAfter = $historicalService->getArchivingStats();
    echo "Real-time locations: " . $statsAfter['realtime_locations'] . "\n";
    echo "Historical trips: " . $statsAfter['historical_trips'] . "\n";
    echo "Locations needing archive: " . $statsAfter['archiving_needed'] . "\n";
    
    // Test 5: Retrieve historical data
    echo "\n5. Retrieving historical data for analysis...\n";
    $historicalData = $historicalService->getHistoricalData(
        'B1',
        $oldDate->copy()->startOfDay(),
        $oldDate->copy()->endOfDay()
    );
    
    echo "Historical trips found: " . $historicalData['trip_count'] . "\n";
    
    if ($historicalData['trip_count'] > 0) {
        $trip = $historicalData['trips'][0];
        echo "Trip date: " . $trip['date'] . "\n";
        echo "Total locations: " . $trip['stats']['total_locations'] . "\n";
        echo "Trusted locations: " . $trip['stats']['trusted_locations'] . "\n";
        echo "Trust percentage: " . $trip['stats']['trust_percentage'] . "%\n";
        echo "Trip duration: " . ($trip['stats']['trip_duration'] ?? 'N/A') . " minutes\n";
        
        echo "\nRoute analysis:\n";
        echo "Total points: " . $trip['route_analysis']['total_points'] . "\n";
        echo "Average speed: " . $trip['route_analysis']['speed_analysis']['average_speed'] . " m/s\n";
        echo "Average trust: " . $trip['route_analysis']['trust_distribution']['average_trust'] . "\n";
    }
    
    // Test 6: Test BusLocationHistory model methods
    echo "\n6. Testing BusLocationHistory model methods...\n";
    $historyRecord = BusLocationHistory::forBus('B1')->first();
    
    if ($historyRecord) {
        echo "History record found for bus B1\n";
        
        $tripStats = $historyRecord->getTripStats();
        echo "Trip stats - Total locations: " . $tripStats['total_locations'] . "\n";
        echo "Trip stats - Trust percentage: " . $tripStats['trust_percentage'] . "%\n";
        echo "Trip stats - Duration: " . ($tripStats['trip_duration'] ?? 'N/A') . " minutes\n";
        
        $routeAnalysis = $historyRecord->getRouteAnalysis();
        echo "Route analysis - Total points: " . $routeAnalysis['total_points'] . "\n";
        echo "Route analysis - Total distance: " . $routeAnalysis['route_coverage']['total_distance'] . " meters\n";
        
        // Test filtered location data
        $filteredData = $historyRecord->getLocationData(['min_trust' => 0.7, 'validated_only' => true]);
        echo "Filtered locations (trust >= 0.7, validated only): " . count($filteredData) . "\n";
    }
    
    // Test 7: Clean up real-time data
    echo "\n7. Testing real-time data cleanup...\n";
    $cleanedCount = $historicalService->cleanupRealtimeData(now()->subHours(1));
    echo "Cleaned up $cleanedCount old real-time location records\n";
    
    // Test 8: Test old historical data archiving
    echo "\n8. Testing old historical data archiving...\n";
    $oldHistoryResults = $historicalService->archiveOldHistoricalData(1); // 1 day retention for testing
    echo "Archived old records: " . $oldHistoryResults['archived_records'] . "\n";
    echo "Deleted old records: " . $oldHistoryResults['deleted_records'] . "\n";
    
    if (!empty($oldHistoryResults['errors'])) {
        echo "Errors:\n";
        foreach ($oldHistoryResults['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    // Test 9: Final statistics
    echo "\n9. Final archiving statistics...\n";
    $finalStats = $historicalService->getArchivingStats();
    echo "Real-time locations: " . $finalStats['realtime_locations'] . "\n";
    echo "Historical trips: " . $finalStats['historical_trips'] . "\n";
    echo "Old historical records: " . $finalStats['old_historical'] . "\n";
    
    echo "\n=== Historical Data Archiving Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}