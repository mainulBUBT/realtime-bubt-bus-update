@extends('layouts.simple')

@section('content')
<div class="p-4">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark">Good Morning</h1>
        <p class="text-muted mb-0">Dhaka, Bangladesh</p>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="h4 fw-bold mb-0">5</h3>
                            <p class="small text-muted mb-0">Active Buses</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-people text-primary" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="h4 fw-bold mb-0">42</h3>
                            <p class="small text-muted mb-0">Students Online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="mb-4">
        <a href="#" class="card border-0 shadow-sm text-decoration-none mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                        <i class="bi bi-calendar3 text-primary" style="font-size: 28px;"></i>
                    </div>
                    <div class="flex-fill">
                        <h5 class="fw-bold text-dark mb-1">Schedule</h5>
                        <p class="text-muted small mb-0">View bus schedules and timings</p>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('tracker') }}" class="card border-0 shadow-sm text-decoration-none mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded p-3 me-3">
                        <i class="bi bi-geo-alt text-success" style="font-size: 28px;"></i>
                    </div>
                    <div class="flex-fill">
                        <h5 class="fw-bold text-dark mb-1">Track Your Bus</h5>
                        <p class="text-muted small mb-0">Join a bus and get real-time updates</p>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('live-map') }}" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 rounded p-3 me-3">
                        <i class="bi bi-map text-warning" style="font-size: 28px;"></i>
                    </div>
                    <div class="flex-fill">
                        <h5 class="fw-bold text-dark mb-1">Live Map</h5>
                        <p class="text-muted small mb-0">View all buses on the map</p>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Recent Activity</h5>
        
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                    <div class="flex-fill">
                        <h6 class="fw-semibold mb-1">Bus B1 is now active</h6>
                        <p class="small text-muted mb-0">Route: Dhanmondi → BUBT Campus</p>
                    </div>
                    <span class="small text-muted">2m ago</span>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                        <i class="bi bi-bell text-primary"></i>
                    </div>
                    <div class="flex-fill">
                        <h6 class="fw-semibold mb-1">Smart notifications enabled</h6>
                        <p class="small text-muted mb-0">Get alerts 5 minutes before arrival</p>
                    </div>
                    <span class="small text-muted">5m ago</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection