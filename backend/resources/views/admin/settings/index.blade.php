@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Settings</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Manage application configuration and preferences</p>
</div>

<!-- Tab Navigation -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px overflow-x-auto" role="tablist">
            <button onclick="switchTab('general')" id="tab-btn-general" class="tab-btn active px-4 sm:px-6 py-4 text-sm font-medium border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 flex items-center gap-2 transition-all" data-tab="general">
                <i class="bi bi-gear"></i>
                <span class="hidden sm:inline">General</span>
            </button>
            <button onclick="switchTab('email')" id="tab-btn-email" class="tab-btn px-4 sm:px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 flex items-center gap-2 transition-all" data-tab="email">
                <i class="bi bi-envelope"></i>
                <span class="hidden sm:inline">Email & Notifications</span>
            </button>
            <button onclick="switchTab('database')" id="tab-btn-database" class="tab-btn px-4 sm:px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 flex items-center gap-2 transition-all" data-tab="database">
                <i class="bi bi-database"></i>
                <span class="hidden sm:inline">Database Management</span>
            </button>
            <button onclick="switchTab('mobile-apps')" id="tab-btn-mobile-apps" class="tab-btn px-4 sm:px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 flex items-center gap-2 transition-all" data-tab="mobile-apps">
                <i class="bi bi-phone"></i>
                <span class="hidden sm:inline">Mobile Apps</span>
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-4 md:p-8">
        @include('admin.settings.tabs.general')
        @include('admin.settings.tabs.email')
        @include('admin.settings.tabs.database')
        @include('admin.settings.tabs.mobile-apps')
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab content
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400');
        el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    });

    // Show selected tab
    const selectedTab = document.getElementById(`tab-${tabName}`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
    }

    // Add active class to selected tab button
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        activeBtn.classList.add('border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400');
    }
}
</script>
@endsection
