<?php

require_once 'vendor/autoload.php';

use App\Services\BusTrackingFallbackService;
use App\Services\BusLastSeenService;
use App\Services\GPSDataValidator;

// Test the fallback and error handling systems
echo "=== Bus Tracking Fallback System Test ===\n\n";

try {
    // Initialize services
    $fallbackService = new BusTrackingFallbackService();
    $lastSeenService = new BusLastSeenService(new App\Services\StoppageCoordinateValidator());
    $gpsValidator = new GPSDataValidator(
        new App\Services\StoppageCoordinateValidator(),
        new App\Services\RouteValidator(new App\Services\BusScheduleService()),
        new App\Services\BusScheduleService()
    );

    echo "✓ Services initialized successfully\n\n";

    // Test 1: Bus tracking status for different scenarios
    echo "1. Testing Bus Tracking Status:\n";
    echo "   - Testing bus with no tracking...\n";
    
    $testBusId = 'BUBT-01';
    $trackingStatus = $fallbackService->getBusTrackingStatus($testBusId);
    
    echo "   Status: " . $trackingStatus['status'] . "\n";
    echo "   Message: " . $trackingStatus['message'] . "\n";
    echo "   Confidence: " . ($trackingStatus['confidence_level'] ?? 0) . "\n";
    echo "   Active Trackers: " . ($trackingStatus['active_trackers'] ?? 0) . "\n\n";

    // Test 2: Last seen functionality
    echo "2. Testing Last Seen Functionality:\n";
    echo "   - Getting last seen info...\n";
    
    $lastSeenInfo = $lastSeenService->getLastSeenWithContext($testBusId);
    
    if ($lastSeenInfo) {
        echo "   Last seen: " . $lastSeenInfo['timestamp']->format('Y-m-d H:i:s') . "\n";
        echo "   Location: " . $lastSeenInfo['display_location'] . "\n";
        echo "   Confidence: " . $lastSeenInfo['confidence_level'] . "\n";
        echo "   Age: " . $lastSeenInfo['age_description'] . "\n";
    } else {
        echo "   No last seen data available\n";
    }
    
    $lastSeenMessage = $lastSeenService->getLastSeenMessage($testBusId);
    echo "   Display Message: " . $lastSeenMessage . "\n\n";

    // Test 3: Tracking gap analysis
    echo "3. Testing Tracking Gap Analysis:\n";
    
    $gapInfo = $lastSeenService->getTrackingGapInfo($testBusId);
    echo "   Has Gap: " . ($gapInfo['has_gap'] ? 'Yes' : 'No') . "\n";
    echo "   Gap Duration: " . ($gapInfo['gap_duration_minutes'] ?? 'N/A') . " minutes\n";
    echo "   Severity: " . $gapInfo['gap_severity'] . "\n";
    echo "   Message: " . $gapInfo['message'] . "\n";
    echo "   Recommendations:\n";
    foreach ($gapInfo['recommendations'] as $recommendation) {
        echo "     - " . $recommendation . "\n";
    }
    echo "\n";

    // Test 4: GPS data validation
    echo "4. Testing GPS Data Validation:\n";
    
    $testLocationData = [
        'device_token' => 'test_device_123',
        'bus_id' => $testBusId,
        'latitude' => 23.7937, // Mirpur-1
        'longitude' => 90.3629,
        'accuracy' => 15.0,
        'speed' => 25.0,
        'timestamp' => time() * 1000 // Current time in milliseconds
    ];
    
    echo "   Validating GPS data...\n";
    $validationResult = $gpsValidator->validateGPSData($testLocationData);
    
    echo "   Valid: " . ($validationResult['valid'] ? 'Yes' : 'No') . "\n";
    echo "   Confidence Score: " . $validationResult['confidence_score'] . "\n";
    echo "   Flags: " . implode(', ', $validationResult['flags']) . "\n";
    
    if (!empty($validationResult['recommendations'])) {
        echo "   Recommendations:\n";
        foreach ($validationResult['recommendations'] as $recommendation) {
            echo "     - " . $recommendation . "\n";
        }
    }
    echo "\n";

    // Test 5: Single user tracking scenario
    echo "5. Testing Single User Tracking:\n";
    
    $singleUserResult = $fallbackService->handleSingleUserTracking($testBusId, 'test_device_123');
    echo "   Status: " . $singleUserResult['status'] . "\n";
    echo "   Confidence: " . $singleUserResult['confidence_level'] . "\n";
    echo "   Message: " . $singleUserResult['message'] . "\n";
    
    if (!empty($singleUserResult['recommendations'])) {
        echo "   Recommendations:\n";
        foreach ($singleUserResult['recommendations'] as $recommendation) {
            echo "     - " . $recommendation . "\n";
        }
    }
    
    if (!empty($singleUserResult['warning_flags'])) {
        echo "   Warning Flags: " . implode(', ', $singleUserResult['warning_flags']) . "\n";
    }
    echo "\n";

    // Test 6: Fallback display data generation
    echo "6. Testing Fallback Display Data:\n";
    
    $displayData = $fallbackService->generateFallbackDisplayData($testBusId);
    echo "   Status: " . $displayData['status'] . "\n";
    echo "   Display Message: " . $displayData['display_message'] . "\n";
    echo "   Show Map: " . ($displayData['show_map'] ? 'Yes' : 'No') . "\n";
    echo "   Fallback Type: " . $displayData['fallback_type'] . "\n";
    echo "   Confidence Level: " . $displayData['confidence_level'] . "\n";
    
    echo "   Status Indicators:\n";
    foreach ($displayData['status_indicators'] as $indicator) {
        echo "     - " . $indicator['type'] . ": " . $indicator['text'] . "\n";
    }
    
    echo "   User Actions:\n";
    foreach ($displayData['user_actions'] as $action) {
        echo "     - " . $action['text'] . " (" . $action['type'] . ")\n";
    }
    echo "\n";

    // Test 7: Boundary validation
    echo "7. Testing Coordinate Boundary Validation:\n";
    
    $testCases = [
        ['lat' => 23.7937, 'lng' => 90.3629, 'description' => 'Valid Dhaka coordinates'],
        ['lat' => 0, 'lng' => 0, 'description' => 'Invalid (0,0) coordinates'],
        ['lat' => 40.7128, 'lng' => -74.0060, 'description' => 'New York coordinates (outside Bangladesh)'],
        ['lat' => 23.8213, 'lng' => 90.3541, 'description' => 'BUBT campus coordinates']
    ];
    
    foreach ($testCases as $testCase) {
        $testData = [
            'device_token' => 'test_device_123',
            'bus_id' => $testBusId,
            'latitude' => $testCase['lat'],
            'longitude' => $testCase['lng'],
            'accuracy' => 10.0,
            'timestamp' => time() * 1000
        ];
        
        $result = $gpsValidator->validateGPSData($testData);
        $boundaryResult = $result['validation_results']['boundary'] ?? null;
        
        echo "   " . $testCase['description'] . ":\n";
        echo "     Valid: " . ($boundaryResult['valid'] ? 'Yes' : 'No') . "\n";
        echo "     Within Bangladesh: " . ($boundaryResult['within_bangladesh'] ? 'Yes' : 'No') . "\n";
        echo "     Message: " . $boundaryResult['message'] . "\n";
    }
    echo "\n";

    echo "=== All Tests Completed Successfully ===\n";

} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}