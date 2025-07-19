<?php

/**
 * BUBT Bus Tracker Database Setup
 * Run this to setup database without artisan commands
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "🚌 BUBT Bus Tracker Database Setup\n";
echo "==================================\n\n";

try {
    // Ensure database file exists
    $dbPath = database_path('database.sqlite');
    if (!file_exists($dbPath)) {
        touch($dbPath);
        echo "✅ Created database file\n";
    }

    // Create tables
    echo "📝 Creating tables...\n";

    // Drop and recreate users table with proper structure
    if (Schema::hasTable('users')) {
        Schema::drop('users');
        echo "🔄 Dropped existing users table\n";
    }
    
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('student_id')->unique()->nullable();
        $table->string('phone')->nullable();
        $table->string('department')->nullable();
        $table->string('role')->default('student'); // Changed from enum to string for SQLite compatibility
        $table->boolean('is_active')->default(true);
        $table->string('remember_token')->nullable();
        $table->timestamps();
    });
    echo "✅ Created users table\n";

    // Buses table
    if (!Schema::hasTable('buses')) {
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('route_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        echo "✅ Created buses table\n";
    }

    // Stops table
    if (!Schema::hasTable('stops')) {
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('order_index');
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
        echo "✅ Created stops table\n";
    }

    // Trips table
    if (!Schema::hasTable('trips')) {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->date('trip_date');
            $table->time('departure_time');
            $table->time('return_time');
            $table->enum('direction', ['outbound', 'inbound']);
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
        echo "✅ Created trips table\n";
    }

    // Locations table
    if (!Schema::hasTable('locations')) {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('recorded_at');
            $table->string('source')->default('api');
            $table->timestamps();
            
            $table->index(['bus_id', 'recorded_at']);
        });
        echo "✅ Created locations table\n";
    }

    // Settings table
    if (!Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });
        echo "✅ Created settings table\n";
    }

    // Push subscriptions table
    if (!Schema::hasTable('push_subscriptions')) {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->json('subscribed_stops')->nullable();
            $table->timestamps();
            
            $table->unique('endpoint');
        });
        echo "✅ Created push_subscriptions table\n";
    }

    // Bus boarding tracking
    if (!Schema::hasTable('bus_boardings')) {
        Schema::create('bus_boardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('boarding_stop_id')->constrained('stops')->onDelete('cascade');
            $table->foreignId('destination_stop_id')->nullable()->constrained('stops')->onDelete('cascade');
            $table->timestamp('boarded_at');
            $table->timestamp('alighted_at')->nullable();
            $table->enum('status', ['waiting', 'boarded', 'completed', 'cancelled'])->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['bus_id', 'trip_id', 'status']);
        });
        echo "✅ Created bus_boardings table\n";
    }

    // Bus capacity and real-time status
    if (!Schema::hasTable('bus_status')) {
        Schema::create('bus_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('current_capacity')->default(0);
            $table->integer('max_capacity')->default(40);
            $table->enum('status', ['idle', 'boarding', 'in_transit', 'arrived'])->default('idle');
            $table->foreignId('current_stop_id')->nullable()->constrained('stops')->onDelete('set null');
            $table->timestamp('last_updated');
            $table->timestamps();
            
            $table->unique(['bus_id', 'trip_id']);
        });
        echo "✅ Created bus_status table\n";
    }

    // Notifications for students
    if (!Schema::hasTable('student_notifications')) {
        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['bus_arrival', 'bus_delay', 'boarding_reminder', 'general'])->default('general');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
        });
        echo "✅ Created student_notifications table\n";
    }

    // Insert sample data
    echo "\n📝 Inserting sample data...\n";

    // Create buses
    $buses = [
        ['name' => 'B1', 'route_name' => 'Buriganga'],
        ['name' => 'B2', 'route_name' => 'Brahmaputra'],
        ['name' => 'B3', 'route_name' => 'Padma'],
        ['name' => 'B4', 'route_name' => 'Meghna'],
        ['name' => 'B5', 'route_name' => 'Jamuna'],
    ];

    foreach ($buses as $busData) {
        $existingBus = DB::table('buses')->where('name', $busData['name'])->first();
        if (!$existingBus) {
            $busId = DB::table('buses')->insertGetId([
                'name' => $busData['name'],
                'route_name' => $busData['route_name'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create stops for each bus
            $routes = [
                'B1' => [
                    ['name' => 'Asad Gate', 'lat' => 23.7550, 'lng' => 90.3700],
                    ['name' => 'Shyamoli', 'lat' => 23.7600, 'lng' => 90.3650],
                    ['name' => 'Mirpur-1', 'lat' => 23.7950, 'lng' => 90.3550],
                    ['name' => 'Rainkhola', 'lat' => 23.8200, 'lng' => 90.3400],
                    ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
                ],
                'B2' => [
                    ['name' => 'Hemayetpur', 'lat' => 23.7900, 'lng' => 90.2800],
                    ['name' => 'Amin Bazar', 'lat' => 23.7950, 'lng' => 90.3100],
                    ['name' => 'Gabtoli', 'lat' => 23.7800, 'lng' => 90.3300],
                    ['name' => 'Mazar Road', 'lat' => 23.8000, 'lng' => 90.3450],
                    ['name' => 'Mirpur-1', 'lat' => 23.7950, 'lng' => 90.3550],
                    ['name' => 'Rainkhola', 'lat' => 23.8200, 'lng' => 90.3400],
                    ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
                ],
                'B3' => [
                    ['name' => 'Shyamoli (Shishu Mela)', 'lat' => 23.7580, 'lng' => 90.3680],
                    ['name' => 'Agargaon', 'lat' => 23.7750, 'lng' => 90.3600],
                    ['name' => 'Kazipara', 'lat' => 23.7850, 'lng' => 90.3650],
                    ['name' => 'Mirpur-10', 'lat' => 23.8050, 'lng' => 90.3700],
                    ['name' => 'Proshikha', 'lat' => 23.8150, 'lng' => 90.3500],
                    ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
                ],
                'B4' => [
                    ['name' => 'Mirpur-14', 'lat' => 23.8250, 'lng' => 90.3800],
                    ['name' => 'Mirpur-10 (Original)', 'lat' => 23.8050, 'lng' => 90.3700],
                    ['name' => 'Mirpur-11', 'lat' => 23.8100, 'lng' => 90.3600],
                    ['name' => 'Proshikha', 'lat' => 23.8150, 'lng' => 90.3500],
                    ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
                ],
                'B5' => [
                    ['name' => 'ECB Chattar', 'lat' => 23.7400, 'lng' => 90.3900],
                    ['name' => 'Kalshi Bridge', 'lat' => 23.7600, 'lng' => 90.3800],
                    ['name' => 'Mirpur-12', 'lat' => 23.8000, 'lng' => 90.3750],
                    ['name' => 'Duaripara', 'lat' => 23.8200, 'lng' => 90.3600],
                    ['name' => 'BUBT', 'lat' => 23.8300, 'lng' => 90.3200],
                ],
            ];

            $busStops = $routes[$busData['name']] ?? [];
            
            foreach ($busStops as $index => $stop) {
                DB::table('stops')->insert([
                    'name' => $stop['name'],
                    'latitude' => $stop['lat'],
                    'longitude' => $stop['lng'],
                    'order_index' => $index + 1,
                    'bus_id' => $busId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create today's trips
            DB::table('trips')->insert([
                [
                    'bus_id' => $busId,
                    'trip_date' => date('Y-m-d'),
                    'departure_time' => '07:00:00',
                    'return_time' => '16:10:00',
                    'direction' => 'outbound',
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'bus_id' => $busId,
                    'trip_date' => date('Y-m-d'),
                    'departure_time' => '17:00:00',
                    'return_time' => '21:25:00',
                    'direction' => 'outbound',
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            echo "✅ Created bus {$busData['name']} with stops and trips\n";
        }
    }

    // Create settings
    $settings = [
        'app_name' => 'BUBT Bus Tracker',
        'refresh_interval' => '30',
        'max_location_age' => '10',
        'clustering_radius' => '60',
        'university_name' => 'Bangladesh University of Business and Technology',
        'university_lat' => '23.8300',
        'university_lng' => '90.3200',
        'contact_email' => 'transport@bubt.edu.bd',
        'contact_phone' => '+880-2-9138234',
    ];

    foreach ($settings as $key => $value) {
        $existing = DB::table('settings')->where('key', $key)->first();
        if (!$existing) {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    echo "✅ Created settings\n";

    // Create sample users
    $sampleUsers = [
        [
            'name' => 'Admin User',
            'email' => 'admin@bubt.edu.bd',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'student_id' => null,
            'phone' => '+880-1700000000',
            'department' => 'Administration',
        ],
        [
            'name' => 'John Doe',
            'email' => 'john.doe@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2021-01-01-001',
            'phone' => '+880-1700000001',
            'department' => 'CSE',
        ],
        [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2021-01-01-002',
            'phone' => '+880-1700000002',
            'department' => 'BBA',
        ]
    ];

    foreach ($sampleUsers as $userData) {
        $existingUser = DB::table('users')->where('email', $userData['email'])->first();
        if (!$existingUser) {
            DB::table('users')->insert([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role' => $userData['role'],
                'student_id' => $userData['student_id'],
                'phone' => $userData['phone'],
                'department' => $userData['department'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "✅ Created user: {$userData['name']} ({$userData['role']})\n";
        }
    }

    // Create sample bus status for active trips
    $activeBuses = DB::table('buses')->where('is_active', true)->get();
    foreach ($activeBuses as $bus) {
        $activeTrip = DB::table('trips')
            ->where('bus_id', $bus->id)
            ->where('trip_date', date('Y-m-d'))
            ->where('status', 'scheduled')
            ->first();
            
        if ($activeTrip) {
            $existingStatus = DB::table('bus_status')
                ->where('bus_id', $bus->id)
                ->where('trip_id', $activeTrip->id)
                ->first();
                
            if (!$existingStatus) {
                DB::table('bus_status')->insert([
                    'bus_id' => $bus->id,
                    'trip_id' => $activeTrip->id,
                    'current_capacity' => rand(5, 25), // Random capacity for demo
                    'max_capacity' => 40,
                    'status' => 'idle',
                    'current_stop_id' => null,
                    'last_updated' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    echo "✅ Created sample users and bus status\n";

    echo "\n🎉 Database setup complete!\n";
    echo "📊 Summary:\n";
    echo "   - " . DB::table('buses')->count() . " buses\n";
    echo "   - " . DB::table('stops')->count() . " stops\n";
    echo "   - " . DB::table('trips')->count() . " trips\n";
    echo "   - " . DB::table('settings')->count() . " settings\n";

    echo "\n🚀 Ready to start!\n";
    echo "Run: php bootstrap.php\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function now() {
    return date('Y-m-d H:i:s');
}