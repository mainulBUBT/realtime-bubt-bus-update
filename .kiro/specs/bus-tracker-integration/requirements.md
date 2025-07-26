# Requirements Document

## Introduction

This feature integrates an existing Bootstrap-based bus tracker UI into a Laravel 12 + Livewire 3 application to create a comprehensive university bus tracking system. The system will enable passengers to share their GPS location when they're on a bus, providing real-time tracking capabilities without requiring driver participation. The integration focuses on maintaining the existing UI functionality while adding backend data management, real-time updates, and GPS-based location sharing.

## Requirements

### Requirement 1

**User Story:** As a passenger, I want to grant GPS permission when I visit the website, so that I can contribute to bus location tracking when I'm on a bus.

#### Acceptance Criteria

1. WHEN a user first visits the website THEN the system SHALL request GPS location permission using the browser's geolocation API
2. IF the user grants permission THEN the system SHALL store the permission status locally
3. IF the user denies permission THEN the system SHALL display a message explaining the importance of location sharing for bus tracking
4. WHEN GPS permission is granted THEN the system SHALL be able to access the user's coordinates at regular intervals

### Requirement 2

**User Story:** As a passenger, I want to see a list of available university buses with their routes, so that I can identify which bus I'm currently on.

#### Acceptance Criteria

1. WHEN a user visits the home page THEN the system SHALL display all active buses based on current schedule
2. WHEN displaying buses THEN the system SHALL show bus ID, route name, current status (active/delayed/inactive), and estimated arrival times
3. WHEN a user applies filters THEN the system SHALL filter buses by bus ID and status as implemented in the existing filterBusCards function
4. IF no buses match the filter criteria THEN the system SHALL display an appropriate message
5. WHEN displaying bus routes THEN the system SHALL show predefined routes based on time of day (morning vs evening schedules)

### Requirement 3

**User Story:** As a passenger, I want to indicate that I'm currently on a specific bus, so that my GPS location can be used to track that bus in real-time with maximum accuracy.

#### Acceptance Criteria

1. WHEN a user clicks on a bus card THEN the system SHALL navigate to the bus tracking page with the selected bus ID
2. WHEN on the tracking page THEN the system SHALL display an "I'm on this Bus" button
3. WHEN a user clicks "I'm on this Bus" THEN the system SHALL start collecting their GPS coordinates every 15-30 seconds and associate data with their device token
4. WHEN GPS tracking is active THEN the system SHALL display a visual indicator showing the user is contributing to bus tracking and their current reputation score
5. WHEN collecting location data THEN the system SHALL validate coordinates against expected bus route patterns and speed limits
6. WHEN a user stops tracking THEN the system SHALL provide a way to end GPS sharing and update their session data
7. IF multiple users are tracking the same bus THEN the system SHALL use reputation-weighted averaging to determine the most accurate bus position
8. WHEN location data is inconsistent with other passengers THEN the system SHALL flag it for review and potentially reduce that user's reputation
9. IF a user provides consistently accurate location data THEN the system SHALL increase their reputation and give their future data higher priority

### Requirement 4

**User Story:** As a passenger, I want to view the real-time location of buses on a map, so that I can see where buses are currently located and plan my journey.

#### Acceptance Criteria

1. WHEN a user visits the track page THEN the system SHALL display a map with current bus locations
2. WHEN displaying bus locations THEN the system SHALL use the existing map functionality from map.js and track.js
3. WHEN bus location data is available THEN the system SHALL update bus markers on the map in real-time
4. WHEN displaying bus information THEN the system SHALL show bus ID, route name, current stop, next stop, and ETA as implemented in existing functions
5. WHEN no location data is available for a bus THEN the system SHALL display the bus as inactive or show last known location

### Requirement 5

**User Story:** As a system administrator, I want to manage bus schedules and routes with stoppage coordinates and radius validation, so that the system only accepts GPS data when buses are scheduled and users are within expected areas.

#### Acceptance Criteria

1. WHEN creating bus schedules THEN the system SHALL store route information with departure times, stops, stoppage coordinates, and coverage radius for each stop
2. WHEN determining active buses THEN the system SHALL check current time against bus schedules and only show currently running buses
3. WHEN a bus has multiple trips per day THEN the system SHALL track each trip separately with individual schedules
4. WHEN displaying routes THEN the system SHALL show different routes for morning and evening schedules based on current time
5. WHEN accepting GPS location data THEN the system SHALL only accept data if the bus is currently scheduled to be running AND user is within expected route radius
6. WHEN validating user location THEN the system SHALL check if GPS coordinates are within the defined radius of expected bus route or stops
7. IF a user is outside the expected radius THEN the system SHALL flag their data as potentially misleading and reduce its weight
8. IF a bus is not scheduled to run THEN the system SHALL reject any GPS data for that bus and not display it in the active bus list
9. WHEN a scheduled bus trip ends THEN the system SHALL stop accepting GPS data for that specific trip
10. WHEN a new scheduled trip begins THEN the system SHALL activate GPS data collection for that bus

### Requirement 6

**User Story:** As a system, I want to ensure data quality through comprehensive validation and prevent fake location data, so that bus locations are accurate and reliable.

#### Acceptance Criteria

1. WHEN a user first visits the website THEN the system SHALL generate a unique device-based token using browser fingerprinting and store it locally
2. WHEN the same device accesses the system THEN the system SHALL recognize it using the stored device token
3. WHEN a user shares location data THEN the system SHALL associate it with their device token and track accuracy over time
4. WHEN calculating reputation scores THEN the system SHALL consider factors like location consistency, movement patterns matching bus routes, and historical accuracy
5. WHEN validating location data THEN the system SHALL check for consistent movement on expected route without large GPS jumps
6. IF GPS coordinates show large jumps or inconsistent movement THEN the system SHALL flag it as spam and reject the data
7. WHEN multiple users track the same bus THEN the system SHALL weight location data based on each user's reputation score
8. WHEN determining bus position THEN the system SHALL use weighted averaging where higher reputation users have more influence
9. IF location data appears inconsistent with expected bus movement patterns THEN the system SHALL reduce that user's reputation score
10. IF a user consistently provides accurate location data THEN the system SHALL increase their reputation score
11. WHEN detecting potentially false data THEN the system SHALL implement validation checks like speed limits, route adherence, and proximity to other trusted users
12. IF a device token shows suspicious patterns THEN the system SHALL temporarily reduce its data weight until accuracy improves

### Requirement 7

**User Story:** As a user, I want to receive real-time updates about bus locations and status, so that I have current information without manually refreshing.

#### Acceptance Criteria

1. WHEN bus location data changes THEN the system SHALL push updates to all connected users in real-time using WebSockets
2. IF WebSocket connection fails THEN the system SHALL automatically fallback to AJAX polling every 10 seconds
3. WHEN using polling fallback THEN the system SHALL continue attempting to re-establish WebSocket connection
4. WHEN displaying real-time data THEN the system SHALL update the UI elements as implemented in existing JavaScript functions
5. IF real-time connection is lost THEN the system SHALL display connection status to users
6. WHEN receiving updates THEN the system SHALL update bus markers, ETA information, and status indicators
7. WHEN polling for updates THEN the system SHALL optimize requests to minimize server load and data usage

### Requirement 8

**User Story:** As a system, I want to handle scenarios where no one is tracking a bus with appropriate fallback displays, so that users understand the tracking status.

#### Acceptance Criteria

1. WHEN no users are tracking a scheduled bus THEN the system SHALL display "Tracking not active" status
2. WHEN a bus was previously tracked but no current data exists THEN the system SHALL display "Last seen at [time/location]"
3. WHEN displaying inactive tracking status THEN the system SHALL show the last known location and timestamp
4. WHEN a bus reaches its final destination THEN the system SHALL stop storing real-time location data for that trip
5. WHEN a bus trip is completed THEN the system SHALL move all trip data to historical storage
6. IF users start tracking an inactive bus THEN the system SHALL immediately activate real-time tracking and update the display

### Requirement 9

**User Story:** As a system, I want to handle scenarios where only one passenger is using the PWA site on a bus, so that bus location data is still collected and managed effectively.

#### Acceptance Criteria

1. WHEN only one user is tracking a bus THEN the system SHALL still collect and display their location data
2. WHEN a single user provides location data THEN the system SHALL validate it against the scheduled route and expected movement patterns
3. WHEN displaying single-user bus location THEN the system SHALL indicate the confidence level based on single-source data
4. WHEN more users join the same bus THEN the system SHALL transition to multi-user weighted averaging
5. WHEN a single user's data appears inconsistent THEN the system SHALL flag the bus location as uncertain
6. WHEN managing single-user data THEN the system SHALL store historical patterns to improve validation
7. IF a single user stops sharing location THEN the system SHALL mark the bus as having no current location data
8. WHEN validating single-user data THEN the system SHALL check against speed limits, route adherence, and time-based expectations

### Requirement 10

**User Story:** As a system administrator, I want to manage historical data efficiently, so that the system maintains fast performance while preserving important tracking history.

#### Acceptance Criteria

1. WHEN a bus trip is completed THEN the system SHALL move all real-time location data to a daily history table
2. WHEN storing historical data THEN the system SHALL organize it by date for efficient retrieval and management
3. WHEN a new day begins THEN the system SHALL create fresh real-time tracking tables for faster current operations
4. WHEN accessing historical data THEN the system SHALL be able to retrieve past trip information for analysis
5. WHEN managing database performance THEN the system SHALL automatically archive old historical data beyond a specified retention period
6. WHEN displaying historical information THEN the system SHALL show previous day's bus patterns and timing data
7. IF real-time tables become too large THEN the system SHALL implement automatic cleanup of completed trip data

### Requirement 11

**User Story:** As a developer, I want to integrate the existing Bootstrap UI components into Laravel views, so that the current functionality is preserved while adding backend capabilities.

#### Acceptance Criteria

1. WHEN integrating UI components THEN the system SHALL convert existing HTML to Laravel Blade templates
2. WHEN preserving JavaScript functionality THEN the system SHALL maintain all existing functions from app.js, map.js, and track.js
3. WHEN using Livewire components THEN the system SHALL ensure compatibility with existing JavaScript interactions
4. WHEN styling components THEN the system SHALL preserve all existing CSS from Bootstrap and custom stylesheets
5. IF conflicts arise between Livewire and existing JavaScript THEN the system SHALL resolve them while maintaining functionality