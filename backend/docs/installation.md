# Installation Guide

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/MariaDB 5.7+
- Node.js 18+ & NPM
- Laravel 11

## Installation Steps

### 1. Clone Repository

```bash
git clone <repository-url>
cd bus-tracker
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` and configure:
```
APP_NAME="BUBT Bus Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_DATABASE=bus_tracker
DB_USERNAME=your_username
DB_PASSWORD=your_password

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Link Storage (for production)

```bash
php artisan storage:link
```

### 7. Build Frontend Assets

```bash
npm run build
```

### 8. Configure Laravel Reverb (WebSocket)

```bash
php artisan reverb:start
```

## System Settings

After installation, configure these system settings via the admin panel or database:

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `min_active_users` | 2 | Minimum users needed to calculate bus location |
| `inactive_user_timeout` | 120 | Seconds before user marked inactive |
| `location_max_age` | 120 | Maximum age of location data to use (seconds) |
| `route_proximity_threshold` | 100 | Max distance from route (meters) |
| `top_users_for_calculation` | 15 | Number of users to use for calculation |
| `cleanup_old_user_locations_days` | 30 | Delete user locations older than X days |
| `cleanup_old_bus_locations_days` | 7 | Delete bus locations older than X days |
| `cleanup_completed_trips_days` | 90 | Archive completed trips older than X days |
| `auto_complete_trips_after_minutes` | 10 | Complete trips with no activity for X minutes |
| `trip_duration_buffer_hours` | 4 | Max hours to keep trip active after schedule |

## Development Setup

### Run Development Server

```bash
php artisan serve
```

### Run Queue Worker

```bash
php artisan queue:work
```

### Run Scheduler (for automated tasks)

```bash
php artisan schedule:work
```

## Cron Job Setup

Add to your crontab (`crontab -e`):

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This enables:
- Daily cleanup at midnight
- Trip completion checks every 5 minutes
- Inactive user cleanup every 2 minutes

## Verification

Test the installation:

```bash
# Check migrations
php artisan migrate:status

# Check scheduler
php artisan schedule:list

# Check queue
php artisan queue:failed
```
