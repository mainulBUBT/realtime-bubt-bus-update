@extends('layouts.admin')

@section('title', 'Schedules')
@section('breadcrumb-title', 'Schedules')

@section('content')
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Manage Schedules</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Assign buses to routes with specific times</p>
    </div>
    <a href="{{ route('admin.schedules.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500 to-violet-500 hover:from-purple-600 hover:to-violet-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-[1.02]">
        <i class="bi bi-plus-lg"></i>
        Add Schedule
    </a>
</div>

{{-- Search & Filter Bar --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-6">
    <div class="p-4 md:p-6">
        <form action="{{ route('admin.schedules.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search schedules by bus, route, or time..."
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div class="flex gap-3">
                <select name="status" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all dark:bg-gray-700 dark:text-white font-medium">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>🔴 Inactive</option>
                </select>
                <button type="submit" class="px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-funnel"></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>
                <a href="{{ route('admin.schedules.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-x-lg"></i>
                    <span class="hidden sm:inline">Clear</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="max-w-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[1040px] divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bus</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Departure</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weekdays</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Effective</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($schedules as $schedule)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-bus-front text-emerald-600 dark:text-emerald-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $schedule->bus->display_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->bus->code }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-lg flex items-center justify-center">
                                <i class="bi bi-signpost text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $schedule->route->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->route->code }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($schedule->schedulePeriod)
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $schedule->schedulePeriod->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $schedule->schedulePeriod->start_date->format('M d') }} - {{ $schedule->schedulePeriod->end_date->format('M d, Y') }}
                                </p>
                            </div>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm font-medium">
                                <i class="bi bi-exclamation-triangle"></i>
                                Unassigned
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 font-semibold">
                            <i class="bi bi-clock"></i>
                            {{ \Carbon\Carbon::parse($schedule->departure_time)->format('g:i A') }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            @php
                                $weekdays = $schedule->weekdays;
                                $weekdayLabels = [
                                    'sunday' => 'S',
                                    'monday' => 'M',
                                    'tuesday' => 'T',
                                    'wednesday' => 'W',
                                    'thursday' => 'T',
                                    'friday' => 'F',
                                    'saturday' => 'S',
                                ];
                                if (is_string($weekdays)) {
                                    $weekdays = json_decode($weekdays, true) ?? [];
                                }
                                $weekdays = (array) $weekdays;
                            @endphp
                            @foreach($weekdays as $weekday)
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-400">
                                {{ $weekdayLabels[$weekday] ?? strtoupper(substr($weekday, 0, 1)) }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($schedule->effective_date)
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium">
                                <i class="bi bi-calendar"></i>
                                {{ $schedule->effective_date->format('M d, Y') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium">
                                <i class="bi bi-infinity"></i>
                                Always
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($schedule->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-700 dark:text-red-400">
                                <i class="bi bi-dash-circle"></i>
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.schedules.edit', $schedule) }}"
                               class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110"
                               title="Edit">
                                <i class="bi bi-pencil-fill text-lg"></i>
                            </a>
                            <button onclick="deleteItem('{{ route('admin.schedules.destroy', $schedule) }}', '{{ $schedule->bus->display_name }} - {{ $schedule->route->name }}')"
                                    class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110" title="Delete">
                                <i class="bi bi-trash-fill text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/30 rounded-3xl flex items-center justify-center mb-4">
                                <i class="bi bi-calendar3 text-purple-500 dark:text-purple-400 text-5xl"></i>
                            </div>
                            <p class="text-gray-900 dark:text-white text-xl font-bold mb-2">No schedules found</p>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first schedule</p>
                            <a href="{{ route('admin.schedules.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500 to-violet-500 hover:from-purple-600 hover:to-violet-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                                <i class="bi bi-plus-lg"></i>
                                Add Your First Schedule
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($schedules->hasPages())
<div class="mt-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-4 md:p-5">
    {{ $schedules->onEachSide(1)->links() }}
</div>
@endif
@endsection
