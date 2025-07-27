/**
 * Connection Manager for Bus Tracker
 * Handles WebSocket connections with AJAX polling fallback
 */

class ConnectionManager {
    constructor(options = {}) {
        this.options = {
            pollingInterval: options.pollingInterval || 10000, // 10 seconds
            reconnectInterval: options.reconnectInterval || 5000, // 5 seconds
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            apiBaseUrl: options.apiBaseUrl || '/api/polling',
            enableWebSocket: options.enableWebSocket !== false,
            debug: options.debug || false,
            ...options
        };

        this.websocket = null;
        this.pollingTimer = null;
        this.reconnectTimer = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.connectionType = 'none'; // 'websocket', 'polling', 'none'
        this.lastUpdate = null;
        this.subscribers = new Map();
        this.connectionStatusCallbacks = [];

        this.init();
    }

    init() {
        this.log('Initializing connection manager');
        
        // Try WebSocket first if enabled
        if (this.options.enableWebSocket) {
            this.connectWebSocket();
        } else {
            this.startPolling();
        }

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.handlePageHidden();
            } else {
                this.handlePageVisible();
            }
        });

        // Handle online/offline events
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
    }

    /**
     * WebSocket Connection Management
     */
    connectWebSocket() {
        if (!window.WebSocket) {
            this.log('WebSocket not supported, falling back to polling');
            this.startPolling();
            return;
        }

        try {
            // This would connect to Laravel Reverb when implemented
            // For now, we'll simulate WebSocket failure and use polling
            this.log('WebSocket connection not yet implemented, using polling fallback');
            this.startPolling();
            return;

            // Future WebSocket implementation:
            // const wsUrl = `ws://${window.location.host}/ws`;
            // this.websocket = new WebSocket(wsUrl);
            // this.setupWebSocketHandlers();
        } catch (error) {
            this.log('WebSocket connection failed:', error);
            this.startPolling();
        }
    }

    setupWebSocketHandlers() {
        if (!this.websocket) return;

        this.websocket.onopen = () => {
            this.log('WebSocket connected');
            this.isConnected = true;
            this.connectionType = 'websocket';
            this.reconnectAttempts = 0;
            this.stopPolling();
            this.notifyConnectionStatus('connected', 'websocket');
        };

        this.websocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            } catch (error) {
                this.log('Error parsing WebSocket message:', error);
            }
        };

        this.websocket.onclose = (event) => {
            this.log('WebSocket closed:', event.code, event.reason);
            this.isConnected = false;
            this.connectionType = 'none';
            this.websocket = null;
            
            if (!event.wasClean) {
                this.handleConnectionLoss();
            }
        };

        this.websocket.onerror = (error) => {
            this.log('WebSocket error:', error);
            this.handleConnectionLoss();
        };
    }

    /**
     * Polling System
     */
    startPolling() {
        this.log('Starting polling system');
        this.stopPolling(); // Clear any existing polling
        
        this.connectionType = 'polling';
        this.isConnected = true;
        this.notifyConnectionStatus('connected', 'polling');
        
        // Start immediate poll
        this.poll();
        
        // Set up regular polling
        this.pollingTimer = setInterval(() => {
            this.poll();
        }, this.options.pollingInterval);
    }

    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    }

    async poll() {
        try {
            // Get subscribed bus IDs
            const busIds = Array.from(this.subscribers.keys());
            
            if (busIds.length === 0) {
                return; // No subscriptions, skip polling
            }

            const params = new URLSearchParams({
                bus_ids: busIds.join(','),
                last_update: this.lastUpdate || ''
            });

            const response = await fetch(`${this.options.apiBaseUrl}/locations?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success && data.locations) {
                this.lastUpdate = data.timestamp;
                
                // Process each bus location update
                Object.entries(data.locations).forEach(([busId, locationData]) => {
                    this.handleMessage({
                        type: 'location_update',
                        bus_id: busId,
                        data: locationData
                    });
                });
            }

            // Reset connection error state if polling succeeds
            if (!this.isConnected) {
                this.isConnected = true;
                this.connectionType = 'polling';
                this.reconnectAttempts = 0;
                this.notifyConnectionStatus('connected', 'polling');
            }

        } catch (error) {
            this.log('Polling error:', error);
            this.handlePollingError(error);
        }
    }

    handlePollingError(error) {
        this.isConnected = false;
        this.connectionType = 'none';
        this.notifyConnectionStatus('error', 'polling', error.message);
        
        // Implement exponential backoff for polling errors
        const backoffDelay = Math.min(
            this.options.reconnectInterval * Math.pow(2, this.reconnectAttempts),
            30000 // Max 30 seconds
        );
        
        this.reconnectAttempts++;
        
        setTimeout(() => {
            if (this.connectionType === 'none') {
                this.startPolling();
            }
        }, backoffDelay);
    }

    /**
     * Connection Loss Handling
     */
    handleConnectionLoss() {
        this.isConnected = false;
        this.connectionType = 'none';
        this.notifyConnectionStatus('disconnected', 'none');
        
        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.scheduleReconnect();
        } else {
            this.log('Max reconnection attempts reached, falling back to polling');
            this.startPolling();
        }
    }

    scheduleReconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }

        const delay = this.options.reconnectInterval * (this.reconnectAttempts + 1);
        this.log(`Scheduling reconnection attempt ${this.reconnectAttempts + 1} in ${delay}ms`);
        
        this.reconnectTimer = setTimeout(() => {
            this.reconnectAttempts++;
            this.notifyConnectionStatus('reconnecting', 'websocket');
            this.connectWebSocket();
        }, delay);
    }

    /**
     * Page Visibility Handling
     */
    handlePageHidden() {
        this.log('Page hidden, reducing connection activity');
        // Could reduce polling frequency or pause WebSocket
    }

    handlePageVisible() {
        this.log('Page visible, resuming normal connection activity');
        // Resume normal polling frequency
        if (this.connectionType === 'polling') {
            this.poll(); // Immediate poll when page becomes visible
        }
    }

    handleOnline() {
        this.log('Network online, attempting to reconnect');
        if (!this.isConnected) {
            if (this.options.enableWebSocket) {
                this.connectWebSocket();
            } else {
                this.startPolling();
            }
        }
    }

    handleOffline() {
        this.log('Network offline');
        this.isConnected = false;
        this.connectionType = 'none';
        this.notifyConnectionStatus('offline', 'none');
    }

    /**
     * Message Handling
     */
    handleMessage(message) {
        this.log('Received message:', message);
        
        if (message.type === 'location_update' && message.bus_id) {
            const callbacks = this.subscribers.get(message.bus_id);
            if (callbacks) {
                callbacks.forEach(callback => {
                    try {
                        callback(message.data, message.bus_id);
                    } catch (error) {
                        this.log('Error in subscriber callback:', error);
                    }
                });
            }
        }
    }

    /**
     * Subscription Management
     */
    subscribe(busId, callback) {
        if (!this.subscribers.has(busId)) {
            this.subscribers.set(busId, new Set());
        }
        
        this.subscribers.get(busId).add(callback);
        this.log(`Subscribed to bus ${busId}`);
        
        // If using WebSocket, send subscription message
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'subscribe',
                bus_id: busId
            }));
        }
        
        return () => this.unsubscribe(busId, callback);
    }

    unsubscribe(busId, callback) {
        const callbacks = this.subscribers.get(busId);
        if (callbacks) {
            callbacks.delete(callback);
            if (callbacks.size === 0) {
                this.subscribers.delete(busId);
                this.log(`Unsubscribed from bus ${busId}`);
                
                // If using WebSocket, send unsubscription message
                if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                    this.websocket.send(JSON.stringify({
                        type: 'unsubscribe',
                        bus_id: busId
                    }));
                }
            }
        }
    }

    /**
     * Location Submission
     */
    async submitLocation(locationData) {
        try {
            const response = await fetch(`${this.options.apiBaseUrl}/location`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(locationData)
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}`);
            }

            return result;
        } catch (error) {
            this.log('Error submitting location:', error);
            throw error;
        }
    }

    /**
     * Connection Status Management
     */
    onConnectionStatusChange(callback) {
        this.connectionStatusCallbacks.push(callback);
        
        // Immediately call with current status
        callback({
            connected: this.isConnected,
            type: this.connectionType,
            reconnectAttempts: this.reconnectAttempts
        });
        
        return () => {
            const index = this.connectionStatusCallbacks.indexOf(callback);
            if (index > -1) {
                this.connectionStatusCallbacks.splice(index, 1);
            }
        };
    }

    notifyConnectionStatus(status, type, message = null) {
        const statusData = {
            status,
            type,
            connected: status === 'connected',
            reconnectAttempts: this.reconnectAttempts,
            message
        };

        this.connectionStatusCallbacks.forEach(callback => {
            try {
                callback(statusData);
            } catch (error) {
                this.log('Error in connection status callback:', error);
            }
        });
    }

    /**
     * Health Check
     */
    async healthCheck() {
        try {
            const response = await fetch(`${this.options.apiBaseUrl}/health`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            return response.ok;
        } catch (error) {
            this.log('Health check failed:', error);
            return false;
        }
    }

    /**
     * Cleanup
     */
    destroy() {
        this.log('Destroying connection manager');
        
        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }
        
        this.stopPolling();
        
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        
        this.subscribers.clear();
        this.connectionStatusCallbacks = [];
        this.isConnected = false;
        this.connectionType = 'none';
    }

    /**
     * Utility Methods
     */
    getConnectionInfo() {
        return {
            connected: this.isConnected,
            type: this.connectionType,
            reconnectAttempts: this.reconnectAttempts,
            subscribedBuses: Array.from(this.subscribers.keys())
        };
    }

    log(...args) {
        if (this.options.debug) {
            console.log('[ConnectionManager]', ...args);
        }
    }
}

// Export for use in other modules
window.ConnectionManager = ConnectionManager;

export default ConnectionManager;