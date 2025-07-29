@extends('layouts.admin')

@section('title', 'Monitoring Dashboard')
@section('page-title', 'Monitoring Dashboard')

@push('styles')
<style>
    .alert-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .alert-card.alert-danger { border-left-color: #dc3545; }
    .alert-card.alert-warning { border-left-color: #ffc107; }
    .alert-card.alert-info { border-left-color: #0dcaf0; }
    
    .trust-meter {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
    }
    
    .trust-meter-fill {
        height: 100%;
        transition: width 0.3s ease;
    }
    
    .trust-high { background: #28a745; }
    .trust-medium { background: #ffc107; }
    .trust-low { background: #fd7e14; }
    .trust-very-low { background: #dc3545; }
</style>
@endpush

@section('content')
<!-- Real-time Statistics -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card primary">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-broadcast"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Active Buses</div>
                    <div class="h4 mb-0">{{ $stats['active_buses'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card success">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Active Trackers</div>
                    <div class="h4 mb-0">{{ $stats['total_trackers'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card info">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Trusted Devices</div>
                    <div class="h4 mb-0">{{ $stats['trusted_devices'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card warning">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Suspicious</div>
                    <div class="h4 mb-0">{{ $stats['suspicious_devices'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card primary">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Avg Trust</div>
                    <div class="h4 mb-0">{{ number_format($stats['avg_trust_score'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="stats-card success">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-geo-alt"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Updates Today</div>
                    <div class="h4 mb-0">{{ number_format($stats['location_updates_today']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Alerts -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bell me-2"></i>
                    System Alerts
                </h5>
                <small class="text-muted">Real-time monitoring</small>
            </div>
            <div class="card-body">
                @if(count($alerts) > 0)
                    @foreach($alerts as $alert)
                    <div class="alert-card alert-{{ $alert['type'] }} bg-light border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    @if($alert['type'] === 'danger')
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                                    @elseif($alert['type'] === 'warning')
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                    @else
                                        <i class="bi bi-info-circle text-info me-2"></i>
                                    @endif
                                    {{ $alert['title'] }}
                                </h6>
                                <p class="mb-1">{{ $alert['message'] }}</p>
                                <small class="text-muted">{{ $alert['action'] }}</small>
                            </div>
                            <a href="{{ $alert['url'] }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <p class="text-muted mt-2 mb-0">All systems operating normally</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Active Sessions by Bus -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity me-2"></i>
                    Active Tracking Sessions
                </h5>
            </div>
            <div class="card-body">
                @if($activeSessions->count() > 0)
                    <div class="row">
                        @foreach($activeSessions as $session)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        {{ $session->bus_id }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">Bus {{ $session->bus_id }}</div>
                                        <small class="text-muted">{{ $session->session_count }} active trackers</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="{{ route('admin.monitoring.live-tracking') }}?bus={{ $session->bus_id }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-pause-circle display-4 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No active tracking sessions</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Trust Score Distribution -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>
                    Trust Score Distribution
                </h5>
            </div>
            <div class="card-body">
                @if($trustDistribution->count() > 0)
                    @foreach($trustDistribution as $level)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small">{{ $level->trust_level }}</span>
                            <span class="small fw-bold">{{ $level->count }}</span>
                        </div>
                        <div class="trust-meter">
                            @php
                                $percentage = ($level->count / $trustDistribution->sum('count')) * 100;
                                $class = match(true) {
                                    str_contains($level->trust_level, 'High') => 'trust-high',
                                    str_contains($level->trust_level, 'Medium') => 'trust-medium',
                                    str_contains($level->trust_level, 'Low') && !str_contains($level->trust_level, 'Very') => 'trust-low',
                                    default => 'trust-very-low'
                                };
                            @endphp
                            <div class="trust-meter-fill {{ $class }}" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-graph-down display-4 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No trust data available</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.monitoring.live-tracking') }}" class="btn btn-outline-primary">
                        <i class="bi bi-geo-alt me-2"></i>Live Tracking Map
                    </a>
                    <a href="{{ route('admin.monitoring.device-trust') }}" class="btn btn-outline-warning">
                        <i class="bi bi-shield-exclamation me-2"></i>Device Trust Management
                    </a>
                    <a href="{{ route('admin.monitoring.analytics') }}" class="btn btn-outline-info">
                        <i class="bi bi-graph-up me-2"></i>Analytics Dashboard
                    </a>
                    <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-bus-front me-2"></i>Bus Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh dashboard every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);

    // Real-time updates via AJAX (optional enhancement)
    function updateRealTimeStats() {
        fetch('{{ route("admin.monitoring.real-time-data") }}')
            .then(response => response.json())
            .then(data => {
                // Update statistics without full page reload
                console.log('Real-time data updated:', data.timestamp);
            })
            .catch(error => {
                console.error('Error fetching real-time data:', error);
            });
    }

    // Update every 10 seconds
    setInterval(updateRealTimeStats, 10000);
</script>
@endpush