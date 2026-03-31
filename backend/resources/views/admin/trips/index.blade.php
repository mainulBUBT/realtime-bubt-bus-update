@extends('layouts.admin')

@section('title', 'Trips')
@section('breadcrumb-title', 'Trips')

@section('content')
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Manage Trips</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">View and manage bus trips</p>
    </div>
    <a href="{{ route('admin.trips.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all duration-300 transform hover:scale-[1.02]">
        <i class="bi bi-plus-lg"></i>
        Add Trip
    </a>
</div>

{{-- Search & Filter Bar --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-6">
    <div class="p-4 md:p-6">
        <form action="{{ route('admin.trips.index') }}" method="GET" class="flex flex-col xl:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search trips by bus, route, or driver..."
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select name="status" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white font-medium">
                    <option value="">All Status</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>📋 Scheduled</option>
                    <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>🔄 Ongoing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all dark:bg-gray-700 dark:text-white">
                <button type="submit" class="px-4 py-3 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-funnel"></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>
                <a href="{{ route('admin.trips.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-x-lg"></i>
                    <span class="hidden sm:inline">Clear</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="max-w-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[980px] divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bus</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($trips as $trip)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-400 font-semibold">
                            <i class="bi bi-calendar"></i>
                            {{ $trip->trip_date->format('M d, Y') }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-bus-front text-emerald-600 dark:text-emerald-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $trip->bus->display_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $trip->bus->code }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-lg flex items-center justify-center">
                                <i class="bi bi-signpost text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $trip->route->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $trip->route->code }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($trip->driver)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 font-medium">
                                <i class="bi bi-person"></i>
                                {{ $trip->driver->name }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium">
                                <i class="bi bi-dash"></i>
                                Unassigned
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($trip->status === 'ongoing')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-400">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                Ongoing
                            </span>
                        @elseif($trip->status === 'completed')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <i class="bi bi-check2"></i>
                                Completed
                            </span>
                        @elseif($trip->status === 'cancelled')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-700 dark:text-red-400">
                                <i class="bi bi-x-lg"></i>
                                Cancelled
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <i class="bi bi-calendar3"></i>
                                Scheduled
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.trips.show', $trip) }}"
                               class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110"
                               title="View">
                                <i class="bi bi-eye-fill text-lg"></i>
                            </a>
                            <button onclick="deleteItem('{{ route('admin.trips.destroy', $trip) }}', 'Trip on {{ $trip->trip_date->format('M d, Y') }} ({{ $trip->bus->display_name }})')"
                                    class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110" title="Delete">
                                <i class="bi bi-trash-fill text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-3xl flex items-center justify-center mb-4">
                                <i class="bi bi-signpost-2 text-cyan-500 dark:text-cyan-400 text-5xl"></i>
                            </div>
                            <p class="text-gray-900 dark:text-white text-xl font-bold mb-2">No trips found</p>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Trips will appear here when buses start their routes</p>
                            <a href="{{ route('admin.trips.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                                <i class="bi bi-plus-lg"></i>
                                Create Your First Trip
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($trips->hasPages())
<div class="mt-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-4 md:p-5">
    {{ $trips->onEachSide(1)->links() }}
</div>
@endif
@endsection
