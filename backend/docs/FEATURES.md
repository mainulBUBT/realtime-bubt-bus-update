# BUBT Bus Tracker - Features Documentation

## Table of Contents
- [Overview](#overview)
- [Authentication System](#authentication-system)
- [Bus Management](#bus-management)
- [Route Management](#route-management)
- [Schedule Management](#schedule-management)
- [Location Tracking System](#location-tracking-system)
- [Real-time Features](#real-time-features)
- [User Management](#user-management)
- [System Settings](#system-settings)
- [API Endpoints](#api-endpoints)
- [Frontend Features](#frontend-features)
- [Mobile App Support with Capacitor](#mobile-app-support-with-capacitor)
- [Data Management](#data-management)
- [Security Features](#security-features)
- [Performance Optimization](#performance-optimization)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Testing Framework](#testing-framework)

## Overview

The BUBT Bus Tracker is a comprehensive real-time bus tracking system built with Laravel (backend) and modern web technologies (frontend). The application allows users to track bus locations in real-time through crowd-sourced GPS data, while administrators can manage buses, routes, schedules, and system settings through an admin panel.

## Authentication System

### Google OAuth Integration
- **Purpose**: Allows users to authenticate using their Google accounts
- **Location**: [`app/Http/Controllers/Auth/GoogleController.php`](../app/Http/Controllers/Auth/GoogleController.php)
- **Key Functions**:
  - `redirectToGoogle()`: Redirects users to Google OAuth
  - `handleGoogleCallback()`: Handles OAuth callback and creates/updates user
- **Dependencies**: Laravel Socialite
- **Usage Example**:
```php
// User authentication flow
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
```

### Admin Authentication
- **Purpose**: Provides secure access to administrative features
- **Location**: [`app/Http/Controllers/Admin/AuthController.php`](../app/Http/Controllers/Admin/AuthController.php)
- **Key Functions**:
  - `showLoginForm()`: Displays admin login form
  - `login()`: Validates credentials and authenticates admin users
- **Dependencies**: Laravel Authentication
- **Usage Example**:
```php
// Admin authentication with middleware protection
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Protected admin routes
});
```

## Bus Management

### Bus CRUD Operations
- **Purpose**: Complete lifecycle management of bus entities
- **Location**: [`app/Http/Controllers/Admin/BusController.php`](../app/Http/Controllers/Admin/BusController.php)
- **Model**: [`app/Models/Bus.php`](../app/Models/Bus.php)
- **Key Functions**:
  - `index()`: Lists all buses with route counts
  - `create()`/`store()`: Creates new buses with validation
  - `edit()`/`update()`: Modifies existing bus information
  - `destroy()`: Soft deletes buses
- **Dependencies**: Laravel Eloquent ORM, SoftDeletes
- **Usage Example**:
```php
// Creating a new bus
Bus::create([
    'name' => 'Buriganga',
    'code' => 'B1',
    'direction' => 'A_TO_Z',
    'capacity' => 40,
    'start_location' => 'Asad Gate',
    'end_location' => 'BUBT'
]);
```

### Bus Status Management
- **Purpose**: Track active/inactive status of buses
- **Features**:
  - Active status filtering
  - Direction-based queries (A_TO_Z, Z_TO_A)
  - Relationship management with routes and schedules
- **Usage Example**:
```php
// Get active buses with current routes
$activeBuses = Bus::active()->with('currentRoute')->get();
```

## Route Management

### Route Configuration
- **Purpose**: Define bus routes with GPS coordinates and stops
- **Location**: [`app/Http/Controllers/Admin/RouteController.php`](../app/Http/Controllers/Admin/RouteController.php)
- **Model**: [`app/Models/Route.php`](../app/Models/Route.php)
- **Key Functions**:
  - Route creation with polyline coordinates
  - Distance and duration estimation
  - Association with route stops
- **Dependencies**: JSON handling, Haversine distance calculation
- **Usage Example**:
```php
// Creating a route with GPS coordinates
Route::create([
    'name' => 'Main Route',
    'polyline' => json_encode($coordinates),
    'distance_km' => 12.5,
    'estimated_duration_minutes' => 45,
    'is_active' => true
]);
```

### Route Stops
- **Purpose**: Define individual stops along routes
- **Model**: [`app/Models/RouteStop.php`](../app/Models/RouteStop.php)
- **Features**:
  - Stop name and coordinates
  - Order in route sequence
  - Arrival time offset from route start

## Schedule Management

### Bus Scheduling
- **Purpose**: Manage bus departure times and schedules
- **Location**: [`app/Http/Controllers/Admin/ScheduleController.php`](../app/Http/Controllers/Admin/ScheduleController.php)
- **Model**: [`app/Models/Schedule.php`](../app/Models/Schedule.php)
- **Key Functions**:
  - Create schedules with departure times
  - Weekday-based scheduling
  - Active/inactive schedule management
- **Usage Example**:
```php
// Creating a bus schedule
Schedule::create([
    'bus_id' => 1,
    'route_id' => 1,
    'schedule_period_id' => 1,
    'departure_time' => '07:00',
    'arrival_time' => '07:45',
    'weekdays' => '{"mon":true,"tue":true,"wed":true,"thu":true,"fri":true}',
    'is_active' => true
]);
```

### Schedule Periods
- **Purpose**: Define time periods for different schedule patterns
- **Model**: [`app/Models/SchedulePeriod.php`](../app/Models/SchedulePeriod.php)
- **Features**:
  - Current period detection
  - Active period filtering
  - Time-based route selection

## Location Tracking System

### Driver Location Collection
- **Purpose**: Collect GPS data from drivers during trips
- **Location**: [`app/Http/Controllers/Api/Driver/LocationController.php`](../app/Http/Controllers/Api/Driver/LocationController.php)
- **Model**: [`app/Models/Location.php`](../app/Models/Location.php)
- **Key Functions**:
  - `update()`: Receives and validates GPS coordinates from driver
  - `batchUpdate()`: Accepts batch location submissions
  - Trip association
- **API Endpoint**: `POST /api/driver/location`
- **Usage Example**:
```javascript
// Sending location from driver frontend
fetch('/api/driver/location', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        trip_id: 1,
        lat: 23.7937,
        lng: 90.3629,
        accuracy: 10,
        speed: 30,
        heading: 180
    })
});
```

### Bus Location Broadcast
- **Purpose**: Broadcast driver location to all subscribed clients
- **Location**: [`app/Events/BusLocationUpdated.php`](../app/Events/BusLocationUpdated.php)
- **Key Functions**:
  - Broadcast location via Laravel Reverb
  - Channel-based communication per bus
- **Dependencies**: Laravel Echo, Laravel Reverb (WebSocket)
- **Usage Example**:
```php
// Broadcasting bus location update
broadcast(new BusLocationUpdated($busId, [
    'lat' => $location->lat,
    'lng' => $location->lng,
    'trip_id' => $trip->id,
    'calculated_at' => now()
]));
```

### Trip Location History
- **Purpose**: Store and retrieve trip location history
- **Model**: [`app/Models/Location.php`](../app/Models/Location.php)
- **Features**:
  - Location history per trip
  - Speed and heading data
  - Accuracy metrics

## Real-time Features

### WebSocket Broadcasting
- **Purpose**: Real-time updates of bus locations to connected clients
- **Location**: [`app/Events/BusLocationUpdated.php`](../app/Events/BusLocationUpdated.php)
- **Key Functions**:
  - Broadcast driver location via Laravel Reverb
  - Channel-based communication per bus
  - Event-driven updates
- **Dependencies**: Laravel Echo, Laravel Reverb (WebSocket)
- **Usage Example**:
```php
// Broadcasting bus location update (using broadcast() directly)
broadcast(new BusLocationUpdated($busId, [
    'lat' => $location->lat,
    'lng' => $location->lng,
    'trip_id' => $trip->id,
    'calculated_at' => now()
]));
```

### Frontend Real-time Updates
- **Location**: [`resources/js/app.js`](../resources/js/app.js)
- **Features**:
  - Laravel Echo integration
  - Channel subscription for bus updates
  - Dynamic marker updates on maps
- **Usage Example**:
```javascript
// Listening for bus location updates
window.Echo.channel('bus.' + busId)
    .listen('BusLocationUpdated', (e) => {
        updateBusMarker(e.bus_id, e.lat, e.lng);
    });
```

## User Management

### User Profile Management
- **Purpose**: Manage user accounts and profiles
- **Model**: [`app/Models/User.php`](../app/Models/User.php)
- **Features**:
  - Google OAuth integration
  - Reputation scoring system
  - Ban/unban functionality
  - Avatar management
- **Usage Example**:
```php
// Creating user from Google OAuth
User::updateOrCreate([
    'google_id' => $googleUser->id,
], [
    'name' => $googleUser->name,
    'email' => $googleUser->email,
    'avatar' => $googleUser->avatar,
    'email_verified' => true
]);
```

### User Reputation System
- **Purpose**: Track user reliability for location data
- **Features**:
  - Decimal precision scoring
  - Influence on location calculation weight
  - Admin-controlled reputation adjustments

## System Settings

### Configuration Management
- **Purpose**: Centralized system configuration
- **Location**: [`app/Models/Setting.php`](../app/Models/Setting.php)
- **Controller**: [`app/Http/Controllers/Admin/SettingsController.php`](../app/Http/Controllers/Admin/SettingsController.php)
- **Key Settings**:
  - `app_name`: Application name
  - `app_version`: Application version
  - `maintenance_mode`: Maintenance status flag
- **Usage Example**:
```php
// Get setting value
$value = Setting::get('key', 'default');

// Update setting
Setting::set('key', 'value');
```

### Caching Strategy
- **Purpose**: Optimize performance with intelligent caching
- **Features**:
  - 5-minute cache TTL for settings
  - Active user count caching
  - Location calculation rate limiting
  - Cache invalidation on updates

## API Endpoints

### Public Routes
- **Purpose**: Routes accessible without authentication
- **Endpoints**:
  - `POST /api/auth/login` - User login
  - `POST /api/auth/register` - User registration
  - `GET /api/settings` - Get app settings

### Authentication Routes (Protected - Bearer Token)
- **Purpose**: User authentication management
- **Endpoints**:
  - `POST /api/auth/logout` - Logout user
  - `GET /api/auth/me` - Get current user
  - `PATCH /api/auth/profile` - Update user profile
  - `PATCH /api/auth/password` - Update password

### Driver Routes (Protected - role:driver)
- **Purpose**: Driver-specific endpoints for trip management
- **Endpoints**:
  - `GET /api/driver/buses` - List available buses
  - `GET /api/driver/routes` - List available routes
  - `POST /api/driver/trips/start` - Start a new trip
  - `POST /api/driver/trips/{trip}/end` - End a trip
  - `GET /api/driver/trips/current` - Get current active trip
  - `GET /api/driver/trips/history` - Get trip history
  - `POST /api/driver/location` - Submit single GPS location
  - `POST /api/driver/location/batch` - Submit batch GPS locations

### Student Routes (Protected - role:student)
- **Purpose**: Student-specific endpoints for tracking
- **Endpoints**:
  - `GET /api/student/routes` - List available routes
  - `GET /api/student/routes/{id}` - Get route details
  - `GET /api/student/trips/active` - Get active trips for student
  - `GET /api/student/trips/{tripId}/locations` - Get locations for a trip
  - `GET /api/student/trips/{tripId}/latest-location` - Get latest location
  - `GET /api/student/schedules` - Get bus schedules
  - `POST /api/student/fcm-token` - Update FCM token for push notifications
  - `GET /api/student/notifications` - Get notifications
  - `GET /api/student/notifications/unread-count` - Get unread count
  - `POST /api/student/notifications/{id}/read` - Mark notification as read
  - `POST /api/student/notifications/read-all` - Mark all as read

### Admin Routes (Protected - role:admin)
- **Purpose**: Administrative endpoints for managing buses, routes, schedules
- **Endpoints**:
  - `GET/POST/PUT/DELETE /api/admin/buses` - Bus CRUD operations
  - `GET/POST/PUT/DELETE /api/admin/routes` - Route CRUD operations
  - `GET/POST/PUT/DELETE /api/admin/schedules` - Schedule CRUD operations

## Frontend Features

### Interactive Map Interface
- **Location**: [`frontend/src/stores/useMapStore.js`](../frontend/src/stores/useMapStore.js) and Vue components
- **Features**:
  - OpenStreetMap integration with Leaflet.js
  - Real-time bus position updates
  - Custom bus markers with IDs
  - Responsive design for mobile/desktop
  - Map controls (zoom, center)
- **Usage Example**:
```javascript
// Initialize map with bus tracking
function initMap() {
    map = L.map('map').setView([23.7937, 90.3629], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    // Add bus marker
    busMarker = L.marker([lat, lng], { icon: busIcon }).addTo(map);
}
```

### Route Timeline
- **Purpose**: Visualize bus route progress with stops
- **Features**:
  - Interactive timeline with stop status
  - Estimated arrival times
  - Completed/current/upcoming stop indicators
  - Progress bars between stops

### Bottom Sheet UI
- **Purpose**: Expandable information panel for mobile
- **Features**:
  - Touch/drag interaction
  - Responsive height adjustment
  - Bus information display
  - Status cards (speed, traffic)
  - Action buttons (favorite, share)

### Real-time Updates
- **Purpose**: Keep UI synchronized with backend data
- **Features**:
  - WebSocket connection management
  - Automatic marker updates
  - ETA calculations
  - Status change notifications

## Mobile App Support with Capacitor

### Backend Setup for Capacitor Development

#### Prerequisites
1. **PHP Environment**: PHP 8.1+ with required extensions
2. **Database**: MySQL, PostgreSQL, or SQLite
3. **Composer**: PHP dependency manager
4. **Web Server**: Apache, Nginx, or PHP Artisan serve

#### Installation Steps
1. **Clone Repository**:
```bash
git clone <repository-url>
cd realtime-bubt-bus-update
```

2. **Install Dependencies**:
```bash
# Backend
cd backend
composer install

# Frontend
cd ../frontend
npm install
```

3. **Environment Configuration**:
```bash
cp .env.example .env
# Edit .env with your database and app settings
```

4. **Generate Application Key**:
```bash
php artisan key:generate
```

5. **Database Setup**:
```bash
# Create database
php artisan migrate

# Seed with sample data
php artisan db:seed
```

#### Running the Backend

##### Development Server
```bash
# Start Laravel development server
php artisan serve

# The application will be available at http://localhost:8000
```

##### Queue Worker (for real-time features)
```bash
# Start queue worker in separate terminal
php artisan queue:work

# Or use supervisord for production
```

##### Broadcasting Server (for real-time updates)
```bash
# Start Laravel Reverb for WebSocket broadcasting
php artisan reverb:start

# Or use Pusher/Laravel Echo
```

### Capacitor Configuration
- **Purpose**: Enable native mobile app deployment for iOS and Android
- **Location**: [`capacitor.config.ts`](../capacitor.config.ts)
- **App Configuration**:
  - App ID: `com.bubt.bustracker`
  - App Name: `BUBT Bus Tracker`
  - Web Directory: `public`
  - Background Geolocation Plugin: Enabled with 10-second heartbeat

### How to Use Capacitor

#### Prerequisites
1. Install Node.js and npm
2. Install Capacitor CLI globally:
```bash
npm install -g @capacitor/cli
```

#### Setup Process
1. **Install Dependencies**:
```bash
cd backend
composer install
cd ../frontend
npm install
```

2. **Build Web Assets**:
```bash
npm run build
```

3. **Add Native Platforms**:
```bash
# Add iOS platform
npx cap add ios

# Add Android platform
npx cap add android
```

4. **Configure Development Server**:
   - Update [`capacitor.config.ts`](../capacitor.config.ts) with your local IP:
```typescript
server: {
    androidScheme: 'https',
    url: 'http://192.168.1.x:8000', // Your local IP
    cleartext: true
}
```

5. **Sync Web Assets to Native Projects**:
```bash
npx cap sync
```

#### Development Workflow

##### Web Development
```bash
# Start Laravel development server
php artisan serve

# Start Vite development server
npm run dev
```

##### iOS Development
```bash
# Open in Xcode
npx cap open ios

# Or run directly
npx cap run ios
```

##### Android Development
```bash
# Open in Android Studio
npx cap open android

# Or run directly
npx cap run android
```

#### Production Build

##### Web Build
```bash
npm run build
# Output in public/build/
```

##### iOS Build
```bash
npx cap build ios
# Follow Xcode build process
```

##### Android Build
```bash
npx cap build android
# Follow Android Studio build process
```

### Local Testing with Capacitor

#### Complete Development Setup
1. **Start Backend Services**:
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker (for location calculations)
php artisan queue:work

# Terminal 3: WebSocket server (for real-time updates)
php artisan reverb:start
```

2. **Get Your Local IP**:
```bash
# On macOS/Linux
ifconfig | grep "inet " | grep -v 127.0.0.1

# On Windows
ipconfig | findstr "IPv4"
```

3. **Update Capacitor Configuration**:
   - Edit [`capacitor.config.ts`](../capacitor.config.ts)
   - Replace `192.168.1.x` with your actual IP
   - Ensure `cleartext: true` for HTTP development

4. **Build and Sync**:
```bash
npm run build
npx cap sync
```

5. **Run on Device/Simulator**:
```bash
# iOS Simulator
npx cap run ios

# Android Emulator
npx cap run android

# Physical Device (connected via USB)
npx cap run android --target=<device-id>
```

#### Testing Checklist
- [ ] Backend services running (Laravel, Queue, WebSocket)
- [ ] Correct local IP in Capacitor config
- [ ] Device and development machine on same network
- [ ] Firewall allows connections on port 8000
- [ ] HTTPS certificate issues resolved (if using HTTPS)
- [ ] Location permissions granted on mobile device

### Background Location Tracking

#### Capacitor Background Geolocation Plugin
- **Purpose**: Continuous GPS tracking when app is in background
- **Configuration**: [`capacitor.config.ts`](../capacitor.config.ts)
- **Key Settings**:
  - `preventSuspend: true`: Prevents app from suspending
  - `heartbeatInterval: 10`: Location update frequency in seconds

#### Implementation in JavaScript
- **Location**: [`resources/js/app.js`](../resources/js/app.js)
- **Platform Detection**:
```javascript
const isCapacitor = typeof window !== 'undefined' && window.Capacitor;
```

#### Location Tracking Functions
```javascript
// Start background location tracking
window.startBackgroundLocation = function (busId) {
    if (isCapacitor) {
        // Mobile: Use Capacitor Background Geolocation
        BackgroundGeolocation.addWatcher({
            backgroundMessage: "Tracking your location for bus updates",
            backgroundTitle: "BUBT Bus Tracker",
            requestPermissions: true,
            distanceFilter: 10 // meters
        }, function callback(location, error) {
            if (error) {
                console.error("Location error:", error);
                return;
            }
            sendLocationToServer(busId, location.latitude, location.longitude);
        });
    } else {
        // Web: Use browser Geolocation API
        navigator.geolocation.watchPosition(
            function (position) {
                sendLocationToServer(busId, position.coords.latitude, position.coords.longitude);
            },
            function (error) {
                console.error("Geolocation error:", error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    }
};
```

#### Platform-Specific Considerations

##### iOS
1. **Permissions**: Add location permissions to `Info.plist`
2. **Background Modes**: Enable "Location updates" in capabilities
3. **App Store**: Include location usage description in metadata

##### Android
1. **Permissions**: Add to `AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION" />
```
2. **Background Service**: Configure for Android 8+ background limitations
3. **Play Store**: Include location disclosure in app description

### Capacitor Dependencies
- **Location**: [`package.json`](../package.json)
- **Key Plugins**:
  - `@capacitor-community/background-geolocation`: Background GPS tracking
  - `@capacitor/geolocation`: Standard GPS functionality
  - `@capacitor/android`: Android platform support
  - `@capacitor/ios`: iOS platform support

### Troubleshooting Common Issues

#### Build Issues
1. **Clean and Rebuild**:
```bash
npx cap clean
npx cap sync
```

2. **Platform-Specific Issues**:
   - iOS: Check Xcode provisioning profiles
   - Android: Verify Android SDK and build tools

#### Location Permission Issues
1. **iOS**: Check `Info.plist` permissions
2. **Android**: Verify runtime permission requests
3. **Web**: Ensure HTTPS for location API (required by browsers)

#### Development Server Connection
1. **Ensure Same Network**: Device and development machine on same WiFi
2. **Firewall**: Check that port 8000 is accessible
3. **IP Address**: Use correct local IP in configuration
4. **SSL Issues**: Use `cleartext: true` for HTTP in development

## Data Management

### Database Schema
- **Purpose**: Structured data storage for all application features
- **Key Tables**:
  - `users`: User accounts and profiles
  - `buses`: Bus information and status
  - `bus_routes`: Route definitions with GPS data
  - `bus_stops`: Individual stop locations
  - `bus_schedules`: Departure times and patterns
  - `schedule_periods`: Time period definitions
  - `user_locations`: Raw GPS data from users
  - `bus_locations`: Calculated bus positions
  - `bus_active_users`: Current user presence tracking
  - `system_settings`: Configuration parameters

### Data Seeding
- **Purpose**: Initialize system with sample data
- **Location**: [`database/seeders/`](../database/seeders/)
- **Features**:
  - Bus schedule generation
  - Route creation with sample coordinates
  - Admin account creation
  - System settings initialization

## Security Features

### Authentication Guards
- **Purpose**: Protect sensitive endpoints and features
- **Features**:
  - Session-based web authentication
  - Token-based API authentication
  - Admin role verification
  - Route protection middleware

### Data Validation
- **Purpose**: Ensure data integrity and prevent abuse
- **Features**:
  - GPS coordinate bounds checking
  - Rate limiting for location submissions
  - Input sanitization
  - CSRF protection

### User Management
- **Purpose**: Control user access and behavior
- **Features**:
  - User banning functionality
  - Reputation-based trust scoring
  - Activity monitoring
  - Automated cleanup of inactive users

## Performance Optimization

### Caching Strategy
- **Purpose**: Reduce database load and improve response times
- **Features**:
  - Redis/Memcached support
  - Query result caching
  - Active user count caching
  - Settings caching with TTL

### Queue System
- **Purpose**: Handle background processing efficiently
- **Features**:
  - Asynchronous location calculations
  - Job rate limiting
  - Failed job handling
  - Queue monitoring

### Database Optimization
- **Purpose**: Ensure efficient data operations
- **Features**:
  - Indexed queries for location data
  - Soft deletes for data retention
  - Relationship eager loading
  - Query optimization for real-time features

## Monitoring and Maintenance

### Logging System
- **Purpose**: Track application behavior and issues
- **Features**:
  - Error logging for location failures
  - Performance monitoring
  - User activity tracking
  - System event logging

### Cleanup Jobs
- **Purpose**: Maintain system performance and storage
- **Location**: [`app/Jobs/CleanupInactiveUsersJob.php`](../app/Jobs/CleanupInactiveUsersJob.php)
- **Features**:
  - Automatic removal of inactive users
  - Location data expiration
  - Cache cleanup
  - Scheduled maintenance tasks

## Testing Framework

### Test Coverage
- **Purpose**: Ensure application reliability
- **Location**: [`tests/`](../tests/)
- **Features**:
  - Unit tests for models and utilities
  - Feature tests for API endpoints
  - Authentication testing
  - Location calculation testing