@extends('layouts.admin')

@section('title', 'Add New Bus')
@section('page-title', 'Add New Bus')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add New Bus
                </h5>
                <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Buses
                </a>
            </div>
            
            <form method="POST" action="{{ route('admin.buses.store') }}">
                @csrf
                
                <div class="card-body">
                    <!-- Bus ID -->
                    <div class="mb-3">
                        <label for="bus_id" class="form-label">
                            <i class="bi bi-bus-front me-2"></i>
                            Bus ID <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('bus_id') is-invalid @enderror" 
                               id="bus_id" 
                               name="bus_id" 
                               value="{{ old('bus_id') }}" 
                               placeholder="e.g., B1, B2, B3..."
                               required>
                        @error('bus_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unique identifier for the bus (e.g., B1, B2, B3, etc.)</div>
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
                               value="{{ old('name') }}" 
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
                                       value="{{ old('capacity', 40) }}" 
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
                                       value="{{ old('vehicle_number') }}" 
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
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
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
                                       value="{{ old('model') }}" 
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
                                       value="{{ old('year') }}" 
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
                                       value="{{ old('driver_name') }}" 
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
                                       value="{{ old('driver_phone') }}" 
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
                                       value="{{ old('last_maintenance_date') }}" 
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
                                       value="{{ old('next_maintenance_date') }}" 
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
                                  placeholder="Any maintenance notes or issues...">{{ old('maintenance_notes') }}</textarea>
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
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="bi bi-power me-2"></i>
                                Bus is Active
                            </label>
                        </div>
                        <div class="form-text">Active buses will appear in the passenger app and accept GPS tracking</div>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.buses.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Bus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Help Card -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Bus Setup Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-bus-front me-2"></i>Bus ID Guidelines</h6>
                        <ul class="small">
                            <li>Use simple identifiers like B1, B2, B3, etc.</li>
                            <li>Keep it short and memorable for passengers</li>
                            <li>Must be unique across all buses</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-clock me-2"></i>Schedule Guidelines</h6>
                        <ul class="small">
                            <li>Departure time is when bus leaves campus</li>
                            <li>Return time is when bus comes back to campus</li>
                            <li>Ensure adequate time between departure and return</li>
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="bi bi-calendar-week me-2"></i>Operating Days</h6>
                        <p class="small mb-0">Select all days when this bus operates. Passengers will only see active buses on their scheduled days.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-calculate return time suggestion
    document.getElementById('departure_time').addEventListener('change', function() {
        const departureTime = this.value;
        if (departureTime) {
            const [hours, minutes] = departureTime.split(':');
            const departureDate = new Date();
            departureDate.setHours(parseInt(hours), parseInt(minutes));
            
            // Add 8 hours for typical round trip
            const returnDate = new Date(departureDate.getTime() + (8 * 60 * 60 * 1000));
            const returnTime = returnDate.toTimeString().slice(0, 5);
            
            const returnInput = document.getElementById('return_time');
            if (!returnInput.value) {
                returnInput.value = returnTime;
            }
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('Please select at least one operating day.');
            return false;
        }
    });
</script>
@endpush