# BUBT Bus Tracker - Knowledge Base Index

## Project Overview
**BUBT Bus Tracker** is a real-time bus tracking system. Built with Laravel (backend) and Vue.js (frontend), with Capacitor for mobile apps.

**Tech Stack:**
- Backend: Laravel 11, PHP 8.2+
- Frontend: Vue 3, Vite
- Mobile: Capacitor (iOS/Android)
- Real-time: Laravel Reverb (WebSocket)
- Database: MySQL/MariaDB 5.7+

---

## Documentation Files

| File | Purpose |
|------|---------|
| `docs/README.md` | Documentation index |
| `docs/CHANGELOG.md` | Version history (v1.0.0, v1.1.0) |
| `docs/FEATURES.md` | Comprehensive feature reference |
| `docs/installation.md` | Setup instructions |
| `docs/deployment.md` | Production deployment guide |
| `docs/architecture/database-schema.md` | Database tables and relationships |
| `docs/architecture/data-flow.md` | System data flow diagrams |
| `docs/features/real-time-updates.md` | Bus movement animation system |
| `docs/features/daily-cleanup.md` | Automated data cleanup |
| `docs/features/multi-trip-tracking.md` | Multi-trip per day support |

---

## Core Entities

### Trip
Represents a single trip instance.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT | Primary key |
| bus_id | FK | References buses |
| route_id | FK | References routes |
| driver_id | FK | References users |
| trip_date | DATE | e.g., 2026-02-27 |
| status | ENUM | pending, active, completed, cancelled |
| started_at | TIMESTAMP | When trip started |
| ended_at | TIMESTAMP | When completed |

**Status Flow:** `pending → active → completed/cancelled`

### Bus
| Field | Type | Notes |
|-------|------|-------|
| name | VARCHAR | e.g., "Buriganga" |
| plate_number | VARCHAR | Vehicle plate |
| capacity | INT | Seat capacity |
| status | ENUM | active, maintenance, inactive |

### Location
Stores GPS coordinates from drivers with `trip_id`.

---

## API Endpoints

### Public
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/settings` - Get app settings

### Driver (role:driver)
- `GET /api/driver/buses` - List available buses
- `GET /api/driver/routes` - List available routes
- `POST /api/driver/trips/start` - Start a new trip
- `POST /api/driver/trips/{trip}/end` - End a trip
- `GET /api/driver/trips/current` - Get current active trip
- `GET /api/driver/trips/history` - Get trip history
- `POST /api/driver/location` - Submit GPS location
- `POST /api/driver/location/batch` - Submit batch GPS locations

### Student (role:student)
- `GET /api/student/routes` - List available routes
- `GET /api/student/routes/{id}` - Get route details
- `GET /api/student/trips/active` - Get active trips
- `GET /api/student/trips/{tripId}/locations` - Get trip locations
- `GET /api/student/trips/{tripId}/latest-location` - Get latest location
- `GET /api/student/schedules` - Get bus schedules

### Admin (role:admin)
- `GET/POST/PUT/DELETE /api/admin/buses` - Bus CRUD
- `GET/POST/PUT/DELETE /api/admin/routes` - Route CRUD
- `GET/POST/PUT/DELETE /api/admin/schedules` - Schedule CRUD

---

## Scheduled Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `DailyCleanupJob` | 12:05 AM daily | Archive completed trips > 90 days |
| `CleanOldLocations` | 02:00 AM daily | Delete locations > 30 days |
| `CompleteExpiredTripsJob` | Every 5 min | Auto-complete stale trips |

**Scheduler Location:** `routes/console.php` (Laravel 11+ style)

---

## System Settings

Stored in `settings` table with key-value structure:

| Key | Purpose |
|-----|---------|
| `app_name` | Application name |
| `app_version` | Version number |
| `maintenance_mode` | Maintenance status |

---

## Real-Time Flow

```
Driver GPS → POST /api/driver/location → LocationController
    → Store location → broadcast BusLocationUpdated
    → Laravel Reverb → Frontend Echo listener
    → Vue map updates marker position
```

---

## Events

### BusLocationUpdated
Broadcast via `bus.{busId}` channel when driver location updates.

### BusTripEnded
Broadcast via `bus.{busId}` channel when trip ends.

---

## Key Directories

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Api/Driver/` | Driver API controllers |
| `app/Http/Controllers/Api/Student/` | Student API controllers |
| `app/Http/Controllers/Api/Admin/` | Admin API controllers |
| `app/Models/` | Eloquent models (Trip, Bus, Location, Route, etc.) |
| `app/Jobs/` | Queue jobs |
| `app/Events/` | Event classes (BusLocationUpdated, BusTripEnded) |
| `routes/console.php` | Scheduler definitions |
| `frontend/src/` | Vue.js frontend |
| `capacitor.config.ts` | Mobile app config |

---

## Recent Changes (v1.1.0 - 2026-04-17)

- Pull-to-refresh composable for mobile
- Driver dashboard UI improvements
- GPS bootstrap mode (200m accuracy for first fix)
- Scheduler moved to `routes/console.php`
- Notification campaign system
- Removed deprecated jobs (CleanupInactiveUsersJob, CalculateBusLocationJob)

---

## Capacitor Mobile Apps

- **Student App**: `frontend/capacitor-student/`
- **Driver App**: `frontend/capacitor-driver/`
- **App ID**: `com.bubt.bustracker`
