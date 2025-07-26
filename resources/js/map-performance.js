/**
 * Map Performance Optimization Module
 * Handles tile caching, clustering, and smooth rendering for OpenStreetMap with Leaflet
 */

class MapPerformanceOptimizer {
    constructor() {
        this.tileCache = new Map();
        this.clusterGroup = null;
        this.busMarkers = new Map();
        this.offlineCache = null;
        this.dhakaRegionBounds = {
            north: 23.9,
            south: 23.6,
            east: 90.5,
            west: 90.2
        };
        this.optimalZoomLevels = {
            mobile: { min: 11, max: 17, default: 13 },
            tablet: { min: 12, max: 18, default: 14 },
            desktop: { min: 13, max: 19, default: 15 }
        };
        
        this.initOfflineCache();
    }

    /**
     * Initialize offline caching using browser storage
     */
    async initOfflineCache() {
        if ('caches' in window) {
            try {
                this.offlineCache = await caches.open('bus-tracker-map-tiles-v1');
                console.log('Offline map cache initialized');
            } catch (error) {
                console.warn('Failed to initialize offline cache:', error);
            }
        }
    }

    /**
     * Create optimized tile layer with caching strategy
     */
    createOptimizedTileLayer() {
        const deviceType = this.getDeviceType();
        const zoomConfig = this.optimalZoomLevels[deviceType];
        
        return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: zoomConfig.max,
            minZoom: zoomConfig.min,
            // Performance optimizations
            updateWhenIdle: true,
            updateWhenZooming: false,
            keepBuffer: 2,
            // Tile loading optimizations
            crossOrigin: true,
            // Custom tile loading with caching
            tileLoadFunction: this.cachedTileLoader.bind(this),
            // Bounds restriction for Dhaka region
            bounds: L.latLngBounds(
                [this.dhakaRegionBounds.south, this.dhakaRegionBounds.west],
                [this.dhakaRegionBounds.north, this.dhakaRegionBounds.east]
            )
        });
    }

    /**
     * Custom tile loader with caching strategy
     */
    async cachedTileLoader(tile, url) {
        const cacheKey = this.generateTileCacheKey(url);
        
        // Check memory cache first
        if (this.tileCache.has(cacheKey)) {
            const cachedData = this.tileCache.get(cacheKey);
            tile.src = cachedData;
            return;
        }

        // Check offline cache
        if (this.offlineCache) {
            try {
                const cachedResponse = await this.offlineCache.match(url);
                if (cachedResponse) {
                    const blob = await cachedResponse.blob();
                    const objectUrl = URL.createObjectURL(blob);
                    tile.src = objectUrl;
                    this.tileCache.set(cacheKey, objectUrl);
                    return;
                }
            } catch (error) {
                console.warn('Failed to load from offline cache:', error);
            }
        }

        // Load from network and cache
        try {
            const response = await fetch(url);
            if (response.ok) {
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                
                // Store in memory cache
                tile.src = objectUrl;
                this.tileCache.set(cacheKey, objectUrl);
                
                // Store in offline cache for frequently accessed areas
                if (this.offlineCache && this.isFrequentlyAccessedArea(url)) {
                    this.offlineCache.put(url, response.clone());
                }
            } else {
                throw new Error(`Failed to load tile: ${response.status}`);
            }
        } catch (error) {
            console.error('Tile loading failed:', error);
            // Fallback to default loading
            tile.src = url;
        }
    }

    /**
     * Generate cache key for tiles
     */
    generateTileCacheKey(url) {
        return url.replace(/[^a-zA-Z0-9]/g, '_');
    }

    /**
     * Check if area is frequently accessed (within Dhaka bus routes)
     */
    isFrequentlyAccessedArea(url) {
        // Extract coordinates from tile URL
        const matches = url.match(/\/(\d+)\/(\d+)\/(\d+)\.png/);
        if (!matches) return false;
        
        const [, z, x, y] = matches.map(Number);
        
        // Convert tile coordinates to lat/lng
        const n = Math.pow(2, z);
        const lon = (x / n) * 360 - 180;
        const lat = Math.atan(Math.sinh(Math.PI * (1 - 2 * y / n))) * 180 / Math.PI;
        
        // Check if within Dhaka region bounds
        return lat >= this.dhakaRegionBounds.south && 
               lat <= this.dhakaRegionBounds.north &&
               lon >= this.dhakaRegionBounds.west && 
               lon <= this.dhakaRegionBounds.east;
    }

    /**
     * Create marker clustering for multiple bus markers
     */
    createMarkerCluster() {
        // Only load clustering if Leaflet.markercluster is available
        if (typeof L.markerClusterGroup !== 'undefined') {
            this.clusterGroup = L.markerClusterGroup({
                // Clustering options optimized for bus markers
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                // Custom cluster icon
                iconCreateFunction: (cluster) => {
                    const count = cluster.getChildCount();
                    return L.divIcon({
                        html: `<div class="cluster-icon">${count}</div>`,
                        className: 'bus-cluster-marker',
                        iconSize: [40, 40]
                    });
                },
                // Performance optimizations
                chunkedLoading: true,
                chunkInterval: 200,
                chunkDelay: 50
            });
            
            return this.clusterGroup;
        } else {
            console.warn('Leaflet.markercluster not available, using regular layer group');
            return L.layerGroup();
        }
    }

    /**
     * Configure optimal map options for smooth performance
     */
    getOptimizedMapOptions() {
        const deviceType = this.getDeviceType();
        const zoomConfig = this.optimalZoomLevels[deviceType];
        
        return {
            // Zoom configuration
            zoomControl: false,
            minZoom: zoomConfig.min,
            maxZoom: zoomConfig.max,
            
            // Performance optimizations
            preferCanvas: true, // Use Canvas renderer for better performance
            updateWhenIdle: true,
            updateWhenZooming: false,
            
            // Smooth panning and zooming
            zoomAnimation: true,
            zoomAnimationThreshold: 4,
            fadeAnimation: true,
            markerZoomAnimation: true,
            
            // Touch and interaction optimizations
            tap: deviceType === 'mobile',
            tapTolerance: deviceType === 'mobile' ? 20 : 15,
            touchZoom: deviceType !== 'desktop',
            doubleClickZoom: true,
            scrollWheelZoom: deviceType === 'desktop',
            
            // Bounds restriction
            maxBounds: L.latLngBounds(
                [this.dhakaRegionBounds.south - 0.1, this.dhakaRegionBounds.west - 0.1],
                [this.dhakaRegionBounds.north + 0.1, this.dhakaRegionBounds.east + 0.1]
            ),
            maxBoundsViscosity: 1.0
        };
    }

    /**
     * Create optimized bus marker with performance considerations
     */
    createOptimizedBusMarker(busId, position, options = {}) {
        const deviceType = this.getDeviceType();
        const iconSize = deviceType === 'mobile' ? 36 : 40;
        
        const busIcon = L.divIcon({
            className: 'bus-marker-icon optimized',
            html: `<div class="marker-icon" data-bus-id="${busId}">${busId}</div>`,
            iconSize: [iconSize, iconSize],
            iconAnchor: [iconSize / 2, iconSize / 2],
            popupAnchor: [0, -iconSize / 2]
        });

        const marker = L.marker(position, {
            icon: busIcon,
            zIndexOffset: 1000,
            riseOnHover: true,
            ...options
        });

        // Store marker reference for efficient updates
        this.busMarkers.set(busId, marker);
        
        return marker;
    }

    /**
     * Efficiently update bus marker position
     */
    updateBusMarkerPosition(busId, newPosition, animate = true) {
        const marker = this.busMarkers.get(busId);
        if (!marker) return;

        if (animate && marker.setLatLng) {
            // Smooth animation for position updates
            marker.setLatLng(newPosition);
        } else {
            // Instant update for performance
            marker.setLatLng(newPosition);
        }
    }

    /**
     * Implement lazy loading for map tiles
     */
    enableLazyTileLoading(map) {
        let tileLoadTimeout;
        
        map.on('movestart', () => {
            // Clear any pending tile loads
            if (tileLoadTimeout) {
                clearTimeout(tileLoadTimeout);
            }
        });
        
        map.on('moveend', () => {
            // Delay tile loading slightly to avoid loading during rapid movements
            tileLoadTimeout = setTimeout(() => {
                map.eachLayer((layer) => {
                    if (layer._url && layer.redraw) {
                        layer.redraw();
                    }
                });
            }, 100);
        });
    }

    /**
     * Progressive enhancement for map features
     */
    enableProgressiveEnhancement(map) {
        // Start with basic functionality
        const basicFeatures = () => {
            // Essential map controls only
            this.addEssentialControls(map);
        };
        
        // Add enhanced features after initial load
        setTimeout(() => {
            this.addEnhancedFeatures(map);
        }, 1000);
        
        // Add advanced features for high-performance devices
        if (this.isHighPerformanceDevice()) {
            setTimeout(() => {
                this.addAdvancedFeatures(map);
            }, 2000);
        }
        
        basicFeatures();
    }

    /**
     * Add essential map controls
     */
    addEssentialControls(map) {
        // Custom zoom control
        const zoomControl = L.control.zoom({
            position: 'topright'
        });
        map.addControl(zoomControl);
    }

    /**
     * Add enhanced features
     */
    addEnhancedFeatures(map) {
        // Add scale control
        const scaleControl = L.control.scale({
            position: 'bottomleft',
            metric: true,
            imperial: false
        });
        map.addControl(scaleControl);
        
        // Add attribution control
        map.attributionControl.setPosition('bottomright');
    }

    /**
     * Add advanced features for high-performance devices
     */
    addAdvancedFeatures(map) {
        // Add fullscreen control if available
        if (L.control.fullscreen) {
            const fullscreenControl = L.control.fullscreen({
                position: 'topright'
            });
            map.addControl(fullscreenControl);
        }
        
        // Add location control if available
        if (L.control.locate) {
            const locateControl = L.control.locate({
                position: 'topright',
                strings: {
                    title: "Show me where I am"
                }
            });
            map.addControl(locateControl);
        }
    }

    /**
     * Detect device type for optimization
     */
    getDeviceType() {
        const width = window.innerWidth;
        if (width < 768) return 'mobile';
        if (width < 1024) return 'tablet';
        return 'desktop';
    }

    /**
     * Check if device has high performance capabilities
     */
    isHighPerformanceDevice() {
        // Check for hardware concurrency (CPU cores)
        const cores = navigator.hardwareConcurrency || 2;
        
        // Check for device memory (if available)
        const memory = navigator.deviceMemory || 4;
        
        // Check for connection speed
        const connection = navigator.connection;
        const isSlowConnection = connection && 
            (connection.effectiveType === 'slow-2g' || 
             connection.effectiveType === '2g' || 
             connection.effectiveType === '3g');
        
        return cores >= 4 && memory >= 4 && !isSlowConnection;
    }

    /**
     * Clean up resources to prevent memory leaks
     */
    cleanup() {
        // Clear tile cache
        this.tileCache.clear();
        
        // Clear bus markers
        this.busMarkers.clear();
        
        // Clear cluster group
        if (this.clusterGroup) {
            this.clusterGroup.clearLayers();
        }
        
        // Clear offline cache if needed
        if (this.offlineCache) {
            // Optionally clear old cache entries
            this.cleanupOfflineCache();
        }
    }

    /**
     * Clean up old offline cache entries
     */
    async cleanupOfflineCache() {
        if (!this.offlineCache) return;
        
        try {
            const keys = await this.offlineCache.keys();
            const now = Date.now();
            const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 days
            
            for (const request of keys) {
                const response = await this.offlineCache.match(request);
                if (response) {
                    const dateHeader = response.headers.get('date');
                    if (dateHeader) {
                        const cacheDate = new Date(dateHeader).getTime();
                        if (now - cacheDate > maxAge) {
                            await this.offlineCache.delete(request);
                        }
                    }
                }
            }
        } catch (error) {
            console.warn('Failed to cleanup offline cache:', error);
        }
    }

    /**
     * Preload tiles for frequently accessed areas
     */
    async preloadFrequentAreas() {
        const frequentAreas = [
            // BUBT area
            { lat: 23.8213, lng: 90.3541, zoom: 15 },
            // Mirpur-1 area
            { lat: 23.7937, lng: 90.3629, zoom: 15 },
            // Asad Gate area
            { lat: 23.7651, lng: 90.3668, zoom: 15 }
        ];
        
        for (const area of frequentAreas) {
            await this.preloadAreaTiles(area.lat, area.lng, area.zoom);
        }
    }

    /**
     * Preload tiles for a specific area
     */
    async preloadAreaTiles(lat, lng, zoom) {
        const tileSize = 256;
        const radius = 2; // Load 5x5 grid of tiles
        
        // Convert lat/lng to tile coordinates
        const n = Math.pow(2, zoom);
        const x = Math.floor((lng + 180) / 360 * n);
        const y = Math.floor((1 - Math.log(Math.tan(lat * Math.PI / 180) + 1 / Math.cos(lat * Math.PI / 180)) / Math.PI) / 2 * n);
        
        // Load surrounding tiles
        for (let dx = -radius; dx <= radius; dx++) {
            for (let dy = -radius; dy <= radius; dy++) {
                const tileX = x + dx;
                const tileY = y + dy;
                const tileUrl = `https://a.tile.openstreetmap.org/${zoom}/${tileX}/${tileY}.png`;
                
                try {
                    const response = await fetch(tileUrl);
                    if (response.ok && this.offlineCache) {
                        await this.offlineCache.put(tileUrl, response);
                    }
                } catch (error) {
                    console.warn(`Failed to preload tile: ${tileUrl}`, error);
                }
            }
        }
    }
}

// Export for use in other modules
window.MapPerformanceOptimizer = MapPerformanceOptimizer;