<x-layouts.app>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mobile-header-title">Good Morning</h1>
                <p class="mobile-header-subtitle">Dhaka, Bangladesh</p>
            </div>
            <button class="btn btn-light rounded-circle" style="width: 40px; height: 40px;">
                <i class="bi bi-bell"></i>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="px-3 py-4">
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card-modern text-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-success-light rounded-3 p-2 me-3">
                            <i class="bi bi-check-circle text-success-custom" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="h4 fw-bold text-dark mb-0">5</h3>
                            <p class="small text-muted mb-0">Active Buses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card-modern text-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-light rounded-3 p-2 me-3">
                            <i class="bi bi-people text-primary-custom" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="h4 fw-bold text-dark mb-0">42</h3>
                            <p class="small text-muted mb-0">Students Online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Action Cards -->
        <div class="row g-3 mb-4">
            <!-- Schedule Card -->
            <div class="col-12">
                <a href="#" class="home-card">
                    <div class="d-flex align-items-center">
                        <div class="home-card-icon bg-primary-light me-3">
                            <i class="bi bi-calendar3 text-primary-custom" style="font-size: 32px;"></i>
                        </div>
                        <div class="flex-fill">
                            <h3 class="home-card-title">Schedule</h3>
                            <p class="home-card-description">View bus schedules and timings for all routes</p>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            </div>

            <!-- Track Your Bus Card -->
            <div class="col-12">
                <a href="{{ route('tracker') }}" class="home-card">
                    <div class="d-flex align-items-center">
                        <div class="home-card-icon bg-success-light me-3">
                            <i class="bi bi-geo-alt text-success-custom" style="font-size: 32px;"></i>
                        </div>
                        <div class="flex-fill">
                            <h3 class="home-card-title">Track Your Bus</h3>
                            <p class="home-card-description">Join a bus and get real-time location updates</p>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            </div>

            <!-- All Buses Card -->
            <div class="col-12">
                <a href="{{ route('tracker') }}" class="home-card">
                    <div class="d-flex align-items-center">
                        <div class="home-card-icon bg-warning-light me-3">
                            <i class="bi bi-bus-front text-warning-custom" style="font-size: 32px;"></i>
                        </div>
                        <div class="flex-fill">
                            <h3 class="home-card-title">All Buses</h3>
                            <p class="home-card-description">View all available buses and their current status</p>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Map Preview -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold text-dark mb-0">Live Map</h2>
                <a href="{{ route('live-map') }}" class="small text-primary-custom text-decoration-none">View Full Map</a>
            </div>
            
            <div class="map-container">
                <div class="map-placeholder">
                    <div class="text-center">
                        <i class="bi bi-geo-alt" style="font-size: 48px; color: #6c757d;"></i>
                        <p class="small text-muted mt-2">Tap to view live bus locations</p>
                    </div>
                </div>
                <div class="live-badge">
                    <span class="small fw-semibold text-dark">Live</span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mb-4">
            <h2 class="h5 fw-bold text-dark mb-3">Recent Activity</h2>
            
            <div class="card-modern">
                <div class="d-flex align-items-center">
                    <div class="bg-success-light rounded-3 p-2 me-3">
                        <i class="bi bi-check-circle text-success-custom" style="font-size: 20px;"></i>
                    </div>
                    <div class="flex-fill">
                        <h6 class="fw-semibold text-dark mb-1">Bus B1 is now active</h6>
                        <p class="small text-muted mb-0">Route: Dhanmondi → BUBT Campus</p>
                    </div>
                    <span class="small text-muted">2m ago</span>
                </div>
            </div>
            
            <div class="card-modern">
                <div class="d-flex align-items-center">
                    <div class="bg-primary-light rounded-3 p-2 me-3">
                        <i class="bi bi-bell text-primary-custom" style="font-size: 20px;"></i>
                    </div>
                    <div class="flex-fill">
                        <h6 class="fw-semibold text-dark mb-1">Smart notifications enabled</h6>
                        <p class="small text-muted mb-0">Get alerts 5 minutes before arrival</p>
                    </div>
                    <span class="small text-muted">5m ago</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>