/**
 * WebSocket Client for Bus Tracker
 * Handles WebSocket connections with Laravel Reverb
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

class WebSocketClient {
    constructor(options = {}) {
        this.options = {
            host: options.host || window.location.hostname,
            port: options.port || 8080,
            scheme: options.scheme || 'ws',
            key: options.key || 'local-key',
            cluster: options.cluster || 'mt1',
            forceTLS: options.forceTLS || false,
            enabledTransports: ['ws', 'wss'],
            disabledTransports: ['xhr_polling', 'xhr_streaming'],
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            debug: options.debug || false,
            ...options
        };

        this.echo = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.subscriptions = new Map();
        this.connectionCallbacks = [];
        this.heartbeatInterval = null;

        this.init();
    }

    /**
     * Initialize WebSocket connection
     */
    init() {
        try {
            // Configure Pusher for Laravel Reverb
            window.Pusher = Pusher;

            this.echo = new Echo({
                broadcaster: 'reverb',
                key: this.options.key,
                wsHost: this.options.host,
                wsPort: this.options.port,
                wssPort: this.options.port,
                forceTLS: this.options.forceTLS,
                enabledTransports: this.options.enabledTransports,
                disabledTransports: this.options.disabledTransports,
                cluster: this.options.cluster,
                encrypted: this.options.forceTLS,
                authorizer: (channel, options) => {
                    return {
                        authorize: (socketId, callback) => {
                            // Custom authorization logic if needed
                            callback(false, {});
                        }
                    };
                }
            });

            this.setupConnectionHandlers();
            this.log('WebSocket client initialized');

        } catch (error) {
            this.log('Failed to initialize WebSocket client:', error);
            this.handleConnectionError(error);
        }
    }

    /**
     * Set up connection event handlers
     */
    setupConnectionHandlers() {
        if (!this.echo) return;

        // Connection established
        this.echo.connector.pusher.connection.bind('connected', () => {
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.startHeartbeat();
            this.notifyConnectionStatus('connected');
            this.log('WebSocket connected');
        });

        // Connection lost
        this.echo.connector.pusher.connection.bind('disconnected', () => {
            this.isConnected = false;
            this.stopHeartbeat();
            this.notifyConnectionStatus('disconnected');
            this.log('WebSocket disconnected');
        });

        // Connection error
        this.echo.connector.pusher.connection.bind('error', (error) => {
            this.log('WebSocket error:', error);
            this.handleConnectionError(error);
        });

        // Connection state changes
        this.echo.connector.pusher.connection.bind('state_change', (states) => {
            this.log('Connection state changed:', states.previous, '->', states.current);
            
            if (states.current === 'connecting') {
                this.notifyConnectionStatus('connecting');
            } else if (states.current === 'unavailable') {
                this.handleConnectionLoss();
            }
        });
    }

    /**
     * Subscribe to bus location updates
     */
    subscribeToBus(busId, callback) {
        if (!this.echo) {
            this.log('Echo not initialized, cannot subscribe to bus:', busId);
            return null;
        }

        try {
            const channelName = `bus.${busId}`;
            
            // Check if already subscribed
            if (this.subscriptions.has(channelName)) {
                this.log('Already subscribed to bus:', busId);
                return this.subscriptions.get(channelName);
            }

            const channel = this.echo.channel(channelName);
            
            // Listen for location updates
            channel.listen('location.updated', (data) => {
                this.log('Received location update for bus:', busId, data);
                callback(data, busId);
            });

            // Listen for tracking status changes
            channel.listen('tracking.status.changed', (data) => {
                this.log('Received tracking status change for bus:', busId, data);
                if (callback.onStatusChange) {
                    callback.onStatusChange(data, busId);
                }
            });

            this.subscriptions.set(channelName, {
                channel,
                busId,
                callback,
                subscribedAt: new Date()
            });

            this.log('Subscribed to bus channel:', channelName);
            return channel;

        } catch (error) {
            this.log('Failed to subscribe to bus:', busId, error);
            return null;
        }
    }

    /**
     * Subscribe to all bus updates
     */
    subscribeToAllBuses(callback) {
        if (!this.echo) {
            this.log('Echo not initialized, cannot subscribe to all buses');
            return null;
        }

        try {
            const channelName = 'bus.all';
            
            if (this.subscriptions.has(channelName)) {
                this.log('Already subscribed to all buses');
                return this.subscriptions.get(channelName);
            }

            const channel = this.echo.channel(channelName);
            
            channel.listen('location.updated', (data) => {
                this.log('Received location update from all buses:', data);
                callback(data, data.bus_id);
            });

            channel.listen('tracking.status.changed', (data) => {
                this.log('Received tracking status change from all buses:', data);
                if (callback.onStatusChange) {
                    callback.onStatusChange(data, data.bus_id);
                }
            });

            this.subscriptions.set(channelName, {
                channel,
                callback,
                subscribedAt: new Date()
            });

            this.log('Subscribed to all buses channel');
            return channel;

        } catch (error) {
            this.log('Failed to subscribe to all buses:', error);
            return null;
        }
    }

    /**
     * Subscribe to bus tracking presence channel
     */
    subscribeToTrackingPresence(busId, callbacks = {}) {
        if (!this.echo) {
            this.log('Echo not initialized, cannot subscribe to tracking presence');
            return null;
        }

        try {
            const channelName = `bus.${busId}.tracking`;
            
            if (this.subscriptions.has(channelName)) {
                this.log('Already subscribed to tracking presence:', busId);
                return this.subscriptions.get(channelName);
            }

            const channel = this.echo.join(channelName);
            
            // User joined tracking
            if (callbacks.onJoin) {
                channel.here((users) => {
                    this.log('Current tracking users for bus:', busId, users);
                    callbacks.onJoin(users, busId);
                });
            }

            // User started tracking
            if (callbacks.onJoining) {
                channel.joining((user) => {
                    this.log('User joined tracking for bus:', busId, user);
                    callbacks.onJoining(user, busId);
                });
            }

            // User stopped tracking
            if (callbacks.onLeaving) {
                channel.leaving((user) => {
                    this.log('User left tracking for bus:', busId, user);
                    callbacks.onLeaving(user, busId);
                });
            }

            this.subscriptions.set(channelName, {
                channel,
                busId,
                callbacks,
                subscribedAt: new Date()
            });

            this.log('Subscribed to tracking presence channel:', channelName);
            return channel;

        } catch (error) {
            this.log('Failed to subscribe to tracking presence:', busId, error);
            return null;
        }
    }

    /**
     * Unsubscribe from a channel
     */
    unsubscribe(channelName) {
        if (this.subscriptions.has(channelName)) {
            const subscription = this.subscriptions.get(channelName);
            
            try {
                this.echo.leave(channelName);
                this.subscriptions.delete(channelName);
                this.log('Unsubscribed from channel:', channelName);
                return true;
            } catch (error) {
                this.log('Failed to unsubscribe from channel:', channelName, error);
                return false;
            }
        }
        
        return false;
    }

    /**
     * Unsubscribe from bus updates
     */
    unsubscribeFromBus(busId) {
        const channelName = `bus.${busId}`;
        return this.unsubscribe(channelName);
    }

    /**
     * Get connection status
     */
    getConnectionStatus() {
        return {
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            subscriptions: Array.from(this.subscriptions.keys()),
            connectionState: this.echo?.connector?.pusher?.connection?.state || 'unknown'
        };
    }

    /**
     * Handle connection loss and attempt reconnection
     */
    handleConnectionLoss() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            this.log('Max reconnection attempts reached');
            this.notifyConnectionStatus('failed');
            return;
        }

        this.reconnectAttempts++;
        this.notifyConnectionStatus('reconnecting');
        
        setTimeout(() => {
            this.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.options.maxReconnectAttempts})`);
            this.reconnect();
        }, this.options.reconnectInterval);
    }

    /**
     * Handle connection errors
     */
    handleConnectionError(error) {
        this.log('Connection error:', error);
        this.notifyConnectionStatus('error', error.message);
        
        // Attempt to reconnect on certain errors
        if (error.type === 'WebSocketError' || error.code === 1006) {
            this.handleConnectionLoss();
        }
    }

    /**
     * Reconnect to WebSocket
     */
    reconnect() {
        try {
            if (this.echo) {
                this.echo.disconnect();
            }
            
            // Reinitialize connection
            this.init();
            
            // Resubscribe to all channels
            this.resubscribeAll();
            
        } catch (error) {
            this.log('Failed to reconnect:', error);
            this.handleConnectionLoss();
        }
    }

    /**
     * Resubscribe to all channels after reconnection
     */
    resubscribeAll() {
        const subscriptions = Array.from(this.subscriptions.entries());
        this.subscriptions.clear();
        
        subscriptions.forEach(([channelName, subscription]) => {
            if (channelName.startsWith('bus.') && channelName.endsWith('.tracking')) {
                // Presence channel
                this.subscribeToTrackingPresence(subscription.busId, subscription.callbacks);
            } else if (channelName === 'bus.all') {
                // All buses channel
                this.subscribeToAllBuses(subscription.callback);
            } else if (channelName.startsWith('bus.')) {
                // Individual bus channel
                this.subscribeToBus(subscription.busId, subscription.callback);
            }
        });
    }

    /**
     * Start heartbeat to keep connection alive
     */
    startHeartbeat() {
        this.stopHeartbeat();
        
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected && this.echo) {
                // Send a ping to keep connection alive
                try {
                    this.echo.connector.pusher.send_event('pusher:ping', {});
                } catch (error) {
                    this.log('Heartbeat failed:', error);
                }
            }
        }, 30000); // 30 seconds
    }

    /**
     * Stop heartbeat
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    /**
     * Add connection status callback
     */
    onConnectionStatusChange(callback) {
        this.connectionCallbacks.push(callback);
        
        // Immediately call with current status
        callback({
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            state: this.echo?.connector?.pusher?.connection?.state || 'unknown'
        });
        
        return () => {
            const index = this.connectionCallbacks.indexOf(callback);
            if (index > -1) {
                this.connectionCallbacks.splice(index, 1);
            }
        };
    }

    /**
     * Notify connection status change
     */
    notifyConnectionStatus(status, message = null) {
        const statusData = {
            status,
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            message,
            timestamp: new Date().toISOString()
        };

        this.connectionCallbacks.forEach(callback => {
            try {
                callback(statusData);
            } catch (error) {
                this.log('Error in connection status callback:', error);
            }
        });
    }

    /**
     * Disconnect and cleanup
     */
    disconnect() {
        this.log('Disconnecting WebSocket client');
        
        this.stopHeartbeat();
        
        if (this.echo) {
            this.echo.disconnect();
            this.echo = null;
        }
        
        this.subscriptions.clear();
        this.connectionCallbacks = [];
        this.isConnected = false;
    }

    /**
     * Get subscription statistics
     */
    getStatistics() {
        return {
            connected: this.isConnected,
            subscriptions: this.subscriptions.size,
            reconnectAttempts: this.reconnectAttempts,
            channels: Array.from(this.subscriptions.keys()),
            connectionState: this.echo?.connector?.pusher?.connection?.state || 'unknown'
        };
    }

    /**
     * Debug logging
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[WebSocketClient]', ...args);
        }
    }
}

// Export for use in other modules
window.WebSocketClient = WebSocketClient;

export default WebSocketClient;