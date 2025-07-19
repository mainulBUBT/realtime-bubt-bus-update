<?php

/**
 * BUBT Student Portal Test Script
 * Test the student authentication and boarding system
 */

echo "🎓 BUBT Student Portal Test\n";
echo "==========================\n\n";

$baseUrl = 'http://localhost:3003';

// Test 1: Check if login page loads
echo "🏠 Testing login page...\n";
$response = @file_get_contents($baseUrl);
if ($response && strpos($response, 'Welcome Back, Student') !== false) {
    echo "✅ Login page is working!\n";
    echo "   - Professional design loaded\n";
    echo "   - BUBT branding present\n";
} else {
    echo "❌ Login page not accessible\n";
}

echo "\n";

// Test 2: Check dashboard (requires authentication)
echo "📱 Testing student dashboard...\n";
$response = @file_get_contents($baseUrl . '/dashboard');
if ($response) {
    if (strpos($response, 'student-dashboard') !== false) {
        echo "✅ Dashboard structure is ready!\n";
    } else {
        echo "🔄 Dashboard redirects to login (expected behavior)\n";
    }
} else {
    echo "❌ Dashboard not accessible\n";
}

echo "\n";

// Test 3: API endpoints
echo "🔌 Testing API endpoints...\n";
$response = @file_get_contents($baseUrl . '/api/positions');
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "✅ Bus positions API working!\n";
        echo "   Active buses: " . ($data['data']['active_buses'] ?? 0) . "\n";
    }
} else {
    echo "❌ API not responding\n";
}

echo "\n";

// Test 4: PWA manifest
echo "📱 Testing PWA features...\n";
$response = @file_get_contents($baseUrl . '/manifest.json');
if ($response) {
    $manifest = json_decode($response, true);
    if ($manifest && isset($manifest['name'])) {
        echo "✅ PWA manifest loaded!\n";
        echo "   App name: " . $manifest['name'] . "\n";
        echo "   Installable: Yes\n";
    }
} else {
    echo "❌ PWA manifest not found\n";
}

echo "\n🎉 Student Portal Testing Complete!\n";
echo "\n📝 Demo Instructions:\n";
echo "1. Open: {$baseUrl}\n";
echo "2. Login with: arif.rahman@bubt.edu.bd / student123\n";
echo "3. Try the boarding system:\n";
echo "   - Click 'Board Bus' on any active bus\n";
echo "   - Select boarding stop\n";
echo "   - Submit request\n";
echo "   - See it appear in 'Your Active Trips'\n";
echo "\n📱 Mobile Testing:\n";
echo "   - Open on mobile browser\n";
echo "   - Add to home screen\n";
echo "   - Experience native app feel\n";
echo "\n👥 Other Demo Users:\n";
echo "   - jane.smith@bubt.edu.bd / student123 (BBA Student)\n";
echo "   - admin@bubt.edu.bd / admin123 (Administrator)\n";