# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## Documentation Updates

### Updated
- `docs/FEATURES.md` - Complete rewrite of Location Tracking System section, removed BusRoute/BusSchedule/BusLocation/BusActiveUser/SystemSetting references, updated to use actual Route/Schedule/Location/Setting models
- `docs/architecture/database-schema.md` - Complete rewrite documenting actual tables: users (role enum), buses (plate_number, status), trips, locations, routes, route_stops, schedules, settings
- `docs/architecture/data-flow.md` - Complete rewrite reflecting driver-based GPS flow, removed UserLocation/BusActiveUser/BusLocation references, updated to reflect actual Trip lifecycle
- `docs/deployment.md` - Updated API endpoints section with actual driver/student/admin routes
- `docs/features/multi-trip-tracking.md` - Fixed BusTrip→Trip, removed non-existent table refs, updated API endpoints to actual driver routes
- `docs/features/real-time-updates.md` - Removed CalculateBusLocationJob references, updated to driver location flow
- `docs/features/daily-cleanup.md` - Fixed actual_ended_at→ended_at reference
- `docs/installation.md` - Updated system settings section to reflect actual Setting model
- `docs/README.md` - Updated overview to reflect driver-based tracking system
- `.kilo/docs-index.md` - Comprehensive knowledge base index updated

## [v1.1.0] - 2026-04-17

### Added

#### Frontend Features
- **Pull-to-Refresh**: Native touch-based refresh for mobile apps
  - Custom `usePullToRefresh` composable (`frontend/src/composables/usePullToRefresh.js`)
  - Implemented on Driver Dashboard, History, Student MapView, ScheduleList
  - 60px threshold with smooth animations
  
- **Driver Dashboard UI Improvements**:
  - Today's Trips + Total Trips stats in single card
  - Recent Activity section (last 3 trips)
  - Skeleton loading states
  - Fade transitions
  
- **Driver Active Trip GPS Bootstrap**:
  - Accepts up to 200m accuracy for first fixes
  - Exits bootstrap mode once good GPS acquired

#### Backend Changes
- **Scheduler**: Moved from `Console/Kernel.php` to `routes/console.php`
- **OSRM Integration**: Road distance calculation for stop approaching

### Removed / Fixed

#### Backend (Cleanup)
- `EndStaleTrips.php` - redundant with CompleteExpiredTripsJob
- `CalculateBusLocationJob.php` - broken, used non-existent models
- `CleanupInactiveUsersJob.php` - broken, used non-existent models
- `map.js`, `track.js`, `splash.js` - unused assets

- **DailyCleanupJob**: Simplified, uses hardcoded values instead of SystemSetting

### Documentation Updated
- `features/daily-cleanup.md` - Reflects current job structure
- `features/real-time-updates.md` - Added OSRM section

## [v1.0.0] - Initial Release

### Features

- Real-time bus tracking using crowd-sourced GPS
- Multi-trip tracking per bus per day
- WebSocket updates via Laravel Reverb
- Automated daily cleanup of old data
- Driver app with background location tracking
- Student app with live map view and schedules