@extends('layouts.admin')

@section('title', 'Edit Bus')
@section('page-title', 'Edit Bus')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil me-2"></i>
                    Edit Bus {{ $bus->bus_id }}
                </h5>
                <div>
                    <a href="{{ route('admin.buses.show', $bus->bus_id) }}" class="btn btn-outline-info me-2">
                        <i class="bi bi-eye me-2"></i>View Details
                    </a>
                    <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Buses
                    </a>
                </div>
            </div>
            
            <form method="POST" action="{{ route('admin.buses.update', $bus->bus_id) }}">
                @csrf
                @method('PUT')
                
                <div class="card-body">
                    <!-- Bus ID (Read-only) -->
                    <div class="mb-3">
                        <label for="bus_id" class="form-label">
                            <i class="bi bi-bus-front me-2"></i>
                            Bus ID
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="bus_id" 
                               value="{{ $bus->bus_id }}" 
                               readonly>
                        <div class="form-text">Bus ID cannot be changed after creation</div>
                    </div>

                    <!-- Bus Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="bi bi-tag me-2"></i>
                            Bus Name
                        </label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $bus->name) }}" 
                               placeholder="e.g., Buriganga, Brahmaputra">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional friendly name for the bus</div>
                    </div>

                    <!-- Vehicle Details -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">
                                    <i class="bi bi-people me-2"></i>
                                    Capacity <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('capacity') is-invalid @enderror" 
                                       id="capacity" 
                                       name="capacity" 
                                       value="{{ old('capacity', $bus->capacity) }}" 
                                       min="1" 
                                       max="100"
                                       required>
                                @error('capacity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Number of passengers</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="vehicle_number" class="form-label">
                                    <i class="bi bi-card-text me-2"></i>
                                    Vehicle Number
                                </label>
                                <input type="text" 
                                       class="form-control @error('vehicle_number') is-invalid @enderror" 
                                       id="vehicle_number" 
                                       name="vehicle_number" 
                                       value="{{ old('vehicle_number', $bus->vehicle_number) }}" 
                                       placeholder="e.g., DHK-GA-1234">
                                @error('vehicle_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">License plate number</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="bi bi-circle me-2"></i>
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status" 
                                        required>
                                    <option value="active" {{ old('status', $bus->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $bus->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="maintenance" {{ old('status', $bus->status) === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Current operational status</div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Model and Year -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">
                                    <i class="bi bi-truck me-2"></i>
                                    Model
                                </label>
                                <input type="text" 
                                       class="form-control @error('model') is-invalid @enderror" 
                                       id="model" 
                                       name="model" 
                                       value="{{ old('model', $bus->model) }}" 
                                       placeholder="e.g., Toyota Coaster">
                                @error('model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Bus model/make</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year" class="form-label">
                                    <i class="bi bi-calendar me-2"></i>
                                    Year
                                </label>
                                <input type="number" 
                                       class="form-control @error('year') is-invalid @enderror" 
                                       id="year" 
                                       name="year" 
                                       value="{{ old('year', $bus->year) }}" 
                                       min="1990" 
                                       max="{{ date('Y') + 1 }}"
                                       placeholder="{{ date('Y') }}">
                                @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Manufacturing year</div>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="driver_name" class="form-label">
                                    <i class="bi bi-person me-2"></i>
                                    Driver Name
                                </label>
                                <input type="text" 
                                       class="form-control @error('driver_name') is-invalid @enderror" 
                                       id="driver_name" 
                                       name="driver_name" 
                                       value="{{ old('driver_name', $bus->driver_name) }}" 
                                       placeholder="Driver's full name">
                                @error('driver_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Primary driver name</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="driver_phone" class="form-label">
                                    <i class="bi bi-phone me-2"></i>
                                    Driver Phone
                                </label>
                                <input type="text" 
                                       class="form-control @error('driver_phone') is-invalid @enderror" 
                                       id="driver_phone" 
                                       name="driver_phone" 
                                       value="{{ old('driver_phone', $bus->driver_phone) }}" 
                                       placeholder="+880 1XXX-XXXXXX">
                                @error('driver_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Driver contact number</div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_maintenance_date" class="form-label">
                                    <i class="bi bi-wrench me-2"></i>
                                    Last Maintenance
                                </label>
                                <input type="date" 
                                       class="form-control @error('last_maintenance_date') is-invalid @enderror" 
                                       id="last_maintenance_date" 
                                       name="last_maintenance_date" 
                                       value="{{ old('last_maintenance_date', $bus->last_maintenance_date?->format('Y-m-d')) }}" 
                                       max="{{ date('Y-m-d') }}">
                                @error('last_maintenance_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Date of last maintenance</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="next_maintenance_date" class="form-label">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Next Maintenance
                                </label>
                                <input type="date" 
                                       class="form-control @error('next_maintenance_date') is-invalid @enderror" 
                                       id="next_maintenance_date" 
                                       name="next_maintenance_date" 
                                       value="{{ old('next_maintenance_date', $bus->next_maintenance_date?->format('Y-m-d')) }}" 
                                       min="{{ date('Y-m-d') }}">
                                @error('next_maintenance_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Scheduled maintenance date</div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Notes -->
                    <div class="mb-3">
                        <label for="maintenance_notes" class="form-label">
                            <i class="bi bi-journal-text me-2"></i>
                            Maintenance Notes
                        </label>
                        <textarea class="form-control @error('maintenance_notes') is-invalid @enderror" 
                                  id="maintenance_notes" 
                                  name="maintenance_notes" 
                                  rows="3" 
                                  placeholder="Any maintenance notes or issues...">{{ old('maintenance_notes', $bus->maintenance_notes) }}</textarea>
                        @error('maintenance_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional maintenance notes and history</div>
                    </div>

                    <!-- Active Status -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active" 
                                   id="is_active" 
                                   value="1"
                                   {{ old('is_active', $bus->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="bi bi-power me-2"></i>
                                Bus is Active
                            </label>
                        </div>
                        <div class="form-text">Active buses will appear in the passenger app and accept GPS tracking</div>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between">
                        <div>
                            <!-- Delete Button -->
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash me-2"></i>Delete Bus
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-admin-primary">
                                <i class="bi bi-check-circle me-2"></i>Update Bus
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete bus <strong>{{ $bus->bus_id }}</strong>?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action will permanently delete:
                    <ul class="mb-0 mt-2">
                        <li>All schedules for this bus</li>
                        <li>All tracking history</li>
                        <li>All current position data</li>
                        <li>All user tracking sessions</li>
                    </ul>
                </div>
                <p class="mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('admin.buses.destroy', $bus->bus_id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete Bus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-calculate next maintenance date suggestion
    document.getElementById('last_maintenance_date').addEventListener('change', function() {
        const lastMaintenanceDate = this.value;
        if (lastMaintenanceDate) {
            const lastDate = new Date(lastMaintenanceDate);
            // Add 6 months for typical maintenance interval
            const nextDate = new Date(lastDate.getTime() + (6 * 30 * 24 * 60 * 60 * 1000));
            const nextMaintenanceInput = document.getElementById('next_maintenance_date');
            if (!nextMaintenanceInput.value) {
                nextMaintenanceInput.value = nextDate.toISOString().split('T')[0];
            }
        }
    });
</script>
@endpush