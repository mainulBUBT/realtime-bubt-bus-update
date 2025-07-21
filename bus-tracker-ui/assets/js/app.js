/**
 * BUBT Bus Tracker App
 * Main JavaScript file
 */

// Bus data
const busData = {
    'B1': {
        id: 'B1',
        name: 'Buriganga',
        route: ['Asad Gate', 'Shyamoli', 'Mirpur-1', 'Rainkhola', 'BUBT'],
        currentStop: 'Shyamoli',
        nextStop: 'Mirpur-1',
        status: 'active',
        arrivalTime: '10 min',
        departureTime: '7:00 AM',
        returnTime: '5:00 PM'
    },
    'B2': {
        id: 'B2',
        name: 'Brahmaputra',
        route: ['Hemayetpur', 'Amin Bazar', 'Gabtoli', 'Mirpur-1', 'BUBT'],
        currentStop: 'Gabtoli',
        nextStop: 'Mirpur-1',
        status: 'delayed',
        arrivalTime: '15 min',
        departureTime: '7:00 AM',
        returnTime: '5:00 PM'
    },
    'B3': {
        id: 'B3',
        name: 'Padma',
        route: ['Shyamoli', 'Agargaon', 'Kazipara', 'Mirpur-10', 'BUBT'],
        currentStop: 'Kazipara',
        nextStop: 'Mirpur-10',
        status: 'active',
        arrivalTime: '8 min',
        departureTime: '7:00 AM',
        returnTime: '5:00 PM'
    },
    'B4': {
        id: 'B4',
        name: 'Meghna',
        route: ['Mirpur-14', 'Mirpur-10', 'Mirpur-11', 'Proshikha', 'BUBT'],
        currentStop: '',
        nextStop: '',
        status: 'inactive',
        arrivalTime: '',
        departureTime: '4:10 PM',
        returnTime: '9:25 PM'
    },
    'B5': {
        id: 'B5',
        name: 'Jamuna',
        route: ['ECB Chattar', 'Kalshi Bridge', 'Mirpur-12', 'Duaripara', 'BUBT'],
        currentStop: 'Mirpur-12',
        nextStop: 'Duaripara',
        status: 'active',
        arrivalTime: '12 min',
        departureTime: '7:00 AM',
        returnTime: '5:00 PM'
    }
};

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    console.log('BUBT Bus Tracker initialized');
    
    // Initialize navigation
    initNavigation();
    
    // Add event listeners
    addEventListeners();
    
    // Initialize home page with new design
    initNewHomePage();
    
    // Initialize bus dropdown
    initBusDropdown();
    
    // Initialize bus cards
    initBusCards();
    
    // Initialize track page
    initTrackPage();
    
    // App initialization complete
});

// Function to load scripts dynamically
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => resolve(script);
        script.onerror = () => reject(new Error(`Script load error for ${src}`));
        document.head.appendChild(script);
    });
}

// Splash screen handling moved to splash.js

/**
 * Initialize search and filter functionality
 */
function initBusDropdown() {
    const searchInput = document.getElementById('bus-search');
    const filterToggle = document.getElementById('filter-toggle');
    const filterDropdown = document.getElementById('filter-dropdown');
    const filterClose = document.getElementById('filter-close');
    const searchResults = document.getElementById('search-results');
    
    if (!searchInput || !filterToggle || !filterDropdown) {
        console.warn('Search elements not found, skipping search initialization');
        return;
    }
    
    // Filter toggle functionality
    filterToggle.addEventListener('click', function() {
        filterDropdown.classList.toggle('show');
        filterToggle.classList.toggle('active');
        searchResults.classList.remove('show');
    });
    
    // Filter close functionality
    if (filterClose) {
        filterClose.addEventListener('click', function() {
            filterDropdown.classList.remove('show');
            filterToggle.classList.remove('active');
        });
    }
    
    // Handle filter option selection
    const filterOptions = document.querySelectorAll('.filter-option');
    filterOptions.forEach(option => {
        option.addEventListener('click', function() {
            const busId = this.getAttribute('data-bus-id');
            
            // Update active filter
            filterOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Filter bus cards
            filterBusCards(busId);
            
            // Close filter dropdown
            filterDropdown.classList.remove('show');
            filterToggle.classList.remove('active');
            
            // Update search placeholder
            const busText = this.querySelector('span').textContent;
            if (busId === 'all') {
                searchInput.placeholder = 'Search buses or routes...';
            } else {
                searchInput.placeholder = `Search in ${busText}...`;
            }
        });
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        
        if (query.length > 0) {
            showSearchResults(query);
            filterDropdown.classList.remove('show');
            filterToggle.classList.remove('active');
        } else {
            searchResults.classList.remove('show');
            clearSearchHighlight();
        }
    });
    
    // Search focus event
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0) {
            showSearchResults(this.value.trim().toLowerCase());
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.search-dropdown-wrapper')) {
            filterDropdown.classList.remove('show');
            searchResults.classList.remove('show');
            filterToggle.classList.remove('active');
        }
    });
    
    // Add clear search functionality
    addClearSearchButton();
}

/**
 * Show search results
 */
function showSearchResults(query) {
    const searchResults = document.getElementById('search-results');
    const results = [];
    
    // Search through bus data
    Object.keys(busData).forEach(busId => {
        const bus = busData[busId];
        const searchText = `${busId} ${bus.name} ${bus.route.join(' ')}`.toLowerCase();
        
        if (searchText.includes(query)) {
            results.push({
                id: busId,
                name: bus.name,
                route: bus.route.join(' → ')
            });
        }
    });
    
    // Display results
    if (results.length > 0) {
        let resultsHTML = '';
        results.forEach(result => {
            resultsHTML += `
                <div class="search-result-item" data-bus-id="${result.id}">
                    <div class="search-result-icon">${result.id}</div>
                    <div class="search-result-info">
                        <div class="search-result-name">${result.name}</div>
                        <div class="search-result-route">${result.route}</div>
                    </div>
                </div>
            `;
        });
        searchResults.innerHTML = resultsHTML;
        
        // Add click handlers to search results
        searchResults.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const busId = this.getAttribute('data-bus-id');
                selectBusFromSearch(busId);
            });
        });
        
        // Highlight matching cards
        highlightSearchResults(results.map(r => r.id));
    } else {
        searchResults.innerHTML = `
            <div class="search-no-results">
                <i class="bi bi-search"></i>
                <div>No buses found for "${query}"</div>
            </div>
        `;
    }
    
    searchResults.classList.add('show');
}

/**
 * Select bus from search results
 */
function selectBusFromSearch(busId) {
    const searchInput = document.getElementById('bus-search');
    const searchResults = document.getElementById('search-results');
    const bus = busData[busId];
    
    if (!bus) return;
    
    // Update search input
    searchInput.value = `${busId} - ${bus.name}`;
    
    // Hide search results
    searchResults.classList.remove('show');
    
    // Filter to show only selected bus
    filterBusCards(busId);
    
    // Highlight the selected bus card
    highlightSearchResults([busId]);
    
    // Show bus modal after a short delay
    setTimeout(() => {
        showOnThisBusModal(bus);
    }, 300);
}

/**
 * Highlight search results
 */
function highlightSearchResults(busIds) {
    const busCards = document.querySelectorAll('.home-bus-card');
    
    busCards.forEach(card => {
        const cardBusId = card.getAttribute('data-bus-id');
        if (busIds.includes(cardBusId)) {
            card.classList.add('highlighted');
        } else {
            card.classList.remove('highlighted');
        }
    });
}

/**
 * Clear search highlight
 */
function clearSearchHighlight() {
    const busCards = document.querySelectorAll('.home-bus-card');
    busCards.forEach(card => {
        card.classList.remove('highlighted');
    });
}

/**
 * Add clear search button
 */
function addClearSearchButton() {
    const searchInput = document.getElementById('bus-search');
    const searchContainer = document.querySelector('.search-input-container');
    
    if (!searchInput || !searchContainer) return;
    
    // Create clear button
    const clearButton = document.createElement('button');
    clearButton.className = 'search-clear';
    clearButton.innerHTML = '<i class="bi bi-x"></i>';
    
    // Insert before filter toggle
    const filterToggle = document.querySelector('.filter-toggle');
    if (filterToggle) {
        searchContainer.insertBefore(clearButton, filterToggle);
    }
    
    // Show/hide clear button based on input
    searchInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            clearButton.classList.add('show');
        } else {
            clearButton.classList.remove('show');
        }
    });
    
    // Clear search functionality
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.placeholder = 'Search buses or routes...';
        clearButton.classList.remove('show');
        document.getElementById('search-results').classList.remove('show');
        clearSearchHighlight();
        filterBusCards('all');
        
        // Reset filter to "All Buses"
        const filterOptions = document.querySelectorAll('.filter-option');
        filterOptions.forEach(opt => opt.classList.remove('active'));
        const allBusesOption = document.querySelector('.filter-option[data-bus-id="all"]');
        if (allBusesOption) {
            allBusesOption.classList.add('active');
        }
    });
}

/**
 * Filter bus cards based on selected bus
 */
function filterBusCards(busId) {
    const busCards = document.querySelectorAll('.home-bus-card');
    
    if (busId === 'all') {
        // Show all buses
        busCards.forEach(card => {
            card.style.display = 'flex';
            card.classList.remove('hidden');
        });
    } else {
        // Show only selected bus
        busCards.forEach(card => {
            if (card.getAttribute('data-bus-id') === busId) {
                card.style.display = 'flex';
                card.classList.remove('hidden');
            } else {
                card.style.display = 'none';
                card.classList.add('hidden');
            }
        });
    }
}

/**
 * Initialize bus cards functionality
 */
function initBusCards() {
    const busCards = document.querySelectorAll('.home-bus-card');
    
    // Open bus details modal when clicking on a bus card
    busCards.forEach(card => {
        card.addEventListener('click', function() {
            const busId = this.getAttribute('data-bus-id');
            const bus = busData[busId];
            
            if (bus) {
                showOnThisBusModal(bus);
            }
        });
    });
}

/**
 * Show the "Are you on this bus?" modal
 */
function showOnThisBusModal(bus) {
    const onThisBusModal = document.getElementById('on-this-bus-modal');
    const busNameSpan = document.querySelector('.on-this-bus-bus-name');
    const busIdBadge = document.querySelector('.bus-id-badge');
    
    // Update bus details in the modal
    if (busNameSpan) {
        busNameSpan.textContent = bus.name;
    }
    
    if (busIdBadge) {
        busIdBadge.textContent = bus.id;
    }
    
    // Update bus status in the modal
    const busStatusElement = document.querySelector('.bus-status-large');
    if (busStatusElement) {
        const statusDot = busStatusElement.querySelector('.status-dot');
        if (statusDot) {
            statusDot.className = 'status-dot';
            if (bus.status === 'active') {
                statusDot.classList.add('active');
                busStatusElement.innerHTML = `<span class="status-dot active"></span> On Route`;
            } else if (bus.status === 'delayed') {
                statusDot.classList.add('delayed');
                busStatusElement.innerHTML = `<span class="status-dot delayed"></span> Delayed`;
            } else {
                statusDot.classList.add('inactive');
                busStatusElement.innerHTML = `<span class="status-dot inactive"></span> Not Running`;
            }
        }
    }
    
    // Show the modal with animation
    onThisBusModal.style.display = 'block';
    // Force reflow to ensure the animation works
    void onThisBusModal.offsetWidth;
    onThisBusModal.classList.add('show');
    
    // Set up event listeners for the buttons
    const yesBtn = document.querySelector('.on-this-bus-yes');
    const noBtn = document.querySelector('.on-this-bus-no');
    const closeBtn = document.querySelector('.confirmation-close');
    
    // Remove any existing event listeners
    const newYesBtn = yesBtn.cloneNode(true);
    const newNoBtn = noBtn.cloneNode(true);
    const newCloseBtn = closeBtn.cloneNode(true);
    yesBtn.parentNode.replaceChild(newYesBtn, yesBtn);
    noBtn.parentNode.replaceChild(newNoBtn, noBtn);
    closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
    
    // Add new event listeners
    newYesBtn.addEventListener('click', function() {
        // Hide the modal with animation
        onThisBusModal.classList.remove('show');
        setTimeout(() => {
            onThisBusModal.style.display = 'none';
        }, 400);
        
        // Show success message
        showSuccessMessage(bus);
        
        // Navigate to track page after a delay
        setTimeout(function() {
            showBusTrackingScreen(bus.id);
        }, 2000);
    });
    
    newNoBtn.addEventListener('click', function() {
        // Hide the modal with animation
        onThisBusModal.classList.remove('show');
        setTimeout(() => {
            onThisBusModal.style.display = 'none';
        }, 400);
    });
    
    newCloseBtn.addEventListener('click', function() {
        // Hide the modal with animation
        onThisBusModal.classList.remove('show');
        setTimeout(() => {
            onThisBusModal.style.display = 'none';
        }, 400);
    });
    
    // Close modal when clicking outside
    const outsideClickHandler = function(event) {
        if (event.target === onThisBusModal) {
            onThisBusModal.classList.remove('show');
            setTimeout(() => {
                onThisBusModal.style.display = 'none';
                // Remove the event listener after closing
                window.removeEventListener('click', outsideClickHandler);
            }, 400);
        }
    };
    
    window.addEventListener('click', outsideClickHandler);
}

/**
 * Update bus modal with bus details
 */
function updateBusModal(bus) {
    // Update bus icon and name
    const modalBusIcon = document.querySelector('.modal-bus-icon');
    const modalBusName = document.querySelector('.modal-bus-name');
    const modalStatusText = document.querySelector('.modal-status-text');
    const modalStatusIndicator = document.querySelector('.status-indicator');
    
    if (modalBusIcon) modalBusIcon.textContent = bus.id;
    if (modalBusName) modalBusName.textContent = bus.name;
    
    // Update status
    if (modalStatusText && modalStatusIndicator) {
        if (bus.status === 'active') {
            modalStatusText.textContent = 'On Route';
            modalStatusIndicator.className = 'status-indicator status-active';
        } else if (bus.status === 'delayed') {
            modalStatusText.textContent = 'Delayed';
            modalStatusIndicator.className = 'status-indicator status-delayed';
        } else {
            modalStatusText.textContent = 'Not Running';
            modalStatusIndicator.className = 'status-indicator status-inactive';
        }
    }
    
    // Update schedule
    const modalDepartureTime = document.querySelector('.modal-departure-time');
    const modalReturnTime = document.querySelector('.modal-return-time');
    
    if (modalDepartureTime) modalDepartureTime.textContent = bus.departureTime;
    if (modalReturnTime) modalReturnTime.textContent = bus.returnTime;
    
    // Update route
    const modalRouteList = document.querySelector('.modal-route-list');
    if (modalRouteList && bus.route) {
        modalRouteList.innerHTML = '';
        
        bus.route.forEach((stop, index) => {
            const stopElement = document.createElement('div');
            stopElement.className = 'route-stop';
            
            // Add active class if this is the current stop
            if (stop === bus.currentStop) {
                stopElement.classList.add('current');
            }
            
            // Create route marker
            const marker = document.createElement('div');
            marker.className = 'route-marker';
            
            // Create route info
            const info = document.createElement('div');
            info.className = 'route-info';
            info.textContent = stop;
            
            // Add elements to stop
            stopElement.appendChild(marker);
            stopElement.appendChild(info);
            
            // Add stop to route list
            modalRouteList.appendChild(stopElement);
        });
    }
    
    // Update current status
    const modalCurrentStop = document.querySelector('.modal-current-stop');
    const modalNextStop = document.querySelector('.modal-next-stop');
    const modalArrivalTime = document.querySelector('.modal-arrival-time');
    
    if (modalCurrentStop) modalCurrentStop.textContent = bus.currentStop || 'N/A';
    if (modalNextStop) modalNextStop.textContent = bus.nextStop || 'N/A';
    if (modalArrivalTime) modalArrivalTime.textContent = bus.arrivalTime || 'N/A';
}

/**
 * Show success message after confirming on bus
 */
function showSuccessMessage(bus) {
    // Create success message element
    const successMessage = document.createElement('div');
    successMessage.className = 'success-message';
    
    // Create success icon
    const icon = document.createElement('div');
    icon.className = 'success-icon';
    icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
    
    // Create success text
    const text = document.createElement('div');
    text.className = 'success-text';
    text.innerHTML = '<h3>Thank you!</h3><p>Thanks for sharing your location. This helps other students track the bus accurately.</p>';
    
    // Add elements to success message
    successMessage.appendChild(icon);
    successMessage.appendChild(text);
    
    // Add success message to body
    document.body.appendChild(successMessage);
    
    // Show success message with animation
    setTimeout(() => {
        successMessage.classList.add('show');
    }, 100);
    
    // Store the bus ID for tracking
    if (bus && bus.id) {
        localStorage.setItem('trackingBusId', bus.id);
    }
    
    // Remove success message after delay
    setTimeout(() => {
        successMessage.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(successMessage);
        }, 300);
    }, 2000);
}

/**
 * Navigate to track page
 */
function navigateToTrackPage() {
    // Hide all screens
    const screens = document.querySelectorAll('.screen');
    screens.forEach(screen => {
        screen.classList.remove('active');
    });
    
    // Show track screen
    const trackScreen = document.getElementById('track-screen');
    if (trackScreen) {
        trackScreen.classList.add('active');
    }
    
    // Update navigation
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
}

/**
 * Initialize navigation
 */
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const screens = document.querySelectorAll('.screen');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all nav items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
            });
            
            // Add active class to clicked nav item
            this.classList.add('active');
            
            // Get target screen
            const targetScreen = this.getAttribute('data-screen');
            
            // Hide all screens
            screens.forEach(screen => {
                screen.classList.remove('active');
            });
            
            // Show target screen
            document.getElementById(targetScreen).classList.add('active');
        });
    });
}

/**
 * Add event listeners
 */
function addEventListeners() {
    // Permission buttons
    const allowOnceBtn = document.getElementById('allow-once');
    const allowWhileUsingBtn = document.getElementById('allow-while-using');
    const dontAllowBtn = document.getElementById('dont-allow');
    
    if (allowOnceBtn) {
        allowOnceBtn.addEventListener('click', function() {
            showHomeScreen();
        });
    }
    
    if (allowWhileUsingBtn) {
        allowWhileUsingBtn.addEventListener('click', function() {
            showHomeScreen();
        });
    }
    
    if (dontAllowBtn) {
        dontAllowBtn.addEventListener('click', function() {
            // Show home screen anyway for demo purposes
            showHomeScreen();
            
            // In a real app, we would show a message about limited functionality
            showNotification('Location access denied. Some features may be limited.', 'warning');
        });
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add notification to body
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Remove notification after delay
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

/**
 * Navigate to screen
 */
function navigateToScreen(screenId) {
    // Hide all screens
    const screens = document.querySelectorAll('.screen');
    screens.forEach(screen => {
        screen.classList.remove('active');
    });
    
    // Show target screen
    const targetScreen = document.getElementById(screenId);
    if (targetScreen) {
        targetScreen.classList.add('active');
    }
    
    // Update navigation if applicable
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        const itemScreenId = item.getAttribute('data-screen');
        if (itemScreenId === screenId) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

/**
 * Show home screen
 */
function showHomeScreen() {
    document.getElementById('permission-screen').classList.remove('active');
    document.getElementById('home-screen').classList.add('active');
}

/**
 * Show bus tracking screen
 */
function showBusTrackingScreen(busId) {
    // Get bus data
    const bus = busData[busId];
    if (!bus) return;
    
    // Store bus data in localStorage for track.html to use
    localStorage.setItem('trackingBus', JSON.stringify(bus));
    
    // Navigate to track.html
    window.location.href = 'track.html';
}

/**
 * Initialize new home page
 */
function initNewHomePage() {
    // Bus cards - Show on-this-bus-modal when clicked
    const homeBusCards = document.querySelectorAll('.home-bus-card');
    homeBusCards.forEach(card => {
        card.addEventListener('click', function() {
            const busId = this.getAttribute('data-bus-id');
            const bus = busData[busId];
            if (bus) {
                showOnThisBusModal(bus);
            }
        });
    });
}/**
 * Initialize track page functionality
 */
function initTrackPage() {
    // Bottom sheet functionality
    const bottomSheet = document.querySelector('.timeline-bottom-sheet');
    const handle = document.querySelector('.bottom-sheet-handle');
    
    if (bottomSheet && handle) {
        let isExpanded = false;
        
        handle.addEventListener('click', function() {
            isExpanded = !isExpanded;
            if (isExpanded) {
                bottomSheet.classList.add('expanded');
            } else {
                bottomSheet.classList.remove('expanded');
            }
        });
        
        // Allow dragging the bottom sheet
        let startY = 0;
        let startTransform = 0;
        
        handle.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            startTransform = bottomSheet.getBoundingClientRect().top;
            e.preventDefault();
        });
        
        handle.addEventListener('touchmove', function(e) {
            const deltaY = e.touches[0].clientY - startY;
            const newTransform = Math.max(0, Math.min(window.innerHeight * 0.7, startTransform + deltaY));
            bottomSheet.style.transform = `translateY(${newTransform}px)`;
            e.preventDefault();
        });
        
        handle.addEventListener('touchend', function(e) {
            const sheetHeight = bottomSheet.offsetHeight;
            const viewportHeight = window.innerHeight;
            const threshold = viewportHeight * 0.3;
            
            if (bottomSheet.getBoundingClientRect().top < threshold) {
                bottomSheet.classList.add('expanded');
                isExpanded = true;
            } else {
                bottomSheet.classList.remove('expanded');
                isExpanded = false;
            }
            
            bottomSheet.style.transform = '';
            e.preventDefault();
        });
    }
    
    // Map control buttons
    const centerMapBtn = document.getElementById('center-map');
    if (centerMapBtn) {
        centerMapBtn.addEventListener('click', function() {
            // Center map on bus location
            showNotification('Map centered on bus', 'info');
        });
    }
    
    const zoomInBtn = document.getElementById('zoom-in');
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() {
            // Zoom in map
            showNotification('Zoomed in', 'info');
        });
    }
    
    const zoomOutBtn = document.getElementById('zoom-out');
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() {
            // Zoom out map
            showNotification('Zoomed out', 'info');
        });
    }
    
    // Back button functionality
    const backButton = document.querySelector('#track-screen .back-button');
    if (backButton) {
        backButton.addEventListener('click', function() {
            navigateToScreen('home-screen');
        });
    }
}

// Drawer Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-btn');
    const drawer = document.getElementById('side-drawer');
    const drawerOverlay = document.getElementById('drawer-overlay');
    const drawerClose = document.getElementById('drawer-close');
    const drawerItems = document.querySelectorAll('.drawer-item');
    const navItems = document.querySelectorAll('.nav-item');
    
    // Open drawer when menu button is clicked
    menuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        drawer.classList.add('active');
        drawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when drawer is open
    });
    
    // Close drawer when overlay is clicked
    drawerOverlay.addEventListener('click', function() {
        drawer.classList.remove('active');
        drawerOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scrolling
    });
    
    // Close drawer when close button is clicked
    drawerClose.addEventListener('click', function() {
        drawer.classList.remove('active');
        drawerOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scrolling
    });
    
    // Function to navigate between screens
    function navigateToScreen(screenId) {
        // Hide all screens
        const screens = document.querySelectorAll('.screen');
        screens.forEach(screen => {
            screen.style.display = 'none';
        });
        
        // Show the selected screen
        const selectedScreen = document.getElementById(screenId);
        if (selectedScreen) {
            selectedScreen.style.display = 'block';
        } else {
            console.error(`Screen with ID ${screenId} not found`);
        }
    }
    
    // Function to update page title based on screen
    function updatePageTitle(screenId) {
        let title = 'BUBT Bus Tracker';
        
        switch(screenId) {
            case 'home-screen':
                title = 'Home - BUBT Bus Tracker';
                break;
            case 'chuti-screen':
                title = 'Chuti Kobe - BUBT Bus Tracker';
                break;
            case 'developer-screen':
                title = 'Developer - BUBT Bus Tracker';
                break;
            default:
                break;
        }
        
        document.title = title;
    }
    
    // Handle drawer item clicks
    drawerItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all drawer items
            drawerItems.forEach(i => i.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Get the screen to show
            const screenId = this.getAttribute('data-screen');
            
            // Update bottom nav to match
            navItems.forEach(navItem => {
                if (navItem.getAttribute('data-screen') === screenId) {
                    navItem.classList.add('active');
                } else {
                    navItem.classList.remove('active');
                }
            });
            
            // Close the drawer
            drawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
            document.body.style.overflow = '';
            
            // Show the selected screen
            navigateToScreen(screenId);
            
            // Update page title
            updatePageTitle(screenId);
        });
    });
    
    // Handle bottom nav item clicks
    navItems.forEach(item => {
        if (!item.classList.contains('menu-btn')) { // Skip the menu button
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items
                navItems.forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Get the screen to show
                const screenId = this.getAttribute('data-screen');
                
                // Update drawer items to match
                drawerItems.forEach(drawerItem => {
                    if (drawerItem.getAttribute('data-screen') === screenId) {
                        drawerItem.classList.add('active');
                    } else {
                        drawerItem.classList.remove('active');
                    }
                });
                
                // Show the selected screen
                navigateToScreen(screenId);
                
                // Log screen navigation for analytics
                console.log(`Navigated to screen: ${screenId}`);
                
                // Update page title based on screen
                updatePageTitle(screenId);
            });
        }
    });
});