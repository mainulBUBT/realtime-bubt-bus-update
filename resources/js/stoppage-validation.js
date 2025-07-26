/**
 * Client-side Stoppage Coordinate Validation
 * Provides real-time GPS validation and geofencing for bus stops
 */

class StoppageValidator {
    constructor() {
        this.busStops = {
            'Asad Gate': {
                lat: 23.7651,
                lng: 90.3668,
                radius: 200,
                aliases: ['asad_gate', 'asadgate']
            },
            'Shyamoli': {
                lat: 23.7746,
                lng: 90.3657,
                radius: 250,
                aliases: ['shyamoli_square', 'shyamoli']
            },
            'Mirpur-1': {
                lat: 23.7937,
                lng: 90.3629,
                radius: 300,
                aliases: ['mirpur_1', 'mirpur1', 'mirpur_one']
            },
            'Rainkhola': {
                lat: 23.8069,
                lng: 90.3554,
                radius: 200,
                aliases: ['rain_khola', 'rainkhola_bridge']
            },
            'BUBT': {
                lat: 23.8213,
                lng: 90.3541,
                radius: 150,
                aliases: ['bubt_campus', 'bangladesh_university']
            }
        };

        this.routeCorridors = {
            'Asad Gate -> Shyamoli': {
                start: 'Asad Gate',
                end: 'Shyamoli',
                corridorWidth: 500,
                waypoints: [
                    { lat: 23.7696, lng: 90.3662 },
                    { lat: 23.7721, lng: 90.3660 }
                ]
            },
            'Shyamoli -> Mirpur-1': {
                start: 'Shyamoli',
                end: 'Mirpur-1',
                corridorWidth: 400,
                waypoints: [
                    { lat: 23.7842, lng: 90.3643 },
                    { lat: 23.7890, lng: 90.3636 }
                ]
            },
            'Mirpur-1 -> Rainkhola': {
                start: 'Mirpur-1',
                end: 'Rainkhola',
                corridorWidth: 350,
                waypoints: [
                    { lat: 23.8003, lng: 90.3592 },
                    { lat: 23.8036, lng: 90.3573 }
                ]
            },
            'Rainkhola -> BUBT': {
                start: 'Rainkhola',
                end: 'BUBT',
                corridorWidth: 300,
                waypoints: [
                    { lat: 23.8141, lng: 90.3548 }
                ]
            }
        };

        this.earthRadius = 6371000; // meters
        this.validationCallbacks = [];
        this.lastValidation = null;
    }

    /**
     * Validate GPS coordinates against bus stop radius
     */
    validateStoppageRadius(lat, lng, expectedStop = null) {
        const result = {
            isValid: false,
            closestStop: null,
            distanceToClosest: null,
            withinRadius: false,
            expectedStopMatch: false,
            validationDetails: []
        };

        let closestStop = null;
        let minDistance = Infinity;

        // Check distance to all bus stops
        for (const [stopName, stopData] of Object.entries(this.busStops)) {
            const distance = this.calculateDistance(lat, lng, stopData.lat, stopData.lng);
            
            if (distance < minDistance) {
                minDistance = distance;
                closestStop = stopName;
            }

            // Check if within radius of this stop
            const withinRadius = distance <= stopData.radius;
            
            result.validationDetails.push({
                stop: stopName,
                distance: Math.round(distance * 100) / 100,
                radius: stopData.radius,
                withinRadius: withinRadius
            });

            if (withinRadius) {
                result.isValid = true;
                result.withinRadius = true;

                // Check if matches expected stop
                if (expectedStop && this.isStopMatch(stopName, expectedStop)) {
                    result.expectedStopMatch = true;
                }
            }
        }

        result.closestStop = closestStop;
        result.distanceToClosest = Math.round(minDistance * 100) / 100;

        // Store last validation for reference
        this.lastValidation = {
            type: 'stoppage',
            timestamp: Date.now(),
            coordinates: { lat, lng },
            result: result
        };

        // Trigger callbacks
        this.triggerValidationCallbacks(result);

        return result;
    }

    /**
     * Validate if user is within route corridor
     */
    validateRouteCorridorPath(lat, lng, fromStop, toStop) {
        const corridorKey = `${fromStop} -> ${toStop}`;
        
        const result = {
            isValid: false,
            withinCorridor: false,
            distanceFromPath: null,
            corridorWidth: null,
            pathProgress: null
        };

        // Check if corridor exists
        if (!this.routeCorridors[corridorKey]) {
            result.error = `Route corridor not defined for ${corridorKey}`;
            return result;
        }

        const corridor = this.routeCorridors[corridorKey];
        result.corridorWidth = corridor.corridorWidth;

        // Build complete path
        const pathPoints = [];
        pathPoints.push(this.busStops[fromStop]);
        pathPoints.push(...corridor.waypoints);
        pathPoints.push(this.busStops[toStop]);

        // Find closest point on the path
        let minDistanceToPath = Infinity;
        let pathProgress = 0;

        for (let i = 0; i < pathPoints.length - 1; i++) {
            const segmentStart = pathPoints[i];
            const segmentEnd = pathPoints[i + 1];
            
            const distanceToSegment = this.distanceToLineSegment(
                lat, lng,
                segmentStart.lat, segmentStart.lng,
                segmentEnd.lat, segmentEnd.lng
            );

            if (distanceToSegment < minDistanceToPath) {
                minDistanceToPath = distanceToSegment;
                pathProgress = (i + 1) / pathPoints.length;
            }
        }

        result.distanceFromPath = Math.round(minDistanceToPath * 100) / 100;
        result.pathProgress = Math.round(pathProgress * 100 * 10) / 10;

        // Check if within corridor width
        if (minDistanceToPath <= corridor.corridorWidth) {
            result.isValid = true;
            result.withinCorridor = true;
        }

        return result;
    }

    /**
     * Real-time validation with visual feedback
     */
    startRealTimeValidation(options = {}) {
        const {
            interval = 5000, // 5 seconds
            expectedStop = null,
            busId = null,
            onValidation = null,
            onOutsideArea = null
        } = options;

        if (!navigator.geolocation) {
            console.error('Geolocation not supported');
            return null;
        }

        const watchId = navigator.geolocation.watchPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                
                // Validate coordinates
                const validation = this.validateStoppageRadius(latitude, longitude, expectedStop);
                
                // Add accuracy information
                validation.gpsAccuracy = accuracy;
                validation.timestamp = Date.now();
                
                // Trigger callbacks
                if (onValidation) {
                    onValidation(validation);
                }
                
                if (!validation.isValid && onOutsideArea) {
                    onOutsideArea(validation);
                }
                
                // Update UI indicators
                this.updateValidationUI(validation);
                
                // Log for debugging
                console.log('Real-time validation:', validation);
            },
            (error) => {
                console.error('Geolocation error:', error);
                this.showValidationError(error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 30000
            }
        );

        return watchId;
    }

    /**
     * Stop real-time validation
     */
    stopRealTimeValidation(watchId) {
        if (watchId && navigator.geolocation) {
            navigator.geolocation.clearWatch(watchId);
        }
    }

    /**
     * Update UI with validation results
     */
    updateValidationUI(validation) {
        // Update validation status indicator
        const statusIndicator = document.querySelector('.validation-status-indicator');
        if (statusIndicator) {
            statusIndicator.className = `validation-status-indicator ${validation.isValid ? 'valid' : 'invalid'}`;
            statusIndicator.title = validation.isValid ? 
                `Valid location near ${validation.closestStop}` : 
                `Outside valid area (${validation.distanceToClosest}m from ${validation.closestStop})`;
        }

        // Update distance display
        const distanceDisplay = document.querySelector('.distance-to-stop');
        if (distanceDisplay && validation.closestStop) {
            distanceDisplay.textContent = `${validation.distanceToClosest}m from ${validation.closestStop}`;
        }

        // Update validation details
        const detailsContainer = document.querySelector('.validation-details');
        if (detailsContainer) {
            detailsContainer.innerHTML = this.generateValidationDetailsHTML(validation);
        }
    }

    /**
     * Generate HTML for validation details
     */
    generateValidationDetailsHTML(validation) {
        let html = '<div class="validation-summary">';
        
        if (validation.isValid) {
            html += '<div class="validation-success"><i class="bi bi-check-circle"></i> Valid Location</div>';
        } else {
            html += '<div class="validation-warning"><i class="bi bi-exclamation-triangle"></i> Outside Valid Area</div>';
        }
        
        html += `<div class="closest-stop">Closest: ${validation.closestStop} (${validation.distanceToClosest}m)</div>`;
        
        if (validation.gpsAccuracy) {
            html += `<div class="gps-accuracy">GPS Accuracy: ±${Math.round(validation.gpsAccuracy)}m</div>`;
        }
        
        html += '</div>';
        
        // Add details for each stop
        html += '<div class="stop-details">';
        validation.validationDetails.forEach(detail => {
            const statusClass = detail.withinRadius ? 'within-radius' : 'outside-radius';
            html += `
                <div class="stop-detail ${statusClass}">
                    <span class="stop-name">${detail.stop}</span>
                    <span class="stop-distance">${detail.distance}m</span>
                    <span class="stop-radius">(±${detail.radius}m)</span>
                </div>
            `;
        });
        html += '</div>';
        
        return html;
    }

    /**
     * Show validation error
     */
    showValidationError(message) {
        const errorContainer = document.querySelector('.validation-error');
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        }
    }

    /**
     * Add validation callback
     */
    onValidation(callback) {
        this.validationCallbacks.push(callback);
    }

    /**
     * Trigger validation callbacks
     */
    triggerValidationCallbacks(result) {
        this.validationCallbacks.forEach(callback => {
            try {
                callback(result);
            } catch (error) {
                console.error('Validation callback error:', error);
            }
        });
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const lat1Rad = lat1 * Math.PI / 180;
        const lng1Rad = lng1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;
        const lng2Rad = lng2 * Math.PI / 180;

        const deltaLat = lat2Rad - lat1Rad;
        const deltaLng = lng2Rad - lng1Rad;

        const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
                  Math.cos(lat1Rad) * Math.cos(lat2Rad) *
                  Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return this.earthRadius * c;
    }

    /**
     * Calculate distance from point to line segment
     */
    distanceToLineSegment(px, py, x1, y1, x2, y2) {
        const A = px - x1;
        const B = py - y1;
        const C = x2 - x1;
        const D = y2 - y1;

        const dot = A * C + B * D;
        const lenSq = C * C + D * D;
        
        if (lenSq === 0) {
            return this.calculateDistance(py, px, y1, x1);
        }

        const param = dot / lenSq;

        if (param < 0) {
            return this.calculateDistance(py, px, y1, x1);
        } else if (param > 1) {
            return this.calculateDistance(py, px, y2, x2);
        } else {
            const closestX = x1 + param * C;
            const closestY = y1 + param * D;
            return this.calculateDistance(py, px, closestY, closestX);
        }
    }

    /**
     * Check if stop name matches expected stop
     */
    isStopMatch(stopName, expectedStop) {
        const expectedLower = expectedStop.toLowerCase().trim();
        const stopLower = stopName.toLowerCase().trim();

        if (stopLower === expectedLower) {
            return true;
        }

        // Check aliases
        const stopData = this.busStops[stopName];
        if (stopData && stopData.aliases) {
            return stopData.aliases.some(alias => 
                alias.toLowerCase() === expectedLower
            );
        }

        return false;
    }

    /**
     * Get geofencing boundaries for map visualization
     */
    getGeofencingBoundaries() {
        const boundaries = [];

        // Add stop circles
        for (const [stopName, stopData] of Object.entries(this.busStops)) {
            boundaries.push({
                type: 'circle',
                name: stopName,
                center: {
                    lat: stopData.lat,
                    lng: stopData.lng
                },
                radius: stopData.radius,
                style: {
                    color: '#1a73e8',
                    fillColor: 'rgba(26, 115, 232, 0.2)',
                    weight: 2
                }
            });
        }

        // Add route corridors
        for (const [corridorName, corridorData] of Object.entries(this.routeCorridors)) {
            const pathPoints = [];
            pathPoints.push(this.busStops[corridorData.start]);
            pathPoints.push(...corridorData.waypoints);
            pathPoints.push(this.busStops[corridorData.end]);

            boundaries.push({
                type: 'corridor',
                name: corridorName,
                path: pathPoints,
                width: corridorData.corridorWidth,
                style: {
                    color: '#ff6b35',
                    fillColor: 'rgba(255, 107, 53, 0.1)',
                    weight: 1,
                    dashArray: '5, 5'
                }
            });
        }

        return boundaries;
    }

    /**
     * Create validation UI elements
     */
    createValidationUI() {
        const container = document.createElement('div');
        container.className = 'validation-container';
        container.innerHTML = `
            <div class="validation-status">
                <div class="validation-status-indicator invalid" title="Validation status"></div>
                <span class="validation-label">Location Status</span>
            </div>
            <div class="distance-to-stop">Calculating...</div>
            <div class="validation-details"></div>
            <div class="validation-error" style="display: none;"></div>
        `;

        return container;
    }
}

// Export for use in other modules
window.StoppageValidator = StoppageValidator;