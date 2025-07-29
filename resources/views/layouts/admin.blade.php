<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Bus Tracker') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    @vite(['resources/css/admin.css'])

    @stack('styles')
</head>
<body class="admin-layout">
    <!-- Sidebar backdrop for mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar" id="adminSidebar">
                <div class="position-sticky pt-3">
                    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Admin Panel
                    </a>
                    
                    <ul class="nav flex-column mt-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                               href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-house-door"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        @can('manage-buses')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.buses.*') ? 'active' : '' }}" 
                               href="{{ route('admin.buses.index') }}">
                                <i class="bi bi-bus-front"></i>
                                Bus Management
                            </a>
                        </li>
                        @endcan
                        
                        @can('manage-schedules')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}" 
                               href="{{ route('admin.schedules.index') }}">
                                <i class="bi bi-calendar-event"></i>
                                Schedule Management
                            </a>
                        </li>
                        @endcan
                        
                        @can('view-monitoring')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}" 
                               href="{{ route('admin.monitoring.index') }}">
                                <i class="bi bi-graph-up"></i>
                                Monitoring
                            </a>
                        </li>
                        @endcan
                        
                        @can('manage-settings')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" 
                               href="{{ route('admin.settings.index') }}">
                                <i class="bi bi-gear"></i>
                                Settings
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="admin-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary d-lg-none me-3" type="button" 
                                id="sidebarToggle"
                                aria-controls="adminSidebar" aria-expanded="false">
                            <i class="bi bi-list"></i>
                        </button>
                        <h1 class="h2 mb-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown user-dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" 
                                    type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-2"></i>
                                {{ auth('admin')->user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <span class="dropdown-item-text">
                                        <small class="text-muted">{{ auth('admin')->user()->role_display }}</small>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="admin-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Layout JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            // Toggle sidebar on mobile/tablet
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    adminSidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show');
                });
            }

            // Close sidebar when clicking backdrop
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    adminSidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                });
            }

            // Close sidebar when clicking nav links on mobile
            const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        adminSidebar.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    adminSidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                }
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>