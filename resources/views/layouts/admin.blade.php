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
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .admin-sidebar {
            background: linear-gradient(180deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .admin-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 1px solid #dee2e6;
        }

        .admin-content {
            padding: 30px;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stats-card.primary .stats-icon { background: var(--admin-accent); }
        .stats-card.success .stats-icon { background: var(--admin-success); }
        .stats-card.warning .stats-icon { background: var(--admin-warning); }
        .stats-card.danger .stats-icon { background: var(--admin-danger); }

        .admin-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-decoration: none;
            padding: 20px;
            display: block;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-brand:hover {
            color: white;
            text-decoration: none;
        }

        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-admin-primary {
            background: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
        }

        .btn-admin-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
            color: white;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1050;
                transition: left 0.3s ease;
            }

            .admin-sidebar.show {
                left: 0;
            }

            .admin-content {
                padding: 20px 15px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse" id="adminSidebar">
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
                        <button class="btn btn-outline-secondary d-md-none me-3" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#adminSidebar" 
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
    
    @stack('scripts')
</body>
</html>