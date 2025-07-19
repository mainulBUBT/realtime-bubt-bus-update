<?php

/**
 * BUBT Bus Tracker Bootstrap Script
 * Run this instead of php artisan commands
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Create Laravel application instance
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Bind important interfaces
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🚌 BUBT Bus Tracker Bootstrap\n";
echo "============================\n\n";

try {
    // Check if database exists and has tables
    if (file_exists(database_path('database.sqlite'))) {
        echo "✅ Database file exists\n";
        
        // Check if tables exist
        $tables = ['buses', 'stops', 'trips', 'locations', 'settings'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            echo "✅ All required tables exist\n";
        } else {
            echo "❌ Missing tables: " . implode(', ', $missingTables) . "\n";
            echo "📝 Run: php artisan migrate:fresh --seed\n";
        }
    } else {
        echo "❌ Database file missing\n";
        echo "📝 Creating database file...\n";
        touch(database_path('database.sqlite'));
        echo "✅ Database file created\n";
        echo "📝 Run: php artisan migrate:fresh --seed\n";
    }
    
    // Check if we have sample data
    $busCount = DB::table('buses')->count();
    if ($busCount > 0) {
        echo "✅ Sample data exists ({$busCount} buses)\n";
    } else {
        echo "❌ No sample data found\n";
        echo "📝 Run: php artisan db:seed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "📝 Make sure to run migrations first\n";
}

echo "\n🚀 Starting development server...\n";
echo "📱 Visit: http://localhost:8000\n";
echo "⚙️  Admin: http://localhost:8000/admin\n";
echo "🔌 API: http://localhost:8000/api/positions\n\n";

// Start the built-in PHP server
$command = 'php -S localhost:8000 -t public';
echo "Running: {$command}\n";
echo "Press Ctrl+C to stop\n\n";

passthru($command);