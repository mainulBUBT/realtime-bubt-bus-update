# Daily Cleanup System

## Overview

The daily cleanup system automatically removes old tracking data to keep the database lean and performant. It uses Laravel's scheduler to run jobs at specific intervals.

**Note**: The scheduler is defined in `routes/console.php` (Laravel 11+ style), not in `app/Console/Kernel.php`.

## Scheduled Jobs

### 1. Daily Cleanup Job

**Runs**: Every day at 12:05 AM

**What it cleans**:
- `Location` records older than 30 days

**Configuration**: Hardcoded values (previously used SystemSetting)

| Setting | Value | Purpose |
|---------|-------|---------|
| `cleanup_locations_days` | 30 | Keep GPS data for 30 days |

### 2. Complete Expired Trips Job

**Runs**: Every 5 minutes

**What it does**:
- Finds active trips with no location updates for X minutes
- Finds active trips past schedule time + buffer hours
- Marks trips as completed
- Records `actual_ended_at` timestamp

**Configuration** (from database settings table):

| Setting | Default | Purpose |
|---------|---------|---------|
| `auto_complete_trips_after_minutes` | 10 | Complete trip if no updates for 10 minutes |
| `trip_duration_buffer_hours` | 4 | Max hours to keep trip active after schedule |

## Data Retention Policy

### User Locations (GPS Data)

- **Kept for**: 30 days
- **Reasoning**: Historical analysis, debugging
- **Cleanup**: Batch deletion (1000 records at a time)

### Bus Locations (Calculated Positions)

- **Kept for**: 7 days
- **Reasoning**: Real-time data, becomes stale quickly
- **Cleanup**: Batch deletion (1000 records at a time)

### Completed Trips

- **Kept for**: 90 days
- **Reasoning**: Trip history, analytics
- **Cleanup**: Soft delete (records remain but marked deleted)

## Performance Considerations

### Batch Operations

All cleanup operations use chunking to prevent memory issues:

```php
do {
    $deletedBatch = Location::where('created_at', '<', $cutoffDate)
        ->limit(1000)
        ->delete();
    $deleted += $deletedBatch;
} while ($deletedBatch > 0);
```

### Low-Traffic Hours

Daily cleanup runs at midnight (12:05 AM) when traffic is lowest.

### Logging

All cleanup operations log results:

```json
{
    "locations_deleted": 1500
}
```

## Manual Cleanup

### Commands Available

```bash
# Run daily cleanup immediately
php artisan queue:work --once --queue=default

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Database Queries

Check data that will be cleaned:

```sql
-- Old locations (to be deleted)
SELECT COUNT(*) FROM locations
WHERE created_at < NOW() - INTERVAL 30 DAY;
```

## Monitoring

### Log Location

Logs are stored in: `storage/logs/laravel.log`

### Key Log Messages

```
[2026-02-27 00:05:00] local.INFO: Daily cleanup completed
[2026-02-27 00:05:00] local.INFO: Deleted 1500 old locations.
```

### Health Monitoring

Create a monitoring endpoint to track cleanup health:

```php
Route::get('/api/cleanup-stats', function () {
    return response()->json([
        'locations_count' => DB::table('locations')->count(),
    ]);
});
```
