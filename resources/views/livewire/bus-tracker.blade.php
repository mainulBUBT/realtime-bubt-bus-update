<div>
    <!-- Header -->
    <div class="app-header track-header">
        <div class="location-header">
            <i class="bi bi-arrow-left back-button" wire:click="$dispatch('navigate-back')"></i>
            <span class="location-name">Live Tracking</span>
        </div>
        <i class="bi bi-bell header-icon"></i>
    </div>

    <!-- Full Page Map -->
    <div id="map" class="full-map" wire:ignore></div>

    <!-- Bus Pin Overlay -->
    <div class="bus-pin-overlay">
        <div class="bus-pin {{ $trackingStatus === 'tracking' ? 'active' : '' }}">
            <div class="bus-pin-icon">
                <i class="bi bi-bus-front"></i>
            </div>
            <div class="bus-pin-pulse"></div>
        </div>
    </div>

    <!-- Map Controls -->
    <div class="map-controls">
        <button class="map-control-btn" id="center-map"><i class="bi bi-geo-alt"></i></button>
        <button class="map-control-btn" id="zoom-in"><i class="bi bi-plus"></i></button>
        <button class="map-control-btn" id="zoom-out"><i class="bi bi-dash"></i></button>
    </div>

    <!-- Compact Floating Info Bar -->
    <div class="floating-info-bar">
        <div class="bus-badge">
            <span class="bus-id">{{ $busId }}</span>
            <div class="bus-pulse {{ $isActive ? 'active' : '' }}"></div>
        </div>
        <div class="info-content">
            <div class="bus-name">{{ $busName }}</div>
            <div class="eta-info">
                @if($isActive && $eta && $nextStop)
                    <i class="bi bi-clock"></i>
                    <span class="eta-time">{{ $eta }}</span>
                    <span class="eta-to">to {{ $nextStop }}</span>
                @elseif($isActive)
                    @switch($trackingStatus)
                        @case('no_tracking')
                            <i class="bi bi-exclamation-triangle"></i>
                            <span class="eta-time">No Tracking</span>
                            @break
                        @case('single_tracker')
                            <i class="bi bi-person"></i>
                            <span class="eta-time">Limited Data</span>
                            @break
                        @default
                            <i class="bi bi-pause-circle"></i>
                            <span class="eta-time">Calculating...</span>
                    @endswitch
                @else
                    <i class="bi bi-pause-circle"></i>
                    <span class="eta-time">Not Active</span>
                @endif
            </div>
        </div>
        <div class="quick-actions">
            <button class="quick-btn" title="Refresh" wire:click="refreshData">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="quick-btn" title="Share">
                <i class="bi bi-share"></i>
            </button>
        </div>
    </div>

    <!-- I'm on this Bus Button -->
    @if($isActive)
        <div class="tracking-control">
            @if(!$isTracking)
                <button class="btn btn-primary btn-lg tracking-btn" wire:click="startTracking">
                    <i class="bi bi-geo-alt"></i>
                    I'm on this Bus
                </button>
            @else
                <button class="btn btn-danger btn-lg tracking-btn" wire:click="stopTracking">
                    <i class="bi bi-geo-alt-fill"></i>
                    Stop Tracking
                </button>
            @endif
        </div>
    @endif

    <!-- Bottom Sheet for Timeline -->
    <div class="bottom-sheet">
        <div class="bottom-sheet-handle" title="Drag to expand or collapse">
            <div class="handle-bar"></div>
            <div class="handle-indicator">Drag to expand</div>
        </div>

        <div class="bottom-sheet-content">
            <!-- Bus Info Card -->
            <div class="track-info-card">
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Current Stop</div>
                        <div class="info-value">{{ $currentStop ?? 'Unknown' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Next Stop</div>
                        <div class="info-value">{{ $nextStop ?? 'Unknown' }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Speed</div>
                        <div class="info-value">{{ $speed }} km/h</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tracking Status</div>
                        <div class="info-value {{ $trackingStatus }}">
                            @switch($trackingStatus)
                                @case('active')
                                    {{ $activeTrackers }} {{ $activeTrackers === 1 ? 'Tracker' : 'Trackers' }}
                                    @break
                                @case('single_tracker')
                                    Single Tracker
                                    <small class="text-warning d-block">Limited accuracy</small>
                                    @break
                                @case('no_tracking')
                                    No Tracking
                                    <small class="text-danger d-block">No passengers sharing location</small>
                                    @break
                                @case('tracking')
                                    You're Tracking
                                    @break
                                @default
                                    Inactive
                            @endswitch
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value">{{ $lastUpdated ?? 'Never' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Confidence</div>
                        <div class="info-value">
                            <div class="confidence-bar">
                                <div class="confidence-fill" style="width: {{ $confidenceLevel * 100 }}%"></div>
                            </div>
                            {{ round($confidenceLevel * 100) }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Route Timeline -->
            <div class="route-timeline">
                <div class="timeline-header">
                    <div class="route-info">
                        <div class="route-name">{{ $currentTrip === 'departure' ? 'Campus → City' : ($currentTrip === 'return' ? 'City → Campus' : 'Complete Route') }}</div>
                        <div class="route-stats">{{ count($routes) }} stops • {{ $isActive ? 'Active' : 'Inactive' }}</div>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($routes as $route)
                        <div class="timeline-item {{ $route['status'] }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="time-badge">{{ $route['estimated_time'] }}</div>
                                <h4>{{ $route['stop_name'] }}</h4>
                                <p>
                                    @switch($route['status'])
                                        @case('completed')
                                            Departed
                                            @break
                                        @case('current')
                                            @if($route['progress_percentage'] > 0)
                                                Arriving in {{ $eta ?? 'calculating...' }}
                                            @else
                                                Currently here
                                            @endif
                                            @break
                                        @case('upcoming')
                                            Estimated arrival
                                            @break
                                        @default
                                            Scheduled
                                    @endswitch
                                </p>
                                @if($route['status'] === 'current' && $route['progress_percentage'] > 0)
                                    <div class="progress-bar">
                                        <div class="progress" style="width: {{ $route['progress_percentage'] }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h4>No route data available</h4>
                                <p>Bus schedule not found or inactive</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tracking Status Info -->
            @if($isActive)
                <div class="tracking-info">
                    <div class="tracking-icon">
                        @switch($trackingStatus)
                            @case('no_tracking')
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                @break
                            @case('single_tracker')
                                <i class="bi bi-person text-warning"></i>
                                @break
                            @case('active')
                                <i class="bi bi-people text-success"></i>
                                @break
                            @default
                                <i class="bi bi-people"></i>
                        @endswitch
                    </div>
                    <div class="tracking-text">
                        <h4>
                            @switch($trackingStatus)
                                @case('no_tracking')
                                    No one is currently tracking this bus
                                    @break
                                @case('single_tracker')
                                    1 person is tracking this bus
                                    @break
                                @case('active')
                                    {{ $activeTrackers }} {{ $activeTrackers === 1 ? 'person is' : 'people are' }} tracking this bus
                                    @break
                                @default
                                    Tracking status unknown
                            @endswitch
                        </h4>
                        <p>
                            @switch($trackingStatus)
                                @case('no_tracking')
                                    @if($isTracking)
                                        You are the first to track this bus
                                    @else
                                        Be the first to help others by sharing your location
                                    @endif
                                    @break
                                @case('single_tracker')
                                    @if($isTracking)
                                        You are providing single-user tracking (limited accuracy)
                                    @else
                                        Join tracking to improve location accuracy
                                    @endif
                                    @break
                                @case('active')
                                    @if($isTracking)
                                        You are contributing to accurate bus location tracking
                                    @else
                                        Multiple people are providing accurate location data
                                    @endif
                                    @break
                                @default
                                    @if($isTracking)
                                        You are contributing to bus location tracking
                                    @else
                                        Tap "I'm on this Bus" to help others track this bus
                                    @endif
                            @endswitch
                        </p>
                        
                        <!-- Confidence Level Indicator -->
                        @if($confidenceLevel > 0)
                            <div class="confidence-indicator">
                                <small class="text-muted">
                                    Confidence: 
                                    <span class="confidence-badge {{ $confidenceLevel > 0.7 ? 'high' : ($confidenceLevel > 0.4 ? 'medium' : 'low') }}">
                                        {{ round($confidenceLevel * 100) }}%
                                    </span>
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Last Seen Information -->
                @if($trackingStatus === 'no_tracking' && $lastSeenInfo)
                    <div class="last-seen-info">
                        <div class="last-seen-header">
                            <i class="bi bi-clock-history"></i>
                            <span>Last Known Location</span>
                        </div>
                        <div class="last-seen-content">
                            <div class="last-seen-message">{{ $lastSeenMessage }}</div>
                            @if($lastSeenInfo['location_context']['type'] === 'bus_stop')
                                <div class="last-seen-details">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i>
                                        At {{ $lastSeenInfo['location_context']['name'] }}
                                        • {{ $lastSeenInfo['confidence_description'] }}
                                    </small>
                                </div>
                            @elseif($lastSeenInfo['location_context']['type'] === 'near_stop')
                                <div class="last-seen-details">
                                    <small class="text-muted">
                                        <i class="bi bi-geo"></i>
                                        Near {{ $lastSeenInfo['location_context']['name'] }}
                                        • {{ round($lastSeenInfo['location_context']['distance_to_stop']) }}m away
                                    </small>
                                </div>
                            @endif
                            
                            @if($trackingGapInfo['has_gap'] && $trackingGapInfo['gap_severity'] !== 'none')
                                <div class="tracking-gap-warning">
                                    <small class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        {{ $trackingGapInfo['message'] }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Fallback Recommendations -->
                @if($trackingStatus === 'no_tracking' || $trackingStatus === 'single_tracker')
                    <div class="fallback-recommendations">
                        <div class="recommendation-header">
                            <i class="bi bi-lightbulb"></i>
                            <span>
                                @if($trackingStatus === 'no_tracking')
                                    What You Can Do
                                @else
                                    Improve Tracking
                                @endif
                            </span>
                        </div>
                        <ul class="recommendation-list">
                            @if($trackingStatus === 'no_tracking')
                                @if($trackingGapInfo['has_gap'] && isset($trackingGapInfo['recommendations']))
                                    @foreach($trackingGapInfo['recommendations'] as $recommendation)
                                        <li>{{ $recommendation }}</li>
                                    @endforeach
                                @else
                                    <li>Be the first to share your location if you're on this bus</li>
                                    <li>Check back in a few minutes for updated tracking data</li>
                                @endif
                            @else
                                <li>More passengers sharing location will improve accuracy</li>
                                <li>Keep the app open while on the bus for better data</li>
                            @endif
                            <li>Ensure GPS and location services are enabled</li>
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    let map = null;
    let busMarker = null;
    let busPosition = { lat: 23.7937, lng: 90.3629 }; // Default position (Dhaka)
    let connectionManager = null;
    let unsubscribeConnection = null;

    document.addEventListener('livewire:navigated', function() {
        initBusTracker();
    });

    document.addEventListener('DOMContentLoaded', function() {
        initBusTracker();
    });

    function initBusTracker() {
        initMap();
        initBottomSheet();
        initMapControls();
        setupLivewireListeners();
        initConnectionManager();
    }

    function initConnectionManager() {
        // Initialize connection manager with polling fallback
        if (window.ConnectionManager) {
            connectionManager = new window.ConnectionManager({
                pollingInterval: 10000, // 10 seconds
                reconnectInterval: 5000, // 5 seconds
                maxReconnectAttempts: 10,
                enableWebSocket: false, // Disable WebSocket for now, use polling
                debug: true
            });

            // Subscribe to bus location updates
            const busId = '{{ $busId }}';
            unsubscribeConnection = connectionManager.subscribe(busId, (locationData, busId) => {
                handleLocationUpdate(locationData, busId);
            });

            // Listen for connection status changes
            connectionManager.onConnectionStatusChange((status) => {
                @this.dispatch('connectionStatusChanged', status);
            });
        }
    }

    function initMap() {
        if (map) {
            map.remove();
        }

        // Create map centered on Dhaka, Bangladesh
        map = L.map('map', {
            zoomControl: false
        }).setView([23.7937, 90.3629], 14);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Create bus marker
        const busIcon = L.divIcon({
            className: 'bus-marker-icon',
            html: '<div class="marker-icon">{{ $busId }}</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        busMarker = L.marker([busPosition.lat, busPosition.lng], {
            icon: busIcon,
            opacity: 0 // Hidden as we use the overlay pin
        }).addTo(map);
    }

    function initBottomSheet() {
        const bottomSheet = document.querySelector('.bottom-sheet');
        const handle = document.querySelector('.bottom-sheet-handle');
        
        if (bottomSheet && handle) {
            let isExpanded = false;
            
            handle.addEventListener('click', function() {
                isExpanded = !isExpanded;
                if (isExpanded) {
                    bottomSheet.classList.add('expanded');
                } else {
                    bottomSheet.classList.remove('expanded');
                }
            });
        }
    }

    function initMapControls() {
        const centerMapBtn = document.getElementById('center-map');
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');

        if (centerMapBtn) {
            centerMapBtn.addEventListener('click', () => {
                map.setView([busPosition.lat, busPosition.lng], 15);
            });
        }

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => {
                map.setZoom(map.getZoom() + 1);
            });
        }

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => {
                map.setZoom(map.getZoom() - 1);
            });
        }
    }

    function setupLivewireListeners() {
        // Listen for navigation back
        Livewire.on('navigate-back', () => {
            cleanup();
            window.location.href = '{{ route("home") }}';
        });

        // Listen for GPS tracking start
        Livewire.on('start-gps-tracking', (data) => {
            startGPSTracking(data[0]);
        });

        // Listen for GPS tracking stop
        Livewire.on('stop-gps-tracking', () => {
            stopGPSTracking();
        });

        // Listen for location broadcast
        Livewire.on('location-broadcast', (data) => {
            broadcastLocation(data[0]);
        });

        // Listen for notifications
        Livewire.on('show-notification', (data) => {
            showNotification(data[0]);
        });
    }

    function handleLocationUpdate(locationData, busId) {
        console.log('Received location update for bus', busId, locationData);
        
        if (locationData.status === 'active') {
            // Update bus position on map
            busPosition = { 
                lat: locationData.latitude, 
                lng: locationData.longitude 
            };
            
            if (busMarker) {
                busMarker.setLatLng([busPosition.lat, busPosition.lng]);
            }
            
            // Update Livewire component with new tracking status
            @this.call('updateTrackingStatus', {
                status: locationData.status,
                confidence: locationData.confidence_level,
                active_trackers: locationData.active_trackers,
                speed: locationData.speed || 0
            });
            
        } else if (locationData.status === 'no_tracking') {
            // Handle no tracking scenario
            @this.call('updateTrackingStatus', {
                status: 'no_tracking',
                confidence: 0,
                active_trackers: 0,
                speed: 0
            });
            
            if (locationData.last_known) {
                // Show last known position
                busPosition = {
                    lat: locationData.last_known.latitude,
                    lng: locationData.last_known.longitude
                };
                
                if (busMarker) {
                    busMarker.setLatLng([busPosition.lat, busPosition.lng]);
                }
            }
        }
    }

    function cleanup() {
        if (unsubscribeConnection) {
            unsubscribeConnection();
            unsubscribeConnection = null;
        }
        
        if (connectionManager) {
            connectionManager.destroy();
            connectionManager = null;
        }
        
        stopGPSTracking();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);

    let gpsWatchId = null;

    function startGPSTracking(data) {
        if (navigator.geolocation) {
            gpsWatchId = navigator.geolocation.watchPosition(
                function(position) {
                    const locationData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed || 0,
                        timestamp: new Date().toISOString()
                    };

                    // Update bus position on map
                    busPosition = { lat: locationData.latitude, lng: locationData.longitude };
                    if (busMarker) {
                        busMarker.setLatLng([busPosition.lat, busPosition.lng]);
                    }
                    map.setView([busPosition.lat, busPosition.lng]);

                    // Send location to Livewire component
                    @this.call('updateLocation', locationData);
                },
                function(error) {
                    console.error('GPS Error:', error);
                    showNotification({
                        type: 'error',
                        message: 'Unable to get your location. Please check GPS permissions.'
                    });
                },
                {
                    enableHighAccuracy: true,
                    timeout: 30000,
                    maximumAge: 15000
                }
            );
        }
    }

    function stopGPSTracking() {
        if (gpsWatchId) {
            navigator.geolocation.clearWatch(gpsWatchId);
            gpsWatchId = null;
        }
    }

    async function broadcastLocation(data) {
        // Send location data through connection manager
        if (connectionManager) {
            try {
                const result = await connectionManager.submitLocation({
                    bus_id: data.busId,
                    device_token: data.deviceToken,
                    latitude: data.location.latitude,
                    longitude: data.location.longitude,
                    accuracy: data.location.accuracy,
                    speed: data.location.speed,
                    timestamp: data.location.timestamp
                });
                
                console.log('Location submitted successfully:', result);
                
                if (result.reputation_updated) {
                    showNotification({
                        type: 'info',
                        message: 'Your tracking reputation has been updated'
                    });
                }
                
            } catch (error) {
                console.error('Failed to submit location:', error);
                showNotification({
                    type: 'error',
                    message: 'Failed to submit location data'
                });
            }
        }
    }

    function showNotification(data) {
        // Create or update notification
        let notification = document.querySelector('.notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'notification';
            document.body.appendChild(notification);
        }

        notification.className = `notification ${data.type} show`;
        notification.textContent = data.message;

        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }

    // Auto-refresh data every 30 seconds (reduced since we have real-time updates)
    setInterval(function() {
        @this.call('refreshData');
    }, 30000);
</script>
@endpush

@push('styles')
<style>
    .tracking-control {
        position: fixed;
        bottom: 120px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
    }

    .tracking-btn {
        border-radius: 25px;
        padding: 12px 24px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        min-width: 180px;
    }

    .tracking-btn i {
        margin-right: 8px;
    }

    .bus-pin.active .bus-pin-pulse {
        animation: pulse 2s infinite;
        background-color: #28a745;
    }

    .bus-pulse.active {
        animation: pulse 2s infinite;
        background-color: #28a745;
    }

    .confidence-bar {
        width: 60px;
        height: 8px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        display: inline-block;
        margin-right: 8px;
    }

    .confidence-fill {
        height: 100%;
        background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
        transition: width 0.3s ease;
    }

    .tracking-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(0,123,255,0.1);
        border-radius: 8px;
        margin-top: 1rem;
    }

    .tracking-icon {
        font-size: 1.5rem;
        color: #007bff;
    }

    .tracking-text h4 {
        margin: 0 0 0.5rem 0;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .tracking-text p {
        margin: 0;
        font-size: 0.8rem;
        color: #6c757d;
    }

    .info-value.tracking {
        color: #28a745;
        font-weight: 600;
    }

    .info-value.multiple_trackers {
        color: #007bff;
        font-weight: 600;
    }

    .info-value.single_tracker {
        color: #ffc107;
        font-weight: 600;
    }

    .info-value.no_tracking {
        color: #dc3545;
        font-weight: 600;
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    }

    .notification.show {
        transform: translateX(0);
    }

    .notification.success {
        background-color: #28a745;
    }

    .notification.error {
        background-color: #dc3545;
    }

    .notification.info {
        background-color: #17a2b8;
    }

    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* Fallback and Error Handling Styles */
    .confidence-indicator {
        margin-top: 8px;
    }

    .confidence-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .confidence-badge.high {
        background-color: #d4edda;
        color: #155724;
    }

    .confidence-badge.medium {
        background-color: #fff3cd;
        color: #856404;
    }

    .confidence-badge.low {
        background-color: #f8d7da;
        color: #721c24;
    }

    .fallback-recommendations {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .recommendation-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #856404;
        margin-bottom: 0.5rem;
    }

    .recommendation-list {
        margin: 0;
        padding-left: 1.2rem;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .recommendation-list li {
        margin-bottom: 0.3rem;
    }

    /* Enhanced tracking status colors */
    .info-value.no_tracking {
        color: #dc3545;
        font-weight: 600;
    }

    .info-value.single_tracker {
        color: #ffc107;
        font-weight: 600;
    }

    .info-value.active {
        color: #28a745;
        font-weight: 600;
    }

    /* ETA info status colors */
    .eta-info .eta-time {
        font-weight: 600;
    }

    .eta-info i.bi-exclamation-triangle {
        color: #ffc107;
    }

    .eta-info i.bi-person {
        color: #ffc107;
    }

    /* Small text styling */
    .info-value small {
        font-size: 0.7rem;
        font-weight: normal;
        margin-top: 2px;
    }

    /* Tracking info icon colors */
    .tracking-icon .text-warning {
        color: #ffc107 !important;
    }

    .tracking-icon .text-success {
        color: #28a745 !important;
    }

    /* Last Seen Information Styles */
    .last-seen-info {
        background: rgba(108, 117, 125, 0.1);
        border: 1px solid rgba(108, 117, 125, 0.3);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .last-seen-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .last-seen-message {
        font-size: 0.9rem;
        color: #212529;
        margin-bottom: 0.5rem;
    }

    .last-seen-details {
        margin-bottom: 0.3rem;
    }

    .last-seen-details small {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .tracking-gap-warning {
        margin-top: 0.5rem;
        padding: 0.3rem 0.5rem;
        background: rgba(255, 193, 7, 0.1);
        border-radius: 4px;
    }

    .tracking-gap-warning small {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    /* Enhanced recommendation styles */
    .fallback-recommendations .recommendation-header span {
        font-size: 0.9rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .fallback-recommendations, .last-seen-info {
            margin: 0.5rem;
            padding: 0.75rem;
        }
        
        .recommendation-list {
            font-size: 0.8rem;
        }

        .last-seen-message {
            font-size: 0.85rem;
        }
    }
</style>
@endpush
