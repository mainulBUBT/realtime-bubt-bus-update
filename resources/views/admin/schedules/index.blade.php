@extends('layouts.admin')

@section('title', 'Schedule Management')
@section('page-title', 'Schedule Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Bus Schedule Management</h4>
        <p class="text-muted mb-0">Manage departure times, routes, and operating schedules</p>
    </div>
    <a href="{{ route('admin.schedules.create') }}" class="btn btn-admin-primary">
        <i class="bi bi-plus-circle me-2"></i>
        Add New Schedule
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="bus_id" class="form-label">Bus ID</label>
                <select class="form-select" id="bus_id" name="bus_id">
                    <option value="">All Buses</option>
                    @foreach($schedules->pluck('bus_id')->unique()->sort() as $busId)
                        <option value="{{ $busId }}" {{ request('bus_id') === $busId ? 'selected' : '' }}>
                            {{ $busId }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="day" class="form-label">Operating Day</label>
                <select class="form-select" id="day" name="day">
                    <option value="">All Days</option>
                    <option value="monday" {{ request('day') === 'monday' ? 'selected' : '' }}>Monday</option>
                    <option value="tuesday" {{ request('day') === 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                    <option value="wednesday" {{ request('day') === 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                    <option value="thursday" {{ request('day') === 'thursday' ? 'selected' : '' }}>Thursday</option>
                    <option value="friday" {{ request('day') === 'friday' ? 'selected' : '' }}>Friday</option>
                    <option value="saturday" {{ request('day') === 'saturday' ? 'selected' : '' }}>Saturday</option>
                    <option value="sunday" {{ request('day') === 'sunday' ? 'selected' : '' }}>Sunday</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Schedules Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-calendar-event me-2"></i>
            All Schedules
        </h5>
        <small class="text-muted">{{ $schedules->total() }} total schedules</small>
    </div>
    <div class="card-body p-0">
        @if($schedules->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bus ID</th>
                            <th>Route Name</th>
                            <th>Schedule</th>
                            <th>Operating Days</th>
                            <th>Stops</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px; font-size: 12px; font-weight: bold;">
                                        {{ $schedule->bus_id }}
                                    </div>
                                    <strong>{{ $schedule->bus_id }}</strong>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold">{{ $schedule->route_name }}</div>
                                    @if($schedule->description)
                                        <small class="text-muted">{{ Str::limit($schedule->description, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div>
                                        <i class="bi bi-arrow-right text-success me-1"></i>
                                        <strong>{{ $schedule->departure_time->format('H:i') }}</strong>
                                        <small class="text-muted">Departure</small>
                                    </div>
                                    <div>
                                        <i class="bi bi-arrow-left text-info me-1"></i>
                                        <strong>{{ $schedule->return_time->format('H:i') }}</strong>
                                        <small class="text-muted">Return</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($schedule->days_of_week)
                                        @foreach($schedule->days_of_week as $day)
                                            <span class="badge bg-light text-dark">{{ ucfirst(substr($day, 0, 3)) }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-info me-2">{{ $schedule->routes->count() }}</span>
                                    @if($schedule->routes->count() > 0)
                                        <small class="text-muted">stops configured</small>
                                    @else
                                        <small class="text-warning">no stops</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($schedule->is_active)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pause-circle me-1"></i>Inactive
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.schedules.show', $schedule) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.schedules.routes', $schedule) }}">
                                                <i class="bi bi-geo-alt me-2"></i>Manage Routes
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.schedules.edit', $schedule) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Schedule
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this schedule?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Schedule
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($schedules->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $schedules->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                <h4 class="text-muted">No Schedules Found</h4>
                <p class="text-muted mb-4">Create your first bus schedule to get started.</p>
                <a href="{{ route('admin.schedules.create') }}" class="btn btn-admin-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Create First Schedule
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Quick Stats -->
@if($schedules->count() > 0)
<div class="row mt-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 mb-0 text-primary">{{ $schedules->pluck('bus_id')->unique()->count() }}</div>
                <small class="text-muted">Unique Buses</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 mb-0 text-success">{{ $schedules->where('is_active', true)->count() }}</div>
                <small class="text-muted">Active Schedules</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 mb-0 text-info">{{ $schedules->sum(function($s) { return $s->routes->count(); }) }}</div>
                <small class="text-muted">Total Stops</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 mb-0 text-warning">{{ $schedules->where('routes_count', 0)->count() }}</div>
                <small class="text-muted">Need Routes</small>
            </div>
        </div>
    </div>
</div>
@endif
@endsection