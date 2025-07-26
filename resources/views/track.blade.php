@extends('layouts.track')

@section('title', 'BUBT Bus Tracker - Live Tracking')

@section('content')
<!-- Track Screen -->
<div id="track-screen" class="screen active">
    <!-- Header -->
    <div class="app-header track-header">
        <div class="location-header">
            <i class="bi bi-arrow-left back-button" onclick="window.location.href='{{ route('home') }}'"></i>
            <span class="location-name">Live Tracking</span>
        </div>
        <i class="bi bi-bell header-icon"></i>
    </div>

    <!-- Full Page Map -->
    <div id="map" class="full-map"></div>

    <!-- Bus Pin Overlay -->
    <div class="bus-pin-overlay">
        <div class="bus-pin">
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
            <span class="bus-id">{{ $busId ?? 'B1' }}</span>
            <div class="bus-pulse"></div>
        </div>
        <div class="info-content">
            <div class="bus-name">{{ $busName ?? 'Buriganga' }}</div>
            <div class="eta-info">
                <i class="bi bi-clock"></i>
                <span class="eta-time">10 min</span>
                <span class="eta-to">to Mirpur-1</span>
            </div>
        </div>
        <div class="quick-actions">
            <button class="quick-btn" title="Favorite">
                <i class="bi bi-star"></i>
            </button>
            <button class="quick-btn" title="Share">
                <i class="bi bi-share"></i>
            </button>
        </div>
    </div>

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
                        <div class="info-value">Shyamoli</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Next Stop</div>
                        <div class="info-value">Mirpur-1</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Speed</div>
                        <div class="info-value">32 km/h</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Traffic Status</div>
                        <div class="info-value normal">Normal</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value">Just now</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Trip Status</div>
                        <div class="info-value">On Schedule</div>
                    </div>
                </div>
            </div>

            <!-- Route Timeline -->
            <div class="route-timeline">
                <div class="timeline-header">
                    <div class="route-info">
                        <div class="route-name">Complete Route</div>
                        <div class="route-stats">5 stops • 12.5 km • 60 min</div>
                    </div>
                </div>

                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="time-badge">7:00 AM</div>
                            <h4>Asad Gate</h4>
                            <p>Departed</p>
                        </div>
                    </div>
                    <div class="timeline-item completed">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="time-badge">7:15 AM</div>
                            <h4>Shyamoli</h4>
                            <p>Departed</p>
                        </div>
                    </div>
                    <div class="timeline-item current">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="time-badge">7:35 AM</div>
                            <h4>Mirpur-1</h4>
                            <p>Arriving in 10 minutes</p>
                            <div class="progress-bar">
                                <div class="progress" style="width: 70%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="time-badge">7:45 AM</div>
                            <h4>Rainkhola</h4>
                            <p>Estimated arrival</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="time-badge">8:00 AM</div>
                            <h4>BUBT</h4>
                            <p>Final destination</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Subscribe -->
            <div class="notification-subscribe">
                <div class="notification-icon">
                    <i class="bi bi-bell"></i>
                </div>
                <div class="notification-text">
                    <h4>Get notified when bus arrives</h4>
                    <p>We'll send you a notification when the bus is near your stop</p>
                </div>
                <button class="subscribe-btn">Subscribe</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize map and tracking functionality
    let map = null;
    let busMarker = null;
    let busPosition = { lat: 23.7937, lng: 90.3629 }; // Default position (Dhaka)

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        initBottomSheet();
        initMapControls();
    });

    function initMap() {
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
            html: '<div class="marker-icon">{{ $busId ?? "B1" }}</div>',
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
</script>
@endpush