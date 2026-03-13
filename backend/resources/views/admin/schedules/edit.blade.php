@extends('layouts.admin')

@section('title', 'Edit Schedule')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Edit Schedule</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Update schedule assignment</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST" class="space-y-6" data-form-submit>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="bus_id">
                    <i class="bi bi-bus-front text-purple-500"></i>
                    Bus <span class="text-red-500">*</span>
                </label>
                <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('bus_id') border-red-500 @enderror"
                        id="bus_id" name="bus_id" required>
                    <option value="">Select a bus</option>
                    @foreach($buses as $bus)
                        <option value="{{ $bus->id }}" {{ old('bus_id', $schedule->bus_id) == $bus->id ? 'selected' : '' }}>
                            {{ $bus->display_name }} ({{ $bus->code }}) - {{ $bus->capacity }} seats
                        </option>
                    @endforeach
                </select>
                @error('bus_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="route_id">
                    <i class="bi bi-signpost text-purple-500"></i>
                    Route <span class="text-red-500">*</span>
                </label>
                <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('route_id') border-red-500 @enderror"
                        id="route_id" name="route_id" required>
                    <option value="">Select a route</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ old('route_id', $schedule->route_id) == $route->id ? 'selected' : '' }}>
                            {{ $route->name }} ({{ $route->code }}) - {{ $route->origin_name }} to {{ $route->destination_name }}
                        </option>
                    @endforeach
                </select>
                @error('route_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="departure_time">
                    <i class="bi bi-clock text-purple-500"></i>
                    Departure Time <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white @error('departure_time') border-red-500 @enderror"
                       id="departure_time" type="time" name="departure_time" value="{{ old('departure_time', substr($schedule->departure_time, 0, 5)) }}" required>
                @error('departure_time')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="effective_date">
                    <i class="bi bi-calendar text-purple-500"></i>
                    Effective Date
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Optional)</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white @error('effective_date') border-red-500 @enderror"
                       id="effective_date" type="date" name="effective_date" value="{{ old('effective_date', $schedule->effective_date ? $schedule->effective_date->format('Y-m-d') : '') }}">
                @error('effective_date')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-2 flex items-center gap-1">
                    <i class="bi bi-info-circle"></i>
                    Leave empty for schedules effective immediately
                </p>
            </div>
        </div>

        {{-- Weekdays Selection --}}
        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-3 flex items-center gap-2">
                <i class="bi bi-calendar-week text-purple-500"></i>
                Weekdays <span class="text-red-500">*</span>
            </label>

            {{-- Preset Buttons --}}
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" onclick="selectAllWeekdays()" class="text-xs font-semibold bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg transition inline-flex items-center gap-1">
                    <i class="bi bi-calendar-check"></i>
                    All Days
                </button>
                <button type="button" onclick="selectWeekdays()" class="text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 px-4 py-2 rounded-lg transition inline-flex items-center gap-1">
                    <i class="bi bi-briefcase"></i>
                    Weekdays
                </button>
                <button type="button" onclick="selectWeekends()" class="text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/50 px-4 py-2 rounded-lg transition inline-flex items-center gap-1">
                    <i class="bi bi-sun"></i>
                    Weekends
                </button>
                <button type="button" onclick="clearWeekdays()" class="text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 px-4 py-2 rounded-lg transition inline-flex items-center gap-1">
                    <i class="bi bi-x"></i>
                    Clear All
                </button>
            </div>

            @php
                $savedWeekdays = $schedule->weekdays;
                if (is_string($savedWeekdays)) {
                    $savedWeekdays = json_decode($savedWeekdays, true) ?? [];
                }
                $savedWeekdays = (array) $savedWeekdays;
                $oldWeekdays = old('weekdays', $savedWeekdays);
                if (is_string($oldWeekdays)) {
                    $oldWeekdays = json_decode($oldWeekdays, true) ?? [];
                }
                $oldWeekdays = (array) $oldWeekdays;
            @endphp
            <div class="grid grid-cols-7 gap-3" id="weekday-grid">
                @foreach(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day)
                <label class="relative flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-purple-300 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all has-[:checked]:border-purple-500 has-[:checked]:bg-gradient-to-br has-[:checked]:from-purple-50 has-[:checked]:to-violet-50 dark:has-[:checked]:from-purple-900/30 dark:has-[:checked]:to-violet-900/30">
                    <input type="checkbox" name="weekdays[]" value="{{ $day }}" class="sr-only weekday-checkbox" {{ in_array($day, $oldWeekdays) ? 'checked' : '' }}>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ ucfirst(substr($day, 0, 3)) }}</span>
                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-purple-500 opacity-0 has-[:checked]:opacity-100 transition-opacity"></span>
                </label>
                @endforeach
            </div>
            @error('weekdays')
                <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
            @enderror
        </div>

        {{-- Active Status --}}
        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }} id="is_active" class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 focus:ring-2">
            <label for="is_active" class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                <i class="bi bi-toggle-on text-purple-500"></i>
                Active Schedule
            </label>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.schedules.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>
            <button type="submit" class="bg-gradient-to-r from-purple-500 to-violet-500 hover:from-purple-600 hover:to-violet-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 focus:outline-none focus:ring-4 focus:ring-purple-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
                <i class="bi bi-check-circle"></i>
                Update Schedule
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function selectAllWeekdays() {
    document.querySelectorAll('.weekday-checkbox').forEach(cb => cb.checked = true);
}

function selectWeekdays() {
    const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    document.querySelectorAll('.weekday-checkbox').forEach(cb => {
        cb.checked = weekdays.includes(cb.value);
    });
}

function selectWeekends() {
    const weekends = ['saturday', 'sunday'];
    document.querySelectorAll('.weekday-checkbox').forEach(cb => {
        cb.checked = weekends.includes(cb.value);
    });
}

function clearWeekdays() {
    document.querySelectorAll('.weekday-checkbox').forEach(cb => cb.checked = false);
}
</script>
@endpush

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
