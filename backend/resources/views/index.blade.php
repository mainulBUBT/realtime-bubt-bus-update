<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bus Tracker</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        [v-cloak] {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" v-cloak>
        <!-- Navbar -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center gap-3">
                            <div class="bg-gradient-to-br from-purple-600 to-indigo-600 p-2 rounded-lg">
                                <i class="bi bi-bus-front-fill text-white text-xl"></i>
                            </div>
                            <span class="text-xl font-bold text-gray-900">Bus Tracker</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                        <div class="flex items-center gap-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">Student</p>
                            </div>
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                <i class="bi bi-box-arrow-right text-xl"></i>
                            </button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            Login
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Vue App Mount Point -->
            <div id="vue-app"></div>
        </main>
    </div>

    <!-- Laravel Data -->
    <script>
        window.laravel = {
            csrfToken: '{{ csrf_token() }}',
            user: @auth({{ auth()->user()->toJson() }}) @else null @endauth,
            googleClientId: '{{ $google_client_id ?? '' }}',
            buses: @js($buses),
            period: @js($period)
        };
    </script>

    <!-- Vue.js App -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
