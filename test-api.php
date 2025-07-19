<?php

/**
 * BUBT Bus Tracker API Test Script
 * Run this to test the API endpoints
 */

echo "🧪 BUBT Bus Tracker API Test\n";
echo "============================\n\n";

$baseUrl = 'http://localhost:3003';

// Test 1: Get bus positions
echo "📍 Testing GET /api/positions...\n";
$response = @file_get_contents($baseUrl . '/api/positions');
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "✅ API is working!\n";
        echo "   Active buses: " . ($data['data']['active_buses'] ?? 0) . "\n";
        echo "   Positions: " . count($data['data']['positions'] ?? []) . "\n";
    } else {
        echo "❌ Invalid response format\n";
    }
} else {
    echo "❌ Could not connect to API\n";
    echo "   Make sure the server is running on localhost:8000\n";
}

echo "\n";

// Test 2: Send GPS ping
echo "📡 Testing POST /api/ping...\n";
$pingData = json_encode([
    'bus_id' => 1,
    'latitude' => 23.8103,
    'longitude' => 90.4125,
    'source' => 'test_script'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($pingData)
        ],
        'content' => $pingData
    ]
]);

$response = @file_get_contents($baseUrl . '/api/ping', false, $context);
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ GPS ping successful!\n";
        echo "   Message: " . ($data['message'] ?? 'OK') . "\n";
    } else {
        echo "❌ GPS ping failed\n";
        echo "   Response: " . $response . "\n";
    }
} else {
    echo "❌ Could not send GPS ping\n";
    echo "   Make sure the server is running and database is setup\n";
}

echo "\n";

// Test 3: Check main page
echo "🏠 Testing main page...\n";
$response = @file_get_contents($baseUrl);
if ($response && strpos($response, 'BUBT Bus Tracker') !== false) {
    echo "✅ Main page is working!\n";
} else {
    echo "❌ Main page not accessible\n";
}

echo "\n";

// Test 4: Check admin page
echo "⚙️  Testing admin page...\n";
$response = @file_get_contents($baseUrl . '/admin');
if ($response && strpos($response, 'admin') !== false) {
    echo "✅ Admin page is working!\n";
} else {
    echo "❌ Admin page not accessible\n";
}

echo "\n🎉 API testing complete!\n";
echo "\n📝 Usage Examples:\n";
echo "   curl -X GET {$baseUrl}/api/positions\n";
echo "   curl -X POST {$baseUrl}/api/ping -H 'Content-Type: application/json' -d '{\"bus_id\":1,\"latitude\":23.8103,\"longitude\":90.4125}'\n";
echo "\n📱 Open in browser: {$baseUrl}\n";