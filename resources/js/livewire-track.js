/**
 * Livewire-compatible Track Page JavaScript
 * Adapts existing track.js functionality to work with Livewire components
 */

class LivewireTracker {
    constructor() {
        this.currentBus = null;
        this.map = null;
        this.busMarker = null;
        this.busPosition = { lat: 23.7937, lng: 90.3629 }; // Default position (Dhaka)
        this.isInitialized = false;
        this.trackingInterval = null;
        this.bottomSheetState = {
            isExpanded: false,
            isDragging: false
        };
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        // Initialize on DOM ready and Livewire navigation
        document.addEventListener('DOMContentLoaded', () => this.initializeTracker());
        document.addEventListener('livewire:navigated', () => this.initializeTracker());
        
        this.isInitialized = true;
    }

    initializeTracker() {
        console.log('Livewire Tracker initialized');
        
        // Initialize components
        this.initMap();
        this.initBottomSheet();
        this.initMapControls();
        this.initActionButtons();
        
        // Setup Livewire event listeners
        this.setupLivewireListeners();
        
        // Get bus data from Livewire component or fallback
        this.initializeBusData();
    }

    initMap() {
        // Check if map container exists
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.warn('Map container not found');
            return;
        }

        // Remove existing map if it exists
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        
        try {
            // Determine zoom level based on screen size
            let initialZoom = 13;
            if (window.innerWidth < 576) {
                initialZoom = 12;
            } else if (window.innerWidth >= 992) {
                initialZoom = 14;
            }
            
            // Create map with Livewire-compatible settings
            this.map = L.map('map', {
                zoomControl: false,
                attributionControl: true
            }).setView([this.busPosition.lat, this.busPosition.lng], initialZoom);
            
            // Add OpenStreetMap tile layer with error handling
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
                errorTileUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE4IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gVGlsZTwvdGV4dD48L3N2Zz4='
            });
            
            tileLayer.addTo(this.map);
            
            // Create bus marker
            this.createBusMarker();
            
            // Handle window resize events
            window.addEventListener('resize', () => {
                if (this.map) {
                    this.map.invalidateSize();
                    this.centerMap();
                }
            });
            
            console.log('Map initialized successfully');
            
        } catch (error) {
            console.error('Error initializing map:', error);
            this.showMapError();
        }
    }

    createBusMarker() {
        if (!this.map) return;
        
        // Get bus ID from Livewire component or fallback
        const busId = this.getBusId();
        
        // Create custom bus icon
        const iconSize = window.innerWidth < 576 ? 36 : 40;
        const busIcon = L.divIcon({
            className: 'bus-marker-icon',
            html: `<div class="marker-icon">${busId}</div>`,
            iconSize: [iconSize, iconSize],
            iconAnchor: [iconSize/2, iconSize/2]
        });
        
        // Add marker (hidden as we use overlay pin)
        this.busMarker = L.marker([this.busPosition.lat, this.busPosition.lng], { 
            icon: busIcon,
            opacity: 0
        }).addTo(this.map);
        
        // Add accuracy circle
        const accuracyRadius = window.innerWidth < 576 ? 400 : 500;
        L.circle([this.busPosition.lat, this.busPosition.lng], {
            color: 'rgba(0, 123, 255, 0.2)',
            fillColor: 'rgba(0, 123, 255, 0.1)',
            fillOpacity: 0.5,
            radius: accuracyRadius
        }).addTo(this.map);
    }

    initBottomSheet() {
        const bottomSheet = document.querySelector('.bottom-sheet');
        const handle = document.querySelector('.bottom-sheet-handle');
        
        if (!bottomSheet || !handle) return;
        
        // Set initial position
        this.setInitialBottomSheetPosition();
        
        // Handle click to toggle
        handle.removeEventListener('click', this.toggleBottomSheet);
        handle.addEventListener('click', this.toggleBottomSheet.bind(this));
        
        // Handle drag events for mobile and desktop
        this.initBottomSheetDrag(handle, bottomSheet);
        
        // Update position on window resize
        window.addEventListener('resize', () => {
            if (!this.bottomSheetState.isExpanded) {
                this.setInitialBottomSheetPosition();
            }
        });
    }

    setInitialBottomSheetPosition() {
        const bottomSheet = document.querySelector('.bottom-sheet');
        if (!bottomSheet) return;
        
        // Set position based on screen size
        if (window.innerWidth < 576) {
            bottomSheet.style.transform = 'translateY(calc(100% - 100px))';
        } else if (window.innerWidth >= 992) {
            bottomSheet.style.transform = 'translateY(calc(100% - 140px))';
        } else {
            bottomSheet.style.transform = 'translateY(calc(100% - 120px))';
        }
    }

    toggleBottomSheet() {
        const bottomSheet = document.querySelector('.bottom-sheet');
        const handleIndicator = document.querySelector('.handle-indicator');
        
        if (!bottomSheet) return;
        
        this.bottomSheetState.isExpanded = !this.bottomSheetState.isExpanded;
        
        if (this.bottomSheetState.isExpanded) {
            bottomSheet.classList.add('expanded');
            bottomSheet.style.transform = 'translateY(0)';
            if (handleIndicator) {
                handleIndicator.textContent = 'Drag to collapse';
            }
        } else {
            bottomSheet.classList.remove('expanded');
            this.setInitialBottomSheetPosition();
            if (handleIndicator) {
                handleIndicator.textContent = 'Drag to expand';
            }
        }
    }

    initBottomSheetDrag(handle, bottomSheet) {
        let startY = 0;
        let startHeight = 0;
        let startTransform = 0;
        
        // Touch events for mobile
        handle.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
            startHeight = parseInt(window.getComputedStyle(bottomSheet).height);
            const transform = window.getComputedStyle(bottomSheet).transform;
            startTransform = transform !== 'none' ? parseInt(transform.split(',')[5]) : 0;
            this.bottomSheetState.isDragging = true;
            document.body.style.overflow = 'hidden';
        });
        
        handle.addEventListener('touchmove', (e) => {
            if (!this.bottomSheetState.isDragging) return;
            
            const deltaY = e.touches[0].clientY - startY;
            this.handleBottomSheetDrag(deltaY, startHeight, startTransform, bottomSheet);
        });
        
        handle.addEventListener('touchend', () => {
            this.endBottomSheetDrag(bottomSheet);
        });
        
        // Mouse events for desktop
        handle.addEventListener('mousedown', (e) => {
            startY = e.clientY;
            startHeight = parseInt(window.getComputedStyle(bottomSheet).height);
            const transform = window.getComputedStyle(bottomSheet).transform;
            startTransform = transform !== 'none' ? parseInt(transform.split(',')[5]) : 0;
            this.bottomSheetState.isDragging = true;
            document.body.style.overflow = 'hidden';
            
            const handleMouseMove = (e) => {
                if (!this.bottomSheetState.isDragging) return;
                const deltaY = e.clientY - startY;
                this.handleBottomSheetDrag(deltaY, startHeight, startTransform, bottomSheet);
            };
            
            const handleMouseUp = () => {
                this.endBottomSheetDrag(bottomSheet);
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };
            
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
    }

    handleBottomSheetDrag(deltaY, startHeight, startTransform, bottomSheet) {
        const windowHeight = window.innerHeight;
        
        if (this.bottomSheetState.isExpanded) {
            const newHeight = Math.max(100, startHeight - deltaY);
            const maxHeight = windowHeight * (windowHeight < 600 ? 0.6 : windowHeight > 900 ? 0.7 : 0.65);
            
            if (newHeight <= maxHeight) {
                bottomSheet.style.height = newHeight + 'px';
            }
            
            if (deltaY > 100) {
                this.bottomSheetState.isExpanded = false;
                bottomSheet.classList.remove('expanded');
                this.setInitialBottomSheetPosition();
                bottomSheet.style.height = '';
                this.bottomSheetState.isDragging = false;
            }
        } else {
            const newTransform = Math.min(0, startTransform - deltaY);
            
            if (deltaY < -50) {
                this.bottomSheetState.isExpanded = true;
                bottomSheet.classList.add('expanded');
                bottomSheet.style.transform = 'translateY(0)';
                bottomSheet.style.height = '';
                this.bottomSheetState.isDragging = false;
            } else {
                bottomSheet.style.transform = `translateY(${newTransform}px)`;
            }
        }
    }

    endBottomSheetDrag(bottomSheet) {
        document.body.style.overflow = '';
        this.bottomSheetState.isDragging = false;
        
        if (!this.bottomSheetState.isExpanded) {
            this.setInitialBottomSheetPosition();
            bottomSheet.style.height = '';
        }
    }

    initMapControls() {
        const centerMapBtn = document.getElementById('center-map');
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');

        if (centerMapBtn) {
            centerMapBtn.removeEventListener('click', this.centerMap);
            centerMapBtn.addEventListener('click', this.centerMap.bind(this));
        }

        if (zoomInBtn) {
            zoomInBtn.removeEventListener('click', this.zoomIn);
            zoomInBtn.addEventListener('click', this.zoomIn.bind(this));
        }

        if (zoomOutBtn) {
            zoomOutBtn.removeEventListener('click', this.zoomOut);
            zoomOutBtn.addEventListener('click', this.zoomOut.bind(this));
        }
    }

    centerMap() {
        if (this.map && this.busMarker) {
            let zoomLevel = 15;
            if (window.innerWidth < 576) {
                zoomLevel = 14;
            } else if (window.innerWidth >= 992) {
                zoomLevel = 16;
            }
            
            const busPosition = this.busMarker.getLatLng();
            let offsetY = 0;
            
            if (window.innerWidth < 576) {
                offsetY = -0.003;
            }
            
            const adjustedPosition = L.latLng(
                busPosition.lat + offsetY,
                busPosition.lng
            );
            
            this.map.setView(adjustedPosition, zoomLevel);
        }
    }

    zoomIn() {
        if (this.map) {
            this.map.setZoom(this.map.getZoom() + 1);
        }
    }

    zoomOut() {
        if (this.map) {
            this.map.setZoom(this.map.getZoom() - 1);
        }
    }

    initActionButtons() {
        // Back button
        const backBtn = document.querySelector('.back-button');
        if (backBtn) {
            backBtn.removeEventListener('click', this.handleBackButton);
            backBtn.addEventListener('click', this.handleBackButton.bind(this));
        }

        // Share button
        const shareBtn = document.querySelector('.quick-btn[title="Share"]');
        if (shareBtn) {
            shareBtn.removeEventListener('click', this.handleShare);
            shareBtn.addEventListener('click', this.handleShare.bind(this));
        }

        // Favorite button
        const favoriteBtn = document.querySelector('.quick-btn[title="Favorite"]');
        if (favoriteBtn) {
            favoriteBtn.removeEventListener('click', this.handleFavorite);
            favoriteBtn.addEventListener('click', this.handleFavorite.bind(this));
        }

        // Subscribe button
        const subscribeBtn = document.querySelector('.subscribe-btn');
        if (subscribeBtn) {
            subscribeBtn.removeEventListener('click', this.handleSubscribe);
            subscribeBtn.addEventListener('click', this.handleSubscribe.bind(this));
        }
    }

    handleBackButton() {
        // Use Livewire navigation if available
        if (window.Livewire) {
            // Dispatch Livewire event or navigate directly
            const livewireComponent = document.querySelector('[wire\\:id]');
            if (livewireComponent) {
                // Try to dispatch navigate-back event
                window.Livewire.dispatch('navigate-back');
            } else {
                // Fallback navigation
                window.location.href = '/';
            }
        } else {
            // Fallback for non-Livewire environments
            window.location.href = 'index.html';
        }
    }

    handleShare() {
        const busId = this.getBusId();
        const shareData = {
            title: `BUBT Bus ${busId} - Live Tracking`,
            text: `Track Bus ${busId} in real-time`,
            url: window.location.href
        };

        if (navigator.share) {
            navigator.share(shareData).catch(console.error);
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                this.showNotification('Link copied to clipboard!', 'success');
            }).catch(() => {
                this.showNotification('Unable to share', 'error');
            });
        }
    }

    handleFavorite(e) {
        const btn = e.currentTarget;
        const icon = btn.querySelector('i');
        
        if (icon.classList.contains('bi-star')) {
            icon.classList.remove('bi-star');
            icon.classList.add('bi-star-fill');
            this.showNotification('Added to favorites', 'success');
        } else {
            icon.classList.remove('bi-star-fill');
            icon.classList.add('bi-star');
            this.showNotification('Removed from favorites', 'info');
        }
    }

    handleSubscribe() {
        // Handle notification subscription
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                this.showNotification('You will be notified when the bus arrives', 'success');
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        this.showNotification('Notifications enabled!', 'success');
                    }
                });
            } else {
                this.showNotification('Please enable notifications in browser settings', 'warning');
            }
        } else {
            this.showNotification('Notifications not supported', 'error');
        }
    }

    setupLivewireListeners() {
        if (!window.Livewire) return;
        
        // Listen for bus data updates
        Livewire.on('bus-data-updated', (data) => {
            this.updateBusData(data);
        });
        
        // Listen for location updates
        Livewire.on('location-updated', (data) => {
            this.updateBusPosition(data);
        });
        
        // Listen for tracking status changes
        Livewire.on('tracking-status-changed', (data) => {
            this.updateTrackingStatus(data);
        });
        
        // Listen for route timeline updates
        Livewire.on('route-timeline-updated', (data) => {
            this.updateRouteTimeline(data);
        });
        
        // Listen for notifications
        Livewire.on('show-notification', (data) => {
            this.showNotification(data.message, data.type);
        });
    }

    initializeBusData() {
        // Try to get bus data from Livewire component
        const busIdElement = document.querySelector('.bus-id');
        const busNameElement = document.querySelector('.bus-name');
        
        if (busIdElement && busNameElement) {
            this.currentBus = {
                id: busIdElement.textContent.trim(),
                name: busNameElement.textContent.trim()
            };
        } else {
            // Fallback to localStorage or URL params
            const urlParams = new URLSearchParams(window.location.search);
            const busId = urlParams.get('bus') || localStorage.getItem('trackingBusId') || 'B1';
            
            this.currentBus = {
                id: busId,
                name: this.getBusName(busId)
            };
        }
        
        // Update marker if bus data is available
        if (this.currentBus && this.busMarker) {
            this.updateBusMarker();
        }
    }

    updateBusData(data) {
        this.currentBus = data;
        this.updateBusMarker();
        console.log('Bus data updated:', data);
    }

    updateBusPosition(data) {
        if (data.latitude && data.longitude) {
            this.busPosition = { lat: data.latitude, lng: data.longitude };
            
            if (this.busMarker) {
                this.busMarker.setLatLng([this.busPosition.lat, this.busPosition.lng]);
            }
            
            console.log('Bus position updated:', this.busPosition);
        }
    }

    updateTrackingStatus(data) {
        // Update UI based on tracking status
        const busPinOverlay = document.querySelector('.bus-pin');
        const busPulse = document.querySelector('.bus-pulse');
        
        if (data.status === 'tracking' || data.status === 'active') {
            if (busPinOverlay) busPinOverlay.classList.add('active');
            if (busPulse) busPulse.classList.add('active');
        } else {
            if (busPinOverlay) busPinOverlay.classList.remove('active');
            if (busPulse) busPulse.classList.remove('active');
        }
        
        console.log('Tracking status updated:', data);
    }

    updateRouteTimeline(data) {
        // Route timeline is handled by Livewire component
        console.log('Route timeline updated:', data);
    }

    updateBusMarker() {
        if (!this.busMarker || !this.currentBus) return;
        
        const iconSize = window.innerWidth < 576 ? 36 : 40;
        const busIcon = L.divIcon({
            className: 'bus-marker-icon',
            html: `<div class="marker-icon">${this.currentBus.id}</div>`,
            iconSize: [iconSize, iconSize],
            iconAnchor: [iconSize/2, iconSize/2]
        });
        
        this.busMarker.setIcon(busIcon);
    }

    // Utility methods
    getBusId() {
        if (this.currentBus) return this.currentBus.id;
        
        const busIdElement = document.querySelector('.bus-id');
        if (busIdElement) return busIdElement.textContent.trim();
        
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('bus') || localStorage.getItem('trackingBusId') || 'B1';
    }

    getBusName(busId) {
        const busNames = {
            'B1': 'Buriganga',
            'B2': 'Brahmaputra',
            'B3': 'Padma',
            'B4': 'Meghna',
            'B5': 'Jamuna'
        };
        
        return busNames[busId] || 'Unknown Bus';
    }

    showMapError() {
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

    showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.querySelector('.track-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'track-notification';
            document.body.appendChild(notification);
        }
        
        // Set message and show notification
        notification.textContent = message;
        notification.className = `track-notification ${type} show`;
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Cleanup method
    destroy() {
        // Clear intervals
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
            this.trackingInterval = null;
        }
        
        // Remove map
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        
        // Reset states
        this.bottomSheetState = { isExpanded: false, isDragging: false };
        this.currentBus = null;
        this.busMarker = null;
        
        console.log('LivewireTracker destroyed');
    }
}

// Initialize the tracker
let livewireTracker = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (!livewireTracker) {
        livewireTracker = new LivewireTracker();
    }
});

// Re-initialize on Livewire navigation
document.addEventListener('livewire:navigated', function() {
    if (!livewireTracker) {
        livewireTracker = new LivewireTracker();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (livewireTracker) {
        livewireTracker.destroy();
        livewireTracker = null;
    }
});

// Export for global access
window.LivewireTracker = LivewireTracker;
window.livewireTracker = livewireTracker;