/**
 * Track Page JavaScript
 * Handles the bus tracking functionality with OpenStreetMap integration
 */

let currentBus = null;
let map = null;
let busMarker = null;
let busPosition = { lat: 23.7937, lng: 90.3629 }; // Default position (Dhaka)

// Initialize the track page
function initTrackPage() {
    console.log('Track page initialized');
    
    // Initialize the map
    initMap();
    
    // Initialize bottom sheet
    initBottomSheet();
    
    // Get the bus data from localStorage if available
    const busId = localStorage.getItem('trackingBusId') || 'B1'; // Default to B1 if none selected
    if (busId) {
        // Get the bus data from the busData object
        fetchBusData(busId);
    } else {
        // If no bus ID is found, show an error message
        showNotification('No bus selected for tracking', 'warning');
        // Redirect back to home page after a delay
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 3000);
    }

    // Set up back button
    const backBtn = document.querySelector('.back-button');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            // Navigate back to home page
            window.location.href = 'index.html';
        });
    }

    // Set up share button
    const shareBtn = document.querySelector('.action-btn:nth-child(2)');
    if (shareBtn) {
        shareBtn.addEventListener('click', () => {
            shareTracking();
        });
    }

    // Set up favorite button
    const starBtn = document.querySelector('.action-btn:nth-child(1)');
    if (starBtn) {
        starBtn.addEventListener('click', (e) => {
            toggleFavorite(e.currentTarget);
        });
    }
    
    // Set up notification subscription button
    const subscribeBtn = document.querySelector('.subscribe-btn');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', () => {
            subscribeToNotifications();
        });
    }
    
    // Set up map control buttons
    const centerMapBtn = document.getElementById('center-map');
    if (centerMapBtn) {
        centerMapBtn.addEventListener('click', () => {
            centerMap();
        });
    }
    
    const zoomInBtn = document.getElementById('zoom-in');
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', () => {
            map.setZoom(map.getZoom() + 1);
        });
    }
    
    const zoomOutBtn = document.getElementById('zoom-out');
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', () => {
            map.setZoom(map.getZoom() - 1);
        });
    }
}

// Fetch bus data for the selected bus
function fetchBusData(busId) {
    // In a real app, this would make an API call to get the latest bus data
    // For this demo, we'll recreate the bus data here
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
    
    // Get the bus data
    currentBus = busData[busId];
    
    if (currentBus) {
        // Update the UI with the bus data
        updateTrackPageUI(currentBus);
        // Start tracking simulation
        startTracking(currentBus);
    } else {
        // If bus data is not found, show an error message
        showNotification('Bus data not found', 'warning');
        // Redirect back to home page after a delay
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 3000);
    }
}

// Update the track page UI with bus data
function updateTrackPageUI(bus) {
    // Update bus info pill
    const busName = document.querySelector('.bus-name');
    const busStatus = document.querySelector('.bus-status span:last-child');
    const busIcon = document.querySelector('.bus-icon');
    const statusIndicator = document.querySelector('.status-indicator');
    
    if (busName) busName.textContent = bus.name;
    if (busIcon) busIcon.textContent = bus.id;
    
    // Update status indicator
    if (statusIndicator && busStatus) {
        statusIndicator.className = 'status-indicator';
        if (bus.status === 'active') {
            statusIndicator.classList.add('status-active');
            busStatus.textContent = `On Route • Arriving in ${bus.arrivalTime}`;
        } else if (bus.status === 'delayed') {
            statusIndicator.classList.add('status-delayed');
            busStatus.textContent = `Delayed • Arriving in ${bus.arrivalTime}`;
        } else {
            statusIndicator.classList.add('status-inactive');
            busStatus.textContent = 'Not Running';
        }
    }
    
    // Update bus info card in bottom sheet
    const currentStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(1) .info-value');
    const nextStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(2) .info-value');
    const speedValue = document.querySelector('.info-row:nth-child(2) .info-item:nth-child(1) .info-value');
    const trafficValue = document.querySelector('.info-row:nth-child(2) .info-item:nth-child(2) .info-value');
    const lastUpdatedValue = document.querySelector('.info-row:nth-child(3) .info-item:nth-child(1) .info-value');
    const tripStatusValue = document.querySelector('.info-row:nth-child(3) .info-item:nth-child(2) .info-value');
    
    if (currentStopValue) currentStopValue.textContent = bus.currentStop || 'N/A';
    if (nextStopValue) nextStopValue.textContent = bus.nextStop || 'N/A';
    if (speedValue) speedValue.textContent = '32 km/h'; // Simulated value
    if (trafficValue) trafficValue.textContent = 'Normal'; // Simulated value
    if (lastUpdatedValue) lastUpdatedValue.textContent = 'Just now';
    if (tripStatusValue) tripStatusValue.textContent = 'On Schedule';
    
    // Update ETA card
    const etaTime = document.querySelector('.eta-time');
    const etaDestination = document.querySelector('.eta-destination');
    if (etaTime) etaTime.textContent = bus.arrivalTime || 'N/A';
    if (etaDestination) etaDestination.textContent = `To ${bus.nextStop}`;
    
    // Update map marker with bus ID
    if (busMarker) {
        const busIcon = L.divIcon({
            className: 'bus-marker-icon',
            html: `<div class="marker-icon">${bus.id}</div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
        busMarker.setIcon(busIcon);
    }
    
    // Update route timeline
    updateRouteTimeline(bus);
}

// Update the route timeline with stops
function updateRouteTimeline(bus) {
    const timeline = document.querySelector('.timeline');
    if (!timeline) return;
    
    // Clear existing timeline items
    timeline.innerHTML = '';
    
    // Find the index of the current stop
    const currentStopIndex = bus.route.indexOf(bus.currentStop);
    
    // Create timeline stops
    bus.route.forEach((stop, index) => {
        // Determine stop status
        let status = '';
        let progressHTML = '';
        let statusText = '';
        
        if (index < currentStopIndex) {
            status = 'completed';
            statusText = 'Departed';
        } else if (index === currentStopIndex) {
            status = 'current';
            statusText = 'Current location';
            progressHTML = `
                <div class="progress-bar">
                    <div class="progress" style="width: 70%"></div>
                </div>
            `;
        } else if (index === currentStopIndex + 1) {
            statusText = `Arriving in ${bus.arrivalTime}`;
        } else {
            statusText = 'Estimated arrival';
        }
        
        // Calculate estimated time (for demo purposes)
        const now = new Date();
        let estimatedTime;
        
        if (index < currentStopIndex) {
            // Past stops (subtract 15 minutes for each previous stop)
            estimatedTime = new Date(now.getTime() - ((currentStopIndex - index) * 15 * 60000));
        } else if (index === currentStopIndex) {
            // Current stop
            estimatedTime = now;
        } else {
            // Future stops (add 15 minutes for each upcoming stop)
            estimatedTime = new Date(now.getTime() + ((index - currentStopIndex) * 15 * 60000));
        }
        
        const timeString = estimatedTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        // Create timeline item HTML
        const timelineItem = document.createElement('div');
        timelineItem.className = `timeline-item ${status}`;
        timelineItem.innerHTML = `
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <div class="time-badge">${timeString}</div>
                <h4>${stop}</h4>
                <p>${statusText}</p>
                ${progressHTML}
            </div>
        `;
        
        timeline.appendChild(timelineItem);
    });
    
    // Update route stats
    const routeStats = document.querySelector('.route-stats');
    if (routeStats) {
        routeStats.textContent = `${bus.route.length} stops • 12.5 km • 60 min`;
    }
}

// Start tracking simulation
function startTracking(bus) {
    // Set initial bus position based on the current stop
    // In a real app, this would use actual GPS coordinates
    // For demo, we'll use a predefined position for Dhaka
    const busLocations = {
        'Asad Gate': { lat: 23.7651, lng: 90.3668 },
        'Shyamoli': { lat: 23.7746, lng: 90.3657 },
        'Mirpur-1': { lat: 23.7937, lng: 90.3629 },
        'Rainkhola': { lat: 23.8069, lng: 90.3554 },
        'BUBT': { lat: 23.8213, lng: 90.3541 },
        'Hemayetpur': { lat: 23.7784, lng: 90.3144 },
        'Amin Bazar': { lat: 23.7868, lng: 90.3347 },
        'Gabtoli': { lat: 23.7830, lng: 90.3412 },
        'Agargaon': { lat: 23.7781, lng: 90.3795 },
        'Kazipara': { lat: 23.7964, lng: 90.3750 },
        'Mirpur-10': { lat: 23.8071, lng: 90.3687 },
        'Mirpur-14': { lat: 23.8116, lng: 90.3664 },
        'Mirpur-11': { lat: 23.8179, lng: 90.3650 },
        'Proshikha': { lat: 23.8230, lng: 90.3580 },
        'ECB Chattar': { lat: 23.8334, lng: 90.3679 },
        'Kalshi Bridge': { lat: 23.8280, lng: 90.3750 },
        'Mirpur-12': { lat: 23.8280, lng: 90.3650 },
        'Duaripara': { lat: 23.8230, lng: 90.3580 }
    };
    
    // Set initial position based on current stop
    if (bus.currentStop && busLocations[bus.currentStop]) {
        busPosition = busLocations[bus.currentStop];
        
        if (busMarker && map) {
            busMarker.setLatLng([busPosition.lat, busPosition.lng]);
            map.setView([busPosition.lat, busPosition.lng], 15);
        }
    }
    
    // Simulate movement towards next stop
    if (bus.nextStop && busLocations[bus.nextStop]) {
        const targetPosition = busLocations[bus.nextStop];
        const movementInterval = setInterval(() => {
            // Calculate direction vector
            const dx = (targetPosition.lat - busPosition.lat) / 20;
            const dy = (targetPosition.lng - busPosition.lng) / 20;
            
            // Move a small step towards the target
            busPosition.lat += dx;
            busPosition.lng += dy;
            
            // Add some randomness to simulate real movement
            busPosition.lat += (Math.random() - 0.5) * 0.0002;
            busPosition.lng += (Math.random() - 0.5) * 0.0002;
            
            // Update the map
            updateMapPosition();
        }, 3000);
    }
}

// Initialize the map with OpenStreetMap and Leaflet
function initMap() {
    // Check if map is already initialized
    if (map) {
        console.log('Map already initialized');
        return;
    }
    
    // Create a map centered on Dhaka, Bangladesh
    try {
        // Determine zoom level based on screen size
        let initialZoom = 13;
        if (window.innerWidth < 576) {
            initialZoom = 12; // Smaller zoom for mobile devices
        } else if (window.innerWidth >= 992) {
            initialZoom = 14; // Larger zoom for desktops
        }
        
        map = L.map('map').setView([23.7937, 90.3629], initialZoom);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Create a custom bus icon with responsive size
        const iconSize = window.innerWidth < 576 ? 36 : 40;
        const busIcon = L.divIcon({
            className: 'bus-marker-icon',
            html: `<div class="marker-icon">${currentBus ? currentBus.id : 'B1'}</div>`,
            iconSize: [iconSize, iconSize],
            iconAnchor: [iconSize/2, iconSize/2]
        });
        
        // Add a marker for the bus
        busMarker = L.marker([busPosition.lat, busPosition.lng], { icon: busIcon }).addTo(map);
        
        // Add a circle to show the accuracy radius
        const accuracyRadius = window.innerWidth < 576 ? 400 : 500;
        L.circle([busPosition.lat, busPosition.lng], {
            color: 'rgba(0, 123, 255, 0.2)',
            fillColor: 'rgba(0, 123, 255, 0.1)',
            fillOpacity: 0.5,
            radius: accuracyRadius
        }).addTo(map);
        
        // Handle window resize events
        window.addEventListener('resize', function() {
            // Adjust map view on resize
            map.invalidateSize();
            
            // Center the map on the bus position
            centerMap();
        });
        
        console.log('Map initialized successfully');
    } catch (error) {
        console.error('Error initializing map:', error);
    }
}

// Initialize the bottom sheet
function initBottomSheet() {
    const bottomSheet = document.querySelector('.bottom-sheet');
    const handle = document.querySelector('.bottom-sheet-handle');
    
    if (bottomSheet && handle) {
        let isExpanded = false;
        
        // Set initial position based on screen size
        const setInitialPosition = () => {
            // For small screens, show less of the bottom sheet initially
            if (window.innerWidth < 576) {
                bottomSheet.style.transform = 'translateY(calc(100% - 100px))';
            } else if (window.innerWidth >= 992) {
                // For larger screens, show more of the bottom sheet
                bottomSheet.style.transform = 'translateY(calc(100% - 140px))';
            } else {
                // Default for medium screens
                bottomSheet.style.transform = 'translateY(calc(100% - 120px))';
            }
        };
        
        // Set initial position on load
        setInitialPosition();
        
        // Update position on window resize
        window.addEventListener('resize', setInitialPosition);
        
        // Toggle bottom sheet on handle click
        handle.addEventListener('click', function() {
            isExpanded = !isExpanded;
            if (isExpanded) {
                bottomSheet.classList.add('expanded');
            } else {
                bottomSheet.classList.remove('expanded');
                // Reset to initial position when collapsed
                setInitialPosition();
            }
        });
        
        // Allow dragging the bottom sheet
        let startY = 0;
        let startHeight = 0;
        
        handle.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            startHeight = parseInt(window.getComputedStyle(bottomSheet).height);
            document.body.style.overflow = 'hidden'; // Prevent scrolling while dragging
        });
        
        handle.addEventListener('touchmove', function(e) {
            const deltaY = e.touches[0].clientY - startY;
            const newHeight = Math.max(100, startHeight - deltaY);
            
            // Adjust max height based on screen size
            let maxHeight;
            if (window.innerHeight < 600) {
                maxHeight = window.innerHeight * 0.6; // Smaller screens
            } else if (window.innerHeight > 900) {
                maxHeight = window.innerHeight * 0.7; // Larger screens
            } else {
                maxHeight = window.innerHeight * 0.65; // Medium screens
            }
            
            if (newHeight <= maxHeight) {
                bottomSheet.style.height = newHeight + 'px';
            }
        });
        
        handle.addEventListener('touchend', function() {
            document.body.style.overflow = ''; // Re-enable scrolling
            
            // Snap to positions based on screen size
            const currentHeight = parseInt(window.getComputedStyle(bottomSheet).height);
            const windowHeight = window.innerHeight;
            
            if (currentHeight < windowHeight * 0.25) {
                // Collapse
                bottomSheet.classList.remove('expanded');
                bottomSheet.style.height = '';
                isExpanded = false;
            } else if (currentHeight > windowHeight * 0.6) {
                // Expand
                bottomSheet.classList.add('expanded');
                bottomSheet.style.height = '';
                isExpanded = true;
            }
        });
    }
}

// Center the map on the bus position
function centerMap() {
    if (map && busMarker) {
        // Adjust zoom level based on screen size
        let zoomLevel = 15;
        if (window.innerWidth < 576) {
            zoomLevel = 14; // Less zoom on small screens for better context
        } else if (window.innerWidth >= 992) {
            zoomLevel = 16; // More zoom on large screens for detail
        }
        
        // Get current bus position
        const busPosition = busMarker.getLatLng();
        
        // Calculate offset to account for UI elements
        let offsetY = 0;
        
        // On mobile, offset to account for bottom sheet
        if (window.innerWidth < 576) {
            offsetY = -0.003; // Slight offset upward
        }
        
        // Create a new position with the offset
        const adjustedPosition = L.latLng(
            busPosition.lat + offsetY,
            busPosition.lng
        );
        
        // Set the view with the adjusted position
        map.setView(adjustedPosition, zoomLevel);
    }
}

// Update map position with real-time bus location
function updateMapPosition(change) {
    if (!map || !busMarker) return;
    
    // In a real app, this would use actual GPS coordinates
    // For demo, we'll just make small random movements
    busPosition.lat += (Math.random() - 0.5) * 0.001;
    busPosition.lng += (Math.random() - 0.5) * 0.001;
    
    // Update the marker position
    busMarker.setLatLng([busPosition.lat, busPosition.lng]);
    
    // Update the accuracy circle
    const circles = document.querySelectorAll('.leaflet-interactive');
    if (circles.length > 1) {
        const circle = circles[1]; // The second SVG path is our circle
        if (circle && circle._latlng) {
            circle.setLatLng([busPosition.lat, busPosition.lng]);
        }
    }
    
    // Update the bus icon based on screen size
    updateBusIcon();
    
    // Keep the map centered on the bus if auto-follow is enabled
    // This could be toggled by a button in a real app
    const autoFollow = true; // This could be a user preference
    if (autoFollow) {
        // Don't use centerMap() directly to avoid jarring zoom changes
        // Instead, smoothly pan to the new position
        map.panTo([busPosition.lat, busPosition.lng]);
    }
    
    // Update UI elements with new information
    updateBusInfo();
}

// Update the bus icon based on screen size
function updateBusIcon() {
    if (!busMarker) return;
    
    // Determine icon size based on screen width
    const iconSize = window.innerWidth < 576 ? 36 : 40;
    
    // Create a new icon with the appropriate size
    const busIcon = L.divIcon({
        className: 'bus-marker-icon',
        html: `<div class="marker-icon">${currentBus ? currentBus.id : 'B1'}</div>`,
        iconSize: [iconSize, iconSize],
        iconAnchor: [iconSize/2, iconSize/2]
    });
    
    // Update the marker's icon
    busMarker.setIcon(busIcon);
}

// Update bus information in the UI
function updateBusInfo() {
    if (!currentBus) return;
    
    // Update ETA information
    const etaTime = document.querySelector('.eta-time');
    if (etaTime) {
        // In a real app, this would be calculated based on distance and speed
        const minutes = Math.floor(Math.random() * 15) + 5;
        etaTime.textContent = `${minutes} min`;
    }
    
    // Update bus status information
    const busStatus = document.querySelector('.bus-status span:last-child');
    if (busStatus) {
        busStatus.textContent = `On Route • Arriving in ${etaTime ? etaTime.textContent : '10 min'}`;
    }
}

// Share tracking information
function shareTracking() {
    if (!currentBus) return;
    
    // Create a shareable message
    const shareMessage = `I'm tracking ${currentBus.name} (${currentBus.id}) on the BUBT Bus Tracker. Current location: ${currentBus.currentStop}, Next stop: ${currentBus.nextStop}`;
    
    // In a real app, this would use the Web Share API or a custom sharing solution
    alert('Sharing: ' + shareMessage);
}

// Toggle favorite status
function toggleFavorite(button) {
    button.classList.toggle('active');
    
    if (button.classList.contains('active')) {
        // Add to favorites
        button.querySelector('i').className = 'fas fa-star';
        showNotification('Bus added to favorites', 'success');
    } else {
        // Remove from favorites
        button.querySelector('i').className = 'far fa-star';
        showNotification('Bus removed from favorites', 'warning');
    }
}

// Subscribe to notifications
function subscribeToNotifications() {
    const subscribeBtn = document.querySelector('.subscribe-btn');
    
    if (subscribeBtn) {
        subscribeBtn.classList.toggle('subscribed');
        
        if (subscribeBtn.classList.contains('subscribed')) {
            subscribeBtn.textContent = 'Unsubscribe from Notifications';
            showNotification('You will receive notifications for this bus', 'success');
        } else {
            subscribeBtn.textContent = 'Subscribe to Notifications';
            showNotification('Notifications disabled for this bus', 'warning');
        }
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide and remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Initialize track page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize the track page
    initTrackPage();
});