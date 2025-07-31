<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BUBT Bus Tracker - Live Tracking')</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="{{ asset('assets/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <!-- Leaflet CSS for OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    
    <!-- Vite Assets -->
    @vite(['resources/css/bus-tracker.css', 'resources/css/track-map.css', 'resources/css/livewire-integration.css', 'resources/js/connection-manager.js'])
    
    @stack('styles')
</head>

<body>
    <!-- Connection Status Bar -->
    @livewire('connection-status')
    
    @yield('content')

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="{{ route('home') }}" class="nav-item">
            <i class="bi bi-house-door nav-icon"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="#" class="nav-item active">
            <i class="bi bi-bus-front nav-icon"></i>
            <span class="nav-label">Track</span>
        </a>
        <a href="#" class="nav-item menu-btn" id="menu-btn">
            <i class="bi bi-list nav-icon"></i>
            <span class="nav-label">Menu</span>
        </a>
    </nav>

    <!-- JavaScript Libraries -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Leaflet JavaScript for OpenStreetMap -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    
    @stack('scripts')
</body>

</html>