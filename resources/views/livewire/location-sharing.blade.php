<div class="location-sharing-container">
    <!-- Permission Status Card -->
    <div class="permission-status-card">
        <div class="status-header">
            <div class="status-icon">
                @switch($permissionStatus)
                    @case('granted')
                        <i class="bi bi-geo-alt-fill text-success"></i>
                        @break
                    @case('denied')
                        <i class="bi bi-geo-alt text-danger"></i>
                        @break
                    @case('requesting')
                        <i class="bi bi-geo-alt text-warning"></i>
                        @break
                    @default
                        <i class="bi bi-geo-alt text-muted"></i>
                @endswitch
            </div>
            <div class="status-text">
                <h4>Location Permission</h4>
                <p class="status-description">
                    @switch($permissionStatus)
                        @case('granted')
                            Location access granted
                            @break
                        @case('denied')
                            Location access denied
                            @break
                        @case('requesting')
                            Requesting permission...
                            @break
                        @default
                            Permission status unknown
                    @endswitch
                </p>
            </div>
        </div>

        @if($permissionStatus !== 'granted')
            <div class="permission-actions">
                @if($permissionStatus === 'denied')
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Location permission is required for bus tracking. Please enable it in your browser settings.
                    </div>
                @endif
                
                <button class="btn btn-primary" 
                        wire:click="requestPermission" 
                        {{ $permissionStatus === 'requesting' ? 'disabled' : '' }}>
                    @if($permissionStatus === 'requesting')
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Requesting...
                    @else
                        <i class="bi bi-geo-alt me-2"></i>
                        Request Permission
                    @endif
                </button>
            </div>
        @endif
    </div>

    <!-- Location Sharing Controls -->
    @if($permissionStatus === 'granted')
        <div class="sharing-controls-card">
            <div class="sharing-header">
                <h4>Location Sharing</h4>
                <div class="sharing-toggle">
                    @if(!$isSharing)
                        <button class="btn btn-success btn-lg" wire:click="startSharing">
                            <i class="bi bi-play-circle me-2"></i>
                            Start Sharing
                        </button>
                    @else
                        <button class="btn btn-danger btn-lg" wire:click="stopSharing">
                            <i class="bi bi-stop-circle me-2"></i>
                            Stop Sharing
                        </button>
                    @endif
                </div>
            </div>

            @if($isSharing)
                <!-- Current Location Info -->
                <div class="location-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-indicator active"></span>
                                Active
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Accuracy</div>
                            <div class="info-value {{ $accuracyClass }}">
                                {{ $accuracyText }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Update</div>
                            <div class="info-value">
                                {{ $lastUpdate ?? 'Never' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Session</div>
                            <div class="info-value">
                                {{ $hasActiveSession ? 'Active' : 'Inactive' }}
                            </div>
                        </div>
                    </div>

                    @if($currentLocation)
                        <div class="coordinates-display">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                {{ number_format($currentLocation['latitude'], 6) }}, 
                                {{ number_format($currentLocation['longitude'], 6) }}
                            </small>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Settings Card -->
        <div class="settings-card">
            <div class="settings-header">
                <h5>Location Settings</h5>
            </div>
            <div class="settings-options">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-label">Battery Optimization</div>
                        <div class="setting-description">
                            Reduce GPS frequency to save battery
                        </div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="batteryOptimized"
                                   {{ $batteryOptimized ? 'checked' : '' }}
                                   wire:click="toggleBatteryOptimization">
                            <label class="form-check-label" for="batteryOptimized"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Display -->
    @if($errorMessage)
        <div class="alert alert-danger alert-dismissible">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ $errorMessage }}
            <button type="button" class="btn-close" wire:click="$set('errorMessage', null)"></button>
        </div>
    @endif

    <!-- Info Card -->
    <div class="info-card">
        <div class="info-header">
            <i class="bi bi-info-circle"></i>
            <h5>How it works</h5>
        </div>
        <div class="info-content">
            <ul>
                <li>Your location is only shared when you're actively tracking a bus</li>
                <li>Location data is anonymized and associated with your device token</li>
                <li>You can stop sharing at any time</li>
                <li>Battery optimization reduces GPS frequency to save power</li>
            </ul>
        </div>
    </div>

    <!-- Refresh Button -->
    <div class="refresh-section">
        <button class="btn btn-outline-secondary" wire:click="refreshStatus">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Refresh Status
        </button>
    </div>
</div>

@push('scripts')
<script>
    let locationWatchId = null;
    let locationSettings = {
        batteryOptimized: true,
        highAccuracy: true,
        timeout: 30000,
        maximumAge: 15000
    };

    document.addEventListener('livewire:navigated', function() {
        initLocationSharing();
    });

    document.addEventListener('DOMContentLoaded', function() {
        initLocationSharing();
    });

    function initLocationSharing() {
        checkLocationPermission();
        setupLocationListeners();
    }

    function checkLocationPermission() {
        if (navigator.geolocation) {
            navigator.permissions.query({name: 'geolocation'}).then(function(result) {
                @this.call('handlePermissionChange', result.state);
                
                result.onchange = function() {
                    @this.call('handlePermissionChange', result.state);
                };
            }).catch(function() {
                // Fallback for browsers that don't support permissions API
                @this.call('handlePermissionChange', 'unknown');
            });
        } else {
            @this.call('handlePermissionChange', 'unsupported');
        }
    }

    function setupLocationListeners() {
        // Listen for permission request
        Livewire.on('request-location-permission', () => {
            requestLocationPermission();
        });

        // Listen for location sharing start
        Livewire.on('start-location-sharing', (data) => {
            startLocationSharing(data[0]);
        });

        // Listen for location sharing stop
        Livewire.on('stop-location-sharing', () => {
            stopLocationSharing();
        });

        // Listen for settings update
        Livewire.on('update-location-settings', (data) => {
            updateLocationSettings(data[0]);
        });

        // Listen for permission check
        Livewire.on('check-location-permission', () => {
            checkLocationPermission();
        });
    }

    function requestLocationPermission() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    @this.call('handlePermissionChange', 'granted');
                },
                function(error) {
                    if (error.code === error.PERMISSION_DENIED) {
                        @this.call('handlePermissionChange', 'denied');
                    } else {
                        @this.call('handleLocationError', {
                            code: error.code,
                            message: error.message
                        });
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    }

    function startLocationSharing(settings) {
        locationSettings.batteryOptimized = settings.batteryOptimized;
        
        const options = {
            enableHighAccuracy: !locationSettings.batteryOptimized,
            timeout: locationSettings.timeout,
            maximumAge: locationSettings.batteryOptimized ? 30000 : locationSettings.maximumAge
        };

        if (navigator.geolocation) {
            locationWatchId = navigator.geolocation.watchPosition(
                function(position) {
                    const locationData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed || 0,
                        heading: position.coords.heading || null,
                        timestamp: new Date().toISOString()
                    };

                    @this.call('handleLocationUpdate', locationData);
                },
                function(error) {
                    @this.call('handleLocationError', {
                        code: error.code,
                        message: error.message
                    });
                },
                options
            );
        }
    }

    function stopLocationSharing() {
        if (locationWatchId) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
    }

    function updateLocationSettings(settings) {
        locationSettings = { ...locationSettings, ...settings };
        
        if (locationWatchId) {
            // Restart location sharing with new settings
            stopLocationSharing();
            startLocationSharing(settings);
        }
    }
</script>
@endpush

@push('styles')
<style>
    .location-sharing-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 1rem;
    }

    .permission-status-card,
    .sharing-controls-card,
    .settings-card,
    .info-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .status-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .status-icon {
        font-size: 2rem;
    }

    .status-text h4 {
        margin: 0 0 0.25rem 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .status-description {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .permission-actions {
        margin-top: 1rem;
    }

    .sharing-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .sharing-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .location-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .info-item {
        text-align: center;
    }

    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-indicator.active {
        background-color: #28a745;
        animation: pulse 2s infinite;
    }

    .info-value.excellent {
        color: #28a745;
    }

    .info-value.good {
        color: #17a2b8;
    }

    .info-value.fair {
        color: #ffc107;
    }

    .info-value.poor {
        color: #dc3545;
    }

    .coordinates-display {
        text-align: center;
        padding-top: 0.5rem;
        border-top: 1px solid #dee2e6;
    }

    .settings-header h5 {
        margin: 0 0 1rem 0;
        font-size: 1rem;
        font-weight: 600;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
    }

    .setting-label {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .setting-description {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .info-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .info-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }

    .info-content ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .info-content li {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .refresh-section {
        text-align: center;
        margin-top: 2rem;
    }

    .alert {
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    @media (max-width: 576px) {
        .location-sharing-container {
            padding: 0.5rem;
        }

        .permission-status-card,
        .sharing-controls-card,
        .settings-card,
        .info-card {
            padding: 1rem;
        }

        .sharing-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush
