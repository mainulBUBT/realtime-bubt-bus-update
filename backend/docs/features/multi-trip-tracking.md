# Multi-Trip Tracking Feature

## Overview

The multi-trip tracking feature allows drivers to operate multiple trips per day (e.g., morning shift, afternoon shift) with automatic trip lifecycle management.

## How It Works

### Trip Model

Each tracking session is associated with a `Trip` record that includes:
- **Trip Date**: The date the trip occurred
- **Bus Schedule**: The scheduled departure time (if available)
- **Driver Assignment**: Links trip to a specific driver
- **Status**: `pending` → `ongoing` → `completed` / `cancelled`
- **Timestamps**: When the trip started and ended

### Trip Lifecycle

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Pending    │ -> │   Ongoing   │ -> │  Completed   │
│  (Created)  │    │  (Started)  │    │  (Ended)     │
└─────────────┘    └─────────────┘    └─────────────┘
                       │
                       │ Driver ends trip OR
                       │ CompleteExpiredTripsJob
                       ▼
              ┌─────────────┐
              │  Cancelled  │
              │ (Optional)  │
              └─────────────┘
```

### Automatic Trip Creation

When a driver starts a trip:
1. Driver selects bus and route
2. System creates a new trip with:
   - Today's date
   - Selected bus_id, route_id, driver_id
   - Status set to `ongoing`
   - `started_at` timestamp

### Automatic Trip Completion

Trips are automatically completed when:
- **Driver ends trip** manually via `POST /api/driver/trips/{trip}/end`
- **No location updates** for X minutes (default: 10 minutes) via `CompleteExpiredTripsJob`
- **Past schedule time** + buffer hours (default: 4 hours)

This runs every 5 minutes via the `CompleteExpiredTripsJob`.

## Trip Data Isolation

Each trip stores its own tracking data:

| Field | Association | Purpose |
|-------|-------------|---------|
| `trip_id` | locations table | GPS locations per trip |
| `trip_id` | trips table | Track trip history |

## Multi-Trip Example

```
Date: 2026-02-27

Morning Trip            Afternoon Trip
┌─────────────┐        ┌─────────────┐
│ Bus: B1     │        │ Bus: B1     │
│ Driver: Ali │        │ Driver: Rahim│
│ Time: 8 AM  │        │ Time: 2 PM  │
│ Status:     │        │ Status:     │
│ completed   │        │ ongoing      │
└─────────────┘        └─────────────┘

No data mixing - completely separate trips!
```

## API Endpoints

### Start Trip

```javascript
// POST /api/driver/trips/start
{
    "bus_id": 5,
    "route_id": 3,
    "schedule_id": 7  // optional
}
```

### Submit Location

```javascript
// POST /api/driver/location
{
    "trip_id": 123,
    "lat": 23.8103,
    "lng": 90.4125,
    "speed": 15
}
```

### End Trip

```javascript
// POST /api/driver/trips/{trip}/end
// No body required
```

## User Experience

### From Driver's Perspective

1. **Morning**: Driver starts trip → Trip A created
2. **Afternoon**: Driver starts another trip → Trip B created
3. **Evening**: Driver ends trip → Trip B completed
4. **Next Day**: New day, new trips

### From Student's Perspective

1. Student views active trips → Sees running buses
2. Student selects a trip to track
3. Student receives real-time updates via WebSocket
4. Trip auto-completes when driver ends or after inactivity

## Configuration

| Setting | Default | Description |
|---------|---------|-------------|
| `auto_complete_trips_after_minutes` | 10 | Complete trips with no activity for X minutes |
| `trip_duration_buffer_hours` | 4 | Max hours to keep trip active after schedule time |

## Troubleshooting

### Trip Not Auto-Completing

1. Check scheduler is running: `php artisan schedule:list`
2. Verify `CompleteExpiredTripsJob` is in the queue worker
3. Check logs: `storage/logs/laravel.log`

### Multiple Active Trips for Same Bus

If you see multiple active trips:
1. Check `shouldComplete()` logic in Trip model
2. Verify `CompleteExpiredTripsJob` is running every 5 minutes
3. Each driver should have their own active trip
