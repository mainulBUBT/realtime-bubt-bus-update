# Multi-Trip Tracking Feature

## Overview

The multi-trip tracking feature allows users to track buses multiple times per day (e.g., morning trip, afternoon trip) while keeping data properly isolated between trips.

## How It Works

### Trip Model

Each tracking session is associated with a `BusTrip` record that includes:
- **Trip Date**: The date the trip occurred
- **Bus Schedule**: The scheduled departure time (if available)
- **Status**: `pending` → `active` → `completed` / `cancelled`
- **Timestamps**: When the trip actually started and ended

### Trip Lifecycle

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Pending    │ -> │   Active    │ -> │  Completed   │
│  (Created)  │    │  (Users     │    │  (No users   │
│             │    │   Joining)  │    │   for X min) │
└─────────────┘    └─────────────┘    └─────────────┘
```

### Automatic Trip Creation

When a user starts tracking a bus:
1. System checks for an existing active trip for today
2. If none exists, creates a new trip with:
   - Today's date
   - Matching schedule (if found within ±2 hours)
   - Status set to `active`

### Automatic Trip Completion

Trips are automatically completed when:
- **No active users** for X minutes (default: 10 minutes)
- **Past schedule time** + buffer hours (default: 4 hours)

This runs every 5 minutes via the `CompleteExpiredTripsJob`.

## Data Isolation

Each trip isolates tracking data:

| Table | Trip Association | Benefit |
|-------|-----------------|---------|
| `BusActiveUser` | `bus_trip_id` | Know which trip user was on |
| `UserLocation` | `bus_trip_id` | GPS locations per trip |
| `BusLocation` | `bus_trip_id` | Calculated positions per trip |

## Multi-Trip Example

```
Date: 2026-02-27

Trip A (Morning)          Trip B (Afternoon)
┌─────────────┐          ┌─────────────┐
│ Bus: B1     │          │ Bus: B1     │
│ Time: 8 AM  │          │ Time: 2 PM  │
│ Status:     │          │ Status:     │
│ completed   │          │ active      │
│             │          │             │
│ 5 users     │          │ 3 users     │
└─────────────┘          └─────────────┘

No data mixing - completely separate tracking sessions!
```

## API Changes

### confirmBus Endpoint

Now associates tracking with a trip:

```javascript
// POST /api/confirm-bus
{
    "bus_id": 5,
    "trip_id": 123  // Automatically created
}
```

### saveLocation Endpoint

Now includes trip_id with location data:

```javascript
// POST /api/save-location
{
    "bus_id": 5,
    "trip_id": 123,
    "lat": 23.8103,
    "lng": 90.4125
}
```

## User Experience

### From User's Perspective

1. **Morning**: User clicks "I'm on this bus" → Trip A created
2. **Afternoon**: Same user clicks "I'm on this bus" → Trip B created
3. **Next Day**: User logs in → No active trips from yesterday
4. **Clean State**: Only current day's trips are active

### Frontend Behavior

The frontend automatically:
- Shows only active trips for today
- Displays trip status (active/completed)
- Clears old trip indicators automatically

## Configuration

| Setting | Default | Description |
|---------|---------|-------------|
| `auto_complete_trips_after_minutes` | 10 | Complete trips with no activity for X minutes |
| `trip_duration_buffer_hours` | 4 | Max hours to keep trip active after schedule time |

## Troubleshooting

### Trip Not Auto-Completing

1. Check scheduler is running: `php artisan schedule:list`
2. Verify settings: `auto_complete_trips_after_minutes`
3. Check logs: `storage/logs/laravel.log`

### Multiple Trips Showing

If you see multiple active trips:
1. Check `shouldComplete()` logic in BusTrip model
2. Verify `CompleteExpiredTripsJob` is running
3. Check for active users in database
