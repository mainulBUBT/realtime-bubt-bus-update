/**
 * GPS Collection Manager
 * Comprehensive system for collecting, validating, and managing GPS location data
 */

class GPSCollectionManager {
    constructor() {
        this.deviceFingerprint = new DeviceFingerprint();
        this.stoppageValidator = new StoppageValidator();
        this.busTracker = new BusTracker();
        
        // Configuration
        this.config = {
            collectionInterval: 20000, // 20 seconds
            highAccuracyMode: true,
            timeout: 15000,
            maximumAge: 30000,
            minAccuracy: 100, // meters
            maxSpeed: 80, // km/h
            validationEnabled: true,
            batchSize: 5, // locations to batch before sending
            retryAttempts: 3,
            retryDelay: 2000 // ms
        };
        
        // State management
        this.state = {
            isCollecting: false,
            currentBusId: null,
            deviceToken: null,
            sessionId: null,
            lastLocation: null,
            locationBuffer: [],
            watchId: null,
            permissionStatus: 'unknown',
            validationResults: {},
            networkStatus: 'online',
            collectionStats: {
                totalCollected: 0,
                validLocations: 0,
                invalidLocations: 0,
                networkErrors: 0,
                validationErrors: 0
            }
        };
        
        // Event listeners
        this.listeners = {
            onLocationCollected: [],
            onValidationResult: [],
            onCollectionStart: [],
            onCollectionStop: [],
            onError: [],
            onNetworkChange: []
        };
        
        this.init();
    }

    /**
     * Initialize the GPS collection manager
     */
    async init() {
        try {
            // Generate device token
            this.state.deviceToken = await this.deviceFingerprint.generateDeviceToken();
            console.log('GPS Collection Manager initialized with device token:', 
                this.state.deviceToken.substring(0, 8) + '...');
            
            // Check geolocation support
            if (!navigator.geolocation) {
                throw new Error('Geolocation is not supported by this browser');
            }
            
            // Monitor network status
            this.setupNetworkMonitoring();
            
            // Setup permission monitoring
            this.setupPermissionMonitoring();
            
            // Initialize UI components
            this.initializeUI();
            
            // Restore previous session if exists
            this.restoreSession();
            
        } catch (error) {
            console.error('GPS Collection Manager initialization failed:', error);
            this.triggerEvent('onError', { error, phase: 'initialization' });
        }
    }

    /**
     * Request GPS permission with comprehensive handling
     */
    async requestPermission() {
        return new Promise((resolve, reject) => {
            // Show permission request UI
            this.showPermissionRequestUI();
            
            // Request current position to trigger permission
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.state.permissionStatus = 'granted';
                    this.hidePermissionRequestUI();
                    this.showPermissionGrantedMessage();
                    
                    console.log('GPS permission granted');
                    resolve({
                        granted: true,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    });
                },
                (error) => {
                    this.state.permissionStatus = 'denied';
                    this.hidePermissionRequestUI();
                    this.showPermissionDeniedUI(error);
                    
                    console.error('GPS permission denied:', error);
                    reject({
                        granted: false,
                        error: error,
                        code: error.code,
                        message: this.getPermissionErrorMessage(error.code)
                    });
                },
                {
                    enableHighAccuracy: this.config.highAccuracyMode,
                    timeout: this.config.timeout,
                    maximumAge: this.config.maximumAge
                }
            );
        });
    }

    /**
     * Start GPS location collection for a specific bus
     */
    async startCollection(busId, options = {}) {
        if (this.state.isCollecting) {
            console.warn('GPS collection already active');
            return { success: false, message: 'Collection already active' };
        }

        try {
            // Merge options with defaults
            const collectionOptions = { ...this.config, ...options };
            
            // Request permission if not granted
            if (this.state.permissionStatus !== 'granted') {
                await this.requestPermission();
            }
            
            // Validate bus ID
            if (!busId || typeof busId !== 'string') {
                throw new Error('Valid bus ID is required');
            }
            
            // Initialize collection state
            this.state.currentBusId = busId;
            this.state.sessionId = this.generateSessionId();
            this.state.isCollecting = true;
            this.state.locationBuffer = [];
            
            // Start location watching
            this.state.watchId = navigator.geolocation.watchPosition(
                (position) => this.handleLocationUpdate(position),
                (error) => this.handleLocationError(error),
                {
                    enableHighAccuracy: collectionOptions.highAccuracyMode,
                    timeout: collectionOptions.timeout,
                    maximumAge: collectionOptions.maximumAge
                }
            );
            
            // Store session
            this.storeSession();
            
            // Update UI
            this.updateCollectionUI(true);
            
            // Trigger events
            this.triggerEvent('onCollectionStart', {
                busId: busId,
                sessionId: this.state.sessionId,
                deviceToken: this.state.deviceToken
            });
            
            console.log(`Started GPS collection for bus ${busId}`);
            
            return {
                success: true,
                sessionId: this.state.sessionId,
                busId: busId,
                message: 'GPS collection started successfully'
            };
            
        } catch (error) {
            console.error('Failed to start GPS collection:', error);
            this.triggerEvent('onError', { error, phase: 'collection_start' });
            
            return {
                success: false,
                error: error.message,
                message: 'Failed to start GPS collection'
            };
        }
    }

    /**
     * Stop GPS location collection
     */
    stopCollection() {
        if (!this.state.isCollecting) {
            console.warn('No active GPS collection to stop');
            return { success: false, message: 'No active collection' };
        }

        try {
            // Stop location watching
            if (this.state.watchId) {
                navigator.geolocation.clearWatch(this.state.watchId);
                this.state.watchId = null;
            }
            
            // Send any remaining buffered locations
            if (this.state.locationBuffer.length > 0) {
                this.sendLocationBatch(this.state.locationBuffer);
            }
            
            // Calculate session statistics
            const sessionStats = this.calculateSessionStats();
            
            // Reset collection state
            const sessionData = {
                busId: this.state.currentBusId,
                sessionId: this.state.sessionId,
                deviceToken: this.state.deviceToken,
                stats: sessionStats
            };
            
            this.state.isCollecting = false;
            this.state.currentBusId = null;
            this.state.sessionId = null;
            this.state.locationBuffer = [];
            this.state.lastLocation = null;
            
            // Clear stored session
            this.clearSession();
            
            // Update UI
            this.updateCollectionUI(false);
            
            // Trigger events
            this.triggerEvent('onCollectionStop', sessionData);
            
            console.log('GPS collection stopped:', sessionStats);
            
            return {
                success: true,
                sessionStats: sessionStats,
                message: 'GPS collection stopped successfully'
            };
            
        } catch (error) {
            console.error('Failed to stop GPS collection:', error);
            this.triggerEvent('onError', { error, phase: 'collection_stop' });
            
            return {
                success: false,
                error: error.message,
                message: 'Failed to stop GPS collection'
            };
        }
    }

    /**
     * Handle location updates from GPS
     */
    async handleLocationUpdate(position) {
        if (!this.state.isCollecting) return;

        try {
            const { latitude, longitude, accuracy, speed, heading } = position.coords;
            const timestamp = position.timestamp;
            
            // Create location data object
            const locationData = {
                deviceToken: this.state.deviceToken,
                busId: this.state.currentBusId,
                sessionId: this.state.sessionId,
                latitude: latitude,
                longitude: longitude,
                accuracy: accuracy,
                speed: speed || null,
                heading: heading || null,
                timestamp: timestamp,
                collectedAt: Date.now()
            };
            
            // Validate location data
            const validationResult = await this.validateLocationData(locationData);
            locationData.validation = validationResult;
            
            // Update statistics
            this.state.collectionStats.totalCollected++;
            if (validationResult.isValid) {
                this.state.collectionStats.validLocations++;
            } else {
                this.state.collectionStats.invalidLocations++;
            }
            
            // Store last location
            this.state.lastLocation = locationData;
            
            // Add to buffer
            this.state.locationBuffer.push(locationData);
            
            // Send batch if buffer is full
            if (this.state.locationBuffer.length >= this.config.batchSize) {
                await this.sendLocationBatch([...this.state.locationBuffer]);
                this.state.locationBuffer = [];
            }
            
            // Update UI
            this.updateLocationUI(locationData);
            
            // Trigger events
            this.triggerEvent('onLocationCollected', locationData);
            this.triggerEvent('onValidationResult', validationResult);
            
            console.log('Location collected:', {
                lat: latitude.toFixed(6),
                lng: longitude.toFixed(6),
                accuracy: Math.round(accuracy),
                valid: validationResult.isValid,
                busId: this.state.currentBusId
            });
            
        } catch (error) {
            console.error('Location update handling failed:', error);
            this.state.collectionStats.validationErrors++;
            this.triggerEvent('onError', { error, phase: 'location_update' });
        }
    }

    /**
     * Handle location errors
     */
    handleLocationError(error) {
        console.error('GPS location error:', error);
        
        let errorMessage = 'GPS location error occurred';
        let shouldStopCollection = false;
        
        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'GPS permission denied. Please enable location access.';
                this.state.permissionStatus = 'denied';
                shouldStopCollection = true;
                this.showPermissionDeniedUI(error);
                break;
                
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'GPS position unavailable. Please check your location settings.';
                break;
                
            case error.TIMEOUT:
                errorMessage = 'GPS request timed out. Retrying...';
                break;
                
            default:
                errorMessage = `GPS error: ${error.message}`;
        }
        
        // Update UI
        this.showErrorMessage(errorMessage);
        
        // Stop collection if critical error
        if (shouldStopCollection && this.state.isCollecting) {
            this.stopCollection();
        }
        
        // Trigger events
        this.triggerEvent('onError', {
            error: error,
            message: errorMessage,
            phase: 'gps_error',
            shouldStop: shouldStopCollection
        });
    }

    /**
     * Validate location data comprehensively
     */
    async validateLocationData(locationData) {
        const validation = {
            isValid: true,
            errors: [],
            warnings: [],
            confidence: 1.0,
            details: {}
        };

        try {
            // Basic coordinate validation
            if (!this.isValidCoordinate(locationData.latitude, locationData.longitude)) {
                validation.isValid = false;
                validation.errors.push('Invalid GPS coordinates');
                validation.confidence *= 0.1;
            }
            
            // Accuracy validation
            if (locationData.accuracy > this.config.minAccuracy) {
                validation.warnings.push(`Low GPS accuracy: ${Math.round(locationData.accuracy)}m`);
                validation.confidence *= Math.max(0.3, (this.config.minAccuracy / locationData.accuracy));
            }
            
            // Speed validation (if previous location exists)
            if (this.state.lastLocation) {
                const speedValidation = this.validateSpeed(locationData, this.state.lastLocation);
                validation.details.speed = speedValidation;
                
                if (!speedValidation.valid) {
                    validation.warnings.push(`Unrealistic speed: ${speedValidation.calculatedSpeed} km/h`);
                    validation.confidence *= 0.5;
                }
            }
            
            // Route validation using stoppage validator
            if (this.config.validationEnabled) {
                const routeValidation = this.stoppageValidator.validateStoppageRadius(
                    locationData.latitude,
                    locationData.longitude
                );
                validation.details.route = routeValidation;
                
                if (!routeValidation.isValid) {
                    validation.warnings.push(`Outside expected route area`);
                    validation.confidence *= 0.7;
                }
            }
            
            // Timestamp validation
            const now = Date.now();
            const timeDiff = Math.abs(now - locationData.timestamp);
            if (timeDiff > 60000) { // More than 1 minute difference
                validation.warnings.push('Location timestamp is outdated');
                validation.confidence *= 0.8;
            }
            
            // Set overall validity based on confidence
            if (validation.confidence < 0.3) {
                validation.isValid = false;
                validation.errors.push('Location confidence too low');
            }
            
        } catch (error) {
            console.error('Location validation failed:', error);
            validation.isValid = false;
            validation.errors.push('Validation process failed');
            validation.confidence = 0.1;
        }

        return validation;
    }

    /**
     * Send location data batch to server
     */
    async sendLocationBatch(locations) {
        if (!locations || locations.length === 0) return;

        let attempt = 0;
        const maxAttempts = this.config.retryAttempts;

        while (attempt < maxAttempts) {
            try {
                const response = await fetch('/api/gps-locations/batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        locations: locations,
                        deviceToken: this.state.deviceToken,
                        sessionId: this.state.sessionId
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }

                const result = await response.json();
                console.log('Location batch sent successfully:', result);
                
                return result;

            } catch (error) {
                attempt++;
                console.error(`Location batch send attempt ${attempt} failed:`, error);
                
                this.state.collectionStats.networkErrors++;
                
                if (attempt < maxAttempts) {
                    // Wait before retry
                    await new Promise(resolve => setTimeout(resolve, this.config.retryDelay * attempt));
                } else {
                    // Store failed locations for later retry
                    this.storeFailedLocations(locations);
                    this.triggerEvent('onError', {
                        error: error,
                        phase: 'network_send',
                        locations: locations.length
                    });
                }
            }
        }
    }

    /**
     * Validate speed between two locations
     */
    validateSpeed(currentLocation, previousLocation) {
        const distance = this.calculateDistance(
            previousLocation.latitude,
            previousLocation.longitude,
            currentLocation.latitude,
            currentLocation.longitude
        );

        const timeDiff = (currentLocation.timestamp - previousLocation.timestamp) / 1000; // seconds
        
        if (timeDiff <= 0) {
            return {
                valid: false,
                reason: 'Invalid time sequence',
                calculatedSpeed: 0
            };
        }

        const speedKmh = (distance / timeDiff) * 3.6;
        const isValid = speedKmh <= this.config.maxSpeed;

        return {
            valid: isValid,
            calculatedSpeed: Math.round(speedKmh * 100) / 100,
            distance: Math.round(distance * 100) / 100,
            timeDiff: timeDiff,
            maxAllowed: this.config.maxSpeed
        };
    }

    /**
     * Calculate distance between two GPS coordinates
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth's radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c;
    }

    /**
     * Check if coordinates are valid
     */
    isValidCoordinate(lat, lng) {
        // Check basic coordinate bounds
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            return false;
        }
        
        // Check for obviously invalid coordinates
        if (lat === 0 && lng === 0) {
            return false;
        }
        
        // Check if within Bangladesh bounds (approximate)
        if (lat < 20.5 || lat > 26.5 || lng < 88.0 || lng > 92.7) {
            return false;
        }
        
        return true;
    }

    /**
     * Generate unique session ID
     */
    generateSessionId() {
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2);
        const devicePrefix = this.state.deviceToken ? this.state.deviceToken.substring(0, 8) : 'unknown';
        return `${devicePrefix}_${timestamp}_${random}`;
    }

    /**
     * Calculate session statistics
     */
    calculateSessionStats() {
        const stats = { ...this.state.collectionStats };
        
        stats.successRate = stats.totalCollected > 0 ? 
            (stats.validLocations / stats.totalCollected) * 100 : 0;
        
        stats.errorRate = stats.totalCollected > 0 ? 
            ((stats.invalidLocations + stats.networkErrors + stats.validationErrors) / stats.totalCollected) * 100 : 0;
        
        return stats;
    }

    // Additional methods for UI management, session storage, network monitoring, etc.
    // would be implemented here...

    /**
     * Event management
     */
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    triggerEvent(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Event callback error for ${event}:`, error);
                }
            });
        }
    }

    /**
     * Get current collection status
     */
    getStatus() {
        return {
            isCollecting: this.state.isCollecting,
            currentBusId: this.state.currentBusId,
            sessionId: this.state.sessionId,
            permissionStatus: this.state.permissionStatus,
            networkStatus: this.state.networkStatus,
            stats: this.state.collectionStats,
            lastLocation: this.state.lastLocation,
            bufferSize: this.state.locationBuffer.length
        };
    }

    /**
     * Update configuration
     */
    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
        console.log('GPS Collection Manager configuration updated:', this.config);
    }

    /**
     * Cleanup and destroy
     */
    destroy() {
        if (this.state.isCollecting) {
            this.stopCollection();
        }
        
        // Clear all listeners
        Object.keys(this.listeners).forEach(event => {
            this.listeners[event] = [];
        });
        
        // Clear stored data
        this.clearSession();
        
        console.log('GPS Collection Manager destroyed');
    }
}

// Export for use in other modules
window.GPSCollectionManager = GPSCollectionManager;  
  /**
     * UI Management Methods
     */

    /**
     * Initialize UI components
     */
    initializeUI() {
        this.createCollectionStatusUI();
        this.createPermissionUI();
        this.createErrorDisplayUI();
    }

    /**
     * Create collection status UI
     */
    createCollectionStatusUI() {
        const statusContainer = document.createElement('div');
        statusContainer.className = 'gps-collection-status';
        statusContainer.id = 'gps-collection-status';
        statusContainer.innerHTML = `
            <div class="status-header">
                <div class="status-indicator" id="gps-status-indicator"></div>
                <span class="status-text" id="gps-status-text">GPS Collection Inactive</span>
            </div>
            <div class="status-details" id="gps-status-details" style="display: none;">
                <div class="detail-item">
                    <span class="detail-label">Session:</span>
                    <span class="detail-value" id="session-id-display">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Bus:</span>
                    <span class="detail-value" id="bus-id-display">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Locations:</span>
                    <span class="detail-value" id="locations-count">0</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Accuracy:</span>
                    <span class="detail-value" id="current-accuracy">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Validation:</span>
                    <span class="detail-value" id="validation-status">-</span>
                </div>
            </div>
        `;

        // Add to appropriate container
        const container = document.querySelector('.tracking-status-container') || 
                         document.querySelector('.bottom-sheet-content') ||
                         document.body;
        container.appendChild(statusContainer);
    }

    /**
     * Create permission request UI
     */
    createPermissionUI() {
        const permissionModal = document.createElement('div');
        permissionModal.className = 'gps-permission-modal';
        permissionModal.id = 'gps-permission-modal';
        permissionModal.innerHTML = `
            <div class="permission-modal-content">
                <div class="permission-icon">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h3>Enable GPS Location</h3>
                <p>To provide accurate bus tracking, we need access to your device's location. Your location data is only used for bus tracking and is processed anonymously.</p>
                <div class="permission-benefits">
                    <div class="benefit-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Anonymous & Secure</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-clock"></i>
                        <span>Real-time Updates</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-people"></i>
                        <span>Help Other Students</span>
                    </div>
                </div>
                <div class="permission-buttons">
                    <button class="btn btn-primary" id="grant-gps-permission">Enable Location</button>
                    <button class="btn btn-secondary" id="deny-gps-permission">Not Now</button>
                </div>
            </div>
        `;

        document.body.appendChild(permissionModal);

        // Add event listeners
        document.getElementById('grant-gps-permission').addEventListener('click', () => {
            this.requestPermission();
        });

        document.getElementById('deny-gps-permission').addEventListener('click', () => {
            this.hidePermissionRequestUI();
        });
    }

    /**
     * Create error display UI
     */
    createErrorDisplayUI() {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'gps-error-container';
        errorContainer.id = 'gps-error-container';
        errorContainer.style.display = 'none';
        
        document.body.appendChild(errorContainer);
    }

    /**
     * Update collection UI state
     */
    updateCollectionUI(isCollecting) {
        const statusIndicator = document.getElementById('gps-status-indicator');
        const statusText = document.getElementById('gps-status-text');
        const statusDetails = document.getElementById('gps-status-details');

        if (statusIndicator) {
            statusIndicator.className = `status-indicator ${isCollecting ? 'collecting' : 'inactive'}`;
        }

        if (statusText) {
            statusText.textContent = isCollecting ? 
                `GPS Collection Active (${this.state.currentBusId})` : 
                'GPS Collection Inactive';
        }

        if (statusDetails) {
            statusDetails.style.display = isCollecting ? 'block' : 'none';
            
            if (isCollecting) {
                document.getElementById('session-id-display').textContent = 
                    this.state.sessionId ? this.state.sessionId.substring(0, 12) + '...' : '-';
                document.getElementById('bus-id-display').textContent = this.state.currentBusId || '-';
            }
        }
    }

    /**
     * Update location UI with current data
     */
    updateLocationUI(locationData) {
        const locationsCount = document.getElementById('locations-count');
        const currentAccuracy = document.getElementById('current-accuracy');
        const validationStatus = document.getElementById('validation-status');

        if (locationsCount) {
            locationsCount.textContent = this.state.collectionStats.totalCollected;
        }

        if (currentAccuracy) {
            currentAccuracy.textContent = `±${Math.round(locationData.accuracy)}m`;
            currentAccuracy.className = `detail-value ${this.getAccuracyClass(locationData.accuracy)}`;
        }

        if (validationStatus) {
            const validation = locationData.validation;
            validationStatus.textContent = validation.isValid ? 'Valid' : 'Invalid';
            validationStatus.className = `detail-value ${validation.isValid ? 'valid' : 'invalid'}`;
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

    showPermissionGrantedMessage() {
        this.showSuccessMessage('GPS permission granted! You can now contribute to bus tracking.');
    }

    showPermissionDeniedUI(error) {
        this.showErrorMessage('GPS permission is required for bus tracking. Please enable location access in your browser settings.');
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
        const existingMessage = document.querySelector('.gps-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `gps-message ${type}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(messageDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    /**
     * Network monitoring setup
     */
    setupNetworkMonitoring() {
        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.state.networkStatus = 'online';
            this.triggerEvent('onNetworkChange', { status: 'online' });
            console.log('Network connection restored');
        });

        window.addEventListener('offline', () => {
            this.state.networkStatus = 'offline';
            this.triggerEvent('onNetworkChange', { status: 'offline' });
            console.log('Network connection lost');
        });

        // Initial network status
        this.state.networkStatus = navigator.onLine ? 'online' : 'offline';
    }

    /**
     * Permission monitoring setup
     */
    setupPermissionMonitoring() {
        // Check for permission API support
        if ('permissions' in navigator) {
            navigator.permissions.query({ name: 'geolocation' }).then((result) => {
                this.state.permissionStatus = result.state;
                
                result.addEventListener('change', () => {
                    this.state.permissionStatus = result.state;
                    console.log('GPS permission status changed:', result.state);
                    
                    if (result.state === 'denied' && this.state.isCollecting) {
                        this.stopCollection();
                        this.showPermissionDeniedUI({ code: 1 });
                    }
                });
            });
        }
    }

    /**
     * Session management
     */
    storeSession() {
        const sessionData = {
            currentBusId: this.state.currentBusId,
            sessionId: this.state.sessionId,
            deviceToken: this.state.deviceToken,
            startTime: Date.now(),
            isActive: this.state.isCollecting
        };

        try {
            localStorage.setItem('gps_collection_session', JSON.stringify(sessionData));
        } catch (error) {
            console.warn('Failed to store GPS collection session:', error);
        }
    }

    restoreSession() {
        try {
            const sessionData = localStorage.getItem('gps_collection_session');
            if (sessionData) {
                const session = JSON.parse(sessionData);
                
                // Check if session is recent (within last 2 hours)
                const sessionAge = Date.now() - session.startTime;
                if (sessionAge < 2 * 60 * 60 * 1000 && session.isActive) {
                    console.log('Restoring GPS collection session:', session.sessionId);
                    
                    this.state.currentBusId = session.currentBusId;
                    this.state.sessionId = session.sessionId;
                    // Don't auto-restart collection, just restore state for UI
                }
            }
        } catch (error) {
            console.warn('Failed to restore GPS collection session:', error);
        }
    }

    clearSession() {
        try {
            localStorage.removeItem('gps_collection_session');
        } catch (error) {
            console.warn('Failed to clear GPS collection session:', error);
        }
    }

    /**
     * Store failed locations for retry
     */
    storeFailedLocations(locations) {
        try {
            const failedLocations = JSON.parse(localStorage.getItem('gps_failed_locations') || '[]');
            failedLocations.push(...locations);
            
            // Keep only last 50 failed locations
            if (failedLocations.length > 50) {
                failedLocations.splice(0, failedLocations.length - 50);
            }
            
            localStorage.setItem('gps_failed_locations', JSON.stringify(failedLocations));
        } catch (error) {
            console.warn('Failed to store failed locations:', error);
        }
    }

    /**
     * Utility methods
     */
    getPermissionErrorMessage(errorCode) {
        switch (errorCode) {
            case 1:
                return 'GPS permission denied. Please enable location access in your browser settings.';
            case 2:
                return 'GPS position unavailable. Please check your device location settings.';
            case 3:
                return 'GPS request timed out. Please try again.';
            default:
                return 'GPS error occurred. Please try again.';
        }
    }

    getAccuracyClass(accuracy) {
        if (accuracy <= 20) return 'excellent';
        if (accuracy <= 50) return 'good';
        if (accuracy <= 100) return 'fair';
        return 'poor';
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.gpsCollectionManager === 'undefined') {
        window.gpsCollectionManager = new GPSCollectionManager();
        console.log('GPS Collection Manager auto-initialized');
    }
});

// Export for use in other modules
window.GPSCollectionManager = GPSCollectionManager;