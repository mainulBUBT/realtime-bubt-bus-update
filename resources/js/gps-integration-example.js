/**
 * GPS Collection Integration Example
 * Shows how to integrate the GPS Collection Manager with existing bus tracker UI
 */

class GPSIntegrationExample {
    constructor() {
        this.gpsManager = null;
        this.currentBusId = null;
        this.isTrackingActive = false;
        
        this.init();
    }

    async init() {
        // Wait for GPS Collection Manager to be available
        if (typeof GPSCollectionManager !== 'undefined') {
            this.gpsManager = new GPSCollectionManager();
            this.setupEventListeners();
            this.setupUI();
        } else {
            // Retry after a short delay
            setTimeout(() => this.init(), 1000);
        }
    }

    setupEventListeners() {
        // Listen to GPS collection events
        this.gpsManager.on('onLocationCollected', (locationData) => {
            console.log('Location collected:', locationData);
            this.updateLocationDisplay(locationData);
        });

        this.gpsManager.on('onValidationResult', (validation) => {
            console.log('Validation result:', validation);
            this.updateValidationDisplay(validation);
        });

        this.gpsManager.on('onCollectionStart', (data) => {
            console.log('GPS collection started:', data);
            this.isTrackingActive = true;
            this.updateTrackingButton();
        });

        this.gpsManager.on('onCollectionStop', (data) => {
            console.log('GPS collection stopped:', data);
            this.isTrackingActive = false;
            this.updateTrackingButton();
        });

        this.gpsManager.on('onError', (error) => {
            console.error('GPS collection error:', error);
            this.handleGPSError(error);
        });

        this.gpsManager.on('onNetworkChange', (status) => {
            console.log('Network status changed:', status);
            this.updateNetworkStatus(status);
        });
    }

    setupUI() {
        // Add GPS tracking button to existing bus cards
        this.addTrackingButtonsToBusCards();
        
        // Add GPS status to existing track page
        this.addGPSStatusToTrackPage();
        
        // Setup existing "I'm on this bus" button integration
        this.integrateWithExistingButton();
    }

    addTrackingButtonsToBusCards() {
        const busCards = document.querySelectorAll('.bus-card');
        
        busCards.forEach(card => {
            const busId = this.extractBusIdFromCard(card);
            if (!busId) return;

            // Create GPS tracking button
            const trackingButton = document.createElement('button');
            trackingButton.className = 'btn btn-sm btn-outline-primary gps-track-btn';
            trackingButton.innerHTML = '<i class="bi bi-geo-alt"></i> Track GPS';
            trackingButton.dataset.busId = busId;

            // Add click handler
            trackingButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleGPSTracking(busId);
            });

            // Add to card actions
            const cardActions = card.querySelector('.card-actions') || card.querySelector('.bus-actions');
            if (cardActions) {
                cardActions.appendChild(trackingButton);
            }
        });
    }

    addGPSStatusToTrackPage() {
        // Check if we're on the track page
        if (!document.querySelector('#map')) return;

        // Add GPS status indicator to existing UI
        const statusContainer = document.querySelector('.bus-info-pill') || 
                               document.querySelector('.track-info-card');
        
        if (statusContainer) {
            const gpsStatus = document.createElement('div');
            gpsStatus.className = 'gps-status-indicator';
            gpsStatus.innerHTML = `
                <div class="gps-indicator">
                    <i class="bi bi-geo-alt"></i>
                    <span class="gps-status-text">GPS Inactive</span>
                </div>
            `;
            
            statusContainer.appendChild(gpsStatus);
        }
    }

    integrateWithExistingButton() {
        // Find existing "I'm on this bus" button
        const existingButton = document.querySelector('.tracking-button') || 
                              document.querySelector('[data-action="start-tracking"]');
        
        if (existingButton) {
            // Replace or enhance existing functionality
            existingButton.addEventListener('click', (e) => {
                e.preventDefault();
                
                const busId = this.getCurrentBusId();
                if (busId) {
                    this.toggleGPSTracking(busId);
                }
            });
        }
    }

    async toggleGPSTracking(busId) {
        if (!this.gpsManager) {
            console.error('GPS Manager not initialized');
            return;
        }

        try {
            if (this.isTrackingActive && this.currentBusId === busId) {
                // Stop tracking
                const result = this.gpsManager.stopCollection();
                if (result.success) {
                    this.showMessage('GPS tracking stopped', 'success');
                }
            } else {
                // Start tracking
                const result = await this.gpsManager.startCollection(busId, {
                    highAccuracyMode: true,
                    validationEnabled: true
                });
                
                if (result.success) {
                    this.currentBusId = busId;
                    this.showMessage(`GPS tracking started for bus ${busId}`, 'success');
                } else {
                    this.showMessage(result.message || 'Failed to start GPS tracking', 'error');
                }
            }
        } catch (error) {
            console.error('GPS tracking toggle failed:', error);
            this.showMessage('GPS tracking failed', 'error');
        }
    }

    updateLocationDisplay(locationData) {
        // Update existing location displays
        const locationElements = document.querySelectorAll('.current-location');
        locationElements.forEach(element => {
            element.textContent = `${locationData.latitude.toFixed(6)}, ${locationData.longitude.toFixed(6)}`;
        });

        // Update accuracy displays
        const accuracyElements = document.querySelectorAll('.gps-accuracy');
        accuracyElements.forEach(element => {
            element.textContent = `±${Math.round(locationData.accuracy)}m`;
            element.className = `gps-accuracy ${this.getAccuracyClass(locationData.accuracy)}`;
        });
    }

    updateValidationDisplay(validation) {
        const validationElements = document.querySelectorAll('.validation-status');
        validationElements.forEach(element => {
            element.textContent = validation.isValid ? 'Valid' : 'Invalid';
            element.className = `validation-status ${validation.isValid ? 'valid' : 'invalid'}`;
        });
    }

    updateTrackingButton() {
        const trackingButtons = document.querySelectorAll('.gps-track-btn');
        trackingButtons.forEach(button => {
            const busId = button.dataset.busId;
            const isActive = this.isTrackingActive && this.currentBusId === busId;
            
            if (isActive) {
                button.className = 'btn btn-sm btn-danger gps-track-btn';
                button.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Stop GPS';
            } else {
                button.className = 'btn btn-sm btn-outline-primary gps-track-btn';
                button.innerHTML = '<i class="bi bi-geo-alt"></i> Track GPS';
            }
        });

        // Update existing tracking button
        const existingButton = document.querySelector('.tracking-button');
        if (existingButton) {
            if (this.isTrackingActive) {
                existingButton.className = 'tracking-button btn btn-danger';
                existingButton.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Stop GPS Tracking';
            } else {
                existingButton.className = 'tracking-button btn btn-primary';
                existingButton.innerHTML = '<i class="bi bi-geo-alt"></i> I\'m on this Bus';
            }
        }
    }

    updateNetworkStatus(status) {
        const networkIndicators = document.querySelectorAll('.network-status');
        networkIndicators.forEach(indicator => {
            indicator.textContent = status.status === 'online' ? 'Online' : 'Offline';
            indicator.className = `network-status ${status.status}`;
        });
    }

    handleGPSError(error) {
        let message = 'GPS error occurred';
        
        switch (error.phase) {
            case 'permission_denied':
                message = 'GPS permission denied. Please enable location access.';
                break;
            case 'location_unavailable':
                message = 'GPS location unavailable. Please check your settings.';
                break;
            case 'network_error':
                message = 'Network error. GPS data will be retried automatically.';
                break;
            default:
                message = error.message || 'GPS error occurred';
        }
        
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // Use existing notification system if available
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            // Fallback to GPS manager's message system
            if (this.gpsManager) {
                if (type === 'success') {
                    this.gpsManager.showSuccessMessage(message);
                } else {
                    this.gpsManager.showErrorMessage(message);
                }
            } else {
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        }
    }

    extractBusIdFromCard(card) {
        // Try multiple methods to extract bus ID
        const busIcon = card.querySelector('.bus-icon');
        if (busIcon) {
            return busIcon.textContent.trim();
        }
        
        const busIdElement = card.querySelector('[data-bus-id]');
        if (busIdElement) {
            return busIdElement.dataset.busId;
        }
        
        const busNameElement = card.querySelector('.bus-name');
        if (busNameElement) {
            const match = busNameElement.textContent.match(/B\d+/);
            if (match) {
                return match[0];
            }
        }
        
        return null;
    }

    getCurrentBusId() {
        // Try to get bus ID from current page context
        const urlParams = new URLSearchParams(window.location.search);
        const busIdFromUrl = urlParams.get('bus');
        
        if (busIdFromUrl) return busIdFromUrl;
        
        const busIdFromStorage = localStorage.getItem('trackingBusId');
        if (busIdFromStorage) return busIdFromStorage;
        
        const busIcon = document.querySelector('.bus-icon');
        if (busIcon) return busIcon.textContent.trim();
        
        return null;
    }

    getAccuracyClass(accuracy) {
        if (accuracy <= 20) return 'excellent';
        if (accuracy <= 50) return 'good';
        if (accuracy <= 100) return 'fair';
        return 'poor';
    }

    // Public API methods
    startTrackingForBus(busId) {
        return this.toggleGPSTracking(busId);
    }

    stopTracking() {
        if (this.gpsManager && this.isTrackingActive) {
            return this.gpsManager.stopCollection();
        }
        return { success: false, message: 'No active tracking to stop' };
    }

    getTrackingStatus() {
        return {
            isActive: this.isTrackingActive,
            currentBusId: this.currentBusId,
            gpsStatus: this.gpsManager ? this.gpsManager.getStatus() : null
        };
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.gpsIntegration === 'undefined') {
        window.gpsIntegration = new GPSIntegrationExample();
        console.log('GPS Integration initialized');
    }
});

// Export for manual initialization
window.GPSIntegrationExample = GPSIntegrationExample;