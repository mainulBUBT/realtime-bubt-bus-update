/**
 * Device Fingerprinting Utility
 * Generates unique browser fingerprints for device identification
 * and manages local storage for device tokens
 */
class DeviceFingerprint {
    constructor() {
        this.storageKey = 'bus_tracker_device_token';
        this.fingerprintKey = 'bus_tracker_fingerprint';
    }

    /**
     * Generate a comprehensive browser fingerprint
     * @returns {Promise<object>} Fingerprint data object
     */
    async generateFingerprint() {
        const fingerprint = {
            // Screen information
            screen: {
                width: screen.width,
                height: screen.height,
                availWidth: screen.availWidth,
                availHeight: screen.availHeight,
                colorDepth: screen.colorDepth,
                pixelDepth: screen.pixelDepth,
                orientation: screen.orientation ? screen.orientation.type : null
            },

            // Navigator information
            navigator: {
                userAgent: navigator.userAgent,
                language: navigator.language,
                languages: navigator.languages ? navigator.languages.join(',') : null,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                doNotTrack: navigator.doNotTrack,
                hardwareConcurrency: navigator.hardwareConcurrency,
                maxTouchPoints: navigator.maxTouchPoints,
                vendor: navigator.vendor,
                vendorSub: navigator.vendorSub,
                productSub: navigator.productSub,
                appName: navigator.appName,
                appVersion: navigator.appVersion
            },

            // Timezone information
            timezone: {
                offset: new Date().getTimezoneOffset(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            },

            // Canvas fingerprinting
            canvas: await this.getCanvasFingerprint(),

            // WebGL fingerprinting
            webgl: this.getWebGLFingerprint(),

            // Audio context fingerprinting
            audio: await this.getAudioFingerprint(),

            // Performance timing
            timing: this.getTimingFingerprint(),

            // Additional browser features
            features: {
                localStorage: this.hasLocalStorage(),
                sessionStorage: this.hasSessionStorage(),
                indexedDB: this.hasIndexedDB(),
                webWorkers: typeof Worker !== 'undefined',
                webSockets: typeof WebSocket !== 'undefined',
                geolocation: 'geolocation' in navigator,
                touchSupport: 'ontouchstart' in window,
                deviceMemory: navigator.deviceMemory || null,
                connection: navigator.connection ? {
                    effectiveType: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink,
                    rtt: navigator.connection.rtt
                } : null
            },

            // Timestamp for fingerprint generation
            timestamp: Date.now()
        };

        return fingerprint;
    }

    /**
     * Generate canvas fingerprint
     * @returns {Promise<string>} Canvas fingerprint hash
     */
    async getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = 200;
            canvas.height = 50;
            
            // Draw text with various properties
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('Bus Tracker Fingerprint', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Device ID Generation', 4, 35);
            
            // Draw some shapes
            ctx.globalCompositeOperation = 'multiply';
            ctx.fillStyle = 'rgb(255,0,255)';
            ctx.beginPath();
            ctx.arc(50, 25, 20, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            
            return canvas.toDataURL();
        } catch (e) {
            return 'canvas_not_supported';
        }
    }

    /**
     * Generate WebGL fingerprint
     * @returns {object} WebGL fingerprint data
     */
    getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) {
                return { supported: false };
            }

            return {
                supported: true,
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                maxTextureSize: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                maxViewportDims: gl.getParameter(gl.MAX_VIEWPORT_DIMS),
                maxVertexAttribs: gl.getParameter(gl.MAX_VERTEX_ATTRIBS),
                extensions: gl.getSupportedExtensions()
            };
        } catch (e) {
            return { supported: false, error: e.message };
        }
    }

    /**
     * Generate audio context fingerprint
     * @returns {Promise<string>} Audio fingerprint
     */
    async getAudioFingerprint() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return 'audio_not_supported';
            }

            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const analyser = context.createAnalyser();
            const gain = context.createGain();
            const scriptProcessor = context.createScriptProcessor(4096, 1, 1);

            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(10000, context.currentTime);

            gain.gain.setValueAtTime(0, context.currentTime);

            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gain);
            gain.connect(context.destination);

            oscillator.start(0);

            return new Promise((resolve) => {
                scriptProcessor.onaudioprocess = function(bins) {
                    const samples = bins.inputBuffer.getChannelData(0);
                    let sum = 0;
                    for (let i = 0; i < samples.length; i++) {
                        sum += Math.abs(samples[i]);
                    }
                    
                    oscillator.stop();
                    context.close();
                    
                    resolve(sum.toString());
                };
            });
        } catch (e) {
            return 'audio_error';
        }
    }

    /**
     * Get performance timing fingerprint
     * @returns {object} Timing data
     */
    getTimingFingerprint() {
        try {
            const timing = performance.timing;
            return {
                navigationStart: timing.navigationStart,
                loadEventEnd: timing.loadEventEnd,
                domContentLoadedEventEnd: timing.domContentLoadedEventEnd,
                responseEnd: timing.responseEnd,
                requestStart: timing.requestStart,
                domainLookupEnd: timing.domainLookupEnd,
                domainLookupStart: timing.domainLookupStart,
                connectEnd: timing.connectEnd,
                connectStart: timing.connectStart
            };
        } catch (e) {
            return { error: 'timing_not_available' };
        }
    }

    /**
     * Check if localStorage is available
     * @returns {boolean}
     */
    hasLocalStorage() {
        try {
            const test = 'test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if sessionStorage is available
     * @returns {boolean}
     */
    hasSessionStorage() {
        try {
            const test = 'test';
            sessionStorage.setItem(test, test);
            sessionStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if IndexedDB is available
     * @returns {boolean}
     */
    hasIndexedDB() {
        return 'indexedDB' in window;
    }

    /**
     * Generate a hash from fingerprint data
     * @param {object} fingerprint - Fingerprint data
     * @returns {string} Hash string
     */
    async hashFingerprint(fingerprint) {
        const jsonString = JSON.stringify(fingerprint);
        
        if (crypto && crypto.subtle) {
            // Use Web Crypto API if available
            const encoder = new TextEncoder();
            const data = encoder.encode(jsonString);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } else {
            // Fallback to simple hash function
            return this.simpleHash(jsonString);
        }
    }

    /**
     * Simple hash function fallback
     * @param {string} str - String to hash
     * @returns {string} Hash string
     */
    simpleHash(str) {
        let hash = 0;
        if (str.length === 0) return hash.toString();
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        
        return Math.abs(hash).toString(16);
    }

    /**
     * Store device token in local storage
     * @param {string} token - Device token to store
     */
    storeToken(token) {
        if (this.hasLocalStorage()) {
            try {
                localStorage.setItem(this.storageKey, token);
                localStorage.setItem(this.storageKey + '_timestamp', Date.now().toString());
                return true;
            } catch (e) {
                console.warn('Failed to store device token:', e);
                return false;
            }
        }
        return false;
    }

    /**
     * Get stored device token from local storage
     * @returns {string|null} Stored token or null
     */
    getStoredToken() {
        if (this.hasLocalStorage()) {
            try {
                return localStorage.getItem(this.storageKey);
            } catch (e) {
                console.warn('Failed to retrieve device token:', e);
                return null;
            }
        }
        return null;
    }

    /**
     * Store fingerprint data in local storage
     * @param {object} fingerprint - Fingerprint data to store
     */
    storeFingerprint(fingerprint) {
        if (this.hasLocalStorage()) {
            try {
                localStorage.setItem(this.fingerprintKey, JSON.stringify(fingerprint));
                return true;
            } catch (e) {
                console.warn('Failed to store fingerprint:', e);
                return false;
            }
        }
        return false;
    }

    /**
     * Get stored fingerprint from local storage
     * @returns {object|null} Stored fingerprint or null
     */
    getStoredFingerprint() {
        if (this.hasLocalStorage()) {
            try {
                const stored = localStorage.getItem(this.fingerprintKey);
                return stored ? JSON.parse(stored) : null;
            } catch (e) {
                console.warn('Failed to retrieve fingerprint:', e);
                return null;
            }
        }
        return null;
    }

    /**
     * Clear stored token and fingerprint
     */
    clearStoredData() {
        if (this.hasLocalStorage()) {
            try {
                localStorage.removeItem(this.storageKey);
                localStorage.removeItem(this.storageKey + '_timestamp');
                localStorage.removeItem(this.fingerprintKey);
                return true;
            } catch (e) {
                console.warn('Failed to clear stored data:', e);
                return false;
            }
        }
        return false;
    }

    /**
     * Check if stored token is still valid (not expired)
     * @param {number} maxAge - Maximum age in milliseconds (default: 30 days)
     * @returns {boolean}
     */
    isTokenValid(maxAge = 30 * 24 * 60 * 60 * 1000) {
        if (!this.hasLocalStorage()) return false;
        
        try {
            const timestamp = localStorage.getItem(this.storageKey + '_timestamp');
            if (!timestamp) return false;
            
            const age = Date.now() - parseInt(timestamp);
            return age < maxAge;
        } catch (e) {
            return false;
        }
    }

    /**
     * Validate token format
     * @param {string} token - Token to validate
     * @returns {boolean}
     */
    validateTokenFormat(token) {
        if (!token || typeof token !== 'string') return false;
        
        // Token should be a hex string of reasonable length
        const hexPattern = /^[a-f0-9]{32,128}$/i;
        return hexPattern.test(token);
    }

    /**
     * Get or generate device token
     * @returns {Promise<string>} Device token
     */
    async getOrGenerateToken() {
        // Check if we have a valid stored token
        const storedToken = this.getStoredToken();
        if (storedToken && this.validateTokenFormat(storedToken) && this.isTokenValid()) {
            return storedToken;
        }

        // Generate new fingerprint and token
        const fingerprint = await this.generateFingerprint();
        const fingerprintHash = await this.hashFingerprint(fingerprint);
        
        // Store fingerprint and token
        this.storeFingerprint(fingerprint);
        this.storeToken(fingerprintHash);
        
        return fingerprintHash;
    }
}

// Export for use in other modules
window.DeviceFingerprint = DeviceFingerprint;