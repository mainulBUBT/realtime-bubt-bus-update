/**
 * Bus Tracker Geolocation Integration
 * Handles GPS permission requests, continuous tracking, and "I'm on this bus" functionality
 */

class BusTracker {
    constructor() {
        this.deviceFingerprint = new DeviceFingerprint();
        this.stoppageValidator = new StoppageValidator();
        this.isTracking = false;
        this.watchId = null;
        this.deviceToken = null;
        this.currentBusId = null;
        this.trackingStartTime = null;
        this.locationHistory = [];
        this.trackingInterval = 20000; // 20 seconds default
        this.accuracyThreshold = 50; // meters
        this.callbacks = {
            onLocationUpdate: [],
            onTrackingStart: [],
            onTrackingStop: [],
            onError: []
        };
        
        this.init();
    }

    /**
     * Initialize the bus tracker
     */
    async init() {
        try {
            // Generate device token
            this.deviceToken = await this.deviceFingerprint.generateDeviceToken();
            console.log('Device token generated:', this.deviceToken.substring(0, 8) + '...');
            
            // Check geolocation support
            if (!navigator.geolocation) {
                throw new Error('Geolocation is not supported by this browser');
            }
            
            // Initialize UI elements
            this.initializeUI();
            
            // Check for existing tracking session
            this.restoreTrackingSession();
            
        } catch (error) {
            console.error('Bus tracker initialization failed:', error);
            this.handleError(error);
        }
    }

    /**
     * Request GPS permission with user-friendly UI
     */
    async requestGPSPermission() {
        return new Promise((resolve, reject) => {
            // Show permission request UI
            this.showPermissionRequestUI();
            
            // Request permission
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('GPS permission granted');
                    this.hidePermissionRequestUI();
                    this.showPermissionGrantedUI();
                    resolve(position);
                },
                (error) => {
                    console.error('GPS permission denied:', error);
                    this.hidePermissionRequestUI();
                    this.showPermissionDeniedUI(error);
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }

    /**
     * Start continuous location tracking for a specific bus
     */
    async startTracking(busId) {
        if (this.isTracking) {
            console.warn('Tracking already active');
            return;
        }

        try {
            // Request permission if not already granted
            await this.requestGPSPermission();
            
            this.currentBusId = busId;
            this.isTracking = true;
            this.trackingStartTime = Date.now();
            this.locationHistory = [];
            
            // Start continuous tracking
            this.watchId = navigator.geolocation.watchPosition(
                (position) => this.handleLocationUpdate(position),
                (error) => this.handleLocationError(error),
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 30000
                }
            );
            
            // Store tracking session
            this.storeTrackingSession();
            
            // Update UI
            this.updateTrackingUI(true);
            
            // Trigger callbacks
            this.triggerCallbacks('onTrackingStart', { busId, deviceToken: this.deviceToken });
            
            console.log(`Started tracking bus ${busId}`);
            
        } catch (error) {
            console.error('Failed to start tracking:', error);
            this.handleError(error);
        }
    }

    /**
     * Stop location tracking
     */
    stopTracking() {
        if (!this.isTracking) {
            console.warn('No active tracking to stop');
            return;
        }

        // Clear watch
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        // Calculate tracking session stats
        const sessionDuration = Date.now() - this.trackingStartTime;
        const locationCount = this.locationHistory.length;
        
        const sessionStats = {
            busId: this.currentBusId,
            deviceToken: this.deviceToken,
            duration: sessionDuration,
            locationCount: locationCount,
            startTime: this.trackingStartTime,
            endTime: Date.now()
        };

        // Reset tracking state
        this.isTracking = false;
        this.currentBusId = null;
        this.trackingStartTime = null;
        
        // Clear stored session
        this.clearTrackingSession();
        
        // Update UI
        this.updateTrackingUI(false);
        
        // Trigger callbacks
        this.triggerCallbacks('onTrackingStop', sessionStats);
        
        console.log('Stopped tracking:', sessionStats);
    }

    /**
     * Handle location updates
     */
    async handleLocationUpdate(position) {
        if (!this.isTracking) return;

        const { latitude, longitude, accuracy, speed, heading } = position.coords;
        const timestamp = position.timestamp;

        // Validate accuracy
        if (accuracy > this.accuracyThreshold) {
            console.warn(`Low GPS accuracy: ${accuracy}m (threshold: ${this.accuracyThreshold}m)`);
            // Still process but flag as low accuracy
        }

        // Validate location against bus stops
        const validation = this.stoppageValidator.validateStoppageRadius(latitude, longitude);
        
        // Create location data object
        const locationData = {
            deviceToken: this.deviceToken,
            busId: this.currentBusId,
            latitude: latitude,
            longitude: longitude,
            accuracy: accuracy,
            speed: speed || null,
            heading: heading || null,
            timestamp: timestamp,
            validation: validation,
            sessionId: this.generateSessionId()
        };

        // Add to history
        this.locationHistory.push(locationData);
        
        // Keep only recent history (last 50 points)
        if (this.locationHistory.length > 50) {
            this.locationHistory = this.locationHistory.slice(-50);
        }

        // Send to server
        await this.sendLocationToServer(locationData);
        
        // Update UI
        this.updateLocationUI(locationData);
        
        // Trigger callbacks
        this.triggerCallbacks('onLocationUpdate', locationData);
        
        console.log('Location update:', {
            lat: latitude.toFixed(6),
            lng: longitude.toFixed(6),
            accuracy: Math.round(accuracy),
            valid: validation.isValid
        });
    }

    /**
     * Handle location errors
     */
    handleLocationError(error) {
        console.error('Location error:', error);
        
        let errorMessage = 'Location error occurred';
        
        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'GPS permission denied. Please enable location access.';
                this.showPermissionDeniedUI(error);
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'GPS position unavailable. Please check your location settings.';
                break;
            case error.TIMEOUT:
                errorMessage = 'GPS request timed out. Retrying...';
                break;
        }
        
        this.showErrorMessage(errorMessage);
        this.triggerCallbacks('onError', { error, message: errorMessage });
    }

    /**
     * Send location data to server
     */
    async sendLocationToServer(locationData) {
        try {
            const response = await fetch('/api/bus-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(locationData)
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const result = await response.json();
            console.log('Location sent to server:', result);
            
        } catch (error) {
            console.error('Failed to send location to server:', error);
            // Store for retry later
            this.storeFailedLocation(locationData);
        }
    }

    /**
     * Initialize UI elements
     */
    initializeUI() {
        // Create tracking button if it doesn't exist
        this.createTrackingButton();
        
        // Create status indicators
        this.createStatusIndicators();
        
        // Create permission UI
        this.createPermissionUI();
    }

    /**
     * Create "I'm on this bus" button
     */
    createTrackingButton() {
        const existingButton = document.querySelector('.tracking-button');
        if (existingButton) return;

        const button = document.createElement('button');
        button.className = 'tracking-button btn btn-primary';
        button.innerHTML = `
            <i class="bi bi-geo-alt"></i>
            <span class="button-text">I'm on this Bus</span>
        `;
        
        button.addEventListener('click', () => {
            if (this.isTracking) {
                this.stopTracking();
            } else {
                const busId = this.getCurrentBusId();
                if (busId) {
                    this.startTracking(busId);
                } else {
                    this.showErrorMessage('Please select a bus first');
                }
            }
        });

        // Add to appropriate container
        const container = document.querySelector('.bus-actions') || document.querySelector('.bottom-sheet-content');
        if (container) {
            container.appendChild(button);
        }
    }

    /**
     * Create status indicators
     */
    createStatusIndicators() {
        const statusContainer = document.createElement('div');
        statusContainer.className = 'tracking-status-container';
        statusContainer.innerHTML = `
            <div class="tracking-status">
                <div class="status-indicator" id="tracking-indicator"></div>
                <span class="status-text" id="tracking-status-text">Not tracking</span>
            </div>
            <div class="location-info" id="location-info" style="display: none;">
                <div class="accuracy-info">GPS Accuracy: <span id="accuracy-value">-</span></div>
                <div class="validation-info">Location Status: <span id="validation-status">-</span></div>
            </div>
        `;

        const container = document.querySelector('.bus-info-pill') || document.querySelector('.track-info-card');
        if (container) {
            container.appendChild(statusContainer);
        }
    }

    /**
     * Create permission request UI
     */
    createPermissionUI() {
        const permissionModal = document.createElement('div');
        permissionModal.className = 'permission-modal';
        permissionModal.id = 'gps-permission-modal';
        permissionModal.innerHTML = `
            <div class="permission-modal-content">
                <div class="permission-icon">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h3>Enable Location Access</h3>
                <p>To track buses in real-time, we need access to your location. Your location data is only used for bus tracking and is not stored permanently.</p>
                <div class="permission-buttons">
                    <button class="btn btn-primary" id="grant-permission">Enable Location</button>
                    <button class="btn btn-secondary" id="deny-permission">Not Now</button>
                </div>
            </div>
        `;

        document.body.appendChild(permissionModal);

        // Add event listeners
        document.getElementById('grant-permission').addEventListener('click', () => {
            this.requestGPSPermission();
        });

        document.getElementById('deny-permission').addEventListener('click', () => {
            this.hidePermissionRequestUI();
        });
    }

    /**
     * Update tracking UI state
     */
    updateTrackingUI(isTracking) {
        const button = document.querySelector('.tracking-button');
        const indicator = document.getElementById('tracking-indicator');
        const statusText = document.getElementById('tracking-status-text');
        const locationInfo = document.getElementById('location-info');

        if (button) {
            if (isTracking) {
                button.className = 'tracking-button btn btn-danger';
                button.innerHTML = `
                    <i class="bi bi-geo-alt-fill"></i>
                    <span class="button-text">Stop Tracking</span>
                `;
            } else {
                button.className = 'tracking-button btn btn-primary';
                button.innerHTML = `
                    <i class="bi bi-geo-alt"></i>
                    <span class="button-text">I'm on this Bus</span>
                `;
            }
        }

        if (indicator) {
            indicator.className = `status-indicator ${isTracking ? 'active' : 'inactive'}`;
        }

        if (statusText) {
            statusText.textContent = isTracking ? 'Tracking active' : 'Not tracking';
        }

        if (locationInfo) {
            locationInfo.style.display = isTracking ? 'block' : 'none';
        }
    }

    /**
     * Update location UI with current data
     */
    updateLocationUI(locationData) {
        const accuracyValue = document.getElementById('accuracy-value');
        const validationStatus = document.getElementById('validation-status');

        if (accuracyValue) {
            accuracyValue.textContent = `±${Math.round(locationData.accuracy)}m`;
            accuracyValue.className = locationData.accuracy <= 20 ? 'good' : 
                                     locationData.accuracy <= 50 ? 'fair' : 'poor';
        }

        if (validationStatus) {
            validationStatus.textContent = locationData.validation.isValid ? 
                `Valid (${locationData.validation.closestStop})` : 
                `Outside area (${locationData.validation.distanceToClosest}m from ${locationData.validation.closestStop})`;
            validationStatus.className = locationData.validation.isValid ? 'valid' : 'invalid';
        }
    }

    /**
     * Show/hide permission UI
     */
    showPermissionRequestUI() {
        const modal = document.getElementById('gps-permission-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    hidePermissionRequestUI() {
        const modal = document.getElementById('gps-permission-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showPermissionGrantedUI() {
        this.showSuccessMessage('Location access granted! You can now track buses.');
    }

    showPermissionDeniedUI(error) {
        this.showErrorMessage('Location access is required for bus tracking. Please enable it in your browser settings.');
    }

    /**
     * Show success/error messages
     */
    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // Remove existing messages
        const existingMessage = document.querySelector('.tracking-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `tracking-message ${type}`;
        messageDiv.textContent = message;

        document.body.appendChild(messageDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    /**
     * Session management
     */
    storeTrackingSession() {
        const sessionData = {
            busId: this.currentBusId,
            deviceToken: this.deviceToken,
            startTime: this.trackingStartTime,
            isActive: true
        };

        try {
            localStorage.setItem('bus_tracking_session', JSON.stringify(sessionData));
        } catch (error) {
            console.warn('Failed to store tracking session:', error);
        }
    }

    restoreTrackingSession() {
        try {
            const sessionData = localStorage.getItem('bus_tracking_session');
            if (sessionData) {
                const session = JSON.parse(sessionData);
                if (session.isActive && session.busId) {
                    console.log('Restoring tracking session for bus:', session.busId);
                    // Don't auto-restart, just update UI to show previous state
                    this.currentBusId = session.busId;
                }
            }
        } catch (error) {
            console.warn('Failed to restore tracking session:', error);
        }
    }

    clearTrackingSession() {
        try {
            localStorage.removeItem('bus_tracking_session');
        } catch (error) {
            console.warn('Failed to clear tracking session:', error);
        }
    }

    /**
     * Utility methods
     */
    getCurrentBusId() {
        // Try to get from URL, localStorage, or current page context
        const urlParams = new URLSearchParams(window.location.search);
        const busIdFromUrl = urlParams.get('bus');
        
        if (busIdFromUrl) return busIdFromUrl;
        
        const busIdFromStorage = localStorage.getItem('trackingBusId');
        if (busIdFromStorage) return busIdFromStorage;
        
        // Try to extract from page elements
        const busIcon = document.querySelector('.bus-icon');
        if (busIcon) return busIcon.textContent.trim();
        
        return null;
    }

    generateSessionId() {
        return `${this.deviceToken.substring(0, 8)}_${Date.now()}`;
    }

    storeFailedLocation(locationData) {
        try {
            const failedLocations = JSON.parse(localStorage.getItem('failed_locations') || '[]');
            failedLocations.push(locationData);
            
            // Keep only last 10 failed locations
            if (failedLocations.length > 10) {
                failedLocations.splice(0, failedLocations.length - 10);
            }
            
            localStorage.setItem('failed_locations', JSON.stringify(failedLocations));
        } catch (error) {
            console.warn('Failed to store failed location:', error);
        }
    }

    /**
     * Event handling
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    triggerCallbacks(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Callback error for ${event}:`, error);
                }
            });
        }
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stopTracking();
        this.clearTrackingSession();
        
        // Remove UI elements
        const elements = [
            '.tracking-button',
            '.tracking-status-container',
            '#gps-permission-modal',
            '.tracking-message'
        ];
        
        elements.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.remove();
            }
        });
    }
}

// Export for use in other modules
window.BusTracker = BusTracker;