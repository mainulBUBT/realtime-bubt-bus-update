@extends('layouts.admin')

@section('title', 'Business Settings')
@section('page-title', 'Business Settings')

@push('styles')
<style>
    .color-preview {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        display: inline-block;
        vertical-align: middle;
    }
    
    .settings-section {
        border-left: 4px solid #007bff;
        background: #f8f9fa;
    }
    
    .logo-preview {
        max-width: 150px;
        max-height: 100px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        background: white;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Business Settings</h4>
        <p class="text-muted mb-0">Configure PWA appearance, university information, and system settings</p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-info me-2" data-bs-toggle="modal" data-bs-target="#backupModal">
            <i class="bi bi-download me-2"></i>Backup/Restore
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="resetToDefaults()">
            <i class="bi bi-arrow-clockwise me-2"></i>Reset to Defaults
        </button>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
    @csrf
    
    <div class="row">
        <!-- PWA Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header settings-section">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-phone me-2"></i>
                        PWA Customization
                    </h5>
                </div>
                <div class="card-body">
                    <!-- App Name -->
                    <div class="mb-3">
                        <label for="app_name" class="form-label">
                            App Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('app_name') is-invalid @enderror" 
                               id="app_name" 
                               name="app_name" 
                               value="{{ old('app_name', $settings['app_name']) }}" 
                               required>
                        @error('app_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Name displayed in the PWA</div>
                    </div>

                    <!-- App Description -->
                    <div class="mb-3">
                        <label for="app_description" class="form-label">
                            App Description <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('app_description') is-invalid @enderror" 
                                  id="app_description" 
                                  name="app_description" 
                                  rows="2"
                                  required>{{ old('app_description', $settings['app_description']) }}</textarea>
                        @error('app_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Brief description of the app</div>
                    </div>

                    <!-- Header Text -->
                    <div class="mb-3">
                        <label for="header_text" class="form-label">
                            Header Text <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('header_text') is-invalid @enderror" 
                               id="header_text" 
                               name="header_text" 
                               value="{{ old('header_text', $settings['header_text']) }}" 
                               required>
                        @error('header_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Text shown in the app header</div>
                    </div>

                    <!-- Colors -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_primary_color" class="form-label">
                                    Primary Color <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color @error('app_primary_color') is-invalid @enderror" 
                                           id="app_primary_color" 
                                           name="app_primary_color" 
                                           value="{{ old('app_primary_color', $settings['app_primary_color']) }}" 
                                           required>
                                    <input type="text" 
                                           class="form-control" 
                                           id="app_primary_color_text"
                                           value="{{ old('app_primary_color', $settings['app_primary_color']) }}"
                                           readonly>
                                </div>
                                @error('app_primary_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_secondary_color" class="form-label">
                                    Secondary Color <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color @error('app_secondary_color') is-invalid @enderror" 
                                           id="app_secondary_color" 
                                           name="app_secondary_color" 
                                           value="{{ old('app_secondary_color', $settings['app_secondary_color']) }}" 
                                           required>
                                    <input type="text" 
                                           class="form-control" 
                                           id="app_secondary_color_text"
                                           value="{{ old('app_secondary_color', $settings['app_secondary_color']) }}"
                                           readonly>
                                </div>
                                @error('app_secondary_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Logo Upload -->
                    <div class="mb-3">
                        <label class="form-label">App Logo</label>
                        @if($settings['app_logo'])
                            <div class="mb-2">
                                <img src="{{ Storage::url($settings['app_logo']) }}" 
                                     alt="Current Logo" 
                                     class="logo-preview">
                            </div>
                        @endif
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logoModal">
                            <i class="bi bi-upload me-2"></i>
                            {{ $settings['app_logo'] ? 'Change Logo' : 'Upload Logo' }}
                        </button>
                        <div class="form-text">Recommended: 512x512px PNG or SVG</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- University Information -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header settings-section">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>
                        University Information
                    </h5>
                </div>
                <div class="card-body">
                    <!-- University Name -->
                    <div class="mb-3">
                        <label for="university_name" class="form-label">
                            University Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('university_name') is-invalid @enderror" 
                               id="university_name" 
                               name="university_name" 
                               value="{{ old('university_name', $settings['university_name']) }}" 
                               required>
                        @error('university_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- University Address -->
                    <div class="mb-3">
                        <label for="university_address" class="form-label">Address</label>
                        <textarea class="form-control @error('university_address') is-invalid @enderror" 
                                  id="university_address" 
                                  name="university_address" 
                                  rows="2">{{ old('university_address', $settings['university_address']) }}</textarea>
                        @error('university_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="university_phone" class="form-label">Phone</label>
                                <input type="tel" 
                                       class="form-control @error('university_phone') is-invalid @enderror" 
                                       id="university_phone" 
                                       name="university_phone" 
                                       value="{{ old('university_phone', $settings['university_phone']) }}">
                                @error('university_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="university_email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control @error('university_email') is-invalid @enderror" 
                                       id="university_email" 
                                       name="university_email" 
                                       value="{{ old('university_email', $settings['university_email']) }}">
                                @error('university_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Website -->
                    <div class="mb-3">
                        <label for="university_website" class="form-label">Website</label>
                        <input type="url" 
                               class="form-control @error('university_website') is-invalid @enderror" 
                               id="university_website" 
                               name="university_website" 
                               value="{{ old('university_website', $settings['university_website']) }}" 
                               placeholder="https://example.com">
                        @error('university_website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tracking Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header settings-section">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt me-2"></i>
                        Tracking Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Tracking Interval -->
                    <div class="mb-3">
                        <label for="tracking_interval" class="form-label">
                            Tracking Interval (seconds) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('tracking_interval') is-invalid @enderror" 
                               id="tracking_interval" 
                               name="tracking_interval" 
                               value="{{ old('tracking_interval', $settings['tracking_interval']) }}" 
                               min="10" 
                               max="300"
                               required>
                        @error('tracking_interval')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">How often to collect GPS data (10-300 seconds)</div>
                    </div>

                    <!-- Location Accuracy -->
                    <div class="mb-3">
                        <label for="location_accuracy_threshold" class="form-label">
                            Location Accuracy Threshold (meters) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('location_accuracy_threshold') is-invalid @enderror" 
                               id="location_accuracy_threshold" 
                               name="location_accuracy_threshold" 
                               value="{{ old('location_accuracy_threshold', $settings['location_accuracy_threshold']) }}" 
                               min="10" 
                               max="200"
                               required>
                        @error('location_accuracy_threshold')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Minimum GPS accuracy required</div>
                    </div>

                    <!-- Trust Score Threshold -->
                    <div class="mb-3">
                        <label for="trust_score_threshold" class="form-label">
                            Trust Score Threshold <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('trust_score_threshold') is-invalid @enderror" 
                               id="trust_score_threshold" 
                               name="trust_score_threshold" 
                               value="{{ old('trust_score_threshold', $settings['trust_score_threshold']) }}" 
                               min="0.1" 
                               max="1.0" 
                               step="0.1"
                               required>
                        @error('trust_score_threshold')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Minimum trust score for reliable tracking</div>
                    </div>

                    <!-- Speed Threshold -->
                    <div class="mb-3">
                        <label for="max_speed_threshold" class="form-label">
                            Max Speed Threshold (km/h) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('max_speed_threshold') is-invalid @enderror" 
                               id="max_speed_threshold" 
                               name="max_speed_threshold" 
                               value="{{ old('max_speed_threshold', $settings['max_speed_threshold']) }}" 
                               min="20" 
                               max="150"
                               required>
                        @error('max_speed_threshold')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum realistic bus speed</div>
                    </div>

                    <!-- Route Radius -->
                    <div class="mb-3">
                        <label for="route_radius_tolerance" class="form-label">
                            Route Radius Tolerance (meters) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('route_radius_tolerance') is-invalid @enderror" 
                               id="route_radius_tolerance" 
                               name="route_radius_tolerance" 
                               value="{{ old('route_radius_tolerance', $settings['route_radius_tolerance']) }}" 
                               min="50" 
                               max="1000"
                               required>
                        @error('route_radius_tolerance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Acceptable distance from expected route</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header settings-section">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>
                        System Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Data Retention -->
                    <div class="mb-3">
                        <label for="data_retention_days" class="form-label">
                            Data Retention (days) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('data_retention_days') is-invalid @enderror" 
                               id="data_retention_days" 
                               name="data_retention_days" 
                               value="{{ old('data_retention_days', $settings['data_retention_days']) }}" 
                               min="7" 
                               max="365"
                               required>
                        @error('data_retention_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">How long to keep historical data</div>
                    </div>

                    <!-- Max Concurrent Trackers -->
                    <div class="mb-3">
                        <label for="max_concurrent_trackers" class="form-label">
                            Max Concurrent Trackers <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('max_concurrent_trackers') is-invalid @enderror" 
                               id="max_concurrent_trackers" 
                               name="max_concurrent_trackers" 
                               value="{{ old('max_concurrent_trackers', $settings['max_concurrent_trackers']) }}" 
                               min="50" 
                               max="1000"
                               required>
                        @error('max_concurrent_trackers')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum simultaneous tracking sessions</div>
                    </div>

                    <!-- System Toggles -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="enable_notifications" 
                                   id="enable_notifications" 
                                   value="1"
                                   {{ old('enable_notifications', $settings['enable_notifications']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_notifications">
                                Enable Notifications
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="enable_debug_mode" 
                                   id="enable_debug_mode" 
                                   value="1"
                                   {{ old('enable_debug_mode', $settings['enable_debug_mode']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_debug_mode">
                                Enable Debug Mode
                            </label>
                        </div>
                        <div class="form-text">Show additional debugging information</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="maintenance_mode" 
                                   id="maintenance_mode" 
                                   value="1"
                                   {{ old('maintenance_mode', $settings['maintenance_mode']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_mode">
                                Maintenance Mode
                            </label>
                        </div>
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This will disable the app for users
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-admin-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>
                        Save All Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Logo Upload Modal -->
<div class="modal fade" id="logoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>
                    Upload Logo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.settings.upload-logo') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="logo" class="form-label">Select Logo File</label>
                        <input type="file" 
                               class="form-control" 
                               id="logo" 
                               name="logo" 
                               accept="image/*"
                               required>
                        <div class="form-text">
                            Supported formats: JPEG, PNG, JPG, SVG<br>
                            Maximum size: 2MB<br>
                            Recommended: 512x512px
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-primary">
                        <i class="bi bi-upload me-2"></i>Upload Logo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Backup/Restore Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-download me-2"></i>
                    Backup & Restore
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Create Backup -->
                <div class="mb-4">
                    <h6>Create Backup</h6>
                    <p class="text-muted">Download current settings as a JSON file.</p>
                    <form method="POST" action="{{ route('admin.settings.backup') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-download me-2"></i>Create Backup
                        </button>
                    </form>
                </div>

                <hr>

                <!-- Restore Backup -->
                <div>
                    <h6>Restore from Backup</h6>
                    <p class="text-muted">Upload a previously created backup file to restore settings.</p>
                    <form method="POST" action="{{ route('admin.settings.restore') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" 
                                   class="form-control" 
                                   name="backup_file" 
                                   accept=".json"
                                   required>
                        </div>
                        <button type="submit" class="btn btn-outline-warning">
                            <i class="bi bi-upload me-2"></i>Restore Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Color picker sync
    document.getElementById('app_primary_color').addEventListener('input', function() {
        document.getElementById('app_primary_color_text').value = this.value;
    });

    document.getElementById('app_secondary_color').addEventListener('input', function() {
        document.getElementById('app_secondary_color_text').value = this.value;
    });

    // Reset to defaults
    function resetToDefaults() {
        if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
            fetch('{{ route("admin.settings.reset-defaults") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to reset settings: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while resetting settings.');
            });
        }
    }

    // Form validation
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        const maintenanceMode = document.getElementById('maintenance_mode').checked;
        
        if (maintenanceMode) {
            if (!confirm('You are enabling maintenance mode. This will disable the app for all users. Are you sure?')) {
                e.preventDefault();
                return false;
            }
        }
    });
</script>
@endpush