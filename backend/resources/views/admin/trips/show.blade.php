@extends('layouts.admin')

@section('title', 'Trip Details')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Trip Details</h1>
        <p class="text-gray-600 mt-1">View trip information and location history</p>
    </div>
    <div class="flex gap-2">
        @if($trip->status === 'scheduled' || $trip->status === 'ongoing')
        <a href="#" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
            <i class="bi bi-geo-alt"></i>
            View on Map
        </a>
        @endif
        <a href="{{ route('admin.trips.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
            <i class="bi bi-arrow-left"></i>
            Back to Trips
        </a>
    </div>
</div>

{{-- Trip Information --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Main Trip Details --}}
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Trip Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Date</p>
                    <p class="font-medium text-gray-900">{{ $trip->trip_date->format('l, F j, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="font-medium">
                        @if($trip->status === 'ongoing')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Ongoing
                            </span>
                        @elseif($trip->status === 'completed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Completed
                            </span>
                        @elseif($trip->status === 'cancelled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Cancelled
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Scheduled
                            </span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Started At</p>
                    <p class="font-medium text-gray-900">{{ $trip->started_at ? $trip->started_at->format('g:i A') : 'Not started' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ended At</p>
                    <p class="font-medium text-gray-900">{{ $trip->ended_at ? $trip->ended_at->format('g:i A') : 'Not ended' }}</p>
                </div>
            </div>
        </div>

        {{-- Route Details --}}
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Route Information</h2>

            <div class="flex items-center gap-4">
                <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-map text-blue-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $trip->route->name }}</p>
                    <p class="text-sm text-gray-500">{{ $trip->route->code }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Origin:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $trip->route->origin_name }}</span>
                </div>
                <i class="bi bi-arrow-right text-gray-400"></i>
                <div>
                    <span class="text-gray-500">Destination:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $trip->route->destination_name }}</span>
                </div>
            </div>

            @if($trip->schedule)
            <div class="mt-4 pt-4 border-t">
                <p class="text-sm text-gray-500">Linked Schedule: {{ $trip->schedule->bus->display_name }} → {{ $trip->schedule->route->name }} ({{ \Carbon\Carbon::parse($trip->schedule->departure_time)->format('g:i A') }})</p>
            </div>
            @endif
        </div>

        {{-- Location History --}}
        @if($trip->locations->isNotEmpty())
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Location History</h2>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($trip->locations->take(20) as $location)
                <div class="flex items-start gap-3 text-sm">
                    <div class="h-2 w-2 bg-emerald-500 rounded-full mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1">
                        <p class="text-gray-900">{{ number_format($location->lat, 6) }}, {{ number_format($location->lng, 6) }}</p>
                        <p class="text-gray-500 text-xs">{{ $location->recorded_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            @if($trip->locations->count() > 20)
            <p class="text-sm text-gray-500 mt-4">Showing 20 of {{ $trip->locations->count() }} locations</p>
            @endif
        </div>
        @endif
    </div>

    <div class="space-y-6">
        {{-- Bus Information --}}
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bus</h2>

            <div class="flex items-center gap-4">
                <div class="h-12 w-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-bus-front text-emerald-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $trip->bus->display_name }}</p>
                    <p class="text-sm text-gray-500">{{ $trip->bus->code }} · {{ $trip->bus->plate_number }}</p>
                </div>
            </div>
        </div>

        {{-- Driver Information --}}
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Driver</h2>

            @if($trip->driver)
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-bold">
                    {{ strtoupper(substr($trip->driver->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $trip->driver->name }}</p>
                    <p class="text-sm text-gray-500">{{ $trip->driver->email }}</p>
                </div>
            </div>
            @else
            <p class="text-gray-400">No driver assigned</p>
            @endif
        </div>

        {{-- Current Location --}}
        @if($trip->current_lat && $trip->current_lng)
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Location</h2>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Latitude:</span>
                    <span class="font-medium text-gray-900">{{ number_format($trip->current_lat, 6) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Longitude:</span>
                    <span class="font-medium text-gray-900">{{ number_format($trip->current_lng, 6) }}</span>
                </div>
                @if($trip->last_location_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Last Updated:</span>
                    <span class="font-medium text-gray-900">{{ $trip->last_location_at->diffForHumans() }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
