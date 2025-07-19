<?php

/**
 * Test Login Functionality
 * Verify that authentication works correctly
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🔐 Testing BUBT Login System\n";
echo "============================\n\n";

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

try {
    // Create Laravel app
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Test database connection
    echo "📡 Testing database connection...\n";
    $userCount = \Illuminate\Support\Facades\DB::table('users')->count();
    echo "✅ Database connected - {$userCount} users found\n\n";

    // Test user authentication
    echo "👤 Testing user credentials...\n";
    
    $testUsers = [
        'arif.rahman@bubt.edu.bd',
        'fatima.khatun@bubt.edu.bd',
        'tanvir.ahmed@bubt.edu.bd',
        'admin@bubt.edu.bd'
    ];

    foreach ($testUsers as $email) {
        $user = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->first();
        if ($user) {
            echo "✅ User found: {$user->name} ({$user->role})\n";
            
            // Test password verification
            if (password_verify('student123', $user->password) || password_verify('admin123', $user->password)) {
                echo "   🔑 Password verification: PASS\n";
            } else {
                echo "   ❌ Password verification: FAIL\n";
            }
        } else {
            echo "❌ User not found: {$email}\n";
        }
    }

    echo "\n🚌 Testing bus data...\n";
    $busCount = \Illuminate\Support\Facades\DB::table('buses')->count();
    $tripCount = \Illuminate\Support\Facades\DB::table('trips')->whereDate('trip_date', date('Y-m-d'))->count();
    echo "✅ {$busCount} buses with {$tripCount} trips today\n";

    echo "\n📊 Testing session table...\n";
    $sessionTableExists = \Illuminate\Support\Facades\Schema::hasTable('sessions');
    if ($sessionTableExists) {
        echo "✅ Sessions table exists\n";
    } else {
        echo "❌ Sessions table missing\n";
    }

    echo "\n🎉 Login system test complete!\n";
    echo "\n📝 To test login:\n";
    echo "1. Start server: php start.php\n";
    echo "2. Open: http://localhost:3003\n";
    echo "3. Login with: arif.rahman@bubt.edu.bd / student123\n";
    echo "4. Should redirect to dashboard with toast notification\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}