<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ $appName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-gradient-to-br from-white/10 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-gradient-to-tr from-white/10 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute top-20 left-20 w-64 h-64 bg-white/5 rounded-full blur-2xl"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-white/5 rounded-full blur-2xl"></div>
    </div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-5xl">
        <div class="glass-card rounded-3xl shadow-2xl overflow-hidden animate-fade-in">
            <div class="flex flex-col md:flex-row">
                <!-- Left Side - Branding -->
                <div class="md:w-1/2 bg-gradient-to-br from-emerald-600 to-teal-700 p-10 md:p-14 text-white flex flex-col justify-center relative overflow-hidden">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

                    <div class="relative z-10">
                        <!-- Logo -->
                        <div class="mb-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl mb-6 animate-float">
                                <i class="bi bi-shield-lock-fill text-4xl"></i>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-bold mb-3">Admin Panel</h1>
                            <p class="text-emerald-100 text-lg">{{ $appName }} Management System</p>
                        </div>

                        <!-- Features -->
                        <div class="space-y-4 mt-10">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-bus-front text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Fleet Management</p>
                                    <p class="text-sm text-emerald-100">Manage your entire bus fleet</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-signpost-2 text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Route Planning</p>
                                    <p class="text-sm text-emerald-100">Create and manage routes</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-calendar-check text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Schedule Control</p>
                                    <p class="text-sm text-emerald-100">Automated scheduling system</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-speedometer2 text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Real-time Dashboard</p>
                                    <p class="text-sm text-emerald-100">Live monitoring & analytics</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Login Form -->
                <div class="md:w-1/2 p-10 md:p-14">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Admin Login</h2>
                        <p class="text-gray-600">Enter your credentials to access the admin panel</p>
                    </div>

                    @if($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg animate-fade-in">
                            <div class="flex items-center">
                                <i class="bi bi-exclamation-circle-fill text-red-500 text-xl mr-3"></i>
                                <div>
                                    <p class="font-semibold text-red-800">Login Failed</p>
                                    <p class="text-sm text-red-600">{{ $errors->first() }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Email Field -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2" for="email">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-envelope text-gray-400"></i>
                                </div>
                                <input
                                    class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                                    id="email"
                                    type="email"
                                    name="email"
                                    placeholder="admin@bustracker.com"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2" for="password">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock text-gray-400"></i>
                                </div>
                                <input
                                    class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="••••••••"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 focus:outline-none focus:ring-4 focus:ring-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02]"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <i class="bi bi-box-arrow-in-right"></i>
                                Sign In to Admin Panel
                            </span>
                        </button>
                    </form>

                    <!-- Footer Info -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                            <i class="bi bi-shield-check text-emerald-500"></i>
                            <span>Secured with end-to-end encryption</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Text -->
        <p class="text-center text-white/80 text-sm mt-8">
            © {{ date('Y') }} {{ $appName }} System. All rights reserved.
        </p>
    </div>
</body>
</html>
