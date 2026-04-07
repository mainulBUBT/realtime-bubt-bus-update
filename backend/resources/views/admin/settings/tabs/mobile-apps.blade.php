<div id="tab-mobile-apps" class="tab-content hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Student App Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <i class="bi bi-mortarboard-fill text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Student App</h3>
                        <p class="text-indigo-100 text-sm">Customize the student experience</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.settings.update.mobile', 'student') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <!-- App Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        App Name
                    </label>
                    <input type="text" name="student_app_name"
                           value="{{ $studentSettings['student_app_name'] ?? 'BUBT Bus Tracker' }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-shadow"
                           placeholder="BUBT Bus Tracker">
                </div>

                <!-- Tagline -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Tagline
                    </label>
                    <input type="text" name="student_app_tagline"
                           value="{{ $studentSettings['student_app_tagline'] ?? 'Your Campus Shuttle Companion' }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-shadow"
                           placeholder="Your Campus Shuttle Companion">
                </div>

                <!-- Theme Color -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Theme Color
                    </label>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input type="color" name="student_splash_primary_color"
                                   value="{{ $studentSettings['student_splash_primary_color'] ?? '#4F46E5' }}"
                                   class="w-16 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-600 cursor-pointer"
                                   id="student-primary-color">
                        </div>
                        <div class="flex-1">
                            <input type="text" readonly
                                   value="{{ $studentSettings['student_splash_primary_color'] ?? '#4F46E5' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white text-sm font-mono"
                                   id="student-primary-text">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <i class="bi bi-info-circle mr-1"></i>
                        This color will be applied throughout the entire app (headers, buttons, timeline, etc.)
                    </p>
                </div>

                <!-- Live Preview -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Preview</h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Auto-generates gradient</span>
                    </div>
                    <div class="rounded-xl overflow-hidden shadow-md">
                        <div class="student-preview-box p-8 text-center"
                             style="background: linear-gradient(135deg, {{ $studentSettings['student_splash_primary_color'] ?? '#4F46E5' }} 0%, var(--student-dark, {{ $studentSettings['student_splash_primary_color'] ?? '#4F46E5' }}) 100%);">
                            <div class="mb-4">
                                <i class="bi bi-bus-front-fill text-5xl text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white student-preview-name">{{ $studentSettings['student_app_name'] ?? 'BUBT Bus Tracker' }}</h3>
                            <p class="text-white/80 text-sm mt-1 student-preview-tagline">{{ $studentSettings['student_app_tagline'] ?? 'Your Campus Shuttle Companion' }}</p>
                            <div class="mt-6 flex items-center justify-center gap-2">
                                <div class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></div>
                                <span class="text-white text-sm">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About / Help & Support -->
                <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/20 p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i class="bi bi-info-circle"></i>
                            About / Help & Support
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Student app</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Support Email</label>
                            <input type="email" name="student_support_email"
                                   value="{{ $studentSettings['student_support_email'] ?? '' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                   placeholder="support@yourdomain.com">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Support Phone</label>
                            <input type="text" name="student_support_phone"
                                   value="{{ $studentSettings['student_support_phone'] ?? '' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                   placeholder="+8801XXXXXXXXX">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Support URL</label>
                        <input type="url" name="student_support_url"
                               value="{{ $studentSettings['student_support_url'] ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="https://yourdomain.com/help">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">About Text</label>
                        <textarea name="student_about_text" rows="5"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                  placeholder="Write something about the app...">{{ $studentSettings['student_about_text'] ?? '' }}</textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            This text is shown in the Student app About page.
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="bi bi-check-circle"></i>
                        Save Student App Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Driver App Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <i class="bi bi-bus-front-fill text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Driver App</h3>
                        <p class="text-emerald-100 text-sm">Customize the driver experience</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.settings.update.mobile', 'driver') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <!-- App Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        App Name
                    </label>
                    <input type="text" name="driver_app_name"
                           value="{{ $driverSettings['driver_app_name'] ?? 'BUBT Bus Tracker - Driver' }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-shadow"
                           placeholder="BUBT Bus Tracker - Driver">
                </div>

                <!-- Tagline -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Tagline
                    </label>
                    <input type="text" name="driver_app_tagline"
                           value="{{ $driverSettings['driver_app_tagline'] ?? 'Campus Shuttle Driver App' }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-shadow"
                           placeholder="Campus Shuttle Driver App">
                </div>

                <!-- Theme Color -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Theme Color
                    </label>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input type="color" name="driver_splash_primary_color"
                                   value="{{ $driverSettings['driver_splash_primary_color'] ?? '#059669' }}"
                                   class="w-16 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-600 cursor-pointer"
                                   id="driver-primary-color">
                        </div>
                        <div class="flex-1">
                            <input type="text" readonly
                                   value="{{ $driverSettings['driver_splash_primary_color'] ?? '#059669' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white text-sm font-mono"
                                   id="driver-primary-text">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <i class="bi bi-info-circle mr-1"></i>
                        This color will be applied throughout the entire app (headers, buttons, timeline, etc.)
                    </p>
                </div>

                <!-- Live Preview -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Preview</h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Auto-generates gradient</span>
                    </div>
                    <div class="rounded-xl overflow-hidden shadow-md">
                        <div class="driver-preview-box p-8 text-center"
                             style="background: linear-gradient(135deg, {{ $driverSettings['driver_splash_primary_color'] ?? '#059669' }} 0%, var(--driver-dark, {{ $driverSettings['driver_splash_primary_color'] ?? '#059669' }}) 100%);">
                            <div class="mb-4">
                                <i class="bi bi-bus-front-fill text-5xl text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white driver-preview-name">{{ $driverSettings['driver_app_name'] ?? 'BUBT Bus Tracker - Driver' }}</h3>
                            <p class="text-white/80 text-sm mt-1 driver-preview-tagline">{{ $driverSettings['driver_app_tagline'] ?? 'Campus Shuttle Driver App' }}</p>
                            <div class="mt-6 flex items-center justify-center gap-2">
                                <div class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></div>
                                <span class="text-white text-sm">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="bi bi-check-circle"></i>
                        Save Driver App Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <i class="bi bi-lightbulb text-blue-500 text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">How It Works</h4>
                <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
                    The theme color you choose will automatically generate lighter and darker variants for different UI elements like buttons, hover states, and backgrounds. This ensures a consistent, professional appearance throughout the entire app.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Color darkening utility
    function adjustBrightness(hex, percent) {
        // Remove hash if present
        hex = hex.replace(/^#/, '');

        // Parse RGB
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);

        // Adjust brightness
        const adjust = (value) => {
            const adjusted = Math.round(value + (percent * 255));
            return Math.max(0, Math.min(255, adjusted));
        };

        const newR = adjust(r).toString(16).padStart(2, '0');
        const newG = adjust(g).toString(16).padStart(2, '0');
        const newB = adjust(b).toString(16).padStart(2, '0');

        return `#${newR}${newG}${newB}`;
    }

    // Update preview for student app
    function updateStudentPreview() {
        const colorInput = document.getElementById('student-primary-color');
        const nameInput = document.querySelector('input[name="student_app_name"]');
        const taglineInput = document.querySelector('input[name="student_app_tagline"]');
        const previewBox = document.querySelector('.student-preview-box');
        const previewName = document.querySelector('.student-preview-name');
        const previewTagline = document.querySelector('.student-preview-tagline');

        if (!colorInput || !previewBox) return;

        const primaryColor = colorInput.value;
        const darkColor = adjustBrightness(primaryColor, -0.2);

        previewBox.style.background = `linear-gradient(135deg, ${primaryColor} 0%, ${darkColor} 100%)`;
        previewBox.style.setProperty('--student-primary', primaryColor);
        previewBox.style.setProperty('--student-dark', darkColor);

        if (nameInput) previewName.textContent = nameInput.value || 'BUBT Bus Tracker';
        if (taglineInput) previewTagline.textContent = taglineInput.value || 'Your Campus Shuttle Companion';
    }

    // Update preview for driver app
    function updateDriverPreview() {
        const colorInput = document.getElementById('driver-primary-color');
        const nameInput = document.querySelector('input[name="driver_app_name"]');
        const taglineInput = document.querySelector('input[name="driver_app_tagline"]');
        const previewBox = document.querySelector('.driver-preview-box');
        const previewName = document.querySelector('.driver-preview-name');
        const previewTagline = document.querySelector('.driver-preview-tagline');

        if (!colorInput || !previewBox) return;

        const primaryColor = colorInput.value;
        const darkColor = adjustBrightness(primaryColor, -0.2);

        previewBox.style.background = `linear-gradient(135deg, ${primaryColor} 0%, ${darkColor} 100%)`;
        previewBox.style.setProperty('--driver-primary', primaryColor);
        previewBox.style.setProperty('--driver-dark', darkColor);

        if (nameInput) previewName.textContent = nameInput.value || 'BUBT Bus Tracker - Driver';
        if (taglineInput) previewTagline.textContent = taglineInput.value || 'Campus Shuttle Driver App';
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Student app color input
        const studentColorInput = document.getElementById('student-primary-color');
        const studentColorText = document.getElementById('student-primary-text');
        const studentNameInput = document.querySelector('input[name="student_app_name"]');
        const studentTaglineInput = document.querySelector('input[name="student_app_tagline"]');

        if (studentColorInput && studentColorText) {
            studentColorInput.addEventListener('input', function() {
                studentColorText.value = this.value;
                updateStudentPreview();
            });
        }

        if (studentNameInput) {
            studentNameInput.addEventListener('input', updateStudentPreview);
        }

        if (studentTaglineInput) {
            studentTaglineInput.addEventListener('input', updateStudentPreview);
        }

        // Driver app color input
        const driverColorInput = document.getElementById('driver-primary-color');
        const driverColorText = document.getElementById('driver-primary-text');
        const driverNameInput = document.querySelector('input[name="driver_app_name"]');
        const driverTaglineInput = document.querySelector('input[name="driver_app_tagline"]');

        if (driverColorInput && driverColorText) {
            driverColorInput.addEventListener('input', function() {
                driverColorText.value = this.value;
                updateDriverPreview();
            });
        }

        if (driverNameInput) {
            driverNameInput.addEventListener('input', updateDriverPreview);
        }

        if (driverTaglineInput) {
            driverTaglineInput.addEventListener('input', updateDriverPreview);
        }

        // Initial preview update
        updateStudentPreview();
        updateDriverPreview();
    });
})();
</script>
