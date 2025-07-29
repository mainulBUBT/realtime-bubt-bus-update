@extends('layouts.admin')

@section('title', 'Bus Management')
@section('page-title', 'Bus Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Bus Fleet Management</h4>
        <p class="text-muted mb-0">Manage university buses, schedules, and real-time status</p>
    </div>
    <a href="{{ route('admin.buses.create') }}" class="btn btn-admin-primary">
        <i class="bi bi-plus-circle me-2"></i>
        Add New Bus
    </a>
</div>

<div class="row">
    @forelse($buses as $bus)
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bus-front me-2"></i>
                        {{ $bus->bus_id }}
                    </h5>
                    <small class="text-muted">{{ $bus->name ?: 'No name set' }}</small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                            type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.buses.show', $bus->bus_id) }}">
                                <i class="bi bi-eye me-2"></i>View Details
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.buses.edit', $bus->bus_id) }}">
                                <i class="bi bi-pencil me-2"></i>Edit Bus
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('admin.buses.toggle-status', $bus->bus_id) }}" 
                                  class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="dropdown-item">
                                    @if($bus->is_active)
                                        <i class="bi bi-pause-circle me-2"></i>Deactivate
                                    @else
                                        <i class="bi bi-play-circle me-2"></i>Activate
                                    @endif
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.buses.destroy', $bus->bus_id) }}" 
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to delete this bus? This will remove all related data.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-trash me-2"></i>Delete Bus
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Bus Status -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="me-2">
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
                                @if($bus->needs_maintenance)
                                    <span class="badge bg-warning ms-1">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Maintenance Due
                                    </span>
                                @endif
                            </div>
                        </div>
                        <small class="text-muted">Bus Status</small>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            @if($bus->current_status === 'active')
                                <span class="badge bg-success">
                                    <i class="bi bi-broadcast me-1"></i>Tracking
                                </span>
                            @elseif($bus->current_status === 'inactive')
                                <span class="badge bg-warning">
                                    <i class="bi bi-pause me-1"></i>Idle
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-question-circle me-1"></i>No Data
                                </span>
                            @endif
                        </div>
                        <small class="text-muted">Tracking Status</small>
                    </div>
                </div>

                <!-- Real-time Stats -->
                <div class="row mb-3">
                    <div class="col-3 text-center">
                        <div class="h6 mb-0 text-primary">{{ $bus->capacity }}</div>
                        <small class="text-muted">Capacity</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="h6 mb-0 text-success">{{ $bus->active_schedules }}</div>
                        <small class="text-muted">Active</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="h6 mb-0 text-info">{{ $bus->active_trackers }}</div>
                        <small class="text-muted">Trackers</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="h6 mb-0 text-warning">{{ number_format($bus->confidence_level * 100) }}%</div>
                        <small class="text-muted">Trust</small>
                    </div>
                </div>

                <!-- Driver Info -->
                @if($bus->driver_name)
                <div class="mb-3">
                    <small class="text-muted">Driver:</small>
                    <div class="fw-bold">{{ $bus->driver_name }}</div>
                    @if($bus->driver_phone)
                        <small class="text-muted">{{ $bus->driver_phone }}</small>
                    @endif
                </div>
                @endif

                <!-- Last Updated -->
                @if($bus->last_updated)
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Last updated {{ \Carbon\Carbon::parse($bus->last_updated)->diffForHumans() }}
                        </small>
                    </div>
                @else
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Never tracked
                        </small>
                    </div>
                @endif
            </div>
            
            <div class="card-footer bg-transparent">
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('admin.buses.show', $bus->bus_id) }}" 
                           class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.monitoring.live-tracking') }}?bus={{ $bus->bus_id }}" 
                           class="btn btn-sm btn-outline-success w-100">
                            <i class="bi bi-geo-alt me-1"></i>Track
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-bus-front display-1 text-muted mb-3"></i>
                <h4 class="text-muted">No Buses Found</h4>
                <p class="text-muted mb-4">Get started by adding your first bus to the system.</p>
                <a href="{{ route('admin.buses.create') }}" class="btn btn-admin-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Your First Bus
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Quick Stats Summary -->
@if($buses->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Fleet Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <div class="h3 mb-0 text-primary">{{ $buses->count() }}</div>
                            <small class="text-muted">Total Buses</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <div class="h3 mb-0 text-success">{{ $buses->where('is_active', true)->count() }}</div>
                            <small class="text-muted">Active Buses</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <div class="h3 mb-0 text-info">{{ $buses->where('current_status', 'active')->count() }}</div>
                            <small class="text-muted">Currently Tracking</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <div class="h3 mb-0 text-warning">{{ $buses->sum('active_trackers') }}</div>
                            <small class="text-muted">Total Active Trackers</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Auto-refresh bus status every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);
</script>
@endpush