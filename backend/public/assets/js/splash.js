/**
 * BUBT Bus Tracker App
 * Splash Screen Handler - Version 3.0
 * Flow: Splash → Login → Permission → Main
 */

document.addEventListener('DOMContentLoaded', function () {
    const splashScreen = document.getElementById('splash-screen');
    const loginScreen = document.getElementById('login-screen');
    const permissionScreen = document.getElementById('permission-screen');
    const mainScreen = document.getElementById('main-screen');

    // Check stored states
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const hasPermission = localStorage.getItem('locationPermission');

    // Show splash animation
    if (splashScreen) {
        splashScreen.classList.add('animate');

        // Hide splash after 2.5 seconds
        setTimeout(function () {
            splashScreen.classList.add('hidden');

            // Remove splash screen from DOM after animation
            setTimeout(() => {
                splashScreen.style.display = 'none';
            }, 500);

            // Determine which screen to show
            if (isLoggedIn) {
                // User is logged in
                if (hasPermission) {
                    // Permission already granted
                    if (mainScreen) mainScreen.classList.add('active');
                } else {
                    // Show permission screen
                    if (permissionScreen) permissionScreen.classList.add('active');
                }
            } else {
                // Show login screen for first-time users
                if (loginScreen) loginScreen.classList.add('active');
            }
        }, 2500);
    }

    // Google Login Button Handler
    const googleLoginBtn = document.getElementById('google-login-btn');
    if (googleLoginBtn) {
        googleLoginBtn.addEventListener('click', function () {
            // Simulate Google login (replace with actual Google OAuth)
            // For demo purposes, we'll just set a flag and proceed

            // Add button loading state
            googleLoginBtn.disabled = true;
            googleLoginBtn.innerHTML = `
                <div class="loading-spinner" style="width: 20px; height: 20px; border-width: 2px;"></div>
                Signing in...
            `;

            // Simulate async login
            setTimeout(() => {
                // Set login state
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userName', 'BUBT Student');

                // Hide login screen
                if (loginScreen) {
                    loginScreen.classList.remove('active');
                }

                // Show permission screen
                if (hasPermission) {
                    if (mainScreen) mainScreen.classList.add('active');
                } else {
                    if (permissionScreen) permissionScreen.classList.add('active');
                }
            }, 1500);
        });
    }
});