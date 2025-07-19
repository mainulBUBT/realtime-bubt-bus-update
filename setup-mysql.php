<?php

/**
 * BUBT Bus Tracker MySQL Setup
 * Creates database and tables for MySQL
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🚌 BUBT Bus Tracker MySQL Setup\n";
echo "===============================\n\n";

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'bubt_bus';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Connect to MySQL server (without database)
    echo "📡 Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL server\n";

    // Create database if it doesn't exist
    echo "🗄️  Creating database '{$database}'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '{$database}' ready\n";

    // Connect to the specific database
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "\n📝 Creating tables...\n";

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            email_verified_at TIMESTAMP NULL,
            password VARCHAR(255) NOT NULL,
            student_id VARCHAR(255) NULL UNIQUE,
            phone VARCHAR(255) NULL,
            department VARCHAR(255) NULL,
            role ENUM('student', 'admin', 'driver') DEFAULT 'student',
            is_active BOOLEAN DEFAULT TRUE,
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created users table\n";

    // Buses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS buses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            route_name VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created buses table\n";

    // Stops table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stops (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            latitude DECIMAL(10,8) NOT NULL,
            longitude DECIMAL(11,8) NOT NULL,
            order_index INT NOT NULL,
            bus_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            INDEX idx_bus_order (bus_id, order_index)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created stops table\n";

    // Trips table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trips (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bus_id BIGINT UNSIGNED NOT NULL,
            trip_date DATE NOT NULL,
            departure_time TIME NOT NULL,
            return_time TIME NOT NULL,
            direction ENUM('outbound', 'inbound') NOT NULL,
            status ENUM('scheduled', 'active', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            INDEX idx_bus_date (bus_id, trip_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created trips table\n";

    // Locations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS locations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bus_id BIGINT UNSIGNED NOT NULL,
            latitude DECIMAL(10,8) NOT NULL,
            longitude DECIMAL(11,8) NOT NULL,
            recorded_at TIMESTAMP NOT NULL,
            source VARCHAR(255) DEFAULT 'api',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            INDEX idx_bus_time (bus_id, recorded_at),
            INDEX idx_recorded_at (recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created locations table\n";

    // Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) NOT NULL UNIQUE,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created settings table\n";

    // Push subscriptions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            endpoint VARCHAR(500) NOT NULL UNIQUE,
            public_key VARCHAR(255) NOT NULL,
            auth_token VARCHAR(255) NOT NULL,
            subscribed_stops JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created push_subscriptions table\n";

    // Bus boardings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bus_boardings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            bus_id BIGINT UNSIGNED NOT NULL,
            trip_id BIGINT UNSIGNED NOT NULL,
            boarding_stop_id BIGINT UNSIGNED NOT NULL,
            destination_stop_id BIGINT UNSIGNED NULL,
            boarded_at TIMESTAMP NOT NULL,
            alighted_at TIMESTAMP NULL,
            status ENUM('waiting', 'boarded', 'completed', 'cancelled') DEFAULT 'waiting',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
            FOREIGN KEY (boarding_stop_id) REFERENCES stops(id) ON DELETE CASCADE,
            FOREIGN KEY (destination_stop_id) REFERENCES stops(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_bus_trip (bus_id, trip_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created bus_boardings table\n";

    // Bus status table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bus_status (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bus_id BIGINT UNSIGNED NOT NULL,
            trip_id BIGINT UNSIGNED NULL,
            current_capacity INT DEFAULT 0,
            max_capacity INT DEFAULT 40,
            status ENUM('idle', 'boarding', 'in_transit', 'arrived') DEFAULT 'idle',
            current_stop_id BIGINT UNSIGNED NULL,
            last_updated TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
            FOREIGN KEY (current_stop_id) REFERENCES stops(id) ON DELETE SET NULL,
            UNIQUE KEY unique_bus_trip (bus_id, trip_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created bus_status table\n";

    // Student notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('bus_arrival', 'bus_delay', 'boarding_reminder', 'general') DEFAULT 'general',
            data JSON NULL,
            is_read BOOLEAN DEFAULT FALSE,
            scheduled_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_scheduled (scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created student_notifications table\n";

    // Sessions table (required for Laravel session management)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload LONGTEXT NOT NULL,
            last_activity INT NOT NULL,
            INDEX sessions_user_id_index (user_id),
            INDEX sessions_last_activity_index (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created sessions table\n";

    // Cache table (for Laravel caching)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cache (
            `key` VARCHAR(255) NOT NULL PRIMARY KEY,
            value MEDIUMTEXT NOT NULL,
            expiration INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created cache table\n";

    // Cache locks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cache_locks (
            `key` VARCHAR(255) NOT NULL PRIMARY KEY,
            owner VARCHAR(255) NOT NULL,
            expiration INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created cache_locks table\n";

    // Jobs table (for Laravel queues)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            payload LONGTEXT NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL,
            reserved_at INT UNSIGNED NULL,
            available_at INT UNSIGNED NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            INDEX jobs_queue_index (queue)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created jobs table\n";

    // Job batches table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job_batches (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            total_jobs INT NOT NULL,
            pending_jobs INT NOT NULL,
            failed_jobs INT NOT NULL,
            failed_job_ids LONGTEXT NOT NULL,
            options MEDIUMTEXT NULL,
            cancelled_at INT NULL,
            created_at INT NOT NULL,
            finished_at INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created job_batches table\n";

    // Failed jobs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS failed_jobs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid VARCHAR(255) NOT NULL UNIQUE,
            connection TEXT NOT NULL,
            queue TEXT NOT NULL,
            payload LONGTEXT NOT NULL,
            exception LONGTEXT NOT NULL,
            failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created failed_jobs table\n";

    // Password reset tokens table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            email VARCHAR(255) NOT NULL PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created password_reset_tokens table\n";

    echo "\n📝 Inserting sample data...\n";

    // Insert sample buses
    $buses = [
        ['B1', 'Buriganga'],
        ['B2', 'Brahmaputra'],
        ['B3', 'Padma'],
        ['B4', 'Meghna'],
        ['B5', 'Jamuna'],
    ];

    foreach ($buses as $busData) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM buses WHERE name = ?");
        $stmt->execute([$busData[0]]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO buses (name, route_name, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$busData[0], $busData[1]]);
            $busId = $pdo->lastInsertId();

            // Insert stops for each bus
            $routes = [
                'B1' => [
                    ['Asad Gate', 23.7550, 90.3700],
                    ['Shyamoli', 23.7600, 90.3650],
                    ['Mirpur-1', 23.7950, 90.3550],
                    ['Rainkhola', 23.8200, 90.3400],
                    ['BUBT', 23.8300, 90.3200],
                ],
                'B2' => [
                    ['Hemayetpur', 23.7900, 90.2800],
                    ['Amin Bazar', 23.7950, 90.3100],
                    ['Gabtoli', 23.7800, 90.3300],
                    ['Mazar Road', 23.8000, 90.3450],
                    ['Mirpur-1', 23.7950, 90.3550],
                    ['Rainkhola', 23.8200, 90.3400],
                    ['BUBT', 23.8300, 90.3200],
                ],
                'B3' => [
                    ['Shyamoli (Shishu Mela)', 23.7580, 90.3680],
                    ['Agargaon', 23.7750, 90.3600],
                    ['Kazipara', 23.7850, 90.3650],
                    ['Mirpur-10', 23.8050, 90.3700],
                    ['Proshikha', 23.8150, 90.3500],
                    ['BUBT', 23.8300, 90.3200],
                ],
                'B4' => [
                    ['Mirpur-14', 23.8250, 90.3800],
                    ['Mirpur-10 (Original)', 23.8050, 90.3700],
                    ['Mirpur-11', 23.8100, 90.3600],
                    ['Proshikha', 23.8150, 90.3500],
                    ['BUBT', 23.8300, 90.3200],
                ],
                'B5' => [
                    ['ECB Chattar', 23.7400, 90.3900],
                    ['Kalshi Bridge', 23.7600, 90.3800],
                    ['Mirpur-12', 23.8000, 90.3750],
                    ['Duaripara', 23.8200, 90.3600],
                    ['BUBT', 23.8300, 90.3200],
                ],
            ];

            $busStops = $routes[$busData[0]] ?? [];
            foreach ($busStops as $index => $stop) {
                $stmt = $pdo->prepare("INSERT INTO stops (name, latitude, longitude, order_index, bus_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$stop[0], $stop[1], $stop[2], $index + 1, $busId]);
            }

            // Create today's trips
            $stmt = $pdo->prepare("INSERT INTO trips (bus_id, trip_date, departure_time, return_time, direction, status) VALUES (?, CURDATE(), '07:00:00', '16:10:00', 'outbound', 'scheduled')");
            $stmt->execute([$busId]);

            $stmt = $pdo->prepare("INSERT INTO trips (bus_id, trip_date, departure_time, return_time, direction, status) VALUES (?, CURDATE(), '17:00:00', '21:25:00', 'outbound', 'scheduled')");
            $stmt->execute([$busId]);

            echo "✅ Created bus {$busData[0]} with stops and trips\n";
        }
    }

    // Insert settings
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
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }

    // Insert demo users
    $demoUsers = [
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
            'name' => 'Md. Arif Rahman',
            'email' => 'arif.rahman@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2021-01-01-001',
            'phone' => '+880-1712345678',
            'department' => 'CSE',
        ],
        [
            'name' => 'Fatima Khatun',
            'email' => 'fatima.khatun@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2021-02-01-015',
            'phone' => '+880-1798765432',
            'department' => 'BBA',
        ],
        [
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir.ahmed@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2020-01-01-045',
            'phone' => '+880-1634567890',
            'department' => 'EEE',
        ],
        [
            'name' => 'Rashida Begum',
            'email' => 'rashida.begum@bubt.edu.bd',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'student_id' => '2022-03-01-008',
            'phone' => '+880-1556789012',
            'department' => 'English',
        ]
    ];

    foreach ($demoUsers as $userData) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, student_id, phone, department, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $userData['password'],
                $userData['role'],
                $userData['student_id'],
                $userData['phone'],
                $userData['department']
            ]);
            echo "✅ Created user: {$userData['name']} ({$userData['role']})\n";
        }
    }

    // Create sample bus status
    $stmt = $pdo->query("SELECT id FROM buses WHERE is_active = 1");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($buses as $bus) {
        $stmt = $pdo->prepare("SELECT id FROM trips WHERE bus_id = ? AND trip_date = CURDATE() AND status = 'scheduled' LIMIT 1");
        $stmt->execute([$bus['id']]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trip) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO bus_status (bus_id, trip_id, current_capacity, max_capacity, status, last_updated) VALUES (?, ?, ?, 40, 'idle', NOW())");
            $stmt->execute([$bus['id'], $trip['id'], rand(5, 25)]);
        }
    }

    echo "✅ Created sample bus status\n";

    // Get final counts
    $stmt = $pdo->query("SELECT COUNT(*) FROM buses");
    $busCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM stops");
    $stopCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM trips");
    $tripCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    echo "\n🎉 MySQL Database setup complete!\n";
    echo "📊 Summary:\n";
    echo "   - {$busCount} buses\n";
    echo "   - {$stopCount} stops\n";
    echo "   - {$tripCount} trips\n";
    echo "   - {$userCount} users\n";

    echo "\n🎓 Demo Student Accounts:\n";
    echo "   📧 arif.rahman@bubt.edu.bd | 🔑 student123 | 🎓 CSE\n";
    echo "   📧 fatima.khatun@bubt.edu.bd | 🔑 student123 | 🎓 BBA\n";
    echo "   📧 tanvir.ahmed@bubt.edu.bd | 🔑 student123 | 🎓 EEE\n";
    echo "   📧 rashida.begum@bubt.edu.bd | 🔑 student123 | 🎓 English\n";

    echo "\n👨‍💼 Admin Account:\n";
    echo "   📧 admin@bubt.edu.bd | 🔑 admin123\n";

    echo "\n🚀 Ready to start!\n";
    echo "Run: php start.php\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure MySQL server is running\n";
    echo "2. Check your MySQL credentials in .env file\n";
    echo "3. Ensure MySQL user has CREATE DATABASE privileges\n";
    echo "4. Try: mysql -u root -p\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}