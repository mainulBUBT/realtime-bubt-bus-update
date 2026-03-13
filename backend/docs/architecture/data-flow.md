# Data Flow

## Overview

This document explains how data flows through the BUBT Bus Tracker system, from user GPS updates to the map display.

## Real-Time Location Tracking Flow

```
┌─────────────┐
│   Frontend   │
│  (Browser)   │
└──────┬──────┘
       │
       │ 1. User GPS Update
       │    (watchPosition - every ~1-5s)
       ▼
┌─────────────────────────────────────────────────────────┐
│                    Backend API                          │
│                                                           │
│  ┌────────────────┐                                   │
│  │ POST            │                                   │
│  │ /api/save-      │  LocationController.php            │
│  │   location      │  - Validates banned users            │
│  │                 │  - Gets/Creates active trip           │
│  │                 │  - Stores to UserLocation            │
│  │                 │  - Updates BusActiveUser           │
│  │                 │  - Updates cache                    │
│  └────────┬───────┘                                   │
│           │                                             │
│           │ 2. Check threshold (≥2 users?)             │
│           │    AND rate limit (1s cooldown)            │
│           ▼                                             │
│  ┌────────────────┐                                   │
│  │ Queue Job       │                                   │
│  │ CalculateBus    │  - Fetches recent locations       │
│  │ LocationJob     │  - Filters by route proximity     │
│  │                 │  - Takes top 15 users              │
│  │                 │  - Calculates average position     │
│  │                 │  - Saves to BusLocation           │
│  └────────┬───────┘                                   │
│           │                                             │
│           │ 3. Broadcast Update                         │
│           ▼                                             │
│  ┌────────────────┐                                   │
│  │ Event           │                                   │
│  │ BusLocationUp    │  Laravel Reverb (WebSocket)        │
│  │ dated           │  - Broadcasts to 'bus.{id}' channel│
│  │                 │  - Includes: lat, lng, users        │
│  └────────┬───────┘                                   │
└───────────┼─────────────────────────────────────────┘
            │
            │ 4. WebSocket Event
            ▼
┌─────────────────────────────────────────────────────────┐
│                   Frontend (Browser)                     │
│                                                           │
│  ┌────────────────┐                                   │
│  │ Echo Listener   │  index.blade.php                  │
│  │ (bus.{id})      │  - Listens for events             │
│  │                 │  - Receives update                 │
│  └────────┬───────┘                                   │
│           │                                             │
│           │ 5. Update Marker                            │
│           ▼                                             │
│  ┌────────────────┐                                   │
│  │ updateBusMarker │  - Starts animation               │
│  │                 │  - Interpolates positions          │
│  │                 │  - Updates marker on map          │
│  │                 │  - Completes when done            │
│  └────────────────┘                                   │
│           │                                             │
│           ▼                                             │
│  ┌────────────────┐                                   │
│  │ Leaflet Map     │  - Marker glides smoothly         │
│  │                 │  - User sees "live movement"      │
│  └────────────────┘                                   │
└─────────────────────────────────────────────────────────┘
```

## User Join/Leave Bus Flow

```
User clicks "I'm on this bus"
        │
        ▼
┌────────────────────────┐
│ POST /api/confirm-bus   │
│ LocationController       │
└───────────┬───────────────┘
            │
            │ 1. Get or create active trip
            ▼
    ┌───────────────┐
    │ Bus::          │
    │ getOrCreate   │
    │ ActiveTrip()  │
    └───────┬───────┘
            │
            │ Returns BusTrip (status: active)
            ▼
    ┌───────────────┐
    │ Create        │
    │ BusActiveUser│
    │ (with trip_id)│
    └───────────────┘
            │
            │ 2. Remove from other buses
            ▼
    Delete old BusActiveUser records
            │
            ▼
        User added to trip
        (Can now track location)
```

## Trip Lifecycle Flow

```
┌─────────────┐
│ Trip Created │
│ (status:     │
│  pending)    │
└──────┬──────┘
       │
       │ First user joins
       ▼
┌─────────────┐
│ Trip Active  │
│ (status:     │
│  active)     │
└──────┬──────┘
       │
       │ Users tracking...
       │
       ├─ No users for 10+ minutes
       │
       ├─ Past schedule time + 4 hours
       │
       ▼
┌─────────────┐
│ Trip         │
│ Completed    │
│ (status:     │
│  completed)  │
└─────────────┘
       │
       │ Data retained for 90 days
       │
       ▼
┌─────────────┐
│ Trip         │
│ Archived     │
│ (soft-delete) │
└─────────────┘
```

## Scheduled Cleanup Flow

```
Every 5 minutes:
┌───────────────────────┐
│ CompleteExpiredTripsJob │
│ - Find active trips      │
│ - Check shouldComplete() │
│ - Mark as completed      │
└─────────────────────────┘

Every 2 minutes:
┌───────────────────────┐
│ CleanupInactiveUsersJob │
│ - Find users inactive   │
│   for 120+ seconds      │
│ - Remove from tracking   │
└─────────────────────────┘

Daily at 12:05 AM:
┌───────────────────────┐
│ DailyCleanupJob        │
│ - Delete old user_locs  │
│ - Delete old bus_locs   │
│ - Archive old trips     │
└─────────────────────────┘
```

## Cache Layer

```
┌──────────────┐
│ Active Users │
│ Cache        │
│              │
│ Key:         │
│ active_users_│
│ bus_{bus_id} │
│              │
│ TTL: 60 sec  │
└──────────────┘
```

Used for quick access to active users list without querying database.

## Broadcast Channels

```
Laravel Reverb

├── bus.{busId}
│   └── BusLocationUpdated event
│       ├── lat
│       ├── lng
│       └── active_users
│
└── Presence channels (optional)
    └── user.{userId}
```

## API Rate Limiting

```
Location Calculation Rate Limit:
┌──────────────────────────────┐
│ Cache Lock:                    │
│ "calculating_bus_{busId}"    │
│ TTL: 1 second                  │
│                                │
│ Prevents excessive            │
│ location calculations         │
└──────────────────────────────┘
```

This ensures calculations happen at most once per second per bus, even if many users send locations simultaneously.
