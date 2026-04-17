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
cd realtime-bubt-bus-update
```

### 2. Install Backend Dependencies

```bash
cd backend
composer install
```

### 3. Install Frontend Dependencies

```bash
cd ../frontend
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

### 7. Run Frontend Development Servers

```bash
cd ../frontend
npm run dev:student
# or for driver app
npm run dev:driver
```

### 8. Native Android App Setup (Capacitor)

To build and run the native Android apps for the frontend, follow these steps to initialize Capacitor for each app.

#### For the Student App:
```bash
cd frontend/capacitor-student
npm init -y
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap add android

# Build the vue app and sync it with the native project
cd ..
npm run build:student

# Open the project in Android Studio
cd capacitor-student
npx cap open android
```

#### For the Driver App:
```bash
cd frontend/capacitor-driver
npm init -y
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap add android

# Build the vue app and sync it with the native project
cd ..
npm run build:driver

# Open the project in Android Studio
cd capacitor-driver
npx cap open android
```

### 9. Configure Laravel Reverb (WebSocket)

Return to the `backend` directory:
```bash
cd ../backend
php artisan reverb:start
```

## System Settings

After installation, configure these system settings via the admin panel or database:

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `app_name` | BUBT Bus Tracker | Application name |
| `app_version` | 1.0.0 | Application version |
| `maintenance_mode` | false | Enable/disable maintenance mode |

**Note**: Many settings are now hardcoded in jobs rather than stored in database.

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
