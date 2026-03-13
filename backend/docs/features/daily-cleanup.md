# Daily Cleanup System

## Overview

The daily cleanup system automatically removes old tracking data to keep the database lean and performant. It uses Laravel's scheduler to run jobs at specific intervals.

## Scheduled Jobs

### 1. Daily Cleanup Job

**Runs**: Every day at 12:05 AM

**What it cleans**:
- `UserLocation` records older than 30 days
- `BusLocation` records older than 7 days
- Completed trips older than 90 days

**Configuration**:

| Setting | Default | Purpose |
|---------|---------|---------|
| `cleanup_old_user_locations_days` | 30 | Keep GPS data for 30 days |
| `cleanup_old_bus_locations_days` | 7 | Keep calculated positions for 7 days |
| `cleanup_completed_trips_days` | 90 | Archive completed trips after 90 days |

### 2. Complete Expired Trips Job

**Runs**: Every 5 minutes

**What it does**:
- Finds active trips with no active users for X minutes
- Finds active trips past schedule time + buffer hours
- Marks trips as completed
- Records `actual_ended_at` timestamp

**Configuration**:

| Setting | Default | Purpose |
|---------|---------|---------|
| `auto_complete_trips_after_minutes` | 10 | Complete trip if no users for 10 minutes |
| `trip_duration_buffer_hours` | 4 | Max hours to keep trip active after schedule |

### 3. Cleanup Inactive Users Job

**Runs**: Every 2 minutes

**What it does**:
- Removes `BusActiveUser` records for users inactive for 120+ seconds
- This is the timeout mechanism - users must keep sending location updates

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

### Active Users (BusActiveUser)

- **Kept for**: 120 seconds of inactivity
- **Reasoning**: Real-time presence tracking
- **Cleanup**: Immediate removal when timeout expires

## Performance Considerations

### Batch Operations

All cleanup operations use chunking to prevent memory issues:

```php
do {
    $deletedBatch = Model::olderThan($days)
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
    "user_locations_deleted": 1500,
    "bus_locations_deleted": 300,
    "trips_archived": 25
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
-- Old user locations (to be deleted)
SELECT COUNT(*) FROM user_locations
WHERE created_at < NOW() - INTERVAL 30 DAY;

-- Old bus locations (to be deleted)
SELECT COUNT(*) FROM bus_locations
WHERE calculated_at < NOW() - INTERVAL 7 DAY;

-- Old completed trips (to be archived)
SELECT COUNT(*) FROM bus_trips
WHERE status = 'completed'
AND actual_ended_at < NOW() - INTERVAL 90 DAY;
```

## Monitoring

### Log Location

Logs are stored in: `storage/logs/laravel.log`

### Key Log Messages

```
[2026-02-27 00:05:00] local.INFO: Daily cleanup completed
[2026-02-27 00:05:00] local.INFO: Completed 25 expired trips.
[2026-02-27 00:05:00] local.INFO: Deleted 1500 old user locations.
```

### Health Monitoring

Create a monitoring endpoint to track cleanup health:

```php
Route::get('/api/cleanup-stats', function () {
    return response()->json([
        'user_locations_count' => DB::table('user_locations')->count(),
        'bus_locations_count' => DB::table('bus_locations')->count(),
        'trips_count' => DB::table('bus_trips')->count(),
    ]);
});
```
