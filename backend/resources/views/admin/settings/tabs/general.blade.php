<div id="tab-general" class="tab-content">
    <form action="{{ route('admin.settings.update.general') }}" method="POST" class="space-y-6" data-form-submit>
        @csrf

        <!-- Application Name -->
        <div class="group">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                <i class="bi bi-type text-emerald-500 mr-2"></i>
                Application Name
            </label>
            <div class="relative">
                <i class="bi bi-type absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" name="app_name" value="{{ $generalSettings['app_name'] ?? 'Bus Tracker' }}"
                       class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500"
                       placeholder="Bus Tracker">
            </div>
        </div>

        <!-- Timezone -->
        <div class="group">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                <i class="bi bi-globe text-blue-500 mr-2"></i>
                Application Timezone
            </label>
            <div class="relative">
                <i class="bi bi-globe absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg z-10"></i>
                <select name="timezone" class="w-full pl-12 pr-10 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500">
                    @foreach(timezone_identifiers_list() as $timezone)
                        <option value="{{ $timezone }}" {{ ($generalSettings['timezone'] ?? 'America/New_York') === $timezone ? 'selected' : '' }}>
                            {{ $timezone }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            </div>
        </div>

        <!-- Items Per Page -->
        <div class="group">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                <i class="bi bi-list-ol text-purple-500 mr-2"></i>
                Items Per Page
            </label>
            <div class="relative">
                <i class="bi bi-list-ol absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="number" name="items_per_page" value="{{ $generalSettings['items_per_page'] ?? 15 }}"
                       min="5" max="100"
                       class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500"
                       placeholder="15">
            </div>
        </div>

        <!-- Maintenance Mode -->
        <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200 dark:border-orange-800 group">
            <input type="checkbox" name="maintenance_mode" value="1"
                   {{ ($generalSettings['maintenance_mode'] ?? false) ? 'checked' : '' }}
                   class="w-5 h-5 text-emerald-600 rounded focus:ring-4 focus:ring-emerald-500/20 cursor-pointer">
            <div class="flex-1">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                    <i class="bi bi-cone-striped text-orange-600 dark:text-orange-400"></i>
                    Maintenance Mode
                </label>
                <p class="text-gray-600 dark:text-gray-400 text-xs mt-0.5">Take the application offline for maintenance (only admins can access)</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="group bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 transition-all hover:shadow-emerald-500/50 hover:-translate-y-0.5 flex items-center gap-2">
                <i class="bi bi-check-circle text-lg"></i>
                <span>Save General Settings</span>
            </button>
        </div>
    </form>
</div>
