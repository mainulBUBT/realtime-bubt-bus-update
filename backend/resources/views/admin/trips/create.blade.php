@extends('layouts.admin')

@section('title', 'Add Trip')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Add Trip</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Create a manual trip or override a schedule</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <form action="{{ route('admin.trips.store') }}" method="POST" class="space-y-6" data-form-submit>
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bus Selection -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="bus_id">
                    <i class="bi bi-bus-front text-cyan-500"></i>
                    Bus <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('bus_id') border-red-500 @enderror"
                            id="bus_id" name="bus_id" required>
                        <option value="">Select a bus</option>
                        @foreach($buses as $bus)
                            <option value="{{ $bus->id }}" {{ old('bus_id') == $bus->id ? 'selected' : '' }}>
                                {{ $bus->display_name }} ({{ $bus->code }})
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-bus-front absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('bus_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Route Selection -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="route_id">
                    <i class="bi bi-signpost text-blue-500"></i>
                    Route <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('route_id') border-red-500 @enderror"
                            id="route_id" name="route_id" required>
                        <option value="">Select a route</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                                {{ $route->name }} ({{ $route->code }}) - {{ $route->origin_name }} to {{ $route->destination_name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-signpost absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('route_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Driver Selection -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="driver_id">
                    <i class="bi bi-person text-purple-500"></i>
                    Driver
                </label>
                <div class="relative">
                    <select class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('driver_id') border-red-500 @enderror"
                            id="driver_id" name="driver_id">
                        <option value="">Select a driver (optional)</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-person absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('driver_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Schedule Link -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="schedule_id">
                    <i class="bi bi-calendar3 text-orange-500"></i>
                    Schedule (Optional)
                </label>
                <div class="relative">
                    <select class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('schedule_id') border-red-500 @enderror"
                            id="schedule_id" name="schedule_id">
                        <option value="">Link to schedule (optional)</option>
                        @foreach($schedules as $schedule)
                            <option value="{{ $schedule->id }}" {{ old('schedule_id') == $schedule->id ? 'selected' : '' }}>
                                {{ $schedule->bus->display_name }} → {{ $schedule->route->name }} ({{ \Carbon\Carbon::parse($schedule->departure_time)->format('g:i A') }})
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-calendar3 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('schedule_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Trip Date -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="trip_date">
                    <i class="bi bi-calendar-event text-cyan-500"></i>
                    Trip Date <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white @error('trip_date') border-red-500 @enderror"
                           id="trip_date" type="date" name="trip_date" value="{{ old('trip_date') ?? today()->format('Y-m-d') }}" required>
                    <i class="bi bi-calendar-event absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                </div>
                @error('trip_date')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="status">
                    <i class="bi bi-bar-chart text-emerald-500"></i>
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('status') border-red-500 @enderror"
                            id="status" name="status" required>
                        <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>📋 Scheduled</option>
                        <option value="ongoing" {{ old('status') === 'ongoing' ? 'selected' : '' }}>🔄 Ongoing</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                    </select>
                    <i class="bi bi-bar-chart absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('status')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.trips.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>
            <button type="submit" class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 focus:outline-none focus:ring-4 focus:ring-cyan-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                Create Trip
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-form-submit]');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function() {
        setButtonLoading(submitBtn, '<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Creating...');
    });
});
</script>
@endsection
