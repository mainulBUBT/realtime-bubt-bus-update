<?php

/**
 * BUBT Bus Tracker - One-Click Startup
 * This script sets up everything and starts the server
 */

echo "🚌 BUBT Bus Tracker - One-Click Startup\n";
echo "=======================================\n\n";

// Check if vendor directory exists
if (!is_dir(__DIR__ . '/vendor')) {
    echo "❌ Vendor directory not found. Please run 'composer install' first.\n";
    exit(1);
}

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Check if .env exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "📝 Creating .env file...\n";
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
    echo "✅ .env file created\n";
}

// Load environment variables
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Create database directory if it doesn't exist
$dbDir = __DIR__ . '/database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✅ Created database directory\n";
}

// Create database file if it doesn't exist
$dbFile = $dbDir . '/database.sqlite';
if (!file_exists($dbFile)) {
    touch($dbFile);
    echo "✅ Created database file\n";
}

// Create storage directories
$storageDirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs'
];

foreach ($storageDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
        echo "✅ Created {$dir}\n";
    }
}

// Set up basic Laravel bootstrap
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check if we need to setup database
    $needsSetup = true;
    try {
        $busCount = \Illuminate\Support\Facades\DB::table('buses')->count();
        if ($busCount > 0) {
            $needsSetup = false;
            echo "✅ Database already setup with {$busCount} buses\n";
        }
    } catch (Exception $e) {
        echo "📝 Database needs setup...\n";
    }

    if ($needsSetup) {
        echo "🔧 Setting up database...\n";
        include __DIR__ . '/setup-database.php';
    }

} catch (Exception $e) {
    echo "⚠️  Bootstrap warning: " . $e->getMessage() . "\n";
    echo "📝 Will try to setup database manually...\n";
    
    // Manual database setup without full Laravel bootstrap
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create basic tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS buses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            route_name VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert sample data if empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM buses");
        if ($stmt->fetchColumn() == 0) {
            $buses = [
                ['B1', 'Buriganga'],
                ['B2', 'Brahmaputra'],
                ['B3', 'Padma'],
                ['B4', 'Meghna'],
                ['B5', 'Jamuna']
            ];
            
            foreach ($buses as $bus) {
                $pdo->exec("INSERT INTO buses (name, route_name) VALUES ('{$bus[0]}', '{$bus[1]}')");
            }
            echo "✅ Created sample buses\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    }
}

// Use port 3003 as requested
$port = 3003;

// Check if port is available
$connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
if ($connection) {
    fclose($connection);
    echo "❌ Port {$port} is already in use\n";
    echo "Please stop the service using port {$port} or use a different port:\n";
    echo "php -S localhost:3004 -t public\n";
    exit(1);
}

echo "\n🚀 Starting development server on port {$port}...\n";
echo "📱 Main App: http://localhost:{$port}\n";
echo "⚙️  Admin Panel: http://localhost:{$port}/admin\n";
echo "🔌 API Endpoint: http://localhost:{$port}/api/positions\n";
echo "\n📝 To test GPS tracking, POST to: http://localhost:{$port}/api/ping\n";
echo "   Example: {\"bus_id\":1,\"latitude\":23.8103,\"longitude\":90.4125}\n\n";

echo "Press Ctrl+C to stop the server\n";
echo "Starting in 3 seconds...\n";
sleep(1);
echo "2...\n";
sleep(1);
echo "1...\n";
sleep(1);
echo "🚀 GO!\n\n";

// Start the server
$command = "php -S localhost:{$port} -t public";
echo "Running: {$command}\n\n";
passthru($command);