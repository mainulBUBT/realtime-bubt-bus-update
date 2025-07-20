# Implementation Plan

- [x] 1. Set up Laravel project structure and core dependencies
  - Create new Laravel 12 project with required packages
  - Install Livewire 3, Tailwind CSS, DaisyUI, and Toastr.js
  - Configure basic routing structure with admin prefix
  - Set up environment configuration for localhost development
  - _Requirements: 8.1, 8.5_

- [ ] 2. Create database schema with separate migration files
  - [ ] 2.1 Create buses table migration
    - Write separate migration file for buses table with route names (B1-B5)
    - Add status enum and proper indexes
    - _Requirements: 4.1, 4.2_

  - [ ] 2.2 Create stops table migration
    - Write separate migration file for stops table with BUBT route coordinates
    - Add latitude/longitude indexes and foreign key to buses
    - _Requirements: 4.5_

  - [ ] 2.3 Create schedules table migration
    - Write separate migration file for schedules table with bidirectional timing
    - Add indexes for bus schedule queries
    - _Requirements: 4.7_

  - [ ] 2.4 Create trips table migration
    - Write separate migration file for trips table with status tracking
    - Add indexes for trip status and bus queries
    - _Requirements: 5.1, 5.5_

  - [ ] 2.5 Create device_reputations table migration
    - Write separate migration file for device_reputations table with UUID indexing
    - Add reputation score and activity tracking indexes
    - _Requirements: 6.5, 6.9_

  - [ ] 2.6 Create pings table migration
    - Write separate migration file for pings table optimized for real-time queries
    - Add composite indexes for location consensus performance
    - _Requirements: 7.5_

  - [ ] 2.7 Create subscriptions table migration
    - Write separate migration file for subscriptions table for push notifications
    - Add device and stop indexing
    - _Requirements: 3.1_

  - [ ] 2.8 Create trip_histories table migration
    - Write separate migration file for trip_histories table for archived data
    - Add archival date indexing
    - _Requirements: 7.1, 7.2_

- [ ] 3. Seed BUBT route data and initial configuration
  - Create database seeders for all 5 BUBT routes (B1-B5)
  - Seed stops data with exact coordinates for each route
  - Seed schedule data with "To Campus" and "From Campus" timings
  - Create test device reputation data for development
  - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6_

- [ ] 4. Implement core service classes for location processing
  - [ ] 4.1 Create LocationConsensusService class
    - Implement weighted centroid algorithm for 2-10 devices
    - Add device reputation weighting logic
    - Implement single device handling during scheduled windows
    - Add performance optimization to complete within 100ms
    - Write unit tests for location consensus calculations
    - _Requirements: 6.4, 6.5, 6.6, 6.10, 7.7_

  - [ ] 4.2 Create ReputationService class
    - Implement file-based reputation caching system
    - Add reputation scoring logic (+1 success, -2 invalid)
    - Create device eligibility checking methods
    - Add methods for future student ID linking
    - Write unit tests for reputation management
    - _Requirements: 6.1, 6.2, 6.3, 6.8, 6.11_

  - [ ] 4.3 Create TripDetectionService class
    - Implement automatic trip start detection near first stops
    - Add automatic trip end detection at final destinations
    - Create schedule window validation (30-minute tolerance)
    - Add traffic jam detection logic (< 5 km/h for 5+ minutes)
    - Write unit tests for trip detection scenarios
    - _Requirements: 5.1, 5.2, 5.8, 2.3, 2.4_

- [ ] 5. Build student-facing Livewire components
  - [x] 5.1 Create BusList Livewire component
    - Build bus selection interface showing all 5 routes
    - Implement device UUID generation and management
    - Add "I'm on this bus" functionality with location verification
    - Integrate Toastr notifications for user feedback
    - Add real-time bus status updates via WebSocket listeners
    - _Requirements: 1.1, 1.2, 1.3, 1.7, 1.8_

  - [ ] 5.2 Create OnBusTracker Livewire component
    - Implement GPS location tracking with WebSocket broadcasting
    - Add movement pattern analysis for bus presence verification
    - Create automatic tracker removal when device leaves bus
    - Add background tracking capability with Service Worker integration
    - Implement location validation and error handling
    - _Requirements: 1.4, 1.5, 1.6, 9.3, 9.7_

  - [ ] 5.3 Create LiveMap Livewire component
    - Integrate OpenStreetMap with Leaflet.js for free mapping
    - Display real-time bus locations with traffic status indicators
    - Add route polylines for all 5 BUBT routes
    - Implement WebSocket listeners for location updates
    - Add bus status indicators (moving, traffic jam, stationary)
    - _Requirements: 2.1, 2.4, 2.6, 2.7, 2.8, 2.9_

- [ ] 6. Implement WebSocket real-time communication
  - Configure Pusher integration for localhost and production
  - Create WebSocket event broadcasting for location updates
  - Implement location data validation and error handling
  - Add automatic retry logic for failed WebSocket connections
  - Create WebSocket listeners in Livewire components
  - _Requirements: 2.1, 2.11, 8.2_

- [ ] 7. Build Progressive Web App features
  - [ ] 7.1 Create Service Worker for background tracking
    - Implement Service Worker with Background Sync API
    - Add GPS location queuing when app is minimized
    - Create automatic sync when network connectivity returns
    - Add offline capability for viewing cached bus schedules
    - _Requirements: 9.3, 9.4, 9.5, 9.2_

  - [ ] 7.2 Implement PWA manifest and installation
    - Create PWA manifest file with app icons and configuration
    - Add PWA installation prompts for mobile devices
    - Implement responsive design optimized for mobile
    - Add "Add to Home Screen" functionality
    - _Requirements: 9.1, 9.10_

- [ ] 8. Create push notification system
  - Configure Firebase Cloud Messaging (FCM) integration
  - Create subscription management for bus stop notifications
  - Implement 400-meter proximity detection for push alerts
  - Add notification scheduling to prevent duplicate alerts
  - Create FCM error handling for disabled notifications
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 9. Build admin interface components
  - [ ] 9.1 Create AdminDashboard Livewire component
    - Build overview dashboard with active trips and statistics
    - Add real-time passenger count display
    - Create trip status monitoring interface
    - Add delay and cancellation alerts
    - _Requirements: 5.5, 5.6, 5.9_

  - [ ] 9.2 Create ScheduleCrud Livewire component
    - Build CRUD interface for managing bus schedules
    - Add schedule conflict prevention logic
    - Implement bidirectional route management
    - Add bulk schedule operations for efficiency
    - _Requirements: 4.1, 4.6, 4.9, 4.10_

  - [ ] 9.3 Create TripControl Livewire component
    - Build manual trip start/finish controls
    - Add delay marking and student notification features
    - Create trip cancellation functionality
    - Add manual override for automatic detection failures
    - _Requirements: 5.2, 5.3, 5.7, 5.10_

- [ ] 10. Implement background job processing
  - [ ] 10.1 Create NotifyNearStops command
    - Implement 30-second cron job for proximity notifications
    - Add 400-meter radius calculation for bus stops
    - Create FCM batch notification sending
    - Add error handling and retry logic for failed notifications
    - _Requirements: 3.2, 3.5_

  - [ ] 10.2 Create ArchiveOldTrips command
    - Implement nightly archival of completed trips (7+ days old)
    - Add JSON compression for trip history data
    - Create cleanup of location pings (24 hours for finished trips)
    - Add database optimization and table maintenance
    - _Requirements: 7.1, 7.2, 7.3, 7.10_

- [ ] 11. Add location validation and error handling
  - Create LocationValidator class for GPS data validation
  - Implement speed validation (< 70 km/h) and route checking
  - Add geo-fence validation for route adherence
  - Create error logging and device reputation penalties
  - Add graceful degradation for invalid location data
  - _Requirements: 2.2, 6.7, 6.2_

- [ ] 12. Implement database optimization features
  - Add batch insert operations for location pings
  - Create database indexes for performance optimization
  - Implement query result limiting (last 50 pings per device)
  - Add weekly table optimization via cron job
  - Create database performance monitoring
  - _Requirements: 7.5, 7.6, 7.7, 7.10_

- [ ] 13. Create comprehensive test suite
  - [ ] 13.1 Write unit tests for core services
    - Test LocationConsensusService weighted centroid algorithm
    - Test ReputationService scoring and caching
    - Test TripDetectionService automatic detection logic
    - Test LocationValidator validation rules
    - _Requirements: 6.4, 6.1, 5.1, 2.2_

  - [ ] 13.2 Write integration tests for WebSocket communication
    - Test real-time location update broadcasting
    - Test WebSocket connection handling and recovery
    - Test Livewire component WebSocket listeners
    - Test push notification delivery
    - _Requirements: 2.1, 3.2_

  - [ ] 13.3 Write feature tests for complete user flows
    - Test complete trip flow from student join to trip end
    - Test multiple students tracking same bus scenario
    - Test traffic jam detection and notification flow
    - Test PWA background tracking functionality
    - _Requirements: 1.1-1.8, 2.3, 2.4, 9.3_

- [ ] 14. Configure deployment for shared hosting
  - Create production environment configuration
  - Set up cPanel cron jobs for Laravel scheduler
  - Configure file-based caching for shared hosting compatibility
  - Add database connection optimization for shared hosting
  - Create deployment documentation and troubleshooting guide
  - _Requirements: 8.1, 8.3, 8.4, 8.5_

- [ ] 15. Perform end-to-end testing and optimization
  - Test complete system with multiple devices on localhost
  - Verify location consensus accuracy with test devices
  - Test PWA installation and background tracking
  - Validate push notification delivery and timing
  - Optimize database queries for production performance
  - _Requirements: All requirements integration testing_