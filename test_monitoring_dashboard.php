<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Monitoring Dashboard Test ===\n\n";

// Test 1: Check monitoring statistics
echo "1. Testing monitoring statistics:\n";
try {
    $stats = [
        'active_buses' => \App\Models\BusCurrentPosition::where('status', 'active')->count(),
        'total_trackers' => \App\Models\UserTrackingSession::where('is_active', true)->count(),
        'trusted_devices' => \App\Models\DeviceToken::where('is_trusted', true)->count(),
        'suspicious_devices' => \App\Models\DeviceToken::where('trust_score', '<', 0.3)->count(),
        'avg_trust_score' => \App\Models\DeviceToken::avg('trust_score') ?? 0,
        'location_updates_today' => \App\Models\BusLocation::whereDate('created_at', today())->count(),
    ];
    
    foreach ($stats as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test active sessions by bus
echo "2. Testing active sessions by bus:\n";
try {
    $activeSessions = \App\Models\UserTrackingSession::select('bus_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as session_count'))
        ->where('is_active', true)
        ->groupBy('bus_id')
        ->orderBy('session_count', 'desc')
        ->get();
    
    echo "   Found {$activeSessions->count()} buses with active sessions:\n";
    foreach ($activeSessions as $session) {
        echo "   - Bus {$session->bus_id}: {$session->session_count} active sessions\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test trust score distribution
echo "3. Testing trust score distribution:\n";
try {
    $trustDistribution = \App\Models\DeviceToken::select(
        \Illuminate\Support\Facades\DB::raw('CASE 
            WHEN trust_score >= 0.8 THEN "High (0.8+)"
            WHEN trust_score >= 0.6 THEN "Medium (0.6-0.8)"
            WHEN trust_score >= 0.4 THEN "Low (0.4-0.6)"
            ELSE "Very Low (<0.4)"
        END as trust_level'),
        \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
    )
    ->groupBy('trust_level')
    ->get();
    
    echo "   Trust score distribution:\n";
    foreach ($trustDistribution as $level) {
        $percentage = ($level->count / $trustDistribution->sum('count')) * 100;
        echo "   - {$level->trust_level}: {$level->count} devices (" . number_format($percentage, 1) . "%)\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test system alerts
echo "4. Testing system alerts:\n";
try {
    $alerts = [];

    // Low trust devices actively tracking
    $lowTrustActive = \App\Models\UserTrackingSession::join('device_tokens', 'user_tracking_sessions.device_token', '=', 'device_tokens.token_hash')
        ->where('user_tracking_sessions.is_active', true)
        ->where('device_tokens.trust_score', '<', 0.3)
        ->count();

    if ($lowTrustActive > 0) {
        $alerts[] = [
            'type' => 'warning',
            'title' => 'Low Trust Devices Active',
            'message' => "{$lowTrustActive} low-trust devices are currently tracking"
        ];
    }

    // Buses with no active tracking
    $inactiveBuses = \App\Models\BusSchedule::whereNotIn('bus_id', function($query) {
        $query->select('bus_id')
              ->from('bus_current_positions')
              ->where('status', 'active');
    })
    ->where('is_active', true)
    ->count();

    if ($inactiveBuses > 0) {
        $alerts[] = [
            'type' => 'info',
            'title' => 'Buses Not Being Tracked',
            'message' => "{$inactiveBuses} active buses have no current tracking"
        ];
    }

    // High number of location updates
    $recentUpdates = \App\Models\BusLocation::where('created_at', '>=', now()->subMinutes(5))->count();
    if ($recentUpdates > 500) {
        $alerts[] = [
            'type' => 'danger',
            'title' => 'High Location Update Volume',
            'message' => "{$recentUpdates} location updates in the last 5 minutes"
        ];
    }

    echo "   Found " . count($alerts) . " system alerts:\n";
    foreach ($alerts as $alert) {
        echo "   - [{$alert['type']}] {$alert['title']}: {$alert['message']}\n";
    }
    
    if (empty($alerts)) {
        echo "   ✓ All systems operating normally\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Test analytics data
echo "5. Testing analytics data:\n";
try {
    // Daily stats for last 7 days
    $dailyStats = \App\Models\BusLocation::select(
        \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
        \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_locations'),
        \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT device_token) as unique_devices'),
        \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT bus_id) as active_buses')
    )
    ->where('created_at', '>=', now()->subDays(7))
    ->groupBy('date')
    ->orderBy('date')
    ->get();

    echo "   Daily statistics (last 7 days):\n";
    foreach ($dailyStats as $stat) {
        echo "   - {$stat->date}: {$stat->total_locations} locations, {$stat->unique_devices} devices, {$stat->active_buses} buses\n";
    }
    
    if ($dailyStats->isEmpty()) {
        echo "   No location data found for the last 7 days\n";
    }

    // Bus usage statistics
    $busUsage = \App\Models\UserTrackingSession::select(
        'bus_id',
        \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_sessions'),
        \Illuminate\Support\Facades\DB::raw('AVG(locations_contributed) as avg_locations'),
        \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_sessions')
    )
    ->groupBy('bus_id')
    ->orderBy('total_sessions', 'desc')
    ->get();

    echo "   Bus usage statistics:\n";
    foreach ($busUsage as $usage) {
        echo "   - Bus {$usage->bus_id}: {$usage->total_sessions} sessions, " . 
             number_format($usage->avg_locations, 1) . " avg locations, {$usage->active_sessions} active\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Test device trust management
echo "6. Testing device trust management:\n";
try {
    $devices = \App\Models\DeviceToken::select('*')
        ->addSelect(\Illuminate\Support\Facades\DB::raw('(accurate_contributions / GREATEST(total_contributions, 1)) * 100 as accuracy_percentage'))
        ->orderBy('trust_score', 'desc')
        ->limit(5)
        ->get();

    echo "   Top 5 trusted devices:\n";
    foreach ($devices as $device) {
        echo "   - Token: " . substr($device->token_hash, 0, 12) . "...\n";
        echo "     Trust Score: " . number_format($device->trust_score, 3) . "\n";
        echo "     Accuracy: " . number_format($device->accuracy_percentage, 1) . "%\n";
        echo "     Contributions: {$device->accurate_contributions}/{$device->total_contributions}\n";
    }

    // Suspicious devices
    $suspiciousDevices = \App\Models\DeviceToken::where(function($query) {
        $query->where('trust_score', '<', 0.3)
              ->orWhere('movement_consistency', '<', 0.4);
    })
    ->orderBy('trust_score', 'asc')
    ->limit(3)
    ->get();

    echo "   Suspicious devices:\n";
    foreach ($suspiciousDevices as $device) {
        echo "   - Token: " . substr($device->token_hash, 0, 12) . "... (Trust: " . number_format($device->trust_score, 3) . ")\n";
    }
    
    if ($suspiciousDevices->isEmpty()) {
        echo "   No suspicious devices found\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";