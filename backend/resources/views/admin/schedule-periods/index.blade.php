@extends('layouts.admin')

@section('title', 'Schedule Periods')
@section('breadcrumb-title', 'Schedule Periods')

@section('content')
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Schedule Periods</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage academic semesters and schedule periods</p>
    </div>
    <a href="{{ route('admin.schedule-periods.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transition-all duration-300 transform hover:scale-[1.02]">
        <i class="bi bi-plus-lg"></i>
        Add Period
    </a>
</div>

{{-- Search & Filter Bar --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-6">
    <div class="p-4 md:p-6">
        <form action="{{ route('admin.schedule-periods.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search periods by name..."
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div class="flex gap-3">
                <select name="status" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all dark:bg-gray-700 dark:text-white font-medium">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Current</option>
                    <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>🟡 Upcoming</option>
                    <option value="past" {{ request('status') === 'past' ? 'selected' : '' }}>⚫ Past</option>
                </select>
                <button type="submit" class="px-4 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-funnel"></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>
                <a href="{{ route('admin.schedule-periods.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-x-lg"></i>
                    <span class="hidden sm:inline">Clear</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="max-w-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[760px] divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Start Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">End Date</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($periods as $period)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-calendar-range text-orange-600 dark:text-orange-400 text-lg"></i>
                            </div>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $period->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 font-semibold">
                            <i class="bi bi-calendar-event"></i>
                            {{ $period->start_date->format('M d, Y') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 font-semibold">
                            <i class="bi bi-calendar-check"></i>
                            {{ $period->end_date->format('M d, Y') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($period->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-700 dark:to-slate-700 text-gray-700 dark:text-gray-300">
                                <i class="bi bi-dash-circle"></i>
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.schedule-periods.edit', ['schedule_period' => $period->id]) }}"
                               class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110"
                               title="Edit">
                                <i class="bi bi-pencil-fill text-lg"></i>
                            </a>
                            <button data-delete-url="{{ route('admin.schedule-periods.destroy', ['schedule_period' => $period->id]) }}"
                                    data-delete-name="{{ $period->name }}"
                                    class="delete-btn w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110" title="Delete">
                                <i class="bi bi-trash-fill text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-3xl flex items-center justify-center mb-4">
                                <i class="bi bi-calendar-range text-orange-500 dark:text-orange-400 text-5xl"></i>
                            </div>
                            <p class="text-gray-900 dark:text-white text-xl font-bold mb-2">No schedule periods found</p>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first period</p>
                            <a href="{{ route('admin.schedule-periods.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                                <i class="bi bi-plus-lg"></i>
                                Add Your First Period
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($periods->hasPages())
<div class="mt-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-4 md:p-5">
    {{ $periods->onEachSide(1)->links() }}
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete button click handlers
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-delete-url');
            const name = this.getAttribute('data-delete-name');

            console.log('Delete button clicked:', { url, name });

            if (typeof deleteItem === 'function') {
                deleteItem(url, name);
            } else {
                console.error('deleteItem function not found!');
            }
        });
    });
});
</script>
@endsection
