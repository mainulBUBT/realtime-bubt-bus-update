<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4CAF50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BUBT Bus">

    <title>{{ config('app.name', 'BUBT Bus Tracker') }}</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Custom Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="mobile-app">
        <!-- Status Bar (iOS style) -->
        <div class="status-bar">
            <span>9:41</span>
            <div class="d-flex align-items-center">
                <i class="bi bi-wifi me-1" style="font-size: 14px;"></i>
                <i class="bi bi-reception-4 me-1" style="font-size: 14px;"></i>
                <i class="bi bi-battery-full" style="font-size: 14px;"></i>
            </div>
        </div>

        <!-- Main Content -->
        <main style="padding-bottom: 80px;">
            {{ $slot }}
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="row g-0">
                <!-- Home -->
                <div class="col-3">
                    <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                        <i class="bi bi-house nav-icon"></i>
                        <span>Home</span>
                    </a>
                </div>
                
                <!-- Map -->
                <div class="col-3">
                    <a href="{{ route('live-map') }}" class="nav-item {{ request()->routeIs('live-map') ? 'active' : '' }}">
                        <i class="bi bi-map nav-icon"></i>
                        <span>Map</span>
                    </a>
                </div>
                
                <!-- Find Bus -->
                <div class="col-3">
                    <a href="{{ route('tracker') }}" class="nav-item {{ request()->routeIs('tracker') ? 'active' : '' }}">
                        <i class="bi bi-search nav-icon"></i>
                        <span>Find Bus</span>
                    </a>
                </div>
                
                <!-- Account -->
                <div class="col-3">
                    <a href="#" class="nav-item">
                        <i class="bi bi-person nav-icon"></i>
                        <span>Account</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @livewireScripts
</body>
</html>