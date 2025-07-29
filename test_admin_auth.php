<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Admin Authentication System Test ===\n\n";

// Test 1: Check if admin users exist
echo "1. Testing admin users in database:\n";
try {
    $adminUsers = \App\Models\AdminUser::all();
    echo "   Found " . $adminUsers->count() . " admin users:\n";
    foreach ($adminUsers as $user) {
        echo "   - {$user->name} ({$user->email}) - Role: {$user->role} - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check admin routes
echo "2. Testing admin routes registration:\n";
try {
    $adminRoutes = collect(Route::getRoutes())->filter(function ($route) {
        return str_starts_with($route->uri(), 'admin/');
    });
    
    echo "   Found " . $adminRoutes->count() . " admin routes:\n";
    foreach ($adminRoutes->take(10) as $route) {
        echo "   - " . $route->methods()[0] . " /admin/" . ltrim($route->uri(), 'admin/') . " -> " . $route->getName() . "\n";
    }
    if ($adminRoutes->count() > 10) {
        echo "   ... and " . ($adminRoutes->count() - 10) . " more routes\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check admin authentication guard
echo "3. Testing admin authentication guard:\n";
try {
    $guards = config('auth.guards');
    if (isset($guards['admin'])) {
        echo "   ✓ Admin guard configured: " . json_encode($guards['admin']) . "\n";
    } else {
        echo "   ✗ Admin guard not found\n";
    }
    
    $providers = config('auth.providers');
    if (isset($providers['admin_users'])) {
        echo "   ✓ Admin provider configured: " . json_encode($providers['admin_users']) . "\n";
    } else {
        echo "   ✗ Admin provider not found\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test admin permissions
echo "4. Testing admin permissions:\n";
try {
    $superAdmin = \App\Models\AdminUser::where('role', 'super_admin')->first();
    $admin = \App\Models\AdminUser::where('role', 'admin')->first();
    $monitor = \App\Models\AdminUser::where('role', 'monitor')->first();
    
    if ($superAdmin) {
        echo "   Super Admin permissions:\n";
        echo "     - Can manage buses: " . ($superAdmin->canManageBuses() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage schedules: " . ($superAdmin->canManageSchedules() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage settings: " . ($superAdmin->canManageSettings() ? 'Yes' : 'No') . "\n";
        echo "     - Can view monitoring: " . ($superAdmin->canViewMonitoring() ? 'Yes' : 'No') . "\n";
    }
    
    if ($admin) {
        echo "   Admin permissions:\n";
        echo "     - Can manage buses: " . ($admin->canManageBuses() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage schedules: " . ($admin->canManageSchedules() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage settings: " . ($admin->canManageSettings() ? 'Yes' : 'No') . "\n";
        echo "     - Can view monitoring: " . ($admin->canViewMonitoring() ? 'Yes' : 'No') . "\n";
    }
    
    if ($monitor) {
        echo "   Monitor permissions:\n";
        echo "     - Can manage buses: " . ($monitor->canManageBuses() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage schedules: " . ($monitor->canManageSchedules() ? 'Yes' : 'No') . "\n";
        echo "     - Can manage settings: " . ($monitor->canManageSettings() ? 'Yes' : 'No') . "\n";
        echo "     - Can view monitoring: " . ($monitor->canViewMonitoring() ? 'Yes' : 'No') . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";