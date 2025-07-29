@extends('layouts.admin')

@section('title', 'Bulk Create Schedules')
@section('page-title', 'Bulk Create Schedules')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-square me-2"></i>
                    Bulk Create Schedules
                </h5>
                <div>
                    <a href="{{ route('admin.schedules.templates') }}" class="btn btn-outline-info me-2">
                        <i class="bi bi-bookmark me-2"></i>Templates
                    </a>
                    <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Schedules
                    </a>
                </div>
            </div>
            
            <form method="POST" action="{{ route('admin.schedules.bulk-store') }}">
                @csrf
                
                <div class="card-body">
                    <!-- Template Selection -->
                    @if($templates->count() > 0)
                    <div class="mb-4">
                        <label for="template_id" class="form-label">
                            <i class="bi bi-bookmark me-2"></i>
                            Use Template (Optional)
                        </label>
                        <select class="form-select" id="template_id" name="template_id">
                            <option value="">Create from scratch</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-template="{{ json_encode($template->template_data) }}">
                                    {{ $template->name }}
                                    @if($template->description)
                                        - {{ $template->description }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Select a template to pre-fill the form with common settings</div>
                    </div>
                    @endif

                    <!-- Bus Selection -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-bus-front me-2"></i>
                            Select Buses <span class="text-danger">*</span>
                        </label>
                        <div class="row">
                            @foreach($buses as $bus)
                            <div class="col-md-4 col-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input @error('bus_ids') is-invalid @enderror" 
                                           type="checkbox" 
                                           name="bus_ids[]" 
                                           value="{{ $bus->bus_id }}" 
                                           id="bus_{{ $bus->bus_id }}"
                                           {{ in_array($bus->bus_id, old('bus_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="bus_{{ $bus->bus_id }}">
                                        <strong>{{ $bus->bus_id }}</strong>
                                        @if($bus->name)
                                            <br><small class="text-muted">{{ $bus->name }}</small>
                                        @endif
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('bus_ids')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Select all buses that should have this schedule</div>
                    </div>

                    <!-- Route Name -->
                    <div class="mb-3">
                        <label for="route_name" class="form-label">
                            <i class="bi bi-signpost me-2"></i>
                            Route Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('route_name') is-invalid @enderror" 
                               id="route_name" 
                               name="route_name" 
                               value="{{ old('route_name') }}" 
                               placeholder="e.g., BUBT Campus - Asad Gate"
                               required>
                        @error('route_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Descriptive name for the bus route</div>
                    </div>

                    <!-- Schedule Times -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departure_time" class="form-label">
                                    <i class="bi bi-clock me-2"></i>
                                    Departure Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control @error('departure_time') is-invalid @enderror" 
                                       id="departure_time" 
                                       name="departure_time" 
                                       value="{{ old('departure_time') }}" 
                                       required>
                                @error('departure_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time when buses depart from campus</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_time" class="form-label">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Return Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control @error('return_time') is-invalid @enderror" 
                                       id="return_time" 
                                       name="return_time" 
                                       value="{{ old('return_time') }}" 
                                       required>
                                @error('return_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time when buses return to campus</div>
                            </div>
                        </div>
                    </div>

                    <!-- Days of Week -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar-week me-2"></i>
                            Operating Days <span class="text-danger">*</span>
                        </label>
                        <div class="row">
                            @php
                                $days = [
                                    'monday' => 'Monday',
                                    'tuesday' => 'Tuesday', 
                                    'wednesday' => 'Wednesday',
                                    'thursday' => 'Thursday',
                                    'friday' => 'Friday',
                                    'saturday' => 'Saturday',
                                    'sunday' => 'Sunday'
                                ];
                                $oldDays = old('days_of_week', []);
                            @endphp
                            @foreach($days as $value => $label)
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input @error('days_of_week') is-invalid @enderror" 
                                           type="checkbox" 
                                           name="days_of_week[]" 
                                           value="{{ $value }}" 
                                           id="day_{{ $value }}"
                                           {{ in_array($value, $oldDays) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('days_of_week')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Select the days when these buses operate</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="bi bi-journal-text me-2"></i>
                            Description
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Optional description for this schedule...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional description for this schedule</div>
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
                                Schedules are Active
                            </label>
                        </div>
                        <div class="form-text">Active schedules will appear in the passenger app</div>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-info" onclick="selectAllBuses()">
                                <i class="bi bi-check-all me-2"></i>Select All Buses
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearAllBuses()">
                                <i class="bi bi-x-circle me-2"></i>Clear All
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-admin-primary">
                                <i class="bi bi-check-circle me-2"></i>Create Schedules
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>
                    Schedule Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Template selection handler
    document.getElementById('template_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const templateData = selectedOption.dataset.template;
        
        if (templateData) {
            const data = JSON.parse(templateData);
            
            // Fill form fields
            if (data.route_name) document.getElementById('route_name').value = data.route_name;
            if (data.departure_time) document.getElementById('departure_time').value = data.departure_time;
            if (data.return_time) document.getElementById('return_time').value = data.return_time;
            if (data.description) document.getElementById('description').value = data.description;
            if (data.is_active !== undefined) document.getElementById('is_active').checked = data.is_active;
            
            // Fill days of week
            if (data.days_of_week) {
                // Clear all checkboxes first
                document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => cb.checked = false);
                
                // Check selected days
                data.days_of_week.forEach(day => {
                    const checkbox = document.getElementById('day_' + day);
                    if (checkbox) checkbox.checked = true;
                });
            }
        }
    });

    // Bus selection helpers
    function selectAllBuses() {
        document.querySelectorAll('input[name="bus_ids[]"]').forEach(cb => cb.checked = true);
    }

    function clearAllBuses() {
        document.querySelectorAll('input[name="bus_ids[]"]').forEach(cb => cb.checked = false);
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const checkedBuses = document.querySelectorAll('input[name="bus_ids[]"]:checked');
        const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
        
        if (checkedBuses.length === 0) {
            e.preventDefault();
            alert('Please select at least one bus.');
            return false;
        }
        
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('Please select at least one operating day.');
            return false;
        }
        
        // Show confirmation
        const busCount = checkedBuses.length;
        if (!confirm(`Are you sure you want to create schedules for ${busCount} bus(es)?`)) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endpush