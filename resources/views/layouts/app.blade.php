<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BUBT Bus Tracker')</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="{{ asset('assets/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/css/bus-app.css', 'resources/css/bus-tracker.css', 'resources/css/track-map.css', 'resources/css/livewire-integration.css', 'resources/js/app.js', 'resources/js/device-fingerprint.js', 'resources/js/bus-tracker.js', 'resources/js/connection-manager.js', 'resources/js/websocket-client.js', 'resources/js/livewire-app.js', 'resources/js/map.js'])
    
    @stack('styles')
</head>

<body>
    <!-- Splash Screen -->
    <div id="splash-screen" class="splash-screen">
        <div class="splash-content">
            <div class="splash-logo">
                <i class="bi bi-bus-front"></i>
            </div>
            <h1 class="app-name">BUBT CommuteMate</h1>
            <div class="app-tagline">Your University Shuttle Companion</div>
            <div class="splash-spacer"></div>
            <div class="splash-loading">
                <div class="loading-spinner"></div>
                <div class="loading-text">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Location Permission Screen -->
    <div id="permission-screen" class="permission-container">
        <img src="{{ asset('assets/images/location-permission.png') }}" alt="Location Permission" class="permission-illustration">
        <h2 class="permission-title">Allow "CommuteMate" to use your location?</h2>
        <p class="permission-description">Your location is used to show your position on the map and provide accurate
            bus arrival times. We never share your location with others.</p>
        <div class="permission-buttons">
            <button class="btn btn-allow" id="allow-once">Allow Once</button>
            <button class="btn btn-allow" id="allow-while-using">Allow While Using App</button>
            <button class="btn btn-deny" id="dont-allow">Don't Allow</button>
        </div>
    </div>

    <!-- Connection Status Bar -->
    @livewire('connection-status')
    
    @yield('content')

    <!-- Side Drawer Menu -->
    <div class="drawer-overlay" id="drawer-overlay"></div>
    <div class="side-drawer" id="side-drawer">
        <div class="drawer-header">
            <div class="drawer-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="BUBT Bus Tracker">
            </div>
            <h3>BUBT Bus Tracker</h3>
            <button class="drawer-close" id="drawer-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="drawer-content">
            <a href="#" class="drawer-item {{ request()->routeIs('home') ? 'active' : '' }}" data-screen="home-screen">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>
            <a href="#" class="drawer-item" data-screen="chuti-screen">
                <i class="bi bi-calendar-event"></i>
                <span>Chuti Kobe</span>
            </a>
            <a href="#" class="drawer-item">
                <i class="bi bi-code-square"></i>
                <span>Developer</span>
            </a>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="#" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}" data-screen="home-screen">
            <i class="bi bi-house-door nav-icon"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="#" class="nav-item" data-screen="chuti-screen">
            <i class="bi bi-calendar-event nav-icon"></i>
            <span class="nav-label">Chuti Kobe</span>
        </a>
        <a href="#" class="nav-item menu-btn" id="menu-btn">
            <i class="bi bi-list nav-icon"></i>
            <span class="nav-label">Menu</span>
        </a>
    </nav>

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    
    @stack('scripts')
    
    <!-- Initialize splash screen -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const splashScreen = document.getElementById('splash-screen');
            const permissionScreen = document.getElementById('permission-screen');

            // Add animation class for the splash screen
            splashScreen.classList.add('animate');

            // Hide splash screen after 2.5 seconds
            setTimeout(function () {
                splashScreen.classList.add('hidden');

                // Show permission screen if location permission not granted
                const locationPermission = localStorage.getItem('locationPermission');
                if (!locationPermission || locationPermission === 'denied') {
                    permissionScreen.style.display = 'flex';
                    setupPermissionButtons();
                }
            }, 2500);
        });

        function setupPermissionButtons() {
            const permissionScreen = document.getElementById('permission-screen');
            const allowOnceBtn = document.getElementById('allow-once');
            const allowWhileUsingBtn = document.getElementById('allow-while-using');
            const dontAllowBtn = document.getElementById('dont-allow');

            const handlePermissionGranted = () => {
                permissionScreen.style.display = 'none';
                localStorage.setItem('locationPermission', 'granted');
            };

            const handlePermissionDenied = () => {
                permissionScreen.style.display = 'none';
                localStorage.setItem('locationPermission', 'denied');
            };

            if (allowOnceBtn) allowOnceBtn.addEventListener('click', handlePermissionGranted);
            if (allowWhileUsingBtn) allowWhileUsingBtn.addEventListener('click', handlePermissionGranted);
            if (dontAllowBtn) dontAllowBtn.addEventListener('click', handlePermissionDenied);
        }
    </script>
</body>

</html>