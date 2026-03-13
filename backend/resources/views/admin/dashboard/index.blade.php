@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Welcome back! Here's an overview of your bus tracker system.</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    {{-- Buses Card --}}
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-emerald-100 text-sm font-medium">Total Buses</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['buses']['total'] }}</p>
                <p class="text-emerald-100 text-sm mt-1">
                    <i class="bi bi-check-circle mr-1"></i>
                    {{ $stats['buses']['active'] }} active
                </p>
            </div>
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                <i class="bi bi-bus-front text-3xl"></i>
            </div>
        </div>
    </div>

    {{-- Routes Card --}}
    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Total Routes</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['routes']['total'] }}</p>
                <p class="text-blue-100 text-sm mt-1">
                    <i class="bi bi-check-circle mr-1"></i>
                    {{ $stats['routes']['active'] }} active
                </p>
            </div>
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                <i class="bi bi-signpost text-3xl"></i>
            </div>
        </div>
    </div>

    {{-- Schedules Card --}}
    <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-6 text-white shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-medium">Total Schedules</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['schedules']['total'] }}</p>
                <p class="text-purple-100 text-sm mt-1">
                    <i class="bi bi-check-circle mr-1"></i>
                    {{ $stats['schedules']['active'] }} active
                </p>
            </div>
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                <i class="bi bi-calendar3 text-3xl"></i>
            </div>
        </div>
    </div>

    {{-- Today's Trips Card --}}
    <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm font-medium">Today's Trips</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['trips']['today'] }}</p>
                <p class="text-orange-100 text-sm mt-1">
                    <i class="bi bi-activity mr-1"></i>
                    {{ $stats['trips']['ongoing'] }} ongoing
                </p>
            </div>
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                <i class="bi bi-signpost-2 text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left Column: Quick Actions & Setup Progress --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-lightning-charge text-emerald-500"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="p-4 space-y-2">
                <a href="{{ route('admin.buses.create') }}" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 dark:hover:from-emerald-900/20 dark:hover:to-teal-900/20 hover:text-emerald-700 dark:hover:text-emerald-400 transition-all duration-200 group">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="bi bi-plus-lg text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Add New Bus</span>
                </a>
                <a href="{{ route('admin.routes.create') }}" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 hover:text-blue-700 dark:hover:text-blue-400 transition-all duration-200 group">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="bi bi-plus-lg text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Create Route</span>
                </a>
                <a href="{{ route('admin.schedules.create') }}" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 dark:hover:from-purple-900/20 dark:hover:to-violet-900/20 hover:text-purple-700 dark:hover:text-purple-400 transition-all duration-200 group">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="bi bi-plus-lg text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Add Schedule</span>
                </a>
                <a href="{{ route('admin.schedule-periods.create') }}" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 dark:hover:from-orange-900/20 dark:hover:to-amber-900/20 hover:text-orange-700 dark:hover:text-orange-400 transition-all duration-200 group">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="bi bi-plus-lg text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">New Schedule Period</span>
                </a>
            </div>
        </div>

        {{-- Setup Progress --}}
        @if($setupProgress['percentage'] < 100)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-bar-chart-steps text-blue-500"></i>
                    Setup Progress
                </h2>
            </div>
            <div class="p-6">
                <div class="mb-6">
                    <div class="flex justify-between text-sm mb-3">
                        <span class="text-gray-600 dark:text-gray-400">{{ $setupProgress['completed'] }} of {{ $setupProgress['total'] }} steps</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($setupProgress['percentage']) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-3 rounded-full transition-all duration-500 ease-out" style="width: {{ $setupProgress['percentage'] }}%"></div>
                    </div>
                </div>
                <ul class="space-y-3 text-sm">
                    @foreach($setupProgress['steps'] as $key => $step)
                    <li class="flex items-center gap-3 {{ $step['done'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500' }}">
                        @if($step['done'])
                            <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-check-fill text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-circle text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                        @endif
                        <span>{{ $step['label'] }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Schedule Period Info --}}
        @if($activePeriod)
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6 hover:shadow-md transition-all duration-300">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-emerald-500/30">
                    <i class="bi bi-calendar-check text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-emerald-900 dark:text-emerald-400">Active Period</h3>
                    <p class="text-emerald-800 dark:text-emerald-300 font-semibold text-lg">{{ $activePeriod->name }}</p>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400 mt-1">
                        <i class="bi bi-calendar-range mr-1"></i>
                        {{ $activePeriod->start_date->format('M d') }} - {{ $activePeriod->end_date->format('M d, Y') }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right Column: Recent Activity --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Recent Schedules --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-calendar3 text-purple-500"></i>
                    Recent Schedules
                </h2>
                <a href="{{ route('admin.schedules.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors">View All →</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentSchedules as $schedule)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/30 rounded-xl flex items-center justify-center">
                            <i class="bi bi-calendar3 text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $schedule->bus->display_name }} → {{ $schedule->route->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <i class="bi bi-clock mr-1"></i>
                                {{ \Carbon\Carbon::parse($schedule->departure_time)->format('g:i A') }}
                                <span class="ml-2 px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs">
                                    {{ $schedule->formatted_weekdays }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div>
                        @if($schedule->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-calendar3 text-gray-400 dark:text-gray-500 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No schedules yet</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Create your first schedule to get started</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Trips --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-signpost-2 text-orange-500"></i>
                    Recent Trips
                </h2>
                <a href="{{ route('admin.trips.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors">View All →</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentTrips as $trip)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl flex items-center justify-center">
                            <i class="bi bi-signpost-2 text-orange-600 dark:text-orange-400 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $trip->bus->display_name }} → {{ $trip->route->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <i class="bi bi-calendar mr-1"></i>
                                {{ $trip->trip_date->format('M d, Y') }}
                                @if($trip->driver)
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-xs">
                                        <i class="bi bi-person mr-1"></i>{{ $trip->driver->name }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        @if($trip->status === 'ongoing')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-400">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                Ongoing
                            </span>
                        @elseif($trip->status === 'completed')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <i class="bi bi-check2 mr-1"></i>
                                Completed
                            </span>
                        @elseif($trip->status === 'cancelled')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-700 dark:text-red-400">
                                <i class="bi bi-x-lg mr-1"></i>
                                Cancelled
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                Scheduled
                            </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-signpost-2 text-gray-400 dark:text-gray-500 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No trips yet</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Trips will appear here once created</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Schedule Periods --}}
        @if($upcomingPeriods->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-calendar-event text-blue-500"></i>
                    Upcoming Schedule Periods
                </h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($upcomingPeriods as $period)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl flex items-center justify-center">
                            <i class="bi bi-calendar-event text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $period->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <i class="bi bi-calendar-range mr-1"></i>
                                {{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.schedule-periods.edit', ['schedule_period' => $period->id]) }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                        Manage <i class="bi bi-arrow-right ml-1"></i>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
