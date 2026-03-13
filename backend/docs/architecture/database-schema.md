# Database Schema

## Overview

This document describes the database schema for the BUBT Bus Tracker application, including all tables, relationships, and migrations.

## Core Tables

### users

User authentication and profile data.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| name | VARCHAR(255) | |
| email | VARCHAR(255) | Unique |
| password | VARCHAR(255) | Hashed |
| avatar | VARCHAR(255) | Google profile image URL |
| is_admin | BOOLEAN | Default: false |
| is_banned | BOOLEAN | Default: false |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### buses

Bus information and configuration.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| name | VARCHAR(255) | e.g., "Buriganga" |
| code | VARCHAR(10) | e.g., "B1" |
| direction | ENUM | 'A_TO_Z' or 'Z_TO_A' |
| start_location | VARCHAR(255) | |
| end_location | VARCHAR(255) | |
| capacity | INTEGER | |
| is_active | BOOLEAN | Default: true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | Soft delete |

**Relationships**:
- `hasMany` → bus_routes
- `hasMany` → bus_stops
- `hasMany` → bus_schedules
- `hasMany` → bus_trips
- `hasMany` → bus_active_users
- `hasMany` → user_locations
- `hasMany` → bus_locations

### bus_trips

**NEW** - Represents actual trip instances.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | |
| bus_schedule_id | BIGINT UNSIGNED (FK) | Nullable |
| trip_date | DATE | e.g., 2026-02-27 |
| planned_departure_time | TIME | From schedule |
| actual_started_at | TIMESTAMP | When first user joined |
| actual_ended_at | TIMESTAMP | When trip completed |
| status | ENUM | 'pending', 'active', 'completed', 'cancelled' |
| total_users | INTEGER | Count of unique users |
| start_lat | DECIMAL(10,7) | Starting GPS |
| start_lng | DECIMAL(10,7) | Starting GPS |
| end_lat | DECIMAL(10,7) | Ending GPS |
| end_lng | DECIMAL(10,7) | Ending GPS |
| route_snapshot | JSON | Snapshot of route data |
| notes | TEXT | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | Soft delete |

**Relationships**:
- `belongsTo` → bus
- `belongsTo` → bus_schedule
- `hasMany` → bus_active_users
- `hasMany` → user_locations
- `hasMany` → bus_locations

**Indexes**:
- `bus_trips_bus_id_trip_date_status_index`
- `bus_trips_bus_schedule_id_trip_date_index`
- `bus_trips_status_index`
- `bus_trips_trip_date_index`

### bus_active_users

Tracks which users are actively on which buses (for which trip).

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | |
| user_id | BIGINT UNSIGNED (FK) | |
| **bus_trip_id** | BIGINT UNSIGNED (FK) | **NEW** - Links to trip |
| last_seen_at | TIMESTAMP | Updated on each location save |
| joined_at | TIMESTAMP | When user joined the trip |

**Relationships**:
- `belongsTo` → bus
- `belongsTo` → user
- `belongsTo` → bus_trip

**Indexes**:
- `bus_active_users_bus_trip_id_user_id_unique` (unique)

### user_locations

Raw GPS coordinates from users.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| user_id | BIGINT UNSIGNED (FK) | |
| bus_id | BIGINT UNSIGNED (FK) | |
| **bus_trip_id** | BIGINT UNSIGNED (FK) | **NEW** - Links to trip |
| lat | DECIMAL(10,7) | Latitude |
| lng | DECIMAL(10,7) | Longitude |
| accuracy | DECIMAL(8,2) | GPS accuracy in meters |
| speed | DECIMAL(8,2) | Speed in m/s |
| created_at | TIMESTAMP | When location was recorded |

**Relationships**:
- `belongsTo` → user
- `belongsTo` → bus
- `belongsTo` → bus_trip

**Indexes**:
- `user_locations_bus_trip_id_created_at_index`
- `user_locations_user_id_bus_trip_id_index`

### bus_locations

Calculated bus positions (averaged from user locations).

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | |
| **bus_trip_id** | BIGINT UNSIGNED (FK) | **NEW** - Links to trip |
| lat | DECIMAL(10,7) | Calculated latitude |
| lng | DECIMAL(10,7) | Calculated longitude |
| active_users_count | INTEGER | Number of users used |
| accuracy_score | DECIMAL(3,2) | Confidence score |
| calculated_at | TIMESTAMP | When position was calculated |

**Relationships**:
- `belongsTo` → bus
- `belongsTo` → bus_trip

**Indexes**:
- `bus_locations_bus_trip_id_calculated_at_index`

## Supporting Tables

### bus_schedules

Scheduled departure times for buses.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED (PK) | |
| bus_id | BIGINT UNSIGNED (FK) | |
| schedule_period_id | BIGINT UNSIGNED (FK) | |
| bus_route_id | BIGINT UNSIGNED (FK) | |
| departure_time | TIME | e.g., 07:30:00 |
| weekdays | JSON | {"mon":true, "tue":true, ...} |
| specific_date | DATE | For one-off schedules |
| is_active | BOOLEAN | |

### bus_routes

Route definitions with stop sequences.

### bus_stops

Individual stops along routes.

### schedule_periods

Date ranges for schedules (e.g., "Spring 2026", "Ramadan 2026").

### system_settings

Configuration values for the application.

| Key | Value | Description |
|-----|-------|-------------|
| `min_active_users` | 2 | Minimum users for location calculation |
| `inactive_user_timeout` | 120 | Seconds before marking inactive |
| `cleanup_old_user_locations_days` | 30 | Data retention |
| `cleanup_old_bus_locations_days` | 7 | Data retention |
| `cleanup_completed_trips_days` | 90 | Data retention |

## Migration Files

### Core Schema

- `0001_01_01_000000_create_users_table.php`
- `2025_12_06_000002_create_buses_table.php`
- `2025_12_06_000007_create_user_locations_table.php`
- `2025_12_06_000007_create_bus_active_users_table.php`
- `2025_12_06_000008_create_bus_locations_table.php`

### Multi-Trip Tracking (NEW - 2026-02-27)

- `2026_02_27_000001_create_bus_trips_table.php`
- `2026_02_27_000002_add_trip_id_to_bus_active_users.php`
- `2026_02_27_000003_add_trip_id_to_user_locations.php`
- `2026_02_27_000004_add_trip_id_to_bus_locations.php`

## Entity Relationship Diagram

```
┌─────────────┐
│    buses    │
└──────┬──────┘
       │
       ├─────────────────────────────────────┐
       │                                     │
       ▼                                     ▼
┌──────────────┐                    ┌──────────────┐
│ bus_schedules │                    │  bus_trips   │
└──────────────┘                    └──────┬───────┘
                                             │
                    ┌────────────────────────┴────────────┐
                    │                                           │
                    ▼                                           ▼
          ┌─────────────────┐                        ┌─────────────────┐
          │ bus_active_users │                        │ user_locations   │
          └─────────────────┘                        └─────────────────┘
                    │                                           │
                    └──────────────────────┬────────────────────┘
                                           │
                                           ▼
                                  ┌──────────────┐
                                  │ bus_locations │
                                  └──────────────┘
```
