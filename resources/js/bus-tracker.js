// Global device fingerprint instance
let deviceFingerprint = null;
let deviceToken = null;

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize device fingerprinting first
    initDeviceFingerprinting().then(() => {
        // Initialize the app after device token is ready
        initApp();
    });
});

/**
 * Initialize device fingerprinting
 * @returns {Promise<void>}
 */
async function initDeviceFingerprinting() {
    try {
        console.log("Initializing device fingerprinting...");
        
        // Create device fingerprint instance
        deviceFingerprint = new DeviceFingerprint();
        
        // Get or generate device token
        deviceToken = await deviceFingerprint.getOrGenerateToken();
        
        console.log("Device token ready:", deviceToken.substring(0, 8) + "...");
        
        // Store token globally for use in other functions
        window.busTrackerDeviceToken = deviceToken;
        
    } catch (error) {
        console.error("Failed to initialize device fingerprinting:", error);
        
        // Generate a fallback token
        deviceToken = 'fallback_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        window.busTrackerDeviceToken = deviceToken;
    }
}

function initApp() {
    console.log("App initialized with device token!");
    
    // Initialize menu functionality
    initMenu();
    
    // Initialize dropdown functionality
    initDropdowns();
    
    // Initialize bus cards
    initBusCards();
    
    // Initialize device token display (for debugging)
    displayDeviceTokenInfo();
}

/**
 * Display device token information for debugging
 */
function displayDeviceTokenInfo() {
    // Only show in development mode
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log("Device Token Info:");
        console.log("- Token:", deviceToken);
        console.log("- Stored Token:", deviceFingerprint?.getStoredToken());
        console.log("- Token Valid:", deviceFingerprint?.isTokenValid());
        
        // Add debug info to page if in development
        const debugInfo = document.createElement('div');
        debugInfo.id = 'debug-device-token';
        debugInfo.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            max-width: 200px;
            word-break: break-all;
        `;
        debugInfo.innerHTML = `
            <strong>Device Token:</strong><br>
            ${deviceToken.substring(0, 16)}...<br>
            <small>Valid: ${deviceFingerprint?.isTokenValid() ? 'Yes' : 'No'}</small>
        `;
        document.body.appendChild(debugInfo);
        
        // Remove debug info after 10 seconds
        setTimeout(() => {
            debugInfo.remove();
        }, 10000);
    }
}

function initMenu() {
    // Get menu buttons and drawer elements
    const menuBtns = document.querySelectorAll('.menu-btn');
    const drawer = document.getElementById('side-drawer');
    const drawerOverlay = document.getElementById('drawer-overlay');
    const drawerClose = document.getElementById('drawer-close');
    
    // Add click event to menu buttons
    menuBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            drawer.classList.add('active');
            drawerOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        });
    });
    
    // Close drawer when clicking the close button
    drawerClose.addEventListener('click', closeDrawer);
    
    // Close drawer when clicking the overlay
    drawerOverlay.addEventListener('click', closeDrawer);
    
    function closeDrawer() {
        drawer.classList.remove('active');
        drawerOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Enable scrolling
    }
    
    // Handle navigation items
    const navItems = document.querySelectorAll('.nav-item, .drawer-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Prevent default behavior for links
            e.preventDefault();
            
            // Get the target screen
            const targetScreen = this.getAttribute('data-screen');
            
            // Hide all screens
            document.querySelectorAll('.screen').forEach(screen => {
                screen.classList.remove('active-screen');
            });
            
            // Show the target screen
            document.getElementById(targetScreen).classList.add('active-screen');
            
            // Update active state for navigation items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
            });
            
            // Set this item as active
            this.classList.add('active');
            
            // Close the drawer if it's open
            closeDrawer();
        });
    });
}

function initDropdowns() {
    console.log("Initializing dropdown functionality");
    
    // Bus dropdown
    const busDropdownHeader = document.getElementById('dropdown-header');
    const busDropdownMenu = document.getElementById('dropdown-menu');
    const busDropdownItems = document.querySelectorAll('#dropdown-menu .dropdown-item');
    
    // Holiday dropdown
    const holidayDropdownHeader = document.getElementById('holiday-dropdown-header');
    const holidayDropdownMenu = document.getElementById('holiday-dropdown-menu');
    const holidayDropdownItems = document.querySelectorAll('#holiday-dropdown-menu .dropdown-item');
    
    // Toggle bus dropdown
    if (busDropdownHeader && busDropdownMenu) {
        busDropdownHeader.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            busDropdownHeader.classList.toggle('active');
            busDropdownMenu.classList.toggle('show');
            
            // Close holiday dropdown if open
            if (holidayDropdownHeader && holidayDropdownMenu) {
                holidayDropdownHeader.classList.remove('active');
                holidayDropdownMenu.classList.remove('show');
            }
            
            console.log("Bus dropdown toggled");
        });
        
        // Handle bus dropdown item selection
        busDropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                
                // Update active state
                busDropdownItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Update header text
                busDropdownHeader.querySelector('span').textContent = this.querySelector('span').textContent;
                
                // Close dropdown
                busDropdownHeader.classList.remove('active');
                busDropdownMenu.classList.remove('show');
                
                // Filter bus cards
                const busId = this.getAttribute('data-bus-id');
                filterBusCards(busId);
                
                console.log("Selected bus: " + busId);
            });
        });
    }
    
    // Toggle holiday dropdown
    if (holidayDropdownHeader && holidayDropdownMenu) {
        holidayDropdownHeader.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            holidayDropdownHeader.classList.toggle('active');
            holidayDropdownMenu.classList.toggle('show');
            
            // Close bus dropdown if open
            if (busDropdownHeader && busDropdownMenu) {
                busDropdownHeader.classList.remove('active');
                busDropdownMenu.classList.remove('show');
            }
            
            console.log("Holiday dropdown toggled");
        });
        
        // Handle holiday dropdown item selection
        holidayDropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                
                // Update active state
                holidayDropdownItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Update header text
                holidayDropdownHeader.querySelector('span').textContent = this.querySelector('span').textContent;
                
                // Close dropdown
                holidayDropdownHeader.classList.remove('active');
                holidayDropdownMenu.classList.remove('show');
                
                // Filter holidays (if needed)
                const holidayType = this.getAttribute('data-holiday-type');
                filterHolidays(holidayType);
                
                console.log("Selected holiday type: " + holidayType);
            });
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        // Bus dropdown
        if (busDropdownHeader && busDropdownMenu && !event.target.closest('.bus-dropdown')) {
            busDropdownHeader.classList.remove('active');
            busDropdownMenu.classList.remove('show');
        }
        
        // Holiday dropdown
        if (holidayDropdownHeader && holidayDropdownMenu && !event.target.closest('#holiday-dropdown')) {
            holidayDropdownHeader.classList.remove('active');
            holidayDropdownMenu.classList.remove('show');
        }
    });
    
    // Filter bus cards based on selected bus
    function filterBusCards(busId) {
        const busCards = document.querySelectorAll('.home-bus-card');
        
        busCards.forEach(card => {
            const cardBusId = card.getAttribute('data-bus-id');
            
            if (busId === 'all' || cardBusId === busId) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Filter holidays based on selected type
    function filterHolidays(holidayType) {
        const holidayItems = document.querySelectorAll('.chuti-item');
        
        // This is just a placeholder - in a real app, you would add data attributes to the holiday items
        if (holidayType === 'all') {
            holidayItems.forEach(item => {
                item.style.display = '';
            });
        } else {
            // For demo purposes, let's just show/hide based on the type
            // In a real app, you would check data attributes
            holidayItems.forEach((item, index) => {
                if (holidayType === 'national' && (index === 0 || index === 2)) {
                    item.style.display = '';
                } else if (holidayType === 'university' && index === 1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    }
}

function initBusCards() {
    // Get all bus cards
    const busCards = document.querySelectorAll('.home-bus-card');
    
    // Add click event to bus cards
    busCards.forEach(card => {
        card.addEventListener('click', function() {
            // Get bus ID
            const busId = this.getAttribute('data-bus-id');
            
            // Navigate to bus details page
            console.log(`Navigating to bus details for ${busId}`);
            // For Laravel, we'll use a route instead of direct HTML navigation
            window.location.href = `/track/${busId}`;
        });
    });
}

/**
 * Get the current device token
 * @returns {string|null} Current device token
 */
function getDeviceToken() {
    return deviceToken || window.busTrackerDeviceToken || null;
}

/**
 * Validate device token with backend
 * @param {string} token - Token to validate
 * @returns {Promise<boolean>} Validation result
 */
async function validateDeviceTokenWithBackend(token) {
    try {
        const response = await fetch('/api/device-token/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ token: token })
        });
        
        const result = await response.json();
        return result.valid === true;
    } catch (error) {
        console.error('Token validation failed:', error);
        return false;
    }
}

/**
 * Register device token with backend
 * @param {string} token - Token to register
 * @param {object} fingerprint - Fingerprint data
 * @returns {Promise<boolean>} Registration result
 */
async function registerDeviceTokenWithBackend(token, fingerprint) {
    try {
        const response = await fetch('/api/device-token/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ 
                token: token,
                fingerprint: fingerprint
            })
        });
        
        const result = await response.json();
        return result.success === true;
    } catch (error) {
        console.error('Token registration failed:', error);
        return false;
    }
}

// Helper function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Set message and show notification
    notification.textContent = message;
    notification.className = `notification notification-${type}`;
    notification.classList.add('show');
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}