@extends('layouts.admin')

@section('title', 'Route Details')

@section('content')
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ $route->name }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-semibold text-sm">
                <i class="bi bi-signpost"></i>
                {{ $route->code }}
            </span>
        </p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.routes.index') }}" class="inline-flex items-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
        <a href="{{ route('admin.routes.edit', $route) }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-[1.02]">
            <i class="bi bi-pencil-fill"></i>
            Edit Route
        </a>
    </div>
</div>

<!-- Route Information -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8 mb-6">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
        <i class="bi bi-info-circle text-blue-500"></i>
        Route Information
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi bi-geo-alt text-emerald-600 dark:text-emerald-400 text-lg"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Origin</p>
                <p class="text-gray-900 dark:text-white font-semibold">{{ $route->origin_name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <div class="w-12 h-12 bg-gradient-to-br from-rose-100 to-pink-100 dark:from-rose-900/30 dark:to-pink-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi bi-geo-alt-fill text-rose-600 dark:text-rose-400 text-lg"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Destination</p>
                <p class="text-gray-900 dark:text-white font-semibold">{{ $route->destination_name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                @if($route->direction === 'outbound')
                    <i class="bi bi-arrow-up-circle text-blue-600 dark:text-blue-400 text-lg"></i>
                @else
                    <i class="bi bi-arrow-down-circle text-purple-600 dark:text-purple-400 text-lg"></i>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Direction</p>
                @if($route->direction === 'outbound')
                    <p class="text-gray-900 dark:text-white font-semibold">Outbound (Campus to City)</p>
                @else
                    <p class="text-gray-900 dark:text-white font-semibold">Inbound (City to Campus)</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <div class="w-12 h-12 bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-600 dark:to-slate-600 rounded-xl flex items-center justify-center flex-shrink-0">
                @if($route->is_active)
                    <i class="bi bi-check-circle text-emerald-600 dark:text-emerald-400 text-lg"></i>
                @else
                    <i class="bi bi-dash-circle text-gray-400 dark:text-gray-300 text-lg"></i>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                @if($route->is_active)
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
            </div>
        </div>
    </div>
</div>

<!-- Route Stops -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-geo-alt-fill text-purple-500"></i>
            Route Stops
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                {{ $route->stops->count() }}
            </span>
        </h2>
    </div>

    @if($route->stops->count() > 0)
    <div class="space-y-3">
        @foreach($route->stops->sortBy('sequence') as $index => $stop)
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all group">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-500 text-white rounded-xl flex items-center justify-center font-bold text-sm shadow-lg shadow-purple-500/30 flex-shrink-0">
                {{ $stop->sequence }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $stop->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $stop->lat }}, {{ $stop->lng }}</p>
            </div>
            <a href="https://maps.google.com/?q={{ $stop->lat }},{{ $stop->lng }}" target="_blank" class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110 flex-shrink-0" title="View on Map">
                <i class="bi bi-map text-lg"></i>
            </a>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-700 dark:to-slate-700 rounded-3xl flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-geo-alt text-gray-400 dark:text-gray-300 text-4xl"></i>
        </div>
        <p class="text-gray-900 dark:text-white text-lg font-semibold mb-1">No stops defined</p>
        <p class="text-gray-500 dark:text-gray-400">This route doesn't have any stops yet</p>
    </div>
    @endif
</div>
@endsection
