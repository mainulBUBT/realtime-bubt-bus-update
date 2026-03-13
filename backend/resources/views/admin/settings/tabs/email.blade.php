<div id="tab-email" class="tab-content hidden">
    <form action="{{ route('admin.settings.update.email') }}" method="POST" class="space-y-6" data-form-submit>
        @csrf

        <!-- SMTP Configuration -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-envelope text-blue-600 dark:text-blue-400"></i>
                SMTP Configuration
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SMTP Host -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-server text-blue-500 mr-2"></i>
                        SMTP Host
                    </label>
                    <div class="relative">
                        <i class="bi bi-server absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" name="mail_host" value="{{ $emailSettings['mail_host'] ?? '' }}"
                               placeholder="smtp.mailtrap.io"
                               class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                    </div>
                </div>

                <!-- SMTP Port -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-hash text-purple-500 mr-2"></i>
                        SMTP Port
                    </label>
                    <div class="relative">
                        <i class="bi bi-hash absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="number" name="mail_port" value="{{ $emailSettings['mail_port'] ?? 587 }}"
                               placeholder="587"
                               class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                    </div>
                </div>

                <!-- SMTP Username -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-person text-green-500 mr-2"></i>
                        SMTP Username
                    </label>
                    <div class="relative">
                        <i class="bi bi-person absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" name="mail_username" value="{{ $emailSettings['mail_username'] ?? '' }}"
                               placeholder="your-username"
                               class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                    </div>
                </div>

                <!-- SMTP Password -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-lock text-red-500 mr-2"></i>
                        SMTP Password
                    </label>
                    <div class="relative">
                        <i class="bi bi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="password" name="mail_password" value="{{ $emailSettings['mail_password'] ?? '' }}"
                               placeholder="••••••••"
                               class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                    </div>
                </div>

                <!-- Encryption -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-shield-lock text-yellow-500 mr-2"></i>
                        Encryption
                    </label>
                    <div class="relative">
                        <i class="bi bi-shield-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg z-10"></i>
                        <select name="mail_encryption" class="w-full pl-12 pr-10 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500">
                            <option value="tls" {{ ($emailSettings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($emailSettings['mail_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ ($emailSettings['mail_encryption'] ?? null) === '' ? 'selected' : '' }}>None</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- From Email -->
                <div class="group">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <i class="bi bi-envelope-at text-cyan-500 mr-2"></i>
                        From Email Address
                    </label>
                    <div class="relative">
                        <i class="bi bi-envelope-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="email" name="mail_from_address" value="{{ $emailSettings['mail_from_address'] ?? 'noreply@bustracker.com' }}"
                               placeholder="noreply@bustracker.com"
                               class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                    </div>
                </div>
            </div>

            <!-- From Name -->
            <div class="mt-6 group">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                    <i class="bi bi-signature text-indigo-500 mr-2"></i>
                    From Name
                </label>
                <div class="relative">
                    <i class="bi bi-signature absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input type="text" name="mail_from_name" value="{{ $emailSettings['mail_from_name'] ?? 'Bus Tracker' }}"
                           placeholder="Bus Tracker"
                           class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all dark:bg-gray-700 dark:text-white hover:border-gray-300 dark:hover:border-gray-500">
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-bell text-purple-600 dark:text-purple-400"></i>
                Notifications
            </h3>

            <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <input type="checkbox" name="notifications_enabled" value="1"
                       {{ ($emailSettings['notifications_enabled'] ?? true) ? 'checked' : '' }}
                       class="w-5 h-5 text-emerald-600 rounded focus:ring-4 focus:ring-emerald-500/20 cursor-pointer">
                <div class="flex-1">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                        <i class="bi bi-toggle-on text-purple-600 dark:text-purple-400"></i>
                        Enable Email Notifications
                    </label>
                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-0.5">Send email notifications for important system events</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="group bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-500/30 transition-all hover:shadow-blue-500/50 hover:-translate-y-0.5 flex items-center gap-2">
                <i class="bi bi-check-circle text-lg"></i>
                <span>Save Email Settings</span>
            </button>
        </div>
    </form>
</div>
