# Implementation Plan

- [x] 1. Set up Laravel project structure and integrate existing UI assets
  - Create Laravel project structure with proper asset organization
  - Move existing Bootstrap UI files to Laravel public/resources directories
  - Set up Vite configuration for asset compilation
  - Create base Blade layouts preserving existing HTML structure
  - _Requirements: 11.1, 11.2, 11.3_

- [x] 2. Create database schema and migrations
  - [x] 2.1 Create device tokens migration and model
    - Write migration for device_tokens table with fingerprint data and reputation scoring
    - Create DeviceToken model with proper fillable fields and casts
    - Add database indexes for performance optimization
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 2.2 Create bus schedules and routes migrations based on existing route structure
    - Write migration for bus_schedules table supporting round-trip schedules (departure/return times)
    - Write migration for bus_routes table with sequential stops, coordinates, and coverage radius
    - Create BusSchedule model with departure/return time logic for 5 buses (B1-B5)
    - Create BusRoute model with stop_order, timeline progression, and estimated arrival times
    - Add method to determine if bus is on departure trip (campus→city) or return trip (city→campus)
    - _Requirements: 5.1, 5.2, 5.3, 5.7_

  - [x] 2.3 Create location tracking migrations
    - Write migration for bus_locations table with GPS data and reputation weights
    - Write migration for bus_location_history table for daily archiving
    - Write migration for bus_current_positions cache table
    - Create BusLocation model with proper validation and casts
    - Add database indexes for real-time location queries
    - _Requirements: 3.3, 3.7, 10.1, 10.2_

  - [x] 2.4 Create admin and business settings migrations
    - Write migration for admin_users table with role-based permissions
    - Write migration for business_settings table for PWA customization
    - Write migration for system_logs table for admin monitoring
    - Create AdminUser model with authentication capabilities
    - Create BusinessSetting model with key-value configuration storage
    - _Requirements: New admin functionality_

- [x] 3. Implement device token management system
  - [x] 3.1 Create device fingerprinting JavaScript utility
    - Write JavaScript class to generate browser fingerprints using screen, navigator, and timing data
    - Implement local storage management for device tokens
    - Create token validation and storage methods
    - _Requirements: 6.1, 6.2_

  - [x] 3.2 Build DeviceTokenService for backend token management
    - Create service class to generate unique device tokens from fingerprint data
    - Implement token validation and reputation score management
    - Add methods to update reputation based on location data accuracy
    - Write unit tests for token generation and validation
    - _Requirements: 6.1, 6.2, 6.8, 6.12_

- [x] 4. Optimize OpenStreetMap and Leaflet for smooth performance
  - [x] 4.1 Optimize Leaflet map performance for traffic and smooth rendering
    - Implement tile caching strategy for OpenStreetMap to reduce server requests
    - Add map clustering for multiple bus markers to improve performance
    - Configure optimal zoom levels and bounds for Dhaka region to minimize data loading
    - Implement lazy loading for map tiles and progressive enhancement
    - Add offline map caching for frequently accessed areas using browser storage
    - Configure Leaflet options for smooth panning and zooming on mobile devices
    - _Requirements: New map performance optimization_

  - [x] 4.2 Create stoppage coordinate validation system
    - Define precise coordinates and radius for each bus stop (Asad Gate, Shyamoli, Mirpur-1, Rainkhola, BUBT)
    - Implement geofencing validation to reject GPS data outside stoppage radius
    - Create route corridor validation between stops to detect users off the expected path
    - Add distance calculation algorithms for efficient radius checking
    - Implement real-time validation that flags users outside expected areas
    - Create visual indicators on admin panel showing stoppage boundaries and coverage areas
    - _Requirements: 5.1, 5.6, 5.7, 6.5, 6.6_

  - [x] 4.3 Implement browser geolocation integration
    - Create JavaScript GPS permission request handler matching existing UI
    - Implement continuous location tracking with 15-30 second intervals
    - Add location accuracy validation and error handling
    - Create "I'm on this bus" functionality with device token association
    - _Requirements: 1.1, 1.2, 1.3, 3.3, 3.4_

  - [x] 4.4 Build LocationService for GPS data processing
    - Create service class to receive and validate GPS coordinates
    - Implement route radius validation against bus stop coordinates
    - Add speed validation to detect impossible location jumps
    - Create location aggregation logic for multiple users on same bus
    - _Requirements: 5.6, 5.7, 6.5, 6.6, 3.7_

  - [x] 4.5 Implement advanced bus tracking reliability system
    - Create location source validation to only accept GPS when user clicks "I'm on this bus"
    - Implement movement consistency tracking (speed + direction validation)
    - Add user clustering logic to group users within 20-30 meters and ignore outliers
    - Create device trust scoring system based on historical behavior and consistency
    - Implement auto-deactivation for static or off-route GPS data after timeout
    - Add smart broadcasting using averaged GPS from trusted users only
    - Create fallback strategy for "No active tracking" scenarios with last known location
    - _Requirements: 6.4, 6.5, 6.7, 6.9, 6.10, 3.3, 3.5_

  - [x] 4.6 Build device trust and clustering algorithms
    - Implement device trust score calculation based on frequency, consistency, and accuracy
    - Create user clustering algorithm to detect users within 20-30 meter radius
    - Add outlier detection to ignore users far from the main group
    - Implement movement pattern analysis to detect bus-like movement vs walking/stationary
    - Create automatic weight adjustment for low-trust devices
    - Add trust score decay for inactive devices and boost for consistent contributors
    - _Requirements: 6.1, 6.2, 6.8, 6.10, 6.12_

- [x] 5. Create GPS location collection and validation system

- [x] 6. Build bus schedule management system with route timeline progression
  - [x] 6.1 Create BusScheduleService for round-trip schedule management
    - Implement logic to determine active buses based on departure/return schedule (e.g., 7:00 AM departure, 5:00 PM return)
    - Add validation to only accept GPS data during scheduled trip times
    - Create methods to determine trip direction (campus→city morning, city→campus evening)
    - Add schedule transition handling between departure and return trips
    - Implement route reversal logic for return trips (BUBT→Rainkhola→Mirpur-1→Shyamoli→Asad Gate)
    - _Requirements: 5.2, 5.5, 5.9, 5.10_

  - [x] 6.2 Implement route coordinate validation for sequential stop progression
    - Create RouteValidator class to validate GPS coordinates against sequential route stops
    - Implement StopCoordinateManager for stops like Asad Gate, Shyamoli, Mirpur-1, Rainkhola, BUBT
    - Add distance calculation methods for stop coverage radius and route progression validation
    - Create validation for users outside expected route corridor between stops
    - Implement direction-aware validation (different validation for departure vs return trips)
    - _Requirements: 5.1, 5.6, 5.7_

  - [x] 6.3 Create route timeline progression system
    - Implement timeline status management (completed, current, upcoming stops)
    - Create stop progression logic based on GPS location and time estimates
    - Add ETA calculation for current stop based on real-time location data
    - Implement progress bar calculation for current stop completion percentage
    - Create automatic timeline updates when bus reaches each stop
    - Add support for route reversal during return trips
    - _Requirements: 2.1, 2.2, 3.4, 3.5_

- [x] 7. Create Livewire components for real-time UI
  - [x] 7.1 Build BusList Livewire component
    - Convert existing bus cards HTML to Livewire component
    - Implement bus filtering functionality from existing JavaScript
    - Add real-time bus status updates (active/delayed/inactive)
    - Create schedule-based bus display logic
    - _Requirements: 2.1, 2.2, 2.3, 5.2_

  - [x] 7.2 Create BusTracker Livewire component with route timeline integration
    - Convert existing track.html to Livewire component preserving route timeline UI
    - Implement "I'm on this bus" button functionality with current stop detection
    - Add real-time location updates with timeline progression (completed, current, upcoming stops)
    - Create dynamic timeline updates showing bus progress through stops
    - Implement ETA calculations and progress bar updates for current stop
    - Add tracking status indicators and confidence levels based on passenger data
    - _Requirements: 3.1, 3.2, 3.4, 3.5, 9.1, 9.2_

  - [x] 7.3 Build LocationSharing component for GPS management
    - Create component to handle GPS permission requests
    - Implement continuous location sharing with device token association
    - Add tracking start/stop functionality with visual indicators
    - Create fallback displays for inactive tracking scenarios
    - _Requirements: 1.1, 1.4, 3.3, 3.6, 8.1, 8.2_

- [ ] 8. Implement real-time communication system
  - [x] 8.1 Set up Laravel Reverb for WebSocket communication
    - Install and configure Laravel Reverb for unlimited WebSocket connections
    - Create BusLocationBroadcaster for real-time location updates
    - Implement WebSocket event broadcasting for 250-300+ concurrent users
    - Add connection management and cleanup for inactive connections
    - _Requirements: 7.1, 7.4, 7.6_

  - [x] 8.2 Create AJAX polling fallback system
    - Build PollingController for AJAX-based location updates
    - Implement 10-second polling interval when WebSocket fails
    - Add automatic WebSocket reconnection attempts
    - Create connection status display for users
    - _Requirements: 7.2, 7.3, 7.5, 7.7_

  - [x] 8.3 Build smart broadcasting and caching system
    - Implement bus_current_positions table with trusted user averaging
    - Create smart broadcasting that updates bus location every 10-30 seconds using only trusted users
    - Add batch processing for location updates to reduce database load
    - Implement automatic cleanup of old location data and inactive tracking sessions
    - Create fallback display system for buses with no active tracking
    - Add database indexing optimization for real-time queries with trust scores
    - _Requirements: 7.6, 10.3, 10.4, 10.7, 8.1, 8.2_

- [x] 9. Create data quality and validation systems
  - [x] 9.1 Implement comprehensive GPS data validation
    - Add coordinate boundary validation for Bangladesh region
    - Create speed limit validation to prevent impossible movements
    - Implement route adherence checking against expected bus paths
    - Add timestamp validation for location data consistency
    - _Requirements: 6.5, 6.6, 9.8_

  - [x] 9.2 Build fallback and error handling systems
    - Create "Tracking not active" display for buses with no GPS data
    - Implement "Last seen at [time/location]" functionality with historical data
    - Add single-user data validation and confidence level indicators
    - Create smooth transition handling when multiple users join tracking
    - _Requirements: 8.1, 8.2, 8.3, 9.1, 9.3, 9.5_

- [x] 10. Implement historical data management
  - [x] 10.1 Create daily data archiving system
    - Build automated system to move completed trip data to history tables
    - Implement daily cleanup of real-time location tables
    - Create historical data retrieval methods for analysis
    - Add automatic archiving of old historical data beyond retention period
    - _Requirements: 10.1, 10.2, 10.3, 10.5_

  - [x] 10.2 Build trip completion detection
    - Implement logic to detect when buses reach final destinations
    - Add automatic stopping of GPS data collection for completed trips
    - Create trip summary generation for historical storage
    - Add transition handling between trip completion and new trip start
    - _Requirements: 8.4, 8.5, 10.7_

- [-] 11. Create admin panel layouts and interfaces
  - [ ] 11.1 Build admin authentication layouts
    - Create admin login page with separate styling from user PWA
    - Design admin dashboard layout with sidebar navigation
    - Implement admin header with user profile and logout functionality
    - Create responsive admin layout for desktop and tablet use
    - _Requirements: New admin functionality_

  - [ ] 11.2 Design bus management interfaces
    - Create bus listing page with CRUD operations (add, edit, delete, status toggle)
    - Build bus form interface for adding/editing bus details (ID, name, capacity, status)
    - Design bus status dashboard with real-time tracking capabilities
    - Create bus assignment interface for schedule management
    - _Requirements: 2.1, 2.2, 5.1_

  - [ ] 11.3 Build schedule management interfaces
    - Create schedule calendar view for managing departure/return times
    - Design route builder interface for adding/editing stops with map integration
    - Build stop coordinate management with drag-and-drop map interface
    - Create schedule template system for recurring schedules
    - Add schedule conflict detection and resolution interface
    - _Requirements: 5.1, 5.2, 5.3, 5.9_

  - [ ] 11.4 Create business settings interface
    - Build PWA customization panel (logo upload, color picker, header text)
    - Create university information management form
    - Design notification settings interface with message templates
    - Build system configuration panel (tracking intervals, validation rules)
    - Add settings backup/restore interface
    - _Requirements: New business settings functionality_

  - [ ] 11.5 Design advanced monitoring and reliability dashboard
    - Create real-time bus tracking dashboard with live map and trust indicators
    - Build admin tools to view active users per bus with trust scores
    - Design suspicious device detection interface with flagging capabilities
    - Create location history visualization with movement pattern analysis
    - Add user clustering visualization showing grouped vs outlier users
    - Implement alert system for reliability issues (static GPS, off-route users, low trust devices)
    - Build device trust management interface for manual trust score adjustments
    - _Requirements: 7.1, 7.6, 9.1, 10.1, 6.1, 6.8, 6.10_

- [ ] 12. Integrate and preserve existing JavaScript functionality
  - [ ] 12.1 Adapt existing JavaScript for Livewire compatibility
    - Modify app.js functions to work with Livewire components
    - Preserve filterBusCards and initBusCards functionality
    - Adapt track.js functions for real-time updates
    - Ensure map.js integration works with Livewire-rendered content
    - _Requirements: 11.2, 11.3, 11.5_

  - [ ] 12.2 Create seamless UI integration
    - Convert index.html and track.html to Blade templates
    - Preserve all existing Bootstrap styling and responsive design
    - Maintain existing mobile navigation and drawer functionality
    - Ensure JavaScript interactions work with dynamic Livewire content
    - _Requirements: 11.1, 11.4_

- [ ] 13. Testing and optimization
  - [ ] 13.1 Write comprehensive unit tests
    - Create tests for GPS validation logic and coordinate checking
    - Test reputation calculation algorithms with various data scenarios
    - Add tests for schedule management and active bus determination
    - Test device token generation and validation mechanisms
    - _Requirements: 6.1, 6.4, 5.2, 5.5_

  - [ ] 13.2 Implement integration testing
    - Test Livewire components with real-time updates
    - Validate database operations and location data storage
    - Test WebSocket communication and fallback mechanisms
    - Verify API endpoints and data consistency
    - _Requirements: 7.1, 7.2, 3.3, 3.7_

  - [ ] 13.3 Create performance optimization
    - Optimize database queries for 250-300+ concurrent users
    - Test real-time update performance with multiple buses
    - Implement memory usage optimization for extended tracking sessions
    - Add network efficiency optimization for mobile users
    - _Requirements: 7.1, 7.6, 9.1, 9.2_

- [ ] 14. Create admin panel for bus and schedule management
  - [ ] 14.1 Set up admin authentication and routes
    - Create separate admin authentication system with login/logout
    - Set up admin middleware and route protection
    - Create admin dashboard layout with navigation menu
    - Add admin user seeder for initial setup
    - _Requirements: New admin functionality_

  - [ ] 14.2 Build bus CRUD management system
    - Create admin interface to add/edit/delete buses (B1-B5 with names)
    - Implement bus status management (active/inactive/maintenance)
    - Add bus capacity and vehicle details management
    - Create bus assignment and scheduling interface
    - _Requirements: 2.1, 2.2, 5.1_

  - [ ] 14.3 Create schedule management system
    - Build interface to create/edit departure and return schedules
    - Implement route stop management with coordinates and coverage radius
    - Add schedule templates and bulk schedule creation
    - Create schedule conflict detection and validation
    - Add schedule history and change tracking
    - _Requirements: 5.1, 5.2, 5.3, 5.9_

  - [ ] 14.4 Implement business settings management
    - Create settings panel for PWA app configuration (header, logo, colors)
    - Add university information management (name, location, contact)
    - Implement notification settings and message templates
    - Create system configuration options (tracking intervals, validation rules)
    - Add backup and restore functionality for settings
    - _Requirements: New business settings functionality_

  - [ ] 14.5 Build real-time monitoring dashboard
    - Create live bus tracking dashboard for administrators
    - Implement passenger count monitoring and analytics
    - Add system health monitoring (active connections, database performance)
    - Create alerts for system issues or bus delays
    - Add historical data visualization and reporting
    - _Requirements: 7.1, 7.6, 9.1, 10.1_

- [ ] 15. Deploy and configure production environment
  - [ ] 15.1 Set up production database and optimize for scale
    - Configure MySQL with optimized indexes for real-time queries
    - Set up database connection pooling for concurrent users
    - Implement database backup and recovery procedures
    - Configure automatic cleanup jobs for historical data
    - _Requirements: 10.1, 10.2, 10.5_

  - [ ] 15.2 Configure Laravel Reverb for production
    - Set up Laravel Reverb server for 250-300+ WebSocket connections
    - Configure connection scaling and load balancing
    - Implement monitoring and logging for WebSocket connections
    - Set up automatic restart and recovery mechanisms
    - _Requirements: 7.1, 7.4, 7.6_