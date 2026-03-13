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
- **Model**: [`app/Models/BusRoute.php`](../app/Models/BusRoute.php)
- **Key Functions**:
  - Route creation with polyline coordinates
  - Distance and duration estimation
  - Proximity validation for location tracking
- **Dependencies**: JSON handling, Haversine distance calculation
- **Usage Example**:
```php
// Creating a route with GPS coordinates
BusRoute::create([
    'bus_id' => 1,
    'schedule_period_id' => 1,
    'name' => 'Main Route',
    'polyline' => json_encode($coordinates),
    'distance_km' => 12.5,
    'estimated_duration_minutes' => 45
]);
```

### Route Proximity Validation
- **Purpose**: Validates if user locations are near defined routes
- **Key Functions**:
  - `isPointNearRoute()`: Checks if coordinates are within threshold distance
  - `haversineDistance()`: Calculates distance between GPS points
- **Usage Example**:
```php
// Check if user location is near route
$isNearRoute = $route->isPointNearRoute($lat, $lng, 100); // 100 meters threshold
```

## Schedule Management

### Bus Scheduling
- **Purpose**: Manage bus departure times and schedules
- **Location**: [`app/Http/Controllers/Admin/ScheduleController.php`](../app/Http/Controllers/Admin/ScheduleController.php)
- **Model**: [`app/Models/BusSchedule.php`](../app/Models/BusSchedule.php)
- **Key Functions**:
  - Create schedules with departure times
  - Weekday-based scheduling
  - Specific date scheduling
  - Active/inactive schedule management
- **Usage Example**:
```php
// Creating a bus schedule
BusSchedule::create([
    'bus_id' => 1,
    'schedule_period_id' => 1,
    'departure_time' => '07:00',
    'weekdays' => [1, 2, 3, 4, 5], // Monday to Friday
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

### User Location Collection
- **Purpose**: Collect GPS data from users on buses
- **Location**: [`app/Http/Controllers/Api/LocationController.php`](../app/Http/Controllers/Api/LocationController.php)
- **Model**: [`app/Models/UserLocation.php`](../app/Models/UserLocation.php)
- **Key Functions**:
  - `saveLocation()`: Receives and validates GPS coordinates
  - User presence tracking
  - Active user cache management
- **API Endpoint**: `POST /api/save-location`
- **Usage Example**:
```javascript
// Sending location from frontend
fetch('/api/save-location', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        bus_id: 1,
        lat: 23.7937,
        lng: 90.3629,
        accuracy: 10,
        speed: 30
    })
});
```

### Bus Location Calculation
- **Purpose**: Calculate bus position from multiple user locations
- **Location**: [`app/Jobs/CalculateBusLocationJob.php`](../app/Jobs/CalculateBusLocationJob.php)
- **Model**: [`app/Models/BusLocation.php`](../app/Models/BusLocation.php)
- **Key Functions**:
  - Aggregate multiple user locations
  - Filter by route proximity
  - Calculate average position
  - Store calculated location with accuracy metrics
- **Dependencies**: Laravel Queues, Redis Cache
- **Usage Example**:
```php
// Dispatching location calculation job
CalculateBusLocationJob::dispatch($busId);
```

### Active User Management
- **Purpose**: Track which users are currently on which buses
- **Model**: [`app/Models/BusActiveUser.php`](../app/Models/BusActiveUser.php)
- **Features**:
  - User presence tracking
  - Inactivity timeout handling
  - Cache-based quick access
- **Usage Example**:
```php
// Update user presence on bus
BusActiveUser::updateOrCreate(
    ['bus_id' => 1, 'user_id' => 123],
    ['last_seen_at' => now()]
);
```

## Real-time Features

### WebSocket Broadcasting
- **Purpose**: Real-time updates of bus locations to connected clients
- **Location**: [`app/Events/BusLocationUpdated.php`](../app/Events/BusLocationUpdated.php)
- **Key Functions**:
  - Broadcast calculated bus locations
  - Channel-based communication per bus
  - Event-driven updates
- **Dependencies**: Laravel Echo, Pusher/WebSocket
- **Usage Example**:
```php
// Broadcasting bus location update
BusLocationUpdated::dispatch($busId, [
    'lat' => $avgLat,
    'lng' => $avgLng,
    'active_users' => $count,
    'calculated_at' => now()
]);
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
- **Location**: [`app/Models/SystemSetting.php`](../app/Models/SystemSetting.php)
- **Controller**: [`app/Http/Controllers/Admin/SettingsController.php`](../app/Http/Controllers/Admin/SettingsController.php)
- **Key Settings**:
  - `min_active_users`: Minimum users for location calculation (default: 2)
  - `location_update_interval`: GPS update frequency in seconds (default: 5)
  - `location_max_age`: Location data expiration in seconds (default: 120)
  - `inactive_user_timeout`: User inactivity timeout (default: 120)
  - `route_proximity_threshold`: Distance threshold for route validation (default: 100m)
  - `top_users_for_calculation`: Number of users used in calculation (default: 15)
- **Usage Example**:
```php
// Get system setting with type casting
$minUsers = SystemSetting::getValue('min_active_users', 2);

// Update system setting
SystemSetting::setValue('location_update_interval', 10);
```

### Caching Strategy
- **Purpose**: Optimize performance with intelligent caching
- **Features**:
  - 5-minute cache TTL for settings
  - Active user count caching
  - Location calculation rate limiting
  - Cache invalidation on updates

## API Endpoints

### Public Bus Information
- **Purpose**: Provide bus data to public consumers
- **Location**: [`app/Http/Controllers/Api/BusController.php`](../app/Http/Controllers/Api/BusController.php)
- **Endpoints**:
  - `GET /api/buses`: List all active buses with current locations
  - `GET /api/buses/{id}`: Get detailed information for specific bus
- **Response Format**:
```json
{
    "period": "Morning",
    "buses": [
        {
            "id": 1,
            "name": "Buriganga",
            "code": "B1",
            "direction": "A_TO_Z",
            "status": "active",
            "active_users": 5,
            "location": {
                "lat": 23.7937,
                "lng": 90.3629,
                "updated_at": "2023-12-07T10:30:00Z"
            },
            "route": {
                "id": 1,
                "name": "Main Route",
                "polyline": [...]
            }
        }
    ]
}
```

### Location Tracking API
- **Purpose**: Handle user location submissions and bus interactions
- **Endpoints**:
  - `POST /api/save-location`: Submit user GPS coordinates
  - `POST /api/confirm-bus`: Confirm user is on specific bus
  - `POST /api/leave-bus`: User leaves bus tracking
- **Authentication**: Bearer token required
- **Validation**: Coordinate bounds, accuracy limits

## Frontend Features

### Interactive Map Interface
- **Location**: [`public/assets/js/track.js`](../public/assets/js/track.js)
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