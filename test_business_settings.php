<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Business Settings Management Test ===\n\n";

// Test 1: Initialize default settings
echo "1. Testing default settings initialization:\n";
try {
    \App\Models\BusinessSetting::initializeDefaults();
    
    $settingsCount = \App\Models\BusinessSetting::count();
    echo "   Total settings in database: {$settingsCount}\n";
    
    // Test some key settings
    $testSettings = [
        'app_name' => \App\Models\BusinessSetting::get('app_name', 'Not Set'),
        'university_name' => \App\Models\BusinessSetting::get('university_name', 'Not Set'),
        'tracking_interval' => \App\Models\BusinessSetting::get('tracking_interval', 'Not Set'),
        'trust_score_threshold' => \App\Models\BusinessSetting::get('trust_score_threshold', 'Not Set'),
    ];
    
    foreach ($testSettings as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test setting and getting values
echo "2. Testing setting and getting values:\n";
try {
    // Set some test values
    \App\Models\BusinessSetting::set('test_string', 'Hello World', 'string', 'Test string setting');
    \App\Models\BusinessSetting::set('test_integer', 42, 'integer', 'Test integer setting');
    \App\Models\BusinessSetting::set('test_boolean', true, 'boolean', 'Test boolean setting');
    \App\Models\BusinessSetting::set('test_float', 3.14, 'float', 'Test float setting');
    
    // Get the values back
    echo "   String setting: " . \App\Models\BusinessSetting::get('test_string') . "\n";
    echo "   Integer setting: " . \App\Models\BusinessSetting::get('test_integer') . "\n";
    echo "   Boolean setting: " . (\App\Models\BusinessSetting::get('test_boolean') ? 'true' : 'false') . "\n";
    echo "   Float setting: " . \App\Models\BusinessSetting::get('test_float') . "\n";
    echo "   Non-existent setting (with default): " . \App\Models\BusinessSetting::get('non_existent', 'default_value') . "\n";
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test multiple settings retrieval
echo "3. Testing multiple settings retrieval:\n";
try {
    $multipleSettings = \App\Models\BusinessSetting::getMultiple([
        'app_name',
        'university_name',
        'tracking_interval',
        'non_existent_key'
    ]);
    
    foreach ($multipleSettings as $key => $value) {
        echo "   - {$key}: " . ($value ?? 'null') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test grouped settings
echo "4. Testing grouped settings:\n";
try {
    echo "   PWA Settings:\n";
    $pwaSettings = \App\Models\BusinessSetting::getPwaSettings();
    foreach ($pwaSettings as $key => $value) {
        echo "     - {$key}: " . ($value ?? 'null') . "\n";
    }
    
    echo "   University Settings:\n";
    $universitySettings = \App\Models\BusinessSetting::getUniversitySettings();
    foreach ($universitySettings as $key => $value) {
        echo "     - {$key}: " . ($value ?? 'null') . "\n";
    }
    
    echo "   System Settings:\n";
    $systemSettings = \App\Models\BusinessSetting::getSystemSettings();
    foreach ($systemSettings as $key => $value) {
        echo "     - {$key}: " . ($value ?? 'null') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Test cache functionality
echo "5. Testing cache functionality:\n";
try {
    // Set a value
    \App\Models\BusinessSetting::set('cache_test', 'original_value', 'string');
    
    // Get it (should be cached)
    $value1 = \App\Models\BusinessSetting::get('cache_test');
    echo "   First retrieval: {$value1}\n";
    
    // Update the value directly in database (bypassing the model)
    \Illuminate\Support\Facades\DB::table('business_settings')
        ->where('key', 'cache_test')
        ->update(['value' => json_encode('updated_value')]);
    
    // Get it again (should still be cached)
    $value2 = \App\Models\BusinessSetting::get('cache_test');
    echo "   Second retrieval (cached): {$value2}\n";
    
    // Clear cache and get again
    \App\Models\BusinessSetting::clearCache();
    $value3 = \App\Models\BusinessSetting::get('cache_test');
    echo "   Third retrieval (after cache clear): {$value3}\n";
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Test settings by type
echo "6. Testing settings by type:\n";
try {
    $allSettings = \App\Models\BusinessSetting::all();
    $typeGroups = $allSettings->groupBy('type');
    
    foreach ($typeGroups as $type => $settings) {
        echo "   {$type} settings: {$settings->count()}\n";
        foreach ($settings->take(3) as $setting) {
            echo "     - {$setting->key}: {$setting->value}\n";
        }
        if ($settings->count() > 3) {
            echo "     ... and " . ($settings->count() - 3) . " more\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";