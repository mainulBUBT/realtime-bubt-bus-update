<x-layouts.app>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mobile-header-title">Live Map</h1>
                <p class="mobile-header-subtitle">Real-time Bus Tracking</p>
            </div>
            <button class="btn btn-light rounded-circle" style="width: 40px; height: 40px;">
                <i class="bi bi-layers"></i>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="px-3 py-3">
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card-modern text-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-success-light rounded-3 p-2 me-3">
                            <i class="bi bi-check-circle text-success-custom" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h4 class="h5 fw-bold text-dark mb-0">5</h4>
                            <p class="small text-muted mb-0">Active Buses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card-modern text-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-light rounded-3 p-2 me-3">
                            <i class="bi bi-people text-primary-custom" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h4 class="h5 fw-bold text-dark mb-0">42</h4>
                            <p class="small text-muted mb-0">Students Online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Map Container -->
        <div class="card-modern p-0 mb-4" style="height: 300px; position: relative;">
            <!-- Map Header -->
            <div class="bg-primary-custom text-white px-3 py-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Live Bus Locations</h6>
                <div class="live-badge bg-light text-dark">
                    <span class="small fw-semibold">Live</span>
                </div>
            </div>
            
            <!-- Map Area -->
            <div class="position-relative" style="height: 250px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                <div class="position-absolute top-50 start-50 translate-middle text-center">
                    <div class="bg-white rounded-3 p-3 mb-3 shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <div class="spinner-border text-primary" role="status" style="width: 30px; height: 30px;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Loading Interactive Map</h6>
                    <p class="small text-muted mb-3">Initializing real-time bus tracking...</p>
                    <div class="bg-white rounded-pill px-3 py-1 d-inline-flex align-items-center">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        <span class="small fw-semibold text-primary">Coming in next update</span>
                    </div>
                </div>
                
                <!-- Floating Map Controls -->
                <div class="position-absolute top-0 end-0 m-3 d-flex flex-column" style="gap: 8px;">
                    <button class="btn btn-light rounded-circle shadow-sm" style="width: 36px; height: 36px;">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button class="btn btn-light rounded-circle shadow-sm" style="width: 36px; height: 36px;">
                        <i class="bi bi-dash"></i>
                    </button>
                    <button class="btn btn-light rounded-circle shadow-sm" style="width: 36px; height: 36px;">
                        <i class="bi bi-geo-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Buses Section -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">Active Buses</h5>
                <span class="badge bg-primary-custom">5 buses</span>
            </div>
            
            <div class="row g-3">
                @for($i = 1; $i <= 5; $i++)
                <div class="col-12">
                    <div class="card-modern">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="bg-{{ $i % 2 == 0 ? 'primary' : 'success' }}-light rounded-3 p-2 me-3 position-relative">
                                    <i class="bi bi-bus-front text-{{ $i % 2 == 0 ? 'primary' : 'success' }}-custom" style="font-size: 20px;"></i>
                                    @if($i % 3 != 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success" style="font-size: 8px;">
                                        <span class="visually-hidden">online</span>
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Bus B{{ $i }}</h6>
                                    <p class="small text-muted mb-0">{{ ['Dhanmondi', 'Uttara', 'Gulshan', 'Mirpur', 'Wari'][$i-1] }} → BUBT Campus</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $i % 3 == 0 ? 'warning' : 'success' }} mb-1">
                                    {{ $i % 3 == 0 ? 'Delayed' : 'On Time' }}
                                </span>
                                <p class="small fw-semibold text-dark mb-0">{{ rand(5, 15) }} min away</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        <!-- Map Legend -->
        <div class="card-modern">
            <h6 class="fw-bold text-dark mb-3">Map Legend</h6>
            <div class="row g-3">
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                        <span class="small fw-semibold text-dark">Active Bus</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                        <span class="small fw-semibold text-dark">Delayed Bus</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                        <span class="small fw-semibold text-dark">Bus Stop</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                        <span class="small fw-semibold text-dark">BUBT Campus</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>