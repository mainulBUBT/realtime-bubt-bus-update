# Requirements Document

## Introduction

The BUBT Realtime Bus Update system is a Progressive Web Application (PWA) that provides real-time bus tracking for students using their own mobile devices as GPS trackers. The system allows students to see live bus locations on a map, receive push notifications when buses are near their stops, and enables administrators to manage bus schedules and monitor live trips. The solution is designed to work on shared cPanel hosting without requiring dedicated hardware or VPS infrastructure.

## Requirements

### Requirement 1: Flexible Student Bus Tracking Interface

**User Story:** As a student, I want to flexibly track any bus I'm traveling on and have the system intelligently detect my actual presence inside the bus, so that I can travel on different routes as needed.

#### Acceptance Criteria

1. WHEN a student opens the app THEN the system SHALL display a splash screen followed by a list of all available buses across all 5 routes
2. WHEN a student views the bus list THEN the system SHALL show buses with their current status (running/not running) and allow selection of any bus regardless of their usual route
3. WHEN a student taps "I'm on this bus" THEN the system SHALL verify their location using geo-fence (within 50 meters of route), time-window validation, and movement pattern analysis
4. WHEN verifying student presence THEN the system SHALL check if the device is moving with the bus (similar speed and direction) for at least 2 minutes before confirming
5. IF the student passes verification THEN the system SHALL mark them as a verified tracker for that specific trip
6. WHEN a verified student is on a bus THEN their device SHALL automatically push GPS coordinates via WebSocket when location changes significantly
7. WHEN a student regularly travels specific routes THEN the system SHALL learn their patterns but still allow flexibility to track other buses
8. IF a student's device stops moving with the bus (different speed/direction) THEN the system SHALL automatically remove them as a tracker after 5 minutes

### Requirement 2: Near Real-time Location Broadcasting with Traffic Intelligence

**User Story:** As a student waiting at a bus stop, I want to see near real-time bus locations with traffic jam detection on a free OpenStreetMap interface, so that I know when my bus is approaching or stuck in traffic.

#### Acceptance Criteria

1. WHEN verified devices are tracking a bus THEN the system SHALL push location updates via WebSocket immediately when GPS coordinates change
2. WHEN location data is received THEN the system SHALL validate speed is less than 70 km/h and device is on the designated route
3. WHEN a bus remains stationary or moves very slowly (< 5 km/h) for more than 5 minutes THEN the system SHALL mark it as "in traffic jam" and display appropriate status
4. WHEN a bus is in traffic jam THEN the system SHALL continue tracking and show estimated delay time to subscribed students
5. WHEN tracking devices show all students are stationary together THEN the system SHALL confirm they are still inside the bus (not dropped off)
6. IF at least 2 verified devices are tracking THEN the system SHALL display the bus icon on the OpenStreetMap interface with traffic status
7. WHEN displaying the map THEN the system SHALL use OpenStreetMap tiles with Leaflet.js for free mapping without API costs
8. WHEN showing bus routes THEN the system SHALL overlay route polylines on OpenStreetMap for each of the 5 BUBT routes
9. WHEN displaying bus locations THEN the system SHALL only show data from verified tracking devices with traffic status indicators
10. WHEN a trip is marked as finished THEN the system SHALL stop broadcasting location updates for that trip
11. WHEN testing on localhost THEN the system SHALL work with local WebSocket connections and OpenStreetMap tiles for development and testing

### Requirement 3: Push Notification System

**User Story:** As a student subscribed to a bus stop, I want to receive push notifications when a bus is near my stop, so that I don't miss my ride.

#### Acceptance Criteria

1. WHEN a student subscribes to a stop THEN the system SHALL store their device ID, stop ID, and FCM token
2. WHEN a bus comes within 400 meters of a subscribed stop THEN the system SHALL send a push notification to subscribed devices
3. WHEN sending notifications THEN the system SHALL ensure each subscriber receives only one notification per bus per stop
4. IF a device has disabled notifications THEN the system SHALL handle FCM errors gracefully
5. WHEN the notification system runs THEN it SHALL execute every 30 seconds via cron job

### Requirement 4: BUBT Route System Management

**User Story:** As an administrator, I want to manage the BUBT transportation system with 5 specific bus routes (B1-B5) and their bidirectional schedules, so that I can handle the complete campus transportation network.

#### Acceptance Criteria

1. WHEN an admin accesses the route management THEN the system SHALL display the 5 BUBT routes: B1-Buriganga, B2-Brahmaputra, B3-Padma, B4-Meghna, B5-Jamuna
2. WHEN an admin manages B1-Buriganga route THEN the system SHALL handle stops: Asad Gate → Shyamoli → Mirpur-1 → Rainkhola → BUBT
3. WHEN an admin manages B2-Brahmaputra route THEN the system SHALL handle stops: Hemayetpur → Amin Bazar → Gabtoli → Mazar Road → Mirpur-1 → Rainkhola → BUBT
4. WHEN an admin manages B3-Padma route THEN the system SHALL handle stops: Shyamoli (Shishu Mela) → Agargaon → Kazipara → Mirpur-10 → Proshikha → BUBT
5. WHEN an admin manages B4-Meghna route THEN the system SHALL handle stops: Mirpur-14 → Mirpur-10 (Original) → Mirpur-11 → Proshikha → BUBT
6. WHEN an admin manages B5-Jamuna route THEN the system SHALL handle stops: ECB Chattar → Kalshi Bridge → Mirpur-12 → Duaripara → BUBT
7. WHEN an admin sets schedules THEN the system SHALL support "To Campus" times (7:00 AM, 5:00 PM) and "From Campus" times (4:10 PM, 9:25 PM) for all routes
8. WHEN managing bidirectional trips THEN the system SHALL distinguish between inbound (to BUBT) and outbound (from BUBT) journeys
9. WHEN an admin updates route data THEN the system SHALL immediately reflect changes in the student interface
10. IF an admin deletes a route THEN the system SHALL prevent deletion if there are active trips on that route

### Requirement 5: Automatic Trip Detection and Management

**User Story:** As a system user, I want the system to automatically detect and manage bus trips based on observations, so that minimal admin intervention is required for daily operations.

#### Acceptance Criteria

1. WHEN a verified device starts tracking near a route's first stop within 30 minutes of scheduled time THEN the system SHALL automatically create a new trip record with status "running"
2. WHEN multiple devices start tracking the same route simultaneously THEN the system SHALL automatically detect trip start and begin location consensus
3. WHEN a trip starts outside scheduled time (e.g., 9:00 AM instead of 7:30 AM) THEN the system SHALL automatically mark it as "delayed" and notify subscribed students
4. WHEN no devices track a scheduled route within 60 minutes of departure time THEN the system SHALL automatically mark the schedule as "cancelled" for that day
5. WHEN tracking devices reach the final destination and remain stationary for 10 minutes THEN the system SHALL automatically finish the trip
6. WHEN a trip is automatically detected THEN the system SHALL display live passenger count, tracking device information, and actual vs scheduled timing
7. WHEN viewing the live map THEN the system SHALL show all active buses with verified tracking devices and their delay status
8. WHEN a trip is automatically finished THEN the system SHALL move all trip data to trip_histories table as JSON blob including delay information
9. WHEN the system detects unusual patterns (e.g., bus going off-route) THEN it SHALL alert admins while continuing automatic operation
10. WHEN automatic detection fails THEN the system SHALL provide manual override options for admins

### Requirement 6: Device Reputation and Location Consensus

**User Story:** As a system administrator, I want to determine the actual bus location using weighted voting based on device reputation, so that the most accurate location is displayed to students.

#### Acceptance Criteria

1. WHEN a device successfully tracks a complete trip THEN the system SHALL increase its reputation score by +1
2. WHEN a device provides invalid location data THEN the system SHALL decrease its reputation score by -2
3. WHEN determining tracking eligibility THEN the system SHALL consider device UUID-based reputation, geo-fence validation, and time-window constraints
4. WHEN multiple devices track the same bus THEN the system SHALL use weighted centroid algorithm with minimum 2 and maximum 10 devices for location consensus
5. WHEN more than 10 devices are tracking THEN the system SHALL select the top 10 devices with highest reputation scores for location calculation
6. WHEN calculating bus position THEN the system SHALL apply formula: Final_Location = Σ(Device_Location × Device_Reputation) / Σ(Device_Reputation)
7. IF a device location differs significantly from the weighted average THEN the system SHALL reduce that device's reputation score
8. WHEN fewer than 2 devices are tracking AND trip is within scheduled time window THEN the system SHALL display location from single device with high reputation (score ≥ 5)
9. WHEN fewer than 2 devices are tracking AND trip is outside scheduled time window THEN the system SHALL not display bus location until minimum threshold is met
9. IF device reputation falls below threshold (score < 0) THEN the system SHALL exclude that device from location voting
10. WHEN processing location consensus THEN the system SHALL complete calculation within 100ms for real-time performance
9. WHEN storing reputation data THEN the system SHALL use device UUID as the primary key for reputation management
10. WHEN a student ID is added in future THEN the system SHALL link student_id to existing device_id without breaking current reputation scores
11. WHEN managing device reputation THEN the system SHALL use file-based caching for shared hosting compatibility

### Requirement 7: Database Optimization and Cleanup

**User Story:** As a system administrator, I want automatic cleanup and optimization of location data, so that the database remains fast and efficient for real-time operations.

#### Acceptance Criteria

1. WHEN the archive command runs nightly THEN the system SHALL move completed trips older than 7 days to trip_histories
2. WHEN archiving trips THEN the system SHALL compress trip data into JSON format with passenger counts
3. WHEN cleaning up ping data THEN the system SHALL remove location pings older than 2 hours for active trips to keep only recent location history
4. WHEN cleaning up ping data THEN the system SHALL remove location pings older than 24 hours for finished trips
5. WHEN processing location consensus THEN the system SHALL use database indexes on trip_id, device_id, and created_at columns for fast queries
6. WHEN storing location pings THEN the system SHALL batch insert multiple location updates to reduce database load
7. WHEN querying recent locations THEN the system SHALL limit results to last 50 pings per device for performance
8. IF archival fails THEN the system SHALL log errors and retry on next scheduled run
9. WHEN archival completes THEN the system SHALL maintain referential integrity across all tables
10. WHEN database grows large THEN the system SHALL automatically optimize tables weekly via cron job

### Requirement 8: Shared Hosting Compatibility

**User Story:** As a system deployer, I want the application to work on shared cPanel hosting, so that we can deploy without requiring VPS or dedicated infrastructure.

#### Acceptance Criteria

1. WHEN deploying the application THEN the system SHALL work without Redis or other VPS-specific services
2. WHEN using real-time features THEN the system SHALL use Pusher free tier for WebSocket functionality
3. WHEN scheduling background tasks THEN the system SHALL use cPanel cron jobs for Laravel scheduler
4. IF the hosting environment lacks certain features THEN the system SHALL gracefully degrade functionality
5. WHEN configuring the application THEN the system SHALL require only standard shared hosting features (PHP, MySQL, cron)

### Requirement 9: Progressive Web App with Background Tracking

**User Story:** As a student using a mobile device, I want the app to continue tracking my location even when I'm using other apps like Facebook, so that bus tracking works seamlessly in the background.

#### Acceptance Criteria

1. WHEN a student accesses the app on mobile THEN the system SHALL provide PWA installation prompts
2. WHEN the app is installed THEN it SHALL work offline for basic functionality like viewing cached bus schedules
3. WHEN a student accepts tracking and minimizes the PWA THEN the system SHALL continue collecting GPS coordinates using Service Worker background sync
4. WHEN the PWA is not in foreground THEN the system SHALL use Web Background Sync API to queue location updates
5. WHEN the device has network connectivity THEN the system SHALL automatically sync queued location data via WebSocket
6. IF background location access is restricted THEN the system SHALL prompt user to keep the PWA tab active or use "Add to Home Screen" for better background performance
7. WHEN using other apps (Facebook, etc.) THEN the system SHALL maintain location tracking through Service Worker if browser supports it
8. IF browser limitations prevent background tracking THEN the system SHALL notify the user to keep the PWA active for accurate tracking
9. WHEN push notifications are enabled THEN the system SHALL work even when the app is not actively open
10. WHEN using the app THEN the system SHALL provide responsive design optimized for mobile devices