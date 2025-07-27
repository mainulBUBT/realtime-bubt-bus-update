@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card primary">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-bus-front"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Total Buses</div>
                    <div class="h4 mb-0">{{ $stats['total_buses'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card success">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Active Buses</div>
                    <div class="h4 mb-0">{{ $stats['active_buses'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card warning">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="bi bi-phone"></i>
                </div>
                <div class="ms-3">
                    <div class="text-muted small">Total Devices</div>
                    <div class="h4 mb-0">{{ $stats['total_devices'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card danger">
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
</div>

<div class="row">
    <!-- Bus Status Overview -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Bus Status Overview
                </h5>
                <small class="text-muted">Real-time status</small>
            </div>
            <div class="card-body">
                @if($busStatuses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bus ID</th>
                                    <th>Status</th>
                                    <th>Active Trackers</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($busStatuses as $bus)
                                <tr>
                                    <td>
                                        <strong>{{ $bus->bus_id }}</strong>
                                    </td>
                                    <td>
                                        @if($bus->status === 'active')
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Active
                                            </span>
                                        @elseif($bus->status === 'inactive')
                                            <span class="badge bg-warning">
                                                <i class="bi bi-pause-circle me-1"></i>
                                                Inactive
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-question-circle me-1"></i>
                                                No Data
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $bus->active_trackers ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @if($bus->last_updated)
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($bus->last_updated)->diffForHumans() }}
                                            </small>
                                        @else
                                            <small class="text-muted">Never</small>
                                        @endif
                                    </td>
                                    <td>
                                        @can('view-monitoring')
                                            <a href="{{ route('admin.monitoring.live-tracking') }}?bus={{ $bus->bus_id }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-bus-front display-4 text-muted"></i>
                        <p class="text-muted mt-2">No bus data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-heart-pulse me-2"></i>
                    System Health
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Database Connections</small>
                        <small class="fw-bold">{{ $systemHealth['database_connections'] }}</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: {{ min(100, ($systemHealth['database_connections'] / 100) * 100) }}%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Avg Trust Score</small>
                        <small class="fw-bold">{{ number_format($systemHealth['avg_trust_score'], 2) }}</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ $systemHealth['avg_trust_score'] * 100 }}%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Location Updates Today</small>
                        <small class="fw-bold">{{ number_format($systemHealth['location_updates_today']) }}</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: {{ min(100, ($systemHealth['location_updates_today'] / 1000) * 100) }}%"></div>
                    </div>
                </div>

                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Active Sessions</small>
                        <small class="fw-bold">{{ $stats['active_tracking_sessions'] }}</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: {{ min(100, ($stats['active_tracking_sessions'] / 50) * 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Recent Tracking Activity
                </h5>
                <small class="text-muted">Last 24 hours</small>
            </div>
            <div class="card-body">
                @if($recentSessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bus ID</th>
                                    <th>Device Token</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Locations</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSessions as $session)
                                <tr>
                                    <td><strong>{{ $session->bus_id }}</strong></td>
                                    <td>
                                        <code class="small">{{ Str::limit($session->device_token, 12) }}</code>
                                    </td>
                                    <td>
                                        <small>{{ $session->started_at->format('M j, H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($session->ended_at)
                                            <small class="text-muted">
                                                {{ $session->started_at->diffInMinutes($session->ended_at) }}m
                                            </small>
                                        @else
                                            <small class="text-success">Active</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $session->locations_contributed ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @if($session->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Ended</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history display-4 text-muted"></i>
                        <p class="text-muted mt-2">No recent activity</p>
                    </div>
                @endif
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
</script>
@endpush