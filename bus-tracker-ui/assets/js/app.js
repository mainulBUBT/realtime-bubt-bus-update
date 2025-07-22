// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the app
    initApp();
});

function initApp() {
    console.log("App initialized!");
    
    // Initialize menu functionality
    initMenu();
    
    // Initialize dropdown functionality
    initDropdowns();
    
    // Initialize bus cards
    initBusCards();
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
    
    // Handle filter chip selection
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            // Get the filter type (data-filter or data-status)
            const filterType = this.hasAttribute('data-filter') ? 'data-filter' : 'data-status';
            
            // Get all chips of the same type
            const sameTypeChips = document.querySelectorAll(`.filter-chip[${filterType}]`);
            
            // Remove active class from all chips of the same type
            sameTypeChips.forEach(c => {
                c.classList.remove('active');
            });
            
            // Add active class to this chip
            this.classList.add('active');
        });
    });
    
    // Clear all filters
    clearFilters.addEventListener('click', function() {
        // Set "All" options as active
        document.querySelector('.filter-chip[data-filter="all"]').classList.add('active');
        document.querySelector('.filter-chip[data-status="all"]').classList.add('active');
        
        // Remove active class from other chips
        document.querySelectorAll('.filter-chip:not([data-filter="all"]):not([data-status="all"])').forEach(chip => {
            chip.classList.remove('active');
        });
    });
    
    // Apply filters
    applyFilters.addEventListener('click', function() {
        // Get selected filters
        const selectedBusFilter = document.querySelector('.filter-chip[data-filter].active').getAttribute('data-filter');
        const selectedStatusFilter = document.querySelector('.filter-chip[data-status].active').getAttribute('data-status');
        
        // Apply filters to bus cards
        filterBusCards(selectedBusFilter, selectedStatusFilter);
        
        // Close filter panel
        closeFilterPanel();
        
        // Show active state on filter button if filters are applied
        const filterBtn = document.getElementById('filter-btn');
        if (selectedBusFilter !== 'all' || selectedStatusFilter !== 'all') {
            filterBtn.classList.add('active');
        } else {
            filterBtn.classList.remove('active');
        }
    });
    
    // Filter bus cards based on selected filters
    function filterBusCards(busFilter, statusFilter) {
        const busCards = document.querySelectorAll('.home-bus-card');
        
        busCards.forEach(card => {
            const busId = card.getAttribute('data-bus-id');
            const statusElement = card.querySelector('.home-bus-status');
            let statusClass = '';
            
            if (statusElement.classList.contains('active')) {
                statusClass = 'active';
            } else if (statusElement.classList.contains('delayed')) {
                statusClass = 'delayed';
            } else if (statusElement.classList.contains('inactive')) {
                statusClass = 'inactive';
            }
            
            // Check if card matches both filters
            const matchesBusFilter = busFilter === 'all' || busId === busFilter;
            const matchesStatusFilter = statusFilter === 'all' || statusClass === statusFilter;
            
            // Show or hide card based on filters
            if (matchesBusFilter && matchesStatusFilter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
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
            // Uncomment the line below to actually navigate
            // window.location.href = `track.html?bus=${busId}`;
            
            // For demo purposes, just show a notification
            showNotification(`Selected bus: ${busId}`);
        });
    });
}

// Helper function to show notifications
function showNotification(message) {
    // Create notification element if it doesn't exist
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Set message and show notification
    notification.textContent = message;
    notification.classList.add('show');
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}