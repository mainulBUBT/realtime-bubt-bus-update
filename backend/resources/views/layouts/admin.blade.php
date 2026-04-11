<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ $appName }}</title>

    <!-- Tailwind CSS (built via Vite; avoids CDN slowness/failures) -->
    @vite(['resources/css/admin.css'])

    <!-- Bootstrap Icons (local) -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.min.css') }}">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">

    <!-- Legacy admin CSS link removed (now bundled in Vite) -->

    <style>
        /* Additional inline styles for quick customization */
        .sidebar-gradient {
            background-image: linear-gradient(180deg, rgba(16, 185, 129, 0.05) 0%, rgba(20, 184, 166, 0.05) 100%);
            background-repeat: no-repeat;
            background-size: 100% 100%;
        }

        html.dark .sidebar-gradient {
            background-image: linear-gradient(180deg, rgba(16, 185, 129, 0.08) 0%, rgba(20, 184, 166, 0.06) 100%);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">

    <!-- Top Navigation Bar -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 fixed w-full top-0 left-0 right-0 transition-colors duration-300" style="z-index: 9998;">
        <div class="px-2 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between gap-2 h-14 sm:h-16">
                <!-- Logo & Brand -->
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <!-- Mobile Menu Button -->
                    <button id="sidebar-toggle" class="shrink-0 lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="bi bi-list text-xl"></i>
                    </button>

                    <!-- Logo -->
                    <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2 sm:gap-3 group">
                        <div class="shrink-0 bg-gradient-to-br from-emerald-500 to-teal-600 p-1.5 sm:p-2 rounded-xl shadow-lg shadow-emerald-500/30 group-hover:shadow-emerald-500/50 transition-all">
                            <i class="bi bi-bus-front-fill text-white text-base sm:text-lg lg:text-xl"></i>
                        </div>
                        <div class="hidden min-w-0 sm:block">
                            <h1 class="truncate text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ $appName }}</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 hidden lg:block">Admin Panel</p>
                        </div>
                    </a>
                </div>

                <!-- Right Side Actions -->
                <div class="flex shrink-0 items-center gap-0.5 sm:gap-2">
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="p-1.5 sm:p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Toggle dark mode">
                        <i class="bi bi-moon dark:hidden text-lg"></i>
                        <i class="bi bi-sun hidden dark:inline text-lg"></i>
                    </button>

                    <!-- Notifications -->
                    <button class="relative p-1.5 sm:p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Notifications">
                        <i class="bi bi-bell text-lg"></i>
                        <span class="notification-badge">3</span>
                    </button>

                    <!-- View App Button -->
                    <a href="{{ route('app') }}" class="flex items-center justify-center sm:justify-start gap-2 px-1.5 sm:px-3 py-1.5 sm:py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm font-medium" title="View App">
                        <i class="bi bi-eye"></i>
                        <span class="hidden md:inline">View App</span>
                    </a>

                    <!-- User Avatar -->
                    <div class="flex items-center gap-2 pl-1.5 sm:pl-2 border-l border-gray-200 dark:border-gray-700">
                        <div class="hidden md:block text-right">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                        </div>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center text-white font-bold shadow">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex pt-14 sm:pt-16">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-14 sm:top-16 bottom-0 left-0 w-64 shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full lg:static lg:translate-x-0 transition-transform duration-300 ease-in-out sidebar-gradient" style="z-index: 9996;">
            <div class="h-full overflow-y-auto py-6">
                <!-- Navigation Links -->
                <nav class="space-y-1 px-3">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-speedometer2 text-lg"></i>
                        <span class="font-medium">Dashboard</span>
                        @if(request()->routeIs('admin.dashboard'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Schedule Periods -->
                    <a href="{{ route('admin.schedule-periods.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.schedule-periods.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-calendar-range text-lg"></i>
                        <span class="font-medium">Schedule Periods</span>
                        @if(request()->routeIs('admin.schedule-periods.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Routes -->
                    <a href="{{ route('admin.routes.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.routes.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-map text-lg"></i>
                        <span class="font-medium">Routes</span>
                        @if(request()->routeIs('admin.routes.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Buses -->
                    <a href="{{ route('admin.buses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.buses.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-bus-front text-lg"></i>
                        <span class="font-medium">Buses</span>
                        @if(request()->routeIs('admin.buses.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Schedules -->
                    <a href="{{ route('admin.schedules.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.schedules.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-calendar3 text-lg"></i>
                        <span class="font-medium">Schedules</span>
                        @if(request()->routeIs('admin.schedules.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Trips -->
                    <a href="{{ route('admin.trips.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.trips.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-signpost-2 text-lg"></i>
                        <span class="font-medium">Trips</span>
                        @if(request()->routeIs('admin.trips.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Users -->
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-people text-lg"></i>
                        <span class="font-medium">Users</span>
                        @if(request()->routeIs('admin.users.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Notifications -->
                    <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.notifications.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-bell text-lg"></i>
                        <span class="font-medium">Notifications</span>
                        @if(request()->routeIs('admin.notifications.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>

                    <!-- Settings -->
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="bi bi-gear text-lg"></i>
                        <span class="font-medium">Settings</span>
                        @if(request()->routeIs('admin.settings.*'))
                            <i class="bi bi-chevron-right ml-auto"></i>
                        @endif
                    </a>
                </nav>

                <!-- Divider -->
                <div class="my-6 border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Bottom Actions -->
                <div class="px-3 space-y-1">
                    <a href="#" onclick="event.preventDefault(); showLogoutModal();" class="flex items-center gap-3 px-4 py-3 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all duration-200">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                        <span class="font-medium">Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 min-h-screen">
            <div class="min-w-0 p-4 sm:p-6 lg:p-8 page-transition">
                <!-- Breadcrumb -->
                @if(request()->route()->getName() !== 'admin.dashboard')
                <nav class="flex mb-6" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-2 text-sm">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                <i class="bi bi-house-door mr-1"></i>
                                Dashboard
                            </a>
                        </li>
                        @if(request()->route()->getName() !== 'admin.dashboard')
                        <li class="flex items-center">
                            <i class="bi bi-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-900 dark:text-gray-100 font-medium">@yield('breadcrumb-title')</span>
                        </li>
                        @endif
                    </ol>
                </nav>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm lg:hidden hidden transition-opacity" style="z-index: 9995;"></div>

    <!-- Common Confirmation Modal -->
    <div id="confirm-modal" class="fixed inset-0 z-[10000] hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeConfirmModal()"></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all scale-95 opacity-0" id="confirm-modal-content">
                <!-- Icon -->
                <div class="flex justify-center mb-4">
                    <div id="confirm-modal-icon-bg" class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg">
                        <i id="confirm-modal-icon" class="text-white text-2xl"></i>
                    </div>
                </div>

                <!-- Title -->
                <h3 id="confirm-modal-title" class="text-xl font-bold text-center text-gray-900 dark:text-white mb-2">
                    Confirm Action
                </h3>

                <!-- Message -->
                <p id="confirm-modal-message" class="text-center text-gray-600 dark:text-gray-400 mb-6">
                    Are you sure you want to proceed?
                </p>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button onclick="closeConfirmModal()" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="bi bi-x-lg"></i>
                        Cancel
                    </button>
                    <button id="confirm-modal-confirm-btn" onclick="confirmModalAction()" class="flex-1 px-4 py-3 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                        <i id="confirm-modal-confirm-icon" class="bi bi-check-lg"></i>
                        <span id="confirm-modal-confirm-text">Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for toastr.js) -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>

    <!-- Admin JavaScript -->
    <script src="{{ asset('assets/js/admin.js') }}"></script>

	    <script>
	        if (typeof toastr !== 'undefined') {
	            // Configure Toastr
	            toastr.options = {
	                closeButton: true,
	                progressBar: true,
	                positionClass: 'toast-top-right',
	                timeOut: 5000,
	                extendedTimeOut: 1000,
	                preventDuplicates: true,
	                newestOnTop: true,
	                tapToDismiss: true
	            };

	            // Display toastr notifications from session
	            @if(session('toastr'))
	                @foreach(session('toastr') as $toast)
	                    toastr.{{ $toast['type'] }}('{{ $toast['message'] }}');
	                @endforeach
	                @php(session()->forget('toastr'))
	            @endif

	            // Legacy success/error messages - convert to toastr
	            @if(session('success'))
	                toastr.success('{{ session('success') }}');
	                @php(session()->forget('success'))
	            @endif

	            @if(session('error'))
	                toastr.error('{{ session('error') }}');
	                @php(session()->forget('error'))
	            @endif
	        } else {
	            console.warn('Toastr is not available (CDN failed to load).')
	        }

	        // Common Confirmation Modal
	        let confirmModalCallback = null;

        function showConfirmModal(options) {
            const modal = document.getElementById('confirm-modal');
            const modalContent = document.getElementById('confirm-modal-content');
            const iconBg = document.getElementById('confirm-modal-icon-bg');
            const icon = document.getElementById('confirm-modal-icon');
            const title = document.getElementById('confirm-modal-title');
            const message = document.getElementById('confirm-modal-message');
            const confirmBtn = document.getElementById('confirm-modal-confirm-btn');
            const confirmIcon = document.getElementById('confirm-modal-confirm-icon');
            const confirmText = document.getElementById('confirm-modal-confirm-text');

            // Set defaults
            const defaults = {
                title: 'Confirm Action',
                message: 'Are you sure you want to proceed?',
                icon: 'bi-exclamation-triangle',
                iconBgClass: 'bg-gradient-to-br from-yellow-500 to-orange-500',
                confirmBtnClass: 'bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 shadow-yellow-500/30 hover:shadow-yellow-500/50',
                confirmIcon: 'bi-check-lg',
                confirmText: 'Confirm',
                onConfirm: null
            };

            const settings = { ...defaults, ...options };

            console.log('=== showConfirmModal ===');
            console.log('Modal settings:', settings);
            console.log('onConfirm callback type:', typeof settings.onConfirm);

            // Update modal content
            title.textContent = settings.title;
            message.textContent = settings.message;
            icon.className = `text-white text-2xl ${settings.icon}`;
            iconBg.className = `w-16 h-16 rounded-full flex items-center justify-center shadow-lg ${settings.iconBgClass}`;
            confirmBtn.className = `flex-1 px-4 py-3 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 ${settings.confirmBtnClass}`;
            confirmIcon.className = settings.confirmIcon;
            confirmText.textContent = settings.confirmText;

            // Store callback
            confirmModalCallback = settings.onConfirm;
            console.log('Callback stored in confirmModalCallback:', typeof confirmModalCallback);
            console.log('confirmModalCallback value:', confirmModalCallback);

            // Show modal
            modal.classList.remove('hidden');

            // Trigger animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirm-modal');
            const modalContent = document.getElementById('confirm-modal-content');

            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                confirmModalCallback = null;
            }, 200);
        }

        function confirmModalAction() {
            console.log('confirmModalAction called');
            console.log('confirmModalCallback:', confirmModalCallback);
            console.log('Is function?', typeof confirmModalCallback === 'function');

            if (confirmModalCallback && typeof confirmModalCallback === 'function') {
                console.log('Executing callback...');
                try {
                    confirmModalCallback();
                    console.log('Callback executed successfully');
                } catch (error) {
                    console.error('Error executing callback:', error);
                }
            }
            closeConfirmModal();
        }

        // Logout Function using Common Modal
        function showLogoutModal() {
            showConfirmModal({
                title: 'Logout from Admin Panel?',
                message: 'Are you sure you want to logout? You\'ll need to sign in again to access the admin panel.',
                icon: 'bi-box-arrow-right',
                iconBgClass: 'bg-gradient-to-br from-red-500 to-orange-500',
                confirmBtnClass: 'bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 shadow-red-500/30 hover:shadow-red-500/50',
                confirmIcon: 'bi-box-arrow-right',
                confirmText: 'Logout',
                onConfirm: function() {
                    document.getElementById('logout-form').submit();
                }
            });
        }

        // Delete Function using Common Modal (can be called from delete buttons)
        function confirmDelete(options) {
            console.log('=== confirmDelete called ===');
            console.log('Options received:', options);

            const defaults = {
                title: 'Delete This Item?',
                message: 'Are you sure you want to delete this item? This action cannot be undone.',
                itemName: '',
                onConfirm: null
            };

            const settings = { ...defaults, ...options };
            console.log('Final settings:', settings);
            console.log('onConfirm callback type:', typeof settings.onConfirm);
            console.log('onConfirm callback:', settings.onConfirm);

            showConfirmModal({
                title: settings.title,
                message: settings.itemName ?
                    `Are you sure you want to delete "${settings.itemName}"? This action cannot be undone.` :
                    settings.message,
                icon: 'bi-trash',
                iconBgClass: 'bg-gradient-to-br from-red-500 to-rose-500',
                confirmBtnClass: 'bg-gradient-to-r from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 shadow-red-500/30 hover:shadow-red-500/50',
                confirmIcon: 'bi-trash',
                confirmText: 'Delete',
                onConfirm: settings.onConfirm
            });

            console.log('=== showConfirmModal called ===');
        }

        // Helper function for deleting items via form submission (most reliable)
        window.deleteItem = function(url, itemName) {
            console.log('=== deleteItem called ===', { url, itemName });

            confirmDelete({
                itemName: itemName,
                onConfirm: function() {
                    console.log('=== Creating and submitting form ===');

                    // Create a temporary form element
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.style.display = 'none';

                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    // Add DELETE method override
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);

                    console.log('Form details:', {
                        action: form.action,
                        method: form.method,
                        hasCsrf: !!csrfInput.value,
                        hasMethod: methodInput.value
                    });

                    // Add form to DOM and submit
                    document.body.appendChild(form);
                    console.log('Form added to DOM, submitting now...');

                    form.submit();
                }
            });
        };

        console.log('✓ deleteItem function loaded (form submission version)');

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('confirm-modal');
                if (!modal.classList.contains('hidden')) {
                    closeConfirmModal();
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
