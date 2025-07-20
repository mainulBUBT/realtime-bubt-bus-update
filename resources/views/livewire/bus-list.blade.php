<!-- App Header -->
<div class="app-header">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="app-title">Dhaka, BD</h1>
            <p class="app-subtitle">Find a Bus</p>
        </div>
        <button class="btn btn-light rounded-circle" style="width: 40px; height: 40px;">
            <i class="bi bi-bell"></i>
        </button>
    </div>
</div>

<!-- Search Bar -->
<div class="px-3 py-3">
    <input type="text" placeholder="Find a Bus..." class="search-input">
</div>

<!-- Quick Actions -->
<div class="px-3 mb-4">
    <div class="row g-3">
        <div class="col-4">
            <div class="quick-action">
                <div class="quick-action-icon bg-primary-light">
                    <i class="bi bi-calendar3 text-primary-custom" style="font-size: 24px;"></i>
                </div>
                <span class="small fw-semibold text-dark">Schedules</span>
            </div>
        </div>
        <div class="col-4">
            <div class="quick-action">
                <div class="quick-action-icon bg-success-light">
                    <i class="bi bi-geo-alt text-success-custom" style="font-size: 24px;"></i>
                </div>
                <span class="small fw-semibold text-dark">Find a Bus</span>
            </div>
        </div>
        <div class="col-4">
            <div class="quick-action">
                <div class="quick-action-icon bg-warning-light">
                    <i class="bi bi-info-circle text-warning-custom" style="font-size: 24px;"></i>
                </div>
                <span class="small fw-semibold text-dark">Bus Info</span>
            </div>
        </div>
    </div>
</div>

<!-- Buses around you -->
<div class="px-3 mb-4">
    <h2 class="h5 fw-bold text-dark mb-3">Buses around you</h2>
    
    <!-- Map Container -->
    <div class="map-container">
        <div class="map-placeholder">
            <div class="text-center">
                <i class="bi bi-geo-alt" style="font-size: 48px; color: #6c757d;"></i>
                <p class="small text-muted mt-2">Loading map...</p>
            </div>
        </div>
        <!-- Live indicator -->
        <div class="live-badge">
            <span class="small fw-semibold text-dark">Live</span>
        </div>
    </div>
</div>

<!-- Favourites -->
<div class="px-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold text-dark mb-0">Favourites</h2>
        <span class="small text-muted">View all</span>
    </div>
    
    <div class="card-modern">
        <div class="d-flex align-items-center">
            <div class="list-icon bg-primary-light me-3">
                <i class="bi bi-geo-alt text-primary-custom" style="font-size: 24px;"></i>
            </div>
            <div class="flex-fill">
                <h3 class="h6 fw-semibold text-dark mb-1">BUBT Campus / Main Gate</h3>
                <p class="small text-muted mb-0">Bus Stop</p>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </div>
    </div>
</div>

<!-- Available Routes -->
<div class="px-3">
    <h2 class="h5 fw-bold text-dark mb-3">Available Routes</h2>
    
    @foreach($filteredBuses as $index => $bus)
        <div class="list-item" wire:click="joinBus({{ $bus['id'] }})">
            <div class="list-icon bg-{{ $bus['gradient'] === 'primary' ? 'primary' : ($bus['gradient'] === 'success' ? 'success' : 'warning') }}-light me-3">
                <i class="bi bi-bus-front text-{{ $bus['gradient'] === 'primary' ? 'primary' : ($bus['gradient'] === 'success' ? 'success' : 'warning') }}-custom" style="font-size: 24px;"></i>
            </div>
            <div class="flex-fill">
                <h3 class="h6 fw-semibold text-dark mb-1">{{ $bus['route'] }}</h3>
                <p class="small text-muted mb-0">{{ $bus['status'] === 'on_time' ? 'On time' : 'Delayed' }} • {{ $bus['eta'] }}</p>
            </div>
            <div class="text-end me-3">
                <div class="small fw-semibold text-dark">{{ $bus['students_tracking'] }}</div>
                <div class="small text-muted">tracking</div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </div>
    @endforeach
</div>