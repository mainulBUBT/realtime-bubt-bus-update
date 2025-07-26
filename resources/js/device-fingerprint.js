/**
 * Device Fingerprinting and Token Management
 * Generates unique device tokens for anonymous user identification
 */

class DeviceFingerprint {
    constructor() {
        this.tokenKey = 'bus_tracker_device_token';
        this.fingerprintKey = 'bus_tracker_fingerprint';
        this.cachedFingerprint = null;
        this.cachedToken = null;
    }

    /**
     * Generate comprehensive device fingerprint
     */
    async generateFingerprint() {
        if (this.cachedFingerprint) {
            return this.cachedFingerprint;
        }

        const fingerprint = {
            // Screen information
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth,
                pixelDepth: screen.pixelDepth,
                availWidth: screen.availWidth,
                availHeight: screen.availHeight
            },
            
            // Navigator information
            navigator: {
                userAgent: navigator.userAgent,
                language: navigator.language,
                languages: navigator.languages ? navigator.languages.join(',') : '',
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                doNotTrack: navigator.doNotTrack,
                hardwareConcurrency: navigator.hardwareConcurrency || 0,
                maxTouchPoints: navigator.maxTouchPoints || 0,
                deviceMemory: navigator.deviceMemory || 0
            },
            
            // Timezone
            timezone: {
                offset: new Date().getTimezoneOffset(),
                zone: Intl.DateTimeFormat().resolvedOptions().timeZone
            },
            
            // Canvas fingerprint
            canvas: await this.generateCanvasFingerprint(),
            
            // WebGL fingerprint
            webgl: this.generateWebGLFingerprint(),
            
            // Audio context fingerprint
            audio: await this.generateAudioFingerprint(),
            
            // Connection information
            connection: this.getConnectionInfo(),
            
            // Battery information (if available)
            battery: await this.getBatteryInfo(),
            
            // Timestamp
            timestamp: Date.now()
        };

        this.cachedFingerprint = fingerprint;
        
        // Store in localStorage for consistency
        try {
            localStorage.setItem(this.fingerprintKey, JSON.stringify(fingerprint));
        } catch (error) {
            console.warn('Failed to store fingerprint:', error);
        }

        return fingerprint;
    }

    /**
     * Generate canvas-based fingerprint
     */
    async generateCanvasFingerprint() {
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
            ctx.fillText('Bus Tracker 🚌', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Device ID', 4, 35);
            
            // Draw some shapes
            ctx.globalCompositeOperation = 'multiply';
            ctx.fillStyle = 'rgb(255,0,255)';
            ctx.beginPath();
            ctx.arc(50, 25, 20, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            
            return canvas.toDataURL();
        } catch (error) {
            console.warn('Canvas fingerprint failed:', error);
            return 'canvas_unavailable';
        }
    }

    /**
     * Generate WebGL-based fingerprint
     */
    generateWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) {
                return 'webgl_unavailable';
            }

            const info = {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                maxTextureSize: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                maxViewportDims: gl.getParameter(gl.MAX_VIEWPORT_DIMS),
                maxVertexAttribs: gl.getParameter(gl.MAX_VERTEX_ATTRIBS)
            };

            // Get supported extensions
            const extensions = gl.getSupportedExtensions();
            info.extensions = extensions ? extensions.sort().join(',') : '';

            return info;
        } catch (error) {
            console.warn('WebGL fingerprint failed:', error);
            return 'webgl_error';
        }
    }

    /**
     * Generate audio context fingerprint
     */
    async generateAudioFingerprint() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return 'audio_unavailable';
            }

            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const analyser = context.createAnalyser();
            const gain = context.createGain();
            const scriptProcessor = context.createScriptProcessor(4096, 1, 1);

            gain.gain.value = 0; // Mute
            oscillator.type = 'triangle';
            oscillator.frequency.value = 10000;

            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gain);
            gain.connect(context.destination);

            oscillator.start(0);

            return new Promise((resolve) => {
                let samples = [];
                scriptProcessor.onaudioprocess = (event) => {
                    const sample = event.inputBuffer.getChannelData(0)[0];
                    if (sample) {
                        samples.push(sample);
                        if (samples.length > 1000) {
                            oscillator.stop();
                            context.close();
                            
                            // Create hash from samples
                            const hash = samples.slice(0, 50).reduce((acc, val) => {
                                return acc + val.toString();
                            }, '');
                            
                            resolve(hash.substring(0, 50));
                        }
                    }
                };

                // Timeout fallback
                setTimeout(() => {
                    oscillator.stop();
                    context.close();
                    resolve('audio_timeout');
                }, 1000);
            });
        } catch (error) {
            console.warn('Audio fingerprint failed:', error);
            return 'audio_error';
        }
    }

    /**
     * Get connection information
     */
    getConnectionInfo() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (!connection) {
            return 'connection_unavailable';
        }

        return {
            effectiveType: connection.effectiveType,
            downlink: connection.downlink,
            rtt: connection.rtt,
            saveData: connection.saveData
        };
    }

    /**
     * Get battery information
     */
    async getBatteryInfo() {
        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                return {
                    charging: battery.charging,
                    level: Math.round(battery.level * 100),
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
            }
        } catch (error) {
            console.warn('Battery info failed:', error);
        }
        
        return 'battery_unavailable';
    }

    /**
     * Generate device token from fingerprint
     */
    async generateDeviceToken() {
        if (this.cachedToken) {
            return this.cachedToken;
        }

        // Check if token exists in localStorage
        const storedToken = localStorage.getItem(this.tokenKey);
        if (storedToken) {
            this.cachedToken = storedToken;
            return storedToken;
        }

        // Generate new token
        const fingerprint = await this.generateFingerprint();
        const fingerprintString = JSON.stringify(fingerprint);
        
        // Create hash from fingerprint
        const token = await this.hashString(fingerprintString);
        
        // Store token
        try {
            localStorage.setItem(this.tokenKey, token);
            this.cachedToken = token;
        } catch (error) {
            console.warn('Failed to store device token:', error);
        }

        return token;
    }

    /**
     * Hash string using Web Crypto API
     */
    async hashString(str) {
        try {
            const encoder = new TextEncoder();
            const data = encoder.encode(str);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (error) {
            console.warn('Crypto hash failed, using fallback:', error);
            // Fallback hash function
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32-bit integer
            }
            return Math.abs(hash).toString(16);
        }
    }

    /**
     * Get stored device token
     */
    getStoredToken() {
        if (this.cachedToken) {
            return this.cachedToken;
        }

        const storedToken = localStorage.getItem(this.tokenKey);
        if (storedToken) {
            this.cachedToken = storedToken;
            return storedToken;
        }

        return null;
    }

    /**
     * Clear stored token and fingerprint
     */
    clearStoredData() {
        try {
            localStorage.removeItem(this.tokenKey);
            localStorage.removeItem(this.fingerprintKey);
            this.cachedToken = null;
            this.cachedFingerprint = null;
        } catch (error) {
            console.warn('Failed to clear stored data:', error);
        }
    }

    /**
     * Validate token integrity
     */
    async validateToken(token) {
        if (!token) return false;

        try {
            const currentFingerprint = await this.generateFingerprint();
            const currentToken = await this.hashString(JSON.stringify(currentFingerprint));
            
            // Allow some variation in fingerprint due to dynamic properties
            return token === currentToken || this.isTokenSimilar(token, currentToken);
        } catch (error) {
            console.warn('Token validation failed:', error);
            return false;
        }
    }

    /**
     * Check if tokens are similar (allowing for minor variations)
     */
    isTokenSimilar(token1, token2) {
        if (!token1 || !token2 || token1.length !== token2.length) {
            return false;
        }

        let differences = 0;
        for (let i = 0; i < token1.length; i++) {
            if (token1[i] !== token2[i]) {
                differences++;
            }
        }

        // Allow up to 5% difference
        return (differences / token1.length) < 0.05;
    }

    /**
     * Get device information for debugging
     */
    async getDeviceInfo() {
        const fingerprint = await this.generateFingerprint();
        const token = await this.generateDeviceToken();
        
        return {
            token: token,
            fingerprint: fingerprint,
            userAgent: navigator.userAgent,
            timestamp: Date.now()
        };
    }
}

// Export for use in other modules
window.DeviceFingerprint = DeviceFingerprint;