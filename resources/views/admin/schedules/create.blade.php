@extends('layouts.admin')

@section('title', 'Create Schedule')
@section('page-title', 'Create Schedule')

@push('styles')
<style>
    .conflict-warning {
        border-left: 4px solid #f39c12;
        background-color: #fef9e7;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Create New Schedule
                </h5>
                <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Schedules
                </a>
            </div>
            
            <form method="POST" action="{{ route('admin.schedules.store') }}" id="scheduleForm">
                @csrf
                
                <div class="card-body">
                    <!-- Conflict Warning (Hidden by default) -->
                    <div id="conflictWarning" class="alert conflict-warning d-none">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Schedule Conflicts Detected</h6>
                        <div id="conflictDetails"></div>
                        <small class="text-muted">Please review the conflicts before proceeding.</small>
                    </div>

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
                               value="{{ old('bus_id', $busId) }}" 
                               placeholder="e.g., B1, B2, B3..."
                               required>
                        @error('bus_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter the bus identifier (e.g., B1, B2, B3, etc.)</div>
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
                        <div class="form-text">Descriptive name for this route</div>
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
                                <div class="form-text">When bus leaves campus</div>
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
                                <div class="form-text">When bus returns to campus</div>
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
                        <div class="form-text">Select the days when this schedule operates</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="bi bi-card-text me-2"></i>
                            Description
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Optional description or notes about this schedule">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional notes about this schedule</div>
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
                                Schedule is Active
                            </label>
                        </div>
                        <div class="form-text">Active schedules will be available for passenger tracking</div>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Schedule
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Templates -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>
                    Quick Schedule Templates
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <h6>Morning Commute</h6>
                                <p class="small text-muted">07:00 - 15:00<br>Mon-Fri</p>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="applyTemplate('07:00', '15:00', ['monday','tuesday','wednesday','thursday','friday'])">
                                    Apply Template
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <h6>Full Day Service</h6>
                                <p class="small text-muted">08:00 - 18:00<br>Mon-Sat</p>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="applyTemplate('08:00', '18:00', ['monday','tuesday','wednesday','thursday','friday','saturday'])">
                                    Apply Template
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <h6>Weekend Service</h6>
                                <p class="small text-muted">09:00 - 17:00<br>Sat-Sun</p>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="applyTemplate('09:00', '17:00', ['saturday','sunday'])">
                                    Apply Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Apply schedule template
    function applyTemplate(departureTime, returnTime, days) {
        document.getElementById('departure_time').value = departureTime;
        document.getElementById('return_time').value = returnTime;
        
        // Clear all day checkboxes first
        document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => cb.checked = false);
        
        // Check the template days
        days.forEach(day => {
            const checkbox = document.getElementById('day_' + day);
            if (checkbox) checkbox.checked = true;
        });
        
        // Check for conflicts after applying template
        checkConflicts();
    }

    // Check for schedule conflicts
    function checkConflicts() {
        const busId = document.getElementById('bus_id').value;
        const departureTime = document.getElementById('departure_time').value;
        const returnTime = document.getElementById('return_time').value;
        const daysOfWeek = Array.from(document.querySelectorAll('input[name="days_of_week[]"]:checked'))
                               .map(cb => cb.value);

        if (!busId || !departureTime || !returnTime || daysOfWeek.length === 0) {
            document.getElementById('conflictWarning').classList.add('d-none');
            return;
        }

        fetch('{{ route("admin.schedules.check-conflicts") }}?' + new URLSearchParams({
            bus_id: busId,
            departure_time: departureTime,
            return_time: returnTime,
            days_of_week: daysOfWeek
        }))
        .then(response => response.json())
        .then(data => {
            const warningDiv = document.getElementById('conflictWarning');
            const detailsDiv = document.getElementById('conflictDetails');
            
            if (data.has_conflicts) {
                let conflictHtml = '<ul class="mb-0">';
                data.conflicts.forEach(conflict => {
                    conflictHtml += `<li><strong>${conflict.route_name}</strong> (${conflict.departure_time} - ${conflict.return_time}) on ${conflict.days_of_week.join(', ')}</li>`;
                });
                conflictHtml += '</ul>';
                
                detailsDiv.innerHTML = conflictHtml;
                warningDiv.classList.remove('d-none');
            } else {
                warningDiv.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Error checking conflicts:', error);
        });
    }

    // Auto-suggest return time
    document.getElementById('departure_time').addEventListener('change', function() {
        const departureTime = this.value;
        const returnInput = document.getElementById('return_time');
        
        if (departureTime && !returnInput.value) {
            const [hours, minutes] = departureTime.split(':');
            const departureDate = new Date();
            departureDate.setHours(parseInt(hours), parseInt(minutes));
            
            // Add 8 hours for typical round trip
            const returnDate = new Date(departureDate.getTime() + (8 * 60 * 60 * 1000));
            const returnTime = returnDate.toTimeString().slice(0, 5);
            
            returnInput.value = returnTime;
        }
        
        checkConflicts();
    });

    // Check conflicts when form values change
    ['bus_id', 'departure_time', 'return_time'].forEach(id => {
        document.getElementById(id).addEventListener('change', checkConflicts);
    });

    document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => {
        cb.addEventListener('change', checkConflicts);
    });

    // Form validation
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('Please select at least one operating day.');
            return false;
        }
    });
</script>
@endpush