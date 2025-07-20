<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e293b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>Admin - {{ config('app.name', 'BUBT Bus Tracker') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 overflow-x-hidden">
    <div class="min-h-screen">
        <!-- Admin Header -->
        <header class="bg-gradient-to-r from-slate-800 to-slate-900 shadow-lg sticky top-0 z-40">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <!-- Logo & Title -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white">Admin Panel</h1>
                            <p class="text-xs text-slate-300 -mt-1">Bus Management</p>
                        </div>
                    </div>
                    
                    <!-- Status & Back -->
                    <div class="flex items-center space-x-3">
                        <!-- System Status -->
                        <div class="flex items-center space-x-1 bg-green-900/30 px-2 py-1 rounded-full">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-xs font-medium text-green-300">Online</span>
                        </div>
                        
                        <!-- Back Button -->
                        <a href="{{ route('home') }}" class="p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors">
                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 pb-20">
            {{ $slot }}
        </main>

        <!-- Bottom Admin Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-slate-800 border-t border-slate-700 z-50">
            <div class="grid grid-cols-5 h-16">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center justify-center space-y-1 {{ request()->routeIs('admin.dashboard') ? 'text-orange-400 bg-slate-700' : 'text-slate-400 hover:text-slate-300' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>
                
                <!-- Schedules -->
                <a href="{{ route('admin.schedules') }}" class="flex flex-col items-center justify-center space-y-1 {{ request()->routeIs('admin.schedules') ? 'text-orange-400 bg-slate-700' : 'text-slate-400 hover:text-slate-300' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-medium">Schedule</span>
                </a>
                
                <!-- Trips -->
                <a href="{{ route('admin.trips') }}" class="flex flex-col items-center justify-center space-y-1 {{ request()->routeIs('admin.trips') ? 'text-orange-400 bg-slate-700' : 'text-slate-400 hover:text-slate-300' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z"></path>
                    </svg>
                    <span class="text-xs font-medium">Trips</span>
                </a>
                
                <!-- Live Map -->
                <a href="{{ route('admin.live-map') }}" class="flex flex-col items-center justify-center space-y-1 {{ request()->routeIs('admin.live-map') ? 'text-orange-400 bg-slate-700' : 'text-slate-400 hover:text-slate-300' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="text-xs font-medium">Map</span>
                </a>
                
                <!-- Reports -->
                <a href="{{ route('admin.reports') }}" class="flex flex-col items-center justify-center space-y-1 {{ request()->routeIs('admin.reports') ? 'text-orange-400 bg-slate-700' : 'text-slate-400 hover:text-slate-300' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-xs font-medium">Reports</span>
                </a>
            </div>
        </nav>
    </div>

    @livewireScripts
</body>
</html>