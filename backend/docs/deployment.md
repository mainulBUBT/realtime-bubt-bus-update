# Deployment Guide

## Pre-Deployment Checklist

- [ ] Server has PHP 8.2+
- [ ] MySQL/MariaDB configured
- [ ] Composer installed
- [ ] Node.js & NPM installed
- [ ] SSL certificate configured
- [ ] Cron jobs accessible

## Deployment Steps

### 1. Deploy Backend Code

```bash
# Pull latest code
git pull origin main

# Navigate to backend and install dependencies
cd backend
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage if not already linked
php artisan storage:link
```

### 2. Configure Environment

Update `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_DATABASE=bus_tracker_production
DB_USERNAME=production_user
DB_PASSWORD=secure_password

# Broadcast (Reverb)
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret

# Queue
QUEUE_CONNECTION=database
```

### 3. Build Frontend Assets

```bash
cd ../frontend
npm ci --production
npm run build:student
npm run build:driver
```

### 4. Setup Cron Job

Add to crontab (`crontab -e`):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Verify scheduler is running:

```bash
php artisan schedule:list
```

### 5. Start Queue Workers

Use Supervisor to keep queue workers running:

**Install Supervisor:**
```bash
sudo apt-get install supervisor
```

**Create Supervisor Config** (`/etc/supervisor/conf.d/bus-tracker-worker.conf`):
```ini
[program:bus-tracker-worker]
process_name=%(program_name)s_%(process_num)02
command=php /path-to-project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bus-tracker-worker:*
```

### 6. Start Laravel Reverb (WebSocket)

```bash
php artisan reverb:start
```

Or use Supervisor for Reverb as well.

## API Endpoints

### Public Routes
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/settings` - Get app settings

### Driver Routes (role:driver)
- `GET /api/driver/buses` - List available buses
- `GET /api/driver/routes` - List available routes
- `POST /api/driver/trips/start` - Start a new trip
- `POST /api/driver/trips/{trip}/end` - End a trip
- `GET /api/driver/trips/current` - Get current active trip
- `GET /api/driver/trips/history` - Get trip history
- `POST /api/driver/location` - Submit GPS location
- `POST /api/driver/location/batch` - Submit batch GPS locations

### Student Routes (role:student)
- `GET /api/student/routes` - List available routes
- `GET /api/student/routes/{id}` - Get route details
- `GET /api/student/trips/active` - Get active trips
- `GET /api/student/trips/{tripId}/locations` - Get trip locations
- `GET /api/student/trips/{tripId}/latest-location` - Get latest location
- `GET /api/student/schedules` - Get bus schedules

### Admin Routes (role:admin)
- `GET/POST/PUT/DELETE /api/admin/buses` - Bus CRUD
- `GET/POST/PUT/DELETE /api/admin/routes` - Route CRUD
- `GET/POST/PUT/DELETE /api/admin/schedules` - Schedule CRUD

## Troubleshooting

### Scheduler Not Running

Check if cron is configured:
```bash
crontab -l
```

Test scheduler manually:
```bash
php artisan schedule:run
```

### Queue Jobs Not Processing

Check queue status:
```bash
php artisan queue:failed
```

Restart Supervisor workers:
```bash
sudo supervisorctl restart bus-tracker-worker:*
```

### WebSocket Connection Issues

Check Reverb is running:
```bash
ps aux | grep reverb
```

Check Reverb configuration in `.env`:
```env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

### Database Connection Issues

Check database credentials in `.env`:
```bash
php artisan tinker
DB::connection()->getPdo();
```

Test migration status:
```bash
php artisan migrate:status
```

## Monitoring

### Log Files

- Queue Worker: `storage/logs/worker.log`
- Laravel: `storage/logs/laravel.log`
- Scheduler: Check `storage/logs` for job execution logs

### Health Checks

Create a health check endpoint:
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'queue' => \App\Jobs\DailyCleanupJob::dispatchNow() ? 'queued' : 'ok',
    ]);
});
```

## Post-Deployment

1. **Verify Scheduler**: Check logs for daily cleanup execution
2. **Verify Trips**: Create a test trip and verify it auto-completes
3. **Verify Cleanup**: Check database for old data cleanup
4. **Monitor Performance**: Check queue worker and CPU usage
5. **Test Multi-Trip**: Verify multiple trips can be created per day

## Rollback Procedure

If issues occur:

```bash
# Rollback migrations
php artisan migrate:rollback

# Restore previous code
git reset --hard HEAD~1

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
