@extends('layouts.admin')

@section('title', 'Route Management')
@section('page-title', 'Route Management')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #routeMap {
        height: 400px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    
    .stop-marker {
        background: #007bff;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .predefined-stop {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .predefined-stop:hover {
        background-color: #f8f9fa;
        border-color: #007bff;
    }
    
    .stop-order-badge {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-geo-alt me-2"></i>
            Route Management - Bus {{ $schedule->bus_id }}
        </h4>
        <p class="text-muted mb-0">{{ $schedule->route_name }}</p>
    </div>
    <div>
        <a href="{{ route('admin.schedules.show', $schedule) }}" class="btn btn-outline-info me-2">
            <i class="bi bi-eye me-2"></i>View Schedule
        </a>
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Schedules
        </a>
    </div>
</div>

<div class="row">
    <!-- Map and Add Stop Form -->
    <div class="col-lg-8">
        <!-- Interactive Map -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-map me-2"></i>
                    Route Map
                </h5>
            </div>
            <div class="card-body">
                <div id="routeMap"></div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Click on the map to add a new stop, or drag existing markers to reposition them.
                    </small>
                </div>
            </div>
        </div>

        <!-- Add Stop Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add New Stop
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.schedules.routes.store', $schedule) }}" id="addStopForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stop_name" class="form-label">
                                    Stop Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('stop_name') is-invalid @enderror" 
                                       id="stop_name" 
                                       name="stop_name" 
                                       value="{{ old('stop_name') }}" 
                                       placeholder="e.g., Asad Gate"
                                       required>
                                @error('stop_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stop_order" class="form-label">
                                    Stop Order <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('stop_order') is-invalid @enderror" 
                                       id="stop_order" 
                                       name="stop_order" 
                                       value="{{ old('stop_order', $schedule->routes->count() + 1) }}" 
                                       min="1"
                                       required>
                                @error('stop_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="latitude" class="form-label">
                                    Latitude <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('latitude') is-invalid @enderror" 
                                       id="latitude" 
                                       name="latitude" 
                                       value="{{ old('latitude') }}" 
                                       step="0.000001"
                                       min="-90" 
                                       max="90"
                                       required>
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="longitude" class="form-label">
                                    Longitude <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('longitude') is-invalid @enderror" 
                                       id="longitude" 
                                       name="longitude" 
                                       value="{{ old('longitude') }}" 
                                       step="0.000001"
                                       min="-180" 
                                       max="180"
                                       required>
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="coverage_radius" class="form-label">
                                    Coverage Radius (m) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('coverage_radius') is-invalid @enderror" 
                                       id="coverage_radius" 
                                       name="coverage_radius" 
                                       value="{{ old('coverage_radius', 100) }}" 
                                       min="50" 
                                       max="1000"
                                       required>
                                @error('coverage_radius')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estimated_departure_time" class="form-label">
                                    Estimated Departure Time
                                </label>
                                <input type="time" 
                                       class="form-control @error('estimated_departure_time') is-invalid @enderror" 
                                       id="estimated_departure_time" 
                                       name="estimated_departure_time" 
                                       value="{{ old('estimated_departure_time') }}">
                                @error('estimated_departure_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">When bus typically reaches this stop (departure trip)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estimated_return_time" class="form-label">
                                    Estimated Return Time
                                </label>
                                <input type="time" 
                                       class="form-control @error('estimated_return_time') is-invalid @enderror" 
                                       id="estimated_return_time" 
                                       name="estimated_return_time" 
                                       value="{{ old('estimated_return_time') }}">
                                @error('estimated_return_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">When bus typically reaches this stop (return trip)</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="clearForm()">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </button>
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Stop
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Predefined Stops -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bookmark me-2"></i>
                    Quick Add Stops
                </h6>
            </div>
            <div class="card-body">
                @foreach($predefinedStops as $stop)
                <div class="predefined-stop border rounded p-2 mb-2" 
                     onclick="addPredefinedStop('{{ $stop['name'] }}', {{ $stop['lat'] }}, {{ $stop['lng'] }})">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-geo-alt text-primary me-2"></i>
                        <div>
                            <div class="fw-bold">{{ $stop['name'] }}</div>
                            <small class="text-muted">{{ $stop['lat'] }}, {{ $stop['lng'] }}</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Current Route Stops -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-ol me-2"></i>
                    Current Stops
                </h6>
                <span class="badge bg-info">{{ $schedule->routes->count() }}</span>
            </div>
            <div class="card-body">
                @if($schedule->routes->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($schedule->routes as $route)
                        <div class="list-group-item d-flex justify-content-between align-items-start px-0">
                            <div class="d-flex align-items-center">
                                <div class="stop-order-badge bg-primary text-white me-2">
                                    {{ $route->stop_order }}
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $route->stop_name }}</div>
                                    <small class="text-muted">
                                        {{ number_format($route->latitude, 6) }}, {{ number_format($route->longitude, 6) }}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-circle me-1"></i>{{ $route->coverage_radius }}m radius
                                    </small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                        type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" 
                                                onclick="focusOnStop({{ $route->latitude }}, {{ $route->longitude }})">
                                            <i class="bi bi-eye me-2"></i>View on Map
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" 
                                                onclick="editStop({{ $route->id }}, '{{ $route->stop_name }}', {{ $route->latitude }}, {{ $route->longitude }}, {{ $route->coverage_radius }})">
                                            <i class="bi bi-pencil me-2"></i>Edit Stop
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" 
                                              action="{{ route('admin.schedules.routes.destroy', [$schedule, $route]) }}" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to remove this stop?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Remove Stop
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-geo-alt display-4 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No stops configured</p>
                        <small class="text-muted">Add stops using the form or quick add buttons</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map;
    let markers = [];
    let routeLine;

    // Initialize map
    function initMap() {
        // Center on Dhaka, Bangladesh
        map = L.map('routeMap').setView([23.7808, 90.3492], 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add existing stops to map
        @foreach($schedule->routes as $route)
            addStopMarker({{ $route->latitude }}, {{ $route->longitude }}, '{{ $route->stop_name }}', {{ $route->stop_order }}, {{ $route->coverage_radius }});
        @endforeach

        // Update route line
        updateRouteLine();

        // Add click handler for adding new stops
        map.on('click', function(e) {
            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
        });
    }

    // Add stop marker to map
    function addStopMarker(lat, lng, name, order, radius) {
        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: L.divIcon({
                className: 'stop-marker',
                html: order,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(map);

        marker.bindPopup(`<strong>${name}</strong><br>Stop ${order}<br>${radius}m radius`);
        
        // Add coverage circle
        const circle = L.circle([lat, lng], {
            radius: radius,
            color: '#007bff',
            fillColor: '#007bff',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(map);

        markers.push({marker, circle, order});

        // Handle marker drag
        marker.on('dragend', function(e) {
            const newPos = e.target.getLatLng();
            circle.setLatLng(newPos);
            document.getElementById('latitude').value = newPos.lat.toFixed(6);
            document.getElementById('longitude').value = newPos.lng.toFixed(6);
        });
    }

    // Update route line connecting all stops
    function updateRouteLine() {
        if (routeLine) {
            map.removeLayer(routeLine);
        }

        if (markers.length > 1) {
            const sortedMarkers = markers.sort((a, b) => a.order - b.order);
            const latlngs = sortedMarkers.map(m => m.marker.getLatLng());
            
            routeLine = L.polyline(latlngs, {
                color: '#28a745',
                weight: 3,
                opacity: 0.7
            }).addTo(map);
        }
    }

    // Add predefined stop
    function addPredefinedStop(name, lat, lng) {
        document.getElementById('stop_name').value = name;
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Focus map on the location
        map.setView([lat, lng], 15);
        
        // Add temporary marker
        const tempMarker = L.marker([lat, lng]).addTo(map);
        setTimeout(() => map.removeLayer(tempMarker), 3000);
    }

    // Focus on specific stop
    function focusOnStop(lat, lng) {
        map.setView([lat, lng], 16);
    }

    // Edit stop (populate form)
    function editStop(id, name, lat, lng, radius) {
        document.getElementById('stop_name').value = name;
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('coverage_radius').value = radius;
        
        focusOnStop(lat, lng);
    }

    // Clear form
    function clearForm() {
        document.getElementById('addStopForm').reset();
        document.getElementById('stop_order').value = {{ $schedule->routes->count() + 1 }};
    }

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });
</script>
@endpush