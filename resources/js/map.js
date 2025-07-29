/**
 * Livewire-compatible Map initialization and bus tracking functionality
 */

// Global variables
let mapOptimizer = null;
let clusterGroup = null;
let livewireMapInstance = null;

// Livewire-compatible map initialization
function initMap() {
    // Check if we're in a Livewire environment
    const isLivewire = window.Livewire && document.querySelector('[wire\\:id]');
    
    if (isLivewire) {
        // Use Livewire-compatible initialization
        initLivewireMap();
    } else {
        // Use original initialization for non-Livewire pages
        initStandardMap();
    }
}

// Initialize map for Livewire components
function initLivewireMap() {
    // Check if map container exists
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.warn('Map container not found for Livewire initialization');
        return;
    }
    
    // Remove existing map if it exists
    if (window.map) {
        window.map.remove();
        window.map = null;
    }
    
    try {
        // Create map with Livewire-compatible settings
        window.map = L.map('map', {
            zoomControl: false,
            attributionControl: true
        }).setView([23.7937, 90.3629], 14);
        
        // Add tile layer with error handling
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
            errorTileUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE4IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gVGlsZTwvdGV4dD48L3N2Zz4='
        }).addTo(window.map);
        
        // Initialize performance optimizations if available
        if (window.MapPerformanceOptimizer) {
            mapOptimizer = new window.MapPerformanceOptimizer();
            mapOptimizer.optimizeForLivewire(window.map);
        }
        
        console.log('Livewire map initialized successfully');
        
    } catch (error) {
        console.error('Error initializing Livewire map:', error);
        showMapError();
    }
}

// Initialize standard map for non-Livewire pages
function initStandardMap() {
    // Initialize performance optimizer
    if (window.MapPerformanceOptimizer) {
        mapOptimizer = new window.MapPerformanceOptimizer();
        
        // Get optimized map options
        const mapOptions = mapOptimizer.getOptimizedMapOptions();
        
        // Create map with optimized settings
        window.map = L.map('map', mapOptions).setView([23.7937, 90.3629], mapOptions.minZoom + 2);

        // Add optimized tile layer with caching
        const tileLayer = mapOptimizer.createOptimizedTileLayer();
        tileLayer.addTo(window.map);
        
        // Create marker clustering for multiple buses
        clusterGroup = mapOptimizer.createMarkerCluster();
        window.map.addLayer(clusterGroup);
        
        // Enable lazy loading and progressive enhancement
        mapOptimizer.enableLazyTileLoading(window.map);
        mapOptimizer.enableProgressiveEnhancement(window.map);
        
        // Preload frequently accessed areas
        mapOptimizer.preloadFrequentAreas();
        
        // Add connection quality indicator
        addConnectionQualityIndicator();
        
        // Add loading indicator
        addMapLoadingIndicator();

        // Create optimized bus marker
        const busMarker = mapOptimizer.createOptimizedBusMarker('B1', [window.busPosition.lat, window.busPosition.lng]);
        
        // Add bus marker to cluster group for better performance
        clusterGroup.addLayer(busMarker);

        // Add a popup to the bus marker
        busMarker.bindPopup('<b>Bus B1: Buriganga</b><br>On route to Mirpur-1');

        // Simulate bus movement for demo purposes
        simulateBusMovement();
    } else {
        // Fallback to basic map initialization
        window.map = L.map('map', {
            zoomControl: false
        }).setView([23.7937, 90.3629], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(window.map);
    }
}

// Livewire-compatible center map function
function centerMap() {
    const map = window.map;
    const busMarker = window.busMarker;
    
    if (map && busMarker) {
        const deviceType = mapOptimizer ? mapOptimizer.getDeviceType() : 'desktop';
        let zoomLevel = 15;
        
        // Adjust zoom based on device
        if (deviceType === 'mobile') {
            zoomLevel = 14;
        } else if (deviceType === 'desktop') {
            zoomLevel = 16;
        }
        
        // Smooth pan to location
        map.setView(busMarker.getLatLng(), zoomLevel, {
            animate: true,
            duration: 0.5
        });
    } else if (map) {
        // Fallback to default position if no marker
        const defaultPosition = window.busPosition || { lat: 23.7937, lng: 90.3629 };
        map.setView([defaultPosition.lat, defaultPosition.lng], 15, {
            animate: true,
            duration: 0.5
        });
    }
}

// Optimized bus movement simulation
function simulateBusMovement() {
    // Define route coordinates (simplified for demo)
    const route = [
        { lat: 23.7651, lng: 90.3668 }, // Asad Gate
        { lat: 23.7696, lng: 90.3662 }, // In between
        { lat: 23.7746, lng: 90.3657 }, // Shyamoli
        { lat: 23.7842, lng: 90.3643 }, // In between
        { lat: 23.7937, lng: 90.3629 }, // Mirpur-1
        { lat: 23.8069, lng: 90.3554 }, // Rainkhola
        { lat: 23.8213, lng: 90.3541 }  // BUBT
    ];

    // Start from current position in the route
    let currentStopIndex = 2; // Starting from Shyamoli in this demo

    // Update bus position every few seconds with optimization
    const updateInterval = setInterval(() => {
        // Move to next point if not at the end
        if (currentStopIndex < route.length - 1) {
            currentStopIndex += 0.1; // Move gradually between points

            // Interpolate position between points
            const currentIndex = Math.floor(currentStopIndex);
            const nextIndex = Math.min(currentIndex + 1, route.length - 1);
            const fraction = currentStopIndex - currentIndex;

            // Calculate interpolated position
            const lat = route[currentIndex].lat + fraction * (route[nextIndex].lat - route[currentIndex].lat);
            const lng = route[currentIndex].lng + fraction * (route[nextIndex].lng - route[currentIndex].lng);

            // Update bus position
            busPosition = { lat, lng };

            // Update marker position using optimizer
            if (mapOptimizer) {
                mapOptimizer.updateBusMarkerPosition('B1', [lat, lng], true);
            } else if (busMarker) {
                busMarker.setLatLng([lat, lng]);
            }

            // Update the UI with new information
            updateBusInfo(currentStopIndex, route);
        } else {
            // Reached the end, reset to beginning for demo purposes
            currentStopIndex = 0;
        }
    }, 3000); // Update every 3 seconds
    
    // Store interval reference for cleanup
    window.busMovementInterval = updateInterval;
}

// Update bus information based on current position
function updateBusInfo(currentStopIndex, route) {
    // Calculate which stop we're approaching
    const currentIndex = Math.floor(currentStopIndex);
    const nextIndex = Math.min(currentIndex + 1, route.length - 1);

    // Update ETA based on position between stops
    const fraction = currentStopIndex - currentIndex;
    const etaMinutes = Math.round((1 - fraction) * 10);

    // Map route indices to stop names
    const stopNames = ['Asad Gate', 'In between', 'Shyamoli', 'In between', 'Mirpur-1', 'Rainkhola', 'BUBT'];

    // Update ETA card
    const etaTime = document.querySelector('.eta-time');
    const etaDestination = document.querySelector('.eta-destination');
    if (etaTime && nextIndex < stopNames.length) {
        etaTime.textContent = etaMinutes > 0 ? `${etaMinutes} min` : 'Arriving';
    }
    if (etaDestination && nextIndex < stopNames.length) {
        etaDestination.textContent = `To ${stopNames[nextIndex]}`;
    }

    // Update bus status text
    const busStatus = document.querySelector('.bus-status span:last-child');
    if (busStatus) {
        busStatus.textContent = `On Route • Arriving in ${etaMinutes} min`;
    }

    // Update current and next stop in info card
    const currentStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(1) .info-value');
    const nextStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(2) .info-value');

    if (currentStopValue && currentIndex < stopNames.length) {
        currentStopValue.textContent = stopNames[currentIndex];
    }
    if (nextStopValue && nextIndex < stopNames.length) {
        nextStopValue.textContent = stopNames[nextIndex];
    }
}

// Add connection quality indicator
function addConnectionQualityIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'connection-quality-indicator excellent';
    indicator.title = 'Connection Quality: Excellent';
    
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        mapContainer.appendChild(indicator);
    }
    
    // Monitor connection quality
    monitorConnectionQuality(indicator);
}

// Monitor connection quality and update indicator
function monitorConnectionQuality(indicator) {
    const updateQuality = () => {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (!navigator.onLine) {
            indicator.className = 'connection-quality-indicator offline';
            indicator.title = 'Connection Quality: Offline';
            showOfflineIndicator(true);
        } else if (connection) {
            const effectiveType = connection.effectiveType;
            let quality = 'excellent';
            let title = 'Connection Quality: Excellent';
            
            switch (effectiveType) {
                case 'slow-2g':
                case '2g':
                    quality = 'poor';
                    title = 'Connection Quality: Poor';
                    break;
                case '3g':
                    quality = 'fair';
                    title = 'Connection Quality: Fair';
                    break;
                case '4g':
                    quality = 'good';
                    title = 'Connection Quality: Good';
                    break;
            }
            
            indicator.className = `connection-quality-indicator ${quality}`;
            indicator.title = title;
            showOfflineIndicator(false);
        } else {
            indicator.className = 'connection-quality-indicator excellent';
            indicator.title = 'Connection Quality: Excellent';
            showOfflineIndicator(false);
        }
    };
    
    // Initial check
    updateQuality();
    
    // Listen for connection changes
    window.addEventListener('online', updateQuality);
    window.addEventListener('offline', updateQuality);
    
    if (navigator.connection) {
        navigator.connection.addEventListener('change', updateQuality);
    }
}

// Show/hide offline indicator
function showOfflineIndicator(show) {
    let indicator = document.querySelector('.map-offline-indicator');
    
    if (show && !indicator) {
        indicator = document.createElement('div');
        indicator.className = 'map-offline-indicator active';
        indicator.textContent = 'Map tiles may be limited offline';
        
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.appendChild(indicator);
        }
    } else if (!show && indicator) {
        indicator.remove();
    }
}

// Add map loading indicator
function addMapLoadingIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'map-loading-indicator';
    indicator.innerHTML = `
        <div class="loading-spinner"></div>
        Loading map tiles...
    `;
    
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        mapContainer.appendChild(indicator);
    }
    
    // Show loading indicator during tile loading
    if (map) {
        map.on('loading', () => {
            indicator.classList.add('active');
        });
        
        map.on('load', () => {
            indicator.classList.remove('active');
        });
        
        map.on('tileloadstart', () => {
            indicator.classList.add('active');
        });
        
        map.on('tileload', () => {
            // Check if all tiles are loaded
            setTimeout(() => {
                const loadingTiles = document.querySelectorAll('.leaflet-tile-loading');
                if (loadingTiles.length === 0) {
                    indicator.classList.remove('active');
                }
            }, 100);
        });
    }
}

// Show map error for Livewire compatibility
function showMapError() {
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div class="map-error">
                <div class="error-content">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h4>Map Loading Error</h4>
                    <p>Unable to load the map. Please check your internet connection and try again.</p>
                    <button class="btn btn-primary" onclick="location.reload()">Retry</button>
                </div>
            </div>
        `;
    }
}



// Cleanup function to prevent memory leaks
function cleanupMap() {
    // Clear movement interval
    if (window.busMovementInterval) {
        clearInterval(window.busMovementInterval);
        window.busMovementInterval = null;
    }
    
    // Cleanup optimizer
    if (mapOptimizer) {
        mapOptimizer.cleanup();
        mapOptimizer = null;
    }
    
    // Remove map
    if (window.map) {
        window.map.remove();
        window.map = null;
    }
}

// Initialize map on DOM ready and Livewire navigation
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if map container exists
    if (document.getElementById('map')) {
        initMap();
    }
});

document.addEventListener('livewire:navigated', function() {
    // Re-initialize map after Livewire navigation
    setTimeout(() => {
        if (document.getElementById('map')) {
            initMap();
        }
    }, 100);
});

// Call cleanup when page unloads
window.addEventListener('beforeunload', cleanupMap);