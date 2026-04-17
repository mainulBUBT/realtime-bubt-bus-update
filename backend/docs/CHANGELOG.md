# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## Documentation Updates

### Updated
- `docs/FEATURES.md` - Fixed stale references to removed files and old API endpoints
- `docs/architecture/database-schema.md` - Documented actual table names and schema
- `docs/architecture/data-flow.md` - Fixed data flow to reflect actual implementation
- `docs/deployment.md` - Updated API endpoints section with actual routes
- `docs/features/multi-trip-tracking.md` - Fixed references to actual Trip model
- `docs/installation.md` - Updated system settings section
- `docs/README.md` - Updated overview to reflect actual system
- `.kilo/docs-index.md` - Comprehensive knowledge base index

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