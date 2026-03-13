@extends('layouts.admin')

@section('title', 'Edit Route')

@push('scripts')
<script>
let stopSequence = {{ $route->stops->max('sequence') ?? 0 }};

function addStop(name = '', lat = '', lng = '', sequence = null) {
    stopSequence++;
    const stopsContainer = document.getElementById('stops-container');
    const existingStops = document.querySelectorAll('.stop-row');
    const sequenceNum = existingStops.length + 1;
    const isEven = sequenceNum % 2 === 0;
    const bgColor = isEven ? 'bg-gray-50 dark:bg-gray-700/50' : 'bg-white dark:bg-gray-800';
    const seq = sequence ?? sequenceNum;

    const stopRow = document.createElement('div');
    stopRow.className = `stop-row ${bgColor} border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 relative transition-all hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500`;
    stopRow.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 bg-gradient-to-br from-blue-500 to-indigo-500 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-500/30">
                    ${seq}
                </div>
            </div>
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2">
                        <i class="bi bi-signpost text-blue-500"></i>
                        Stop Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="stops[${stopSequence}][name]" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white" placeholder="e.g. Main Gate" value="${name}" required>
                </div>
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2">
                        <i class="bi bi-geo-alt text-blue-500"></i>
                        Latitude
                    </label>
                    <input type="number" step="any" name="stops[${stopSequence}][lat]" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white" placeholder="23.8" value="${lat}" required>
                </div>
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2">
                        <i class="bi bi-geo-alt text-blue-500"></i>
                        Longitude
                    </label>
                    <input type="number" step="any" name="stops[${stopSequence}][lng]" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white" placeholder="90.4" value="${lng}" required>
                </div>
            </div>
            <div class="flex-shrink-0 pt-8">
                <button type="button" onclick="removeStop(this)" class="w-10 h-10 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-110" title="Remove Stop">
                    <i class="bi bi-trash-fill text-lg"></i>
                </button>
            </div>
        </div>
        <input type="hidden" name="stops[${stopSequence}][sequence]" value="${seq}">
    `;
    stopsContainer.appendChild(stopRow);
    updateBadges();
}

function removeStop(button) {
    const stopRow = button.closest('.stop-row');
    stopRow.remove();
    updateBadges();
}

function updateBadges() {
    const stopRows = document.querySelectorAll('.stop-row');
    stopRows.forEach((row, index) => {
        const sequenceNum = index + 1;
        const isEven = sequenceNum % 2 === 0;
        const bgColor = isEven ? 'bg-gray-50 dark:bg-gray-700/50' : 'bg-white dark:bg-gray-800';

        row.className = `stop-row ${bgColor} border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 relative transition-all hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500`;

        const badge = row.querySelector('.bg-gradient-to-br');
        if (badge) {
            badge.textContent = sequenceNum;
        }

        const seqInput = row.querySelector('input[name$="[sequence]"]');
        if (seqInput) {
            seqInput.value = sequenceNum;
        }
    });
}

// Load existing stops on page load
document.addEventListener('DOMContentLoaded', function() {
    @foreach($route->stops->sortBy('sequence') as $stop)
        addStop('{{ $stop->name }}', '{{ $stop->lat }}', '{{ $stop->lng }}', {{ $stop->sequence }});
    @endforeach
});
</script>
@endpush

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Edit Route</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Update route details for <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $route->name }}</span></p>
</div>

<form action="{{ route('admin.routes.update', $route) }}" method="POST" class="space-y-6" data-form-submit>
    @csrf
    @method('PUT')

    <!-- Basic Route Information -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
            <i class="bi bi-info-circle text-blue-500"></i>
            Route Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="code">
                    <i class="bi bi-tag text-blue-500"></i>
                    Route Code <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white @error('code') border-red-500 @enderror"
                       id="code" type="text" name="code" placeholder="e.g. R1" value="{{ old('code', $route->code) }}" required>
                @error('code')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="name">
                    <i class="bi bi-type text-blue-500"></i>
                    Route Name <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                       id="name" type="text" name="name" placeholder="e.g. Campus to Mirpur" value="{{ old('name', $route->name) }}" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="direction">
                    <i class="bi bi-arrow-up-down text-blue-500"></i>
                    Direction <span class="text-red-500">*</span>
                </label>
                <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('direction') border-red-500 @enderror"
                        id="direction" name="direction" required>
                    <option value="">Select Direction</option>
                    <option value="outbound" {{ old('direction', $route->direction) == 'outbound' ? 'selected' : '' }}>⬆️ Outbound (Campus to City)</option>
                    <option value="inbound" {{ old('direction', $route->direction) == 'inbound' ? 'selected' : '' }}>⬇️ Inbound (City to Campus)</option>
                </select>
                @error('direction')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="is_active">
                    <i class="bi bi-toggle-on text-blue-500"></i>
                    Status
                </label>
                <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer"
                        id="is_active" name="is_active">
                    <option value="1" {{ old('is_active', $route->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>🟢 Active</option>
                    <option value="0" {{ old('is_active', $route->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>🔴 Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="origin_name">
                    <i class="bi bi-geo-alt-fill text-emerald-500"></i>
                    Origin <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('origin_name') border-red-500 @enderror"
                       id="origin_name" type="text" name="origin_name" placeholder="e.g. BUBT Campus" value="{{ old('origin_name', $route->origin_name) }}" required>
                @error('origin_name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="destination_name">
                    <i class="bi bi-geo-alt-fill text-rose-500"></i>
                    Destination <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/20 transition-all dark:bg-gray-700 dark:text-white @error('destination_name') border-red-500 @enderror"
                       id="destination_name" type="text" name="destination_name" placeholder="e.g. Mirpur 10" value="{{ old('destination_name', $route->destination_name) }}" required>
                @error('destination_name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="polyline">
                    <i class="bi bi-bezier2 text-blue-500"></i>
                    Polyline JSON
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Optional)</span>
                </label>
                <textarea class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white font-mono text-sm"
                          id="polyline" name="polyline" rows="3" placeholder='[{"lat": 23.8, "lng": 90.4}, ...]'>{{ old('polyline', $route->polyline ? json_encode($route->polyline) : '') }}</textarea>
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-2 flex items-center gap-1">
                    <i class="bi bi-info-circle"></i>
                    JSON array of coordinates for the route path
                </p>
            </div>
        </div>
    </div>

    <!-- Route Stops -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-geo-alt text-blue-500"></i>
                    Route Stops ({{ $route->stops->count() }})
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage stops along this route</p>
            </div>
            <button type="button" onclick="addStop()" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                <i class="bi bi-plus-lg"></i>
                Add Stop
            </button>
        </div>

        <div id="stops-container" class="space-y-3">
            <!-- Stops will be loaded here dynamically via JavaScript -->
        </div>
    </div>

    <!-- Form Actions -->
    <div class="flex items-center justify-end gap-4 pt-6">
        <a href="{{ route('admin.routes.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
            <i class="bi bi-x-lg"></i>
            Cancel
        </a>
        <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 focus:outline-none focus:ring-4 focus:ring-blue-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
            <i class="bi bi-check-circle"></i>
            Update Route
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-form-submit]');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function() {
        setButtonLoading(submitBtn, '<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Updating...');
    });
});
</script>
@endsection
