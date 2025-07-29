/**
 * Livewire-compatible App JavaScript
 * Adapts existing app.js functionality to work with Livewire components
 */

class LivewireApp {
    constructor() {
        this.isInitialized = false;
        this.menuState = {
            isDrawerOpen: false
        };
        this.dropdownState = {
            busDropdown: false,
            holidayDropdown: false
        };
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        // Initialize on DOM ready and Livewire navigation
        document.addEventListener('DOMContentLoaded', () => this.initializeApp());
        document.addEventListener('livewire:navigated', () => this.initializeApp());
        
        this.isInitialized = true;
    }

    initializeApp() {
        console.log("Livewire App initialized!");
        
        // Initialize components
        this.initMenu();
        this.initDropdowns();
        this.initBusCards();
        this.initNotifications();
        
        // Setup Livewire event listeners
        this.setupLivewireListeners();
    }

    initMenu() {
        // Get menu buttons and drawer elements
        const menuBtns = document.querySelectorAll('.menu-btn');
        const drawer = document.getElementById('side-drawer');
        const drawerOverlay = document.getElementById('drawer-overlay');
        const drawerClose = document.getElementById('drawer-close');
        
        if (!drawer || !drawerOverlay) return;
        
        // Add click event to menu buttons
        menuBtns.forEach(btn => {
            // Remove existing listeners to prevent duplicates
            btn.removeEventListener('click', this.openDrawer);
            btn.addEventListener('click', this.openDrawer.bind(this));
        });
        
        // Close drawer when clicking the close button
        if (drawerClose) {
            drawerClose.removeEventListener('click', this.closeDrawer);
            drawerClose.addEventListener('click', this.closeDrawer.bind(this));
        }
        
        // Close drawer when clicking the overlay
        drawerOverlay.removeEventListener('click', this.closeDrawer);
        drawerOverlay.addEventListener('click', this.closeDrawer.bind(this));
        
        // Handle navigation items (compatible with Livewire routing)
        this.initNavigationItems();
    }

    openDrawer() {
        const drawer = document.getElementById('side-drawer');
        const drawerOverlay = document.getElementById('drawer-overlay');
        
        if (drawer && drawerOverlay) {
            drawer.classList.add('active');
            drawerOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            this.menuState.isDrawerOpen = true;
        }
    }

    closeDrawer() {
        const drawer = document.getElementById('side-drawer');
        const drawerOverlay = document.getElementById('drawer-overlay');
        
        if (drawer && drawerOverlay) {
            drawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
            document.body.style.overflow = '';
            this.menuState.isDrawerOpen = false;
        }
    }

    initNavigationItems() {
        const navItems = document.querySelectorAll('.nav-item, .drawer-item');
        
        navItems.forEach(item => {
            // Remove existing listeners
            item.removeEventListener('click', this.handleNavigation);
            item.addEventListener('click', this.handleNavigation.bind(this));
        });
    }

    handleNavigation(e) {
        const targetScreen = e.currentTarget.getAttribute('data-screen');
        const href = e.currentTarget.getAttribute('href');
        
        // If it's a Livewire navigation or has href, let it handle naturally
        if (href && href !== '#') {
            this.closeDrawer();
            return; // Let the browser handle the navigation
        }
        
        // Prevent default for hash links
        if (!href || href === '#') {
            e.preventDefault();
        }
        
        // Handle screen switching for single-page navigation
        if (targetScreen) {
            this.switchScreen(targetScreen);
        }
        
        // Update active state
        this.updateActiveNavigation(e.currentTarget);
        
        // Close drawer
        this.closeDrawer();
    }

    switchScreen(targetScreen) {
        // Hide all screens
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active-screen');
        });
        
        // Show target screen
        const screen = document.getElementById(targetScreen);
        if (screen) {
            screen.classList.add('active-screen');
        }
    }

    updateActiveNavigation(activeItem) {
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item, .drawer-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to clicked item
        activeItem.classList.add('active');
    }

    initDropdowns() {
        console.log("Initializing Livewire-compatible dropdown functionality");
        
        // Bus dropdown
        this.initBusDropdown();
        
        // Holiday dropdown (if exists)
        this.initHolidayDropdown();
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', this.handleOutsideClick.bind(this));
    }

    initBusDropdown() {
        const busDropdownHeader = document.getElementById('dropdown-header');
        const busDropdownMenu = document.getElementById('dropdown-menu');
        
        if (!busDropdownHeader || !busDropdownMenu) return;
        
        // Toggle bus dropdown
        busDropdownHeader.removeEventListener('click', this.toggleBusDropdown);
        busDropdownHeader.addEventListener('click', this.toggleBusDropdown.bind(this));
        
        // Handle bus dropdown item selection (Livewire compatible)
        const busDropdownItems = busDropdownMenu.querySelectorAll('.dropdown-item');
        busDropdownItems.forEach(item => {
            item.removeEventListener('click', this.handleBusSelection);
            item.addEventListener('click', this.handleBusSelection.bind(this));
        });
    }

    toggleBusDropdown(e) {
        e.stopPropagation();
        
        const header = document.getElementById('dropdown-header');
        const menu = document.getElementById('dropdown-menu');
        
        if (header && menu) {
            const isActive = header.classList.contains('active');
            
            // Close other dropdowns
            this.closeAllDropdowns();
            
            if (!isActive) {
                header.classList.add('active');
                menu.classList.add('show');
                this.dropdownState.busDropdown = true;
            }
        }
    }

    handleBusSelection(e) {
        e.stopPropagation();
        
        const item = e.currentTarget;
        const busId = item.getAttribute('data-bus-id');
        
        // If this is a Livewire component, let Livewire handle the click
        if (item.hasAttribute('wire:click')) {
            // Livewire will handle the selection
            this.closeBusDropdown();
            return;
        }
        
        // Fallback for non-Livewire dropdowns
        this.selectBusOption(item, busId);
    }

    selectBusOption(item, busId) {
        const header = document.getElementById('dropdown-header');
        const menu = document.getElementById('dropdown-menu');
        
        // Update active state
        menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        
        // Update header text
        const headerSpan = header.querySelector('span');
        const itemSpan = item.querySelector('span');
        if (headerSpan && itemSpan) {
            headerSpan.textContent = itemSpan.textContent;
        }
        
        // Close dropdown
        this.closeBusDropdown();
        
        // Filter bus cards (if not handled by Livewire)
        this.filterBusCards(busId);
        
        console.log("Selected bus: " + busId);
    }

    closeBusDropdown() {
        const header = document.getElementById('dropdown-header');
        const menu = document.getElementById('dropdown-menu');
        
        if (header && menu) {
            header.classList.remove('active');
            menu.classList.remove('show');
            this.dropdownState.busDropdown = false;
        }
    }

    initHolidayDropdown() {
        const holidayDropdownHeader = document.getElementById('holiday-dropdown-header');
        const holidayDropdownMenu = document.getElementById('holiday-dropdown-menu');
        
        if (!holidayDropdownHeader || !holidayDropdownMenu) return;
        
        // Toggle holiday dropdown
        holidayDropdownHeader.removeEventListener('click', this.toggleHolidayDropdown);
        holidayDropdownHeader.addEventListener('click', this.toggleHolidayDropdown.bind(this));
        
        // Handle holiday dropdown item selection
        const holidayDropdownItems = holidayDropdownMenu.querySelectorAll('.dropdown-item');
        holidayDropdownItems.forEach(item => {
            item.removeEventListener('click', this.handleHolidaySelection);
            item.addEventListener('click', this.handleHolidaySelection.bind(this));
        });
    }

    toggleHolidayDropdown(e) {
        e.stopPropagation();
        
        const header = document.getElementById('holiday-dropdown-header');
        const menu = document.getElementById('holiday-dropdown-menu');
        
        if (header && menu) {
            const isActive = header.classList.contains('active');
            
            // Close other dropdowns
            this.closeAllDropdowns();
            
            if (!isActive) {
                header.classList.add('active');
                menu.classList.add('show');
                this.dropdownState.holidayDropdown = true;
            }
        }
    }

    handleHolidaySelection(e) {
        e.stopPropagation();
        
        const item = e.currentTarget;
        const holidayType = item.getAttribute('data-holiday-type');
        
        // Update active state
        const menu = document.getElementById('holiday-dropdown-menu');
        menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        
        // Update header text
        const header = document.getElementById('holiday-dropdown-header');
        const headerSpan = header.querySelector('span');
        const itemSpan = item.querySelector('span');
        if (headerSpan && itemSpan) {
            headerSpan.textContent = itemSpan.textContent;
        }
        
        // Close dropdown
        this.closeHolidayDropdown();
        
        // Filter holidays
        this.filterHolidays(holidayType);
        
        console.log("Selected holiday type: " + holidayType);
    }

    closeHolidayDropdown() {
        const header = document.getElementById('holiday-dropdown-header');
        const menu = document.getElementById('holiday-dropdown-menu');
        
        if (header && menu) {
            header.classList.remove('active');
            menu.classList.remove('show');
            this.dropdownState.holidayDropdown = false;
        }
    }

    closeAllDropdowns() {
        this.closeBusDropdown();
        this.closeHolidayDropdown();
    }

    handleOutsideClick(event) {
        // Close bus dropdown if clicking outside
        if (!event.target.closest('.bus-dropdown') && this.dropdownState.busDropdown) {
            this.closeBusDropdown();
        }
        
        // Close holiday dropdown if clicking outside
        if (!event.target.closest('#holiday-dropdown') && this.dropdownState.holidayDropdown) {
            this.closeHolidayDropdown();
        }
    }

    initBusCards() {
        // Get all bus cards (works with both static and Livewire-rendered cards)
        const busCards = document.querySelectorAll('.home-bus-card');
        
        busCards.forEach(card => {
            // Remove existing listeners to prevent duplicates
            card.removeEventListener('click', this.handleBusCardClick);
            
            // Only add listener if not handled by Livewire
            if (!card.hasAttribute('wire:click')) {
                card.addEventListener('click', this.handleBusCardClick.bind(this));
            }
        });
    }

    handleBusCardClick(e) {
        const card = e.currentTarget;
        const busId = card.getAttribute('data-bus-id');
        
        if (!busId) return;
        
        // Store bus ID for tracking page
        localStorage.setItem('trackingBusId', busId);
        
        // Navigate to tracking page
        console.log(`Navigating to bus details for ${busId}`);
        
        // Check if we're in a Livewire environment
        if (window.Livewire) {
            // Use Livewire navigation if available
            window.location.href = `/track/${busId}`;
        } else {
            // Fallback navigation
            window.location.href = `track.html?bus=${busId}`;
        }
        
        // Show notification
        this.showNotification(`Selected bus: ${busId}`);
    }

    // Filter functions (compatible with both static and Livewire)
    filterBusCards(busId) {
        // If Livewire is handling filtering, don't interfere
        if (window.Livewire && document.querySelector('[wire\\:click*="filterByBus"]')) {
            return;
        }
        
        // Fallback filtering for static content
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

    filterHolidays(holidayType) {
        const holidayItems = document.querySelectorAll('.chuti-item');
        
        if (holidayType === 'all') {
            holidayItems.forEach(item => {
                item.style.display = '';
            });
        } else {
            // Simple filtering logic (can be enhanced)
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

    initNotifications() {
        // Handle notification buttons
        const notificationBtns = document.querySelectorAll('.notification-btn');
        
        notificationBtns.forEach(btn => {
            btn.removeEventListener('click', this.handleNotificationClick);
            btn.addEventListener('click', this.handleNotificationClick.bind(this));
        });
    }

    handleNotificationClick(e) {
        console.log('Notification clicked');
        // Handle notification logic here
        this.showNotification('Notifications feature coming soon!');
    }

    setupLivewireListeners() {
        // Listen for Livewire events
        if (window.Livewire) {
            // Re-initialize components after Livewire updates
            Livewire.on('component-updated', () => {
                setTimeout(() => {
                    this.initBusCards();
                    this.initDropdowns();
                }, 100);
            });
            
            // Handle custom Livewire events
            Livewire.on('bus-selected', (data) => {
                console.log('Bus selected via Livewire:', data);
                this.closeBusDropdown();
            });
            
            Livewire.on('show-notification', (data) => {
                this.showNotification(data.message, data.type);
            });
        }
    }

    // Utility functions
    showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.querySelector('.app-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'app-notification';
            document.body.appendChild(notification);
        }
        
        // Set message and show notification
        notification.textContent = message;
        notification.className = `app-notification ${type} show`;
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Cleanup method
    destroy() {
        // Remove event listeners
        document.removeEventListener('click', this.handleOutsideClick);
        
        // Clear states
        this.menuState = { isDrawerOpen: false };
        this.dropdownState = { busDropdown: false, holidayDropdown: false };
        
        console.log('LivewireApp destroyed');
    }
}

// Initialize the app
let livewireApp = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (!livewireApp) {
        livewireApp = new LivewireApp();
    }
});

// Re-initialize on Livewire navigation
document.addEventListener('livewire:navigated', function() {
    if (!livewireApp) {
        livewireApp = new LivewireApp();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (livewireApp) {
        livewireApp.destroy();
        livewireApp = null;
    }
});

// Export for global access
window.LivewireApp = LivewireApp;
window.livewireApp = livewireApp;