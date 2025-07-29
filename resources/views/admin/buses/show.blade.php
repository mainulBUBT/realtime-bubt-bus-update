@extends('layouts.admin')

@section('title', 'Bus Details')
@section('page-title', 'Bus Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-bus-front me-2"></i>
            Bus {{ $bus->bus_id }}
        </h4>
        <p class="text-muted mb-0">Detailed information and real-time status</p>
    </div>
    <div>
        <a href="{{ route('admin.buses.edit', $bus->bus_id) }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-pencil me-2"></i>Edit Bus
        </a>
        <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Buses
        </a>
    </div>
</div>

<div class="row">
    <!-- Bus Information -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Bus Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold">Bus ID:</td>
                                <td>{{ $bus->bus_id }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Bus Name:</td>
                                <td>{{ $bus->name ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Capacity:</td>
                                <td>{{ $bus->capacity }} passengers</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Vehicle Number:</td>
                                <td>{{ $bus->vehicle_number ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Status:</td>
                                <td>
                                    <span class="badge {{ $bus->status_badge_class }}">
                                        @if($bus->status === 'active')
                                            <i class="bi bi-check-circle me-1"></i>
                                        @elseif($bus->status === 'maintenance')
                                            <i class="bi bi-wrench me-1"></i>
                                        @else
                                            <i class="bi bi-pause-circle me-1"></i>
                                        @endif
                                        {{ $bus->status_display }}
                                    </span>
                                    @if($bus->needsMaintenance())
                                        <span class="badge bg-warning ms-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Maintenance Due
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Total Schedules:</td>
                                <td>{{ $bus->schedules->count() }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold">Tracking Status:</td>
                                <td>
                                    @if($bus->current_position)
                                        @if($bus->current_position->status === 'active')
                                            <span class="badge bg-success">
                                                <i class="bi bi-broadcast me-1"></i>Active Tracking
                                            </span>
                                        @elseif($bus->current_position->status === 'inactive')
                                            <span class="badge bg-warning">
                                                <i class="bi bi-pause me-1"></i>Idle
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-question-circle me-1"></i>No Data
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-question-circle me-1"></i>Never Tracked
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Active Trackers:</td>
                                <td>
                                    <span class="badge bg-info">{{ $bus->current_position->active_trackers ?? 0 }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Confidence Level:</td>
                                <td>
                                    @if($bus->current_position && $bus->current_position->confidence_level)
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 100px; height: 8px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: {{ $bus->current_position->confidence_level * 100 }}%"></div>
                                            </div>
                                            <small>{{ number_format($bus->current_position->confidence_level * 100) }}%</small>
                                        </div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Last Updated:</td>
                                <td>
                                    @if($bus->current_position && $bus->current_position->last_updated)
                                        <small>{{ \Carbon\Carbon::parse($bus->current_position->last_updated)->format('M j, Y H:i') }}</small>
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($bus->current_position->last_updated)->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedules -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-event me-2"></i>
                    Schedules
                </h5>
                <a href="{{ route('admin.schedules.create') }}?bus_id={{ $bus->bus_id }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus me-1"></i>Add Schedule
                </a>
            </div>
            <div class="card-body">
                @if($bus->schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Departure</th>
                                    <th>Return</th>
                                    <th>Operating Days</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bus->schedules as $schedule)
                                <tr>
                                    <td>
                                        <strong>{{ $schedule->departure_time->format('H:i') }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ $schedule->return_time->format('H:i') }}</strong>
                                    </td>
                                    <td>
                                        @if($schedule->days_of_week)
                                            @foreach($schedule->days_of_week as $day)
                                                <span class="badge bg-light text-dark me-1">{{ ucfirst(substr($day, 0, 3)) }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($schedule->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.schedules.edit', $schedule->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No schedules configured</p>
                        <a href="{{ route('admin.schedules.create') }}?bus_id={{ $bus->bus_id }}" class="btn btn-outline-primary">
                            <i class="bi bi-plus me-2"></i>Add First Schedule
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Tracking Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Recent Tracking Activity
                </h5>
            </div>
            <div class="card-body">
                @if($bus->recent_sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Device Token</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Locations</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bus->recent_sessions as $session)
                                <tr>
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
                        <p class="text-muted mt-2">No recent tracking activity</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Current Location -->
        @if($bus->current_position)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-geo-alt me-2"></i>
                    Current Location
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Coordinates</small>
                    <div class="fw-bold">
                        {{ number_format($bus->current_position->latitude, 6) }}, 
                        {{ number_format($bus->current_position->longitude, 6) }}
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Trust Score</small>
                    <div class="fw-bold">{{ number_format($bus->current_position->average_trust_score ?? 0, 2) }}</div>
                </div>
                <div class="mb-0">
                    <small class="text-muted">Movement Consistency</small>
                    <div class="fw-bold">{{ number_format($bus->current_position->movement_consistency ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="{{ route('admin.monitoring.live-tracking') }}?bus={{ $bus->bus_id }}" 
                   class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-geo-alt me-2"></i>View on Map
                </a>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.buses.edit', $bus->bus_id) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Bus
                    </a>
                    
                    <form method="POST" action="{{ route('admin.buses.toggle-status', $bus->bus_id) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($bus->is_active)
                            <button type="submit" class="btn btn-outline-warning w-100">
                                <i class="bi bi-pause-circle me-2"></i>Deactivate Bus
                            </button>
                        @else
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="bi bi-play-circle me-2"></i>Activate Bus
                            </button>
                        @endif
                    </form>
                    
                    <a href="{{ route('admin.monitoring.live-tracking') }}?bus={{ $bus->bus_id }}" 
                       class="btn btn-outline-info">
                        <i class="bi bi-graph-up me-2"></i>View Analytics
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h5 mb-0 text-primary">{{ $bus->recent_sessions->count() }}</div>
                        <small class="text-muted">Sessions (24h)</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h5 mb-0 text-success">{{ $bus->recent_sessions->where('is_active', true)->count() }}</div>
                        <small class="text-muted">Active Now</small>
                    </div>
                    <div class="col-6">
                        <div class="h5 mb-0 text-info">{{ $bus->recent_sessions->sum('locations_contributed') }}</div>
                        <small class="text-muted">Total Locations</small>
                    </div>
                    <div class="col-6">
                        <div class="h5 mb-0 text-warning">{{ $bus->recent_sessions->sum('valid_locations') }}</div>
                        <small class="text-muted">Valid Locations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);
</script>
@endpush