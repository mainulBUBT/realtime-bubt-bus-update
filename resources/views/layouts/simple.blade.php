<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUBT Bus Tracker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .mobile-container {
            max-width: 428px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .status-bar {
            background: white;
            padding: 10px 20px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            font-weight: 600;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 428px;
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 10px 0;
        }
        .nav-item {
            text-decoration: none;
            color: #6c757d;
            font-size: 12px;
            text-align: center;
        }
        .nav-item.active {
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Status Bar -->
        <div class="status-bar d-flex justify-content-between align-items-center">
            <span>9:41</span>
            <div>
                <i class="bi bi-wifi"></i>
                <i class="bi bi-reception-4 ms-1"></i>
                <i class="bi bi-battery-full ms-1"></i>
            </div>
        </div>

        <!-- Main Content -->
        <main style="padding-bottom: 80px;">
            @yield('content')
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="row g-0">
                <div class="col-3">
                    <a href="{{ route('home') }}" class="nav-item d-flex flex-column align-items-center {{ request()->routeIs('home') ? 'active' : '' }}">
                        <i class="bi bi-house" style="font-size: 20px;"></i>
                        <span>Home</span>
                    </a>
                </div>
                <div class="col-3">
                    <a href="{{ route('live-map') }}" class="nav-item d-flex flex-column align-items-center {{ request()->routeIs('live-map') ? 'active' : '' }}">
                        <i class="bi bi-map" style="font-size: 20px;"></i>
                        <span>Map</span>
                    </a>
                </div>
                <div class="col-3">
                    <a href="{{ route('tracker') }}" class="nav-item d-flex flex-column align-items-center {{ request()->routeIs('tracker') ? 'active' : '' }}">
                        <i class="bi bi-search" style="font-size: 20px;"></i>
                        <span>Find Bus</span>
                    </a>
                </div>
                <div class="col-3">
                    <a href="#" class="nav-item d-flex flex-column align-items-center">
                        <i class="bi bi-person" style="font-size: 20px;"></i>
                        <span>Account</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>