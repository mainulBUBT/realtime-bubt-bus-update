# Data Flow

## Overview

This document explains how data flows through the BUBT Bus Tracker system, from driver GPS updates to the map display.

## Real-Time Location Tracking Flow

```
┌─────────────┐
│   Driver    │
│   Mobile    │
└──────┬──────┘
       │
       │ 1. Driver GPS Location
       │    (background, every ~5-10s)
       ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend API                               │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ POST /api/driver/location                            │    │
│  │ LocationController.php                                │    │
│  │ - Validates driver authentication (role:driver)      │    │
│  │ - Gets driver's active trip                          │    │
│  │ - Stores location to Location model                  │    │
│  │ - Updates trip's current_lat/lng                    │    │
│  └─────────────┬───────────────────────────────────────┘    │
│                │                                             │
│                │ 2. Broadcast Update                          │
│                ▼                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Event                                                  │    │
│  │ BusLocationUpdated                                     │    │
│  │ Laravel Reverb (WebSocket)                            │    │
│  │ - Broadcasts to 'bus.{busId}' channel                │    │
│  │ - Includes: lat, lng, trip_id                         │    │
│  └─────────────┬───────────────────────────────────────┘    │
└────────────────┼─────────────────────────────────────────────┘
                │
                │ 3. WebSocket Event
                ▼
┌─────────────────────────────────────────────────────────────┐
│                   Frontend (Browser/Mobile)                   │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Echo Listener                                         │    │
│  │ (bus.{busId})                                        │    │
│  │ - Listens for events                                 │    │
│  │ - Receives update                                    │    │
│  └─────────────┬───────────────────────────────────────┘    │
│                │                                             │
│                │ 4. Update Marker                             │
│                ▼                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Vue Map Component                                     │    │
│  │ - Animates marker to new position                    │    │
│  │ - Shows trip status                                  │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

## Trip Lifecycle Flow

```
┌─────────────┐
│ Trip Created │
│ (status:     │
│  pending)    │
└──────┬──────┘
       │
       │ Driver starts trip
       │ POST /api/driver/trips/start
       ▼
┌─────────────┐
│ Trip Ongoing │
│ (status:     │
│  ongoing)    │
└──────┬──────┘
       │
       │ Driver sends locations
       │ (every ~5-10 seconds)
       │
       ├─ Driver ends trip
       │   POST /api/driver/trips/{trip}/end
       │
       ├─ No location updates for 10+ minutes
       │   CompleteExpiredTripsJob runs
       │
       └─ Past schedule time + buffer hours
           CompleteExpiredTripsJob runs
           │
           ▼
┌─────────────┐
│ Trip         │
│ Completed    │
│ (status:     │
│  completed)  │
└─────────────┘
       │
       │ Data retained per cleanup settings
       │
       ▼
┌─────────────┐
│ Trip         │
│ Archived     │
│ (soft-delete) │
└─────────────┘
```

## Driver Start Trip Flow

```
Driver clicks "Start Trip"
        │
        ▼
┌─────────────────────────────────────┐
│ POST /api/driver/trips/start        │
│ TripController@start                 │
└───────────────┬─────────────────────┘
               │
               │ 1. Validate driver role
               ▼
       ┌───────────────┐
       │ Create Trip   │
       │ - bus_id      │
       │ - route_id    │
       │ - driver_id   │
       │ - trip_date   │
       │ - status:     │
       │   'ongoing'   │
       │ - started_at  │
       └───────┬───────┘
               │
               │ 2. Broadcast trip started
               ▼
       ┌───────────────┐
       │ BusTripEnded  │
       │ Event         │
       │ (broadcast to │
       │  bus.{busId}) │
       └───────┬───────┘
               │
               ▼
         Trip ongoing
         (Students can track)
```

## Student Tracking Flow

```
Student selects route
        │
        ▼
┌─────────────────────────────────────┐
│ GET /api/student/routes             │
│ GET /api/student/routes/{id}       │
└───────────────┬─────────────────────┘
               │
               │ View active trips
               ▼
┌─────────────────────────────────────┐
│ GET /api/student/trips/active      │
│ (Shows currently running trips)     │
└───────────────┬─────────────────────┘
               │
               │ Select trip to track
               ▼
┌─────────────────────────────────────┐
│ GET /api/student/trips/{tripId}/   │
│      latest-location                │
│ (Poll for bus position)             │
└─────────────────────────────────────┘
               │
               │ Real-time updates via
               │ WebSocket (bus.{busId})
               ▼
         Student sees bus
         on map with live
         position updates
```

## Scheduled Cleanup Flow

```
Every 5 minutes:
┌───────────────────────────────┐
│ CompleteExpiredTripsJob        │
│ - Find ongoing trips          │
│ - Check inactivity timeout    │
│   (no location for 10+ min)  │
│ - Check schedule + buffer     │
│   (> 4 hours past schedule)   │
│ - Mark as completed           │
│ - Broadcast BusTripEnded      │
└───────────────────────────────┘

Daily at 12:05 AM:
┌───────────────────────────────┐
│ DailyCleanupJob                │
│ - Archive completed trips      │
│   older than 90 days          │
└───────────────────────────────┘

Daily at 02:00 AM:
┌───────────────────────────────┐
│ CleanOldLocations(30)         │
│ - Delete locations older      │
│   than 30 days                │
└───────────────────────────────┘
```

## Broadcast Channels

```
Laravel Reverb

├── bus.{busId}
│   └── BusLocationUpdated event
│       ├── lat
│       ├── lng
│       ├── trip_id
│       └── calculated_at
│
└── Presence channels (optional)
    └── user.{userId}
```

## Events

### BusLocationUpdated
Dispatched when a driver's location is received.

```php
broadcast(new BusLocationUpdated($busId, [
    'lat' => $location->lat,
    'lng' => $location->lng,
    'trip_id' => $trip->id,
    'calculated_at' => now()
]));
```

### BusTripEnded
Dispatched when a trip ends (driver ends trip or auto-completed).

```php
broadcast(new BusTripEnded($trip->id, $trip->bus_id));
```
