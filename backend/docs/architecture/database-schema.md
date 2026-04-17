# Database Schema

## Overview

This document describes the actual database schema for the BUBT Bus Tracker application, including all tables, relationships, and migrations.

## Core Tables

### users

User accounts with role-based access.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| role | ENUM | 'admin', 'driver', 'student' |
| name | VARCHAR(255) | |
| email | VARCHAR(255) | Unique |
| phone | VARCHAR(20) | Optional |
| avatar | VARCHAR(255) | Google profile image URL |
| email_verified_at | TIMESTAMP | |
| password | VARCHAR(255) | Hashed |
| remember_token | VARCHAR(100) | |
| fcm_token | VARCHAR(255) | Firebase Cloud Messaging token |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Note:** The original `is_admin` and `is_banned` fields have been replaced with role-based access.

### buses

Bus vehicle information.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| plate_number | VARCHAR(255) | Unique |
| device_id | VARCHAR(255) | Unique, nullable |
| capacity | INT | Seat capacity |
| status | ENUM | 'active', 'maintenance', 'inactive' |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### trips

Represents actual trip instances.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | References buses |
| route_id | BIGINT UNSIGNED (FK) | References routes |
| driver_id | BIGINT UNSIGNED (FK) | References users |
| schedule_id | BIGINT UNSIGNED (FK) | Nullable, references schedules |
| trip_date | DATE | e.g., 2026-02-27 |
| status | ENUM | 'pending', 'ongoing', 'completed', 'cancelled' |
| started_at | TIMESTAMP | When trip started |
| ended_at | TIMESTAMP | When trip ended |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `belongsTo` → Bus
- `belongsTo` → Route
- `belongsTo` → User (driver)
- `belongsTo` → Schedule
- `hasMany` → Locations

**Indexes**:
- `trips_bus_id_trip_date_status_index`
- `trips_driver_id_index`
- `trips_status_index`

### locations

GPS coordinates from drivers during trips.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| trip_id | BIGINT UNSIGNED (FK) | References trips |
| bus_id | BIGINT UNSIGNED (FK) | References buses |
| lat | DECIMAL(10,7) | Latitude |
| lng | DECIMAL(10,7) | Longitude |
| speed | DECIMAL(8,2) | Speed in m/s, nullable |
| recorded_at | TIMESTAMP | When location was recorded |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `belongsTo` → Trip
- `belongsTo` → Bus

**Indexes**:
- `locations_trip_id_recorded_at_index`

## Supporting Tables

### routes

Route definitions with polyline coordinates.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| name | VARCHAR(255) | Route name |
| schedule_period_id | BIGINT UNSIGNED (FK) | References schedule_periods |
| direction | ENUM | 'up' or 'down' |
| origin_name | VARCHAR(255) | Starting point name |
| destination_name | VARCHAR(255) | End point name |
| polyline | JSON | Encoded polyline coordinates |
| is_active | BOOLEAN | Default: true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `hasMany` → RouteStops
- `hasMany` → Trips
- `belongsTo` → SchedulePeriod

### route_stops

Individual stops along routes.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| route_id | BIGINT UNSIGNED (FK) | References routes |
| name | VARCHAR(255) | Stop name |
| lat | DECIMAL(10,7) | Latitude |
| lng | DECIMAL(10,7) | Longitude |
| sequence | INT | Stop order in route |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `belongsTo` → Route

### schedules

Scheduled departure times for buses.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | References buses |
| route_id | BIGINT UNSIGNED (FK) | References routes |
| schedule_period_id | BIGINT UNSIGNED (FK) | References schedule_periods |
| departure_time | TIME | e.g., 07:30:00 |
| weekdays | JSON | Weekday flags {"mon":true,"tue":true,...} |
| is_active | BOOLEAN | Default: true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `belongsTo` → Bus
- `belongsTo` → Route
- `belongsTo` → SchedulePeriod
- `hasMany` → Trips

### schedule_periods

Date ranges for schedules (e.g., "Spring 2026", "Ramadan 2026").

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| name | VARCHAR(255) | Period name |
| start_date | DATE | |
| end_date | DATE | |
| is_active | BOOLEAN | Default: false |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Relationships**:
- `hasMany` → Routes
- `hasMany` → Schedules

### settings

Key-value configuration storage.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| key | VARCHAR(255) | Unique setting key |
| value | TEXT | Setting value |
| type | VARCHAR(50) | Data type (text, boolean, number, json) |
| group | VARCHAR(50) | Setting group (general, email, etc.) |
| description | VARCHAR(255) | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Indexes**:
- `settings_key_unique` (unique)
- `settings_group_index`

## Notification Tables

### notification_campaigns

Push notification campaigns.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| title | VARCHAR(255) | |
| body | TEXT | |
| type | VARCHAR(50) | Campaign type |
| scheduled_at | TIMESTAMP | When to send |
| sent_at | TIMESTAMP | When actually sent |
| status | ENUM | 'draft', 'scheduled', 'sent', 'cancelled' |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### notification_campaign_recipients

Recipients for notification campaigns.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| campaign_id | BIGINT UNSIGNED (FK) | References notification_campaigns |
| user_id | BIGINT UNSIGNED (FK) | References users |
| sent_at | TIMESTAMP | |
| delivered_at | TIMESTAMP | |
| read_at | TIMESTAMP | |
| created_at | TIMESTAMP | |

### notification_campaign_reads

Track read status for notifications.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| campaign_id | BIGINT UNSIGNED (FK) | |
| user_id | BIGINT UNSIGNED (FK) | |
| read_at | TIMESTAMP | |
| created_at | TIMESTAMP | |

## Entity Relationship Diagram

```
┌─────────────┐
│    users    │
└──────┬──────┘
       │
       ├──────────────────────────┐
       │                          │
       ▼                          ▼
┌─────────────┐          ┌─────────────┐
│    trips    │          │ notification │
│  (driver)   │          │  campaigns   │
└──────┬──────┘          └──────┬──────┘
       │                        │
       │                         └──────┐
       ▼                               ▼
┌─────────────┐              ┌─────────────────┐
│    buses    │              │ notification_   │
└──────┬──────┘              │ recipients      │
       │                    └─────────────────┘
       ├────────────┐
       ▼            ▼
┌───────────┐  ┌───────────┐
│  routes   │  │ locations │
└─────┬─────┘  └───────────┘
      │
      ▼
┌───────────┐
│route_stops│
└───────────┘
      │
      ▼
┌─────────────────┐
│schedule_periods │
└─────────────────┘
      │
      ▼
┌───────────┐
│ schedules │
└───────────┘
```

## Key Differences from Old Documentation

The actual schema differs from the old documentation:

1. **Table Naming**: Uses `trips` instead of `bus_trips`, `routes` instead of `bus_routes`, etc.
2. **No `bus_active_users` table**: Student tracking is handled via trip associations
3. **Consolidated Location Storage**: Single `locations` table instead of separate `user_locations` and `bus_locations`
4. **Role-based Users**: Users have `role` enum (admin/driver/student) rather than `is_admin` boolean
5. **Notification System**: New push notification campaign tables

## Migration Files

### Core Schema

| Migration | Purpose |
|-----------|---------|
| `0001_01_01_000000_create_users_table.php` | Base users table |
| `0001_01_01_000001_create_cache_table.php` | Cache table |
| `0001_01_01_000002_create_jobs_table.php` | Jobs table |
| `2026_02_24_180122_create_personal_access_tokens_table.php` | Sanctum tokens |
| `2026_02_28_104640_modify_users_table_add_role_and_fcm.php` | User roles & FCM |
| `2026_02_28_104656_create_schedule_periods_table.php` | Schedule periods |
| `2026_02_28_104720_create_buses_table.php` | Buses |
| `2026_02_28_104743_create_routes_table.php` | Routes |
| `2026_02_28_104746_create_route_stops_table.php` | Route stops |
| `2026_02_28_104747_create_schedules_table.php` | Schedules |
| `2026_02_28_104749_create_trips_table.php` | Trips |
| `2026_02_28_104751_create_locations_table.php` | Locations |
| `2026_03_06_101827_create_settings_table.php` | Settings |
| `2026_04_08_000003_create_notification_campaigns_tables.php` | Notifications |
