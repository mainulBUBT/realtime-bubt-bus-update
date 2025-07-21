/**
 * BUBT Bus Tracker App
 * Splash Screen Handler
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Get elements
    const splashScreen = document.getElementById('splash-screen');
    const homeScreen = document.getElementById('home-screen');
    const permissionScreen = document.getElementById('permission-screen');

    // Make sure home screen is not active initially
    if (homeScreen) {
        homeScreen.classList.remove('active');
    }

    // Hide permission screen initially
    if (permissionScreen) {
        permissionScreen.style.display = 'none';
    }

    // Show splash screen animation
    if (splashScreen) {
        // Add animation class for the redesigned splash screen
        splashScreen.classList.add('animate');

        // Animate the app name and tagline with a slight delay
        const appName = document.querySelector('.splash-app-name');
        const tagline = document.querySelector('.splash-tagline');

        if (appName) {
            setTimeout(() => {
                appName.classList.add('show');
            }, 300);
        }

        if (tagline) {
            setTimeout(() => {
                tagline.classList.add('show');
            }, 600);
        }

        // Hide splash screen after 2.5 seconds
        setTimeout(function () {
            splashScreen.classList.add('hidden');

            // Show permission screen first
            if (permissionScreen) {
                permissionScreen.style.display = 'flex';

                // Set up permission buttons
                setupPermissionButtons();
            } else {
                // If no permission screen, show home screen directly
                if (homeScreen) {
                    homeScreen.classList.add('active');
                }
            }
        }, 2500);
    }
});

/**
 * Set up permission screen buttons
 */
function setupPermissionButtons() {
    const permissionScreen = document.getElementById('permission-screen');
    const homeScreen = document.getElementById('home-screen');
    const allowOnceBtn = document.getElementById('allow-once');
    const allowWhileUsingBtn = document.getElementById('allow-while-using');
    const dontAllowBtn = document.getElementById('dont-allow');

    // Function to handle permission granted
    const handlePermissionGranted = () => {
        permissionScreen.style.display = 'none';
        if (homeScreen) {
            homeScreen.classList.add('active');
        }
        // You could also set a flag in localStorage to remember the permission
        localStorage.setItem('locationPermission', 'granted');
    };

    // Function to handle permission denied
    const handlePermissionDenied = () => {
        permissionScreen.style.display = 'none';
        if (homeScreen) {
            homeScreen.classList.add('active');
        }
        // You could also set a flag in localStorage to remember the permission
        localStorage.setItem('locationPermission', 'denied');

        // Show a notification that some features may not work
        showNotification('Some features may not work without location access', 'warning');
    };

    // Add event listeners to buttons
    if (allowOnceBtn) {
        allowOnceBtn.addEventListener('click', handlePermissionGranted);
    }

    if (allowWhileUsingBtn) {
        allowWhileUsingBtn.addEventListener('click', handlePermissionGranted);
    }

    if (dontAllowBtn) {
        dontAllowBtn.addEventListener('click', handlePermissionDenied);
    }
}

/**
 * Show a notification to the user
 */
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('notification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        document.body.appendChild(notification);
    }

    // Set notification content and type
    notification.textContent = message;
    notification.className = 'notification';
    notification.classList.add(`notification-${type}`);

    // Show notification
    notification.classList.add('show');

    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}