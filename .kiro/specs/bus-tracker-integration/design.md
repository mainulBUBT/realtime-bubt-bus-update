# Design Document

## Overview

This design document outlines the integration of an existing Bootstrap-based bus tracker UI into a Laravel 12 + Livewire 3 application. The system will create a comprehensive university bus tracking platform that leverages passenger GPS data to provide real-time bus location tracking with accuracy validation, reputation scoring, and schedule-based activation.

The architecture follows a modular approach where the existing UI components are preserved while adding robust backend data management, real-time communication, and GPS-based location sharing capabilities.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer                           │
├─────────────────────────────────────────────────────────────┤
│  Bootstrap UI + Custom CSS + JavaScript (Preserved)        │
│  Laravel Blade Templates + Livewire Components             │
│  Real-time Updates (WebSocket/Polling)                     │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                 Application Layer                           │
├─────────────────────────────────────────────────────────────┤
│  Laravel Controllers + Livewire Components                 │
│  GPS Data Processing + Validation Services                 │
│  Device Token Management + Reputation System               │
│  Schedule Management + Route Validation                    │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                   Data Layer                               │
├─────────────────────────────────────────────────────────────┤
│  Real-time Location Data (MySQL with optimized indexes)    │
│  Historical Data (MySQL with daily archiving)              │
│  Device Tokens + Reputation Scores                         │
│  Bus Schedules + Route Coordinates                         │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

- **Backend Framework**: Laravel 12
- **Frontend Interactivity**: Livewire 3
- **Real-time Communication**: Laravel Reverb (WebSocket, free unlimited) with AJAX polling fallback
- **Database**: MySQL (primary), Database caching for real-time data
- **Frontend**: Bootstrap 5 + Custom CSS + JavaScript (preserved from existing UI)
- **Maps**: Leaflet.js with OpenStreetMap
- **GPS**: Browser Geolocation API

## Components and Interfaces

### 1. Device Token Management System

**Purpose**: Generate and manage unique device-based tokens for anonymous user identification and reputation tracking.

**Components**:
- `DeviceTokenService`: Handles token generation and validation
- `DeviceFingerprint`: JavaScript utility for browser fingerprinting
- `device_tokens` table: Stores device information and reputation scores

**Implementation**:
```php
class DeviceTokenService
{
    public function generateToken(array $fingerprint): string
    public function validateToken(string $token): bool
    public function getReputationScore(string $token): float
    public function updateReputation(string $token, float $score): void
}
```

**JavaScript Integration**:
```javascript
class DeviceFingerprint {
    generateFingerprint(): object
    storeToken(token: string): void
    getStoredToken(): string
}
```

### 2. GPS Location Management

**Purpose**: Collect, validate, and process GPS location data from passengers.

**Components**:
- `LocationService`: Core GPS data processing
- `LocationValidator`: Validates GPS data against routes and patterns
- `LocationAggregator`: Combines multiple user locations for bus positioning

**Database Schema**:
```sql
-- Real-time location data (optimized for fast queries)
CREATE TABLE bus_locations (
    id BIGINT PRIMARY KEY,
    bus_id VARCHAR(10),
    device_token VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    accuracy FLOAT,
    speed FLOAT,
    timestamp TIMESTAMP,
    reputation_weight FLOAT,
    is_validated BOOLEAN,
    INDEX idx_bus_timestamp (bus_id, timestamp),
    INDEX idx_active_locations (bus_id, timestamp, is_validated)
);

-- Historical location data (daily archiving)
CREATE TABLE bus_location_history (
    id BIGINT PRIMARY KEY,
    bus_id VARCHAR(10),
    trip_date DATE,
    location_data JSON,
    trip_summary JSON,
    created_at TIMESTAMP,
    INDEX idx_bus_date (bus_id, trip_date)
);

-- Cache table for aggregated bus positions (updated every 10-30 seconds)
CREATE TABLE bus_current_positions (
    bus_id VARCHAR(10) PRIMARY KEY,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    confidence_level FLOAT,
    last_updated TIMESTAMP,
    active_trackers INT,
    trusted_trackers INT,
    average_trust_score FLOAT,
    status ENUM('active', 'inactive', 'no_data'),
    last_known_location JSON,
    movement_consistency FLOAT
);

-- User tracking sessions for reliability management
CREATE TABLE user_tracking_sessions (
    id BIGINT PRIMARY KEY,
    device_token VARCHAR(255),
    bus_id VARCHAR(10),
    started_at TIMESTAMP,
    ended_at TIMESTAMP,
    is_active BOOLEAN DEFAULT true,
    trust_score_at_start FLOAT,
    locations_contributed INT DEFAULT 0,
    valid_locations INT DEFAULT 0,
    INDEX idx_active_sessions (bus_id, is_active, started_at)
);
```

### 3. Bus Schedule Management

**Purpose**: Manage bus schedules, routes, and coordinate validation.

**Components**:
- `BusScheduleService`: Manages schedules and active bus determination
- `RouteValidator`: Validates GPS coordinates against expected routes
- `StopCoordinateManager`: Manages bus stop coordinates and coverage radius

**Database Schema**:
```sql
CREATE TABLE bus_schedules (
    id BIGINT PRIMARY KEY,
    bus_id VARCHAR(10),
    route_name VARCHAR(100),
    departure_time TIME,
    arrival_time TIME,
    days_of_week JSON,
    is_active BOOLEAN
);

CREATE TABLE bus_routes (
    id BIGINT PRIMARY KEY,
    schedule_id BIGINT,
    stop_name VARCHAR(100),
    stop_order INT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    coverage_radius INT, -- in meters
    estimated_time TIME
);
```

### 4. Real-time Communication System

**Purpose**: Provide real-time updates with WebSocket primary and AJAX polling fallback using only free tools.

**Components**:
- `BusLocationBroadcaster`: Broadcasts location updates using Laravel Reverb (free, unlimited connections)
- `WebSocketHandler`: Manages WebSocket connections with Laravel Reverb
- `PollingController`: Handles AJAX polling requests (fallback when WebSocket fails)
- `DatabaseCacheManager`: Manages cached bus positions in MySQL for fast retrieval
- `ConnectionScaler`: Manages 250-300+ concurrent connections efficiently

**Livewire Components**:
```php
class BusTracker extends Component
{
    public $busId;
    public $currentLocation;
    public $isTracking = false;
    
    protected $listeners = ['locationUpdated' => 'updateLocation'];
    
    public function startTracking()
    public function stopTracking()
    public function updateLocation($data)
}
```

### 5. Reputation System

**Purpose**: Maintain user reputation scores based on location data accuracy.

**Components**:
- `ReputationCalculator`: Calculates reputation scores
- `DataQualityAnalyzer`: Analyzes location data quality
- `WeightedAverageCalculator`: Computes weighted bus positions

**Algorithm**:
```php
class ReputationCalculator
{
    public function calculateScore(array $factors): float
    {
        // Factors: consistency, route_adherence, speed_validation, 
        // proximity_to_others, historical_accuracy
        return weighted_average($factors);
    }
}
```

### 6. UI Integration Layer

**Purpose**: Seamlessly integrate existing Bootstrap UI with Laravel Blade and Livewire.

**Blade Templates Structure**:
```
resources/views/
├── layouts/
│   ├── app.blade.php (main PWA layout)
│   ├── mobile.blade.php (mobile-specific layout)
│   └── admin.blade.php (admin panel layout)
├── components/
│   ├── bus-card.blade.php
│   ├── location-permission.blade.php
│   └── tracking-map.blade.php
├── admin/
│   ├── auth/
│   │   └── login.blade.php
│   ├── dashboard.blade.php
│   ├── buses/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── schedules/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── route-builder.blade.php
│   ├── settings/
│   │   ├── business.blade.php
│   │   └── system.blade.php
│   └── monitoring/
│       ├── live-tracking.blade.php
│       └── analytics.blade.php
├── livewire/
│   ├── bus-list.blade.php
│   ├── bus-tracker.blade.php
│   └── location-sharing.blade.php
└── pages/
    ├── home.blade.php
    └── track.blade.php
```

## Data Models

### 1. Device Token Model
```php
class DeviceToken extends Model
{
    protected $fillable = [
        'token_hash',
        'fingerprint_data',
        'reputation_score',
        'trust_score',
        'total_contributions',
        'accurate_contributions',
        'clustering_score',
        'movement_consistency',
        'last_activity',
        'is_trusted'
    ];
    
    protected $casts = [
        'fingerprint_data' => 'array',
        'reputation_score' => 'float',
        'trust_score' => 'float',
        'clustering_score' => 'float',
        'movement_consistency' => 'float',
        'is_trusted' => 'boolean'
    ];
    
    public function isTrustedDevice(): bool
    {
        return $this->trust_score >= 0.7 && $this->is_trusted;
    }
    
    public function updateTrustScore(float $newScore): void
    {
        $this->trust_score = min(1.0, max(0.0, $newScore));
        $this->is_trusted = $this->trust_score >= 0.7;
        $this->save();
    }
}
```

### 2. Bus Schedule Model
```php
class BusSchedule extends Model
{
    protected $fillable = [
        'bus_id',
        'route_name',
        'departure_time',
        'arrival_time',
        'days_of_week',
        'is_active'
    ];
    
    protected $casts = [
        'days_of_week' => 'array',
        'departure_time' => 'datetime:H:i',
        'arrival_time' => 'datetime:H:i'
    ];
    
    public function routes()
    {
        return $this->hasMany(BusRoute::class, 'schedule_id');
    }
    
    public function isCurrentlyActive(): bool
    {
        // Check if bus should be running based on current time and schedule
    }
}
```

### 3. Bus Location Model
```php
class BusLocation extends Model
{
    protected $fillable = [
        'bus_id',
        'device_token',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'reputation_weight',
        'is_validated'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'speed' => 'float',
        'reputation_weight' => 'float',
        'is_validated' => 'boolean'
    ];
}
```

### 4. Bus Route Model
```php
class BusRoute extends Model
{
    protected $fillable = [
        'schedule_id',
        'stop_name',
        'stop_order',
        'latitude',
        'longitude',
        'coverage_radius',
        'estimated_time'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'coverage_radius' => 'integer',
        'estimated_time' => 'datetime:H:i'
    ];
    
    public function isWithinRadius(float $lat, float $lng): bool
    {
        // Calculate distance and check if within coverage radius
    }
}
```

### 5. Admin User Model
```php
class AdminUser extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active'
    ];
    
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];
}
```

### 6. Business Setting Model
```php
class BusinessSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];
    
    protected $casts = [
        'value' => 'json'
    ];
    
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
    
    public static function set(string $key, $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
```

## Error Handling

### 1. GPS Data Validation Errors
- **Invalid Coordinates**: Reject coordinates outside Bangladesh boundaries
- **Speed Violations**: Flag locations showing impossible speed changes
- **Route Deviation**: Handle locations outside expected route radius
- **Timestamp Issues**: Validate location timestamps for consistency

### 2. Real-time Communication Errors
- **WebSocket Failures**: Automatic fallback to AJAX polling
- **Connection Loss**: Retry mechanisms with exponential backoff
- **Data Synchronization**: Handle conflicts between WebSocket and polling data

### 3. Schedule Validation Errors
- **Inactive Bus Tracking**: Reject GPS data for non-scheduled buses
- **Time Boundary Issues**: Handle edge cases around schedule start/end times
- **Route Mismatch**: Validate GPS data against correct route for time of day

### 4. Device Token Errors
- **Token Corruption**: Regenerate tokens when validation fails
- **Reputation Anomalies**: Handle sudden reputation score changes
- **Storage Failures**: Fallback mechanisms for token persistence

## Testing Strategy

### 1. Unit Testing
- **GPS Validation Logic**: Test coordinate validation, speed checks, route adherence
- **Reputation Calculations**: Test scoring algorithms with various data scenarios
- **Schedule Management**: Test active bus determination and time-based logic
- **Device Token Generation**: Test uniqueness and validation mechanisms

### 2. Integration Testing
- **Livewire Components**: Test real-time updates and user interactions
- **Database Operations**: Test location data storage and retrieval
- **WebSocket Communication**: Test real-time broadcasting and fallback mechanisms
- **API Endpoints**: Test AJAX polling endpoints and data consistency

### 3. End-to-End Testing
- **User Journey**: Test complete flow from permission request to location sharing
- **Multi-user Scenarios**: Test location aggregation with multiple users
- **Schedule Transitions**: Test behavior during bus schedule changes
- **Error Recovery**: Test system behavior during various failure scenarios

### 4. Performance Testing
- **Real-time Updates**: Test system performance with multiple concurrent users
- **Database Queries**: Test location data queries and historical data retrieval
- **Memory Usage**: Test JavaScript memory usage during extended tracking sessions
- **Network Efficiency**: Test data usage optimization for mobile users

### 5. Security Testing
- **GPS Data Privacy**: Ensure location data is properly anonymized
- **Device Token Security**: Test token generation and validation security
- **Input Validation**: Test all GPS data inputs for injection attacks
- **Rate Limiting**: Test protection against spam location submissions

### 6. Mobile Testing
- **GPS Accuracy**: Test location accuracy across different mobile devices
- **Battery Usage**: Test impact of continuous GPS tracking on battery life
- **Network Conditions**: Test behavior under poor network conditions
- **Background Processing**: Test location sharing when app is backgrounded

The testing strategy ensures comprehensive coverage of all system components while maintaining focus on real-world usage scenarios and edge cases specific to mobile GPS tracking applications.

## Scalability Considerations for 250-300+ Users

### Connection Management
- **Laravel Reverb**: Free and unlimited WebSocket connections, perfect for 5 buses × 50-60 users each
- **Connection Pooling**: Efficient management of 250-300+ concurrent connections
- **Load Balancing**: Database query optimization for multiple simultaneous location updates

### Performance Optimization
- **Database Indexing**: Optimized indexes for real-time location queries
- **Caching Strategy**: `bus_current_positions` table for fast aggregated data retrieval
- **Batch Processing**: Group location updates to reduce database load
- **Connection Cleanup**: Automatic cleanup of inactive WebSocket connections

**Free Tools Stack:**
- Laravel 12 (free)
- Livewire 3 (free) 
- MySQL (free)
- Laravel Reverb (free WebSocket, unlimited connections)
- Bootstrap + JavaScript (free)
- OpenStreetMap + Leaflet (free)