<div>
    @if($isVisible)
        <div class="connection-status-bar alert alert-{{ $statusClass }} {{ $connectionStatus }}" 
             role="alert" 
             wire:transition.opacity>
            <div class="status-content">
                <div class="status-info">
                    <i class="bi {{ $statusIcon }} status-icon {{ $connectionStatus === 'reconnecting' ? 'spinning' : '' }}"></i>
                    <span class="status-text">{{ $statusText }}</span>
                    
                    @if($errorMessage)
                        <small class="error-details">{{ $errorMessage }}</small>
                    @endif
                </div>

                <div class="status-actions">
                    @if($showRetryButton)
                        <button class="btn btn-sm btn-outline-{{ $statusClass === 'danger' ? 'light' : $statusClass }}" 
                                wire:click="retry" 
                                title="Retry connection">
                            <i class="bi bi-arrow-clockwise"></i>
                            Retry
                        </button>
                    @endif

                    <button class="btn btn-sm btn-link text-{{ $statusClass === 'danger' ? 'light' : 'muted' }}" 
                            wire:click="dismiss" 
                            title="Dismiss">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>

            @if($connectionStatus === 'reconnecting' && $reconnectAttempts > 0)
                <div class="reconnect-progress">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: {{ min(100, ($reconnectAttempts / 10) * 100) }}%">
                        </div>
                    </div>
                    <small class="text-muted">Attempt {{ $reconnectAttempts }} of 10</small>
                </div>
            @endif

            @if($lastUpdate)
                <div class="last-update">
                    <small class="text-muted">Last update: {{ $lastUpdate }}</small>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:navigated', function() {
        setupConnectionStatusListeners();
    });

    document.addEventListener('DOMContentLoaded', function() {
        setupConnectionStatusListeners();
    });

    function setupConnectionStatusListeners() {
        // Listen for auto-hide events
        Livewire.on('auto-hide-status', (data) => {
            const delay = data[0]?.delay || 3000;
            setTimeout(() => {
                @this.call('hide');
            }, delay);
        });

        // Listen for retry connection events
        Livewire.on('retry-connection', () => {
            if (window.connectionManager) {
                // Force reconnection attempt
                if (window.connectionManager.connectionType === 'none') {
                    window.connectionManager.connectWebSocket();
                } else if (window.connectionManager.connectionType === 'polling') {
                    window.connectionManager.poll();
                }
            }
        });
    }
</script>
@endpush

@push('styles')
<style>
    .connection-status-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        margin: 0;
        border-radius: 0;
        border: none;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        animation: slideDown 0.3s ease-out;
    }

    .connection-status-bar.alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }

    .connection-status-bar.alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border-color: #ffeaa7;
    }

    .connection-status-bar.alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }

    .connection-status-bar.alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border-color: #bee5eb;
    }

    .connection-status-bar.alert-secondary {
        background-color: #e2e3e5;
        color: #383d41;
        border-color: #d6d8db;
    }

    .status-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .status-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }

    .status-icon {
        font-size: 1rem;
    }

    .status-icon.spinning {
        animation: spin 1s linear infinite;
    }

    .status-text {
        font-weight: 500;
        font-size: 0.9rem;
    }

    .error-details {
        display: block;
        opacity: 0.8;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .status-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        line-height: 1.2;
    }

    .reconnect-progress {
        margin-top: 0.5rem;
    }

    .reconnect-progress .progress {
        height: 4px;
        margin-bottom: 0.25rem;
    }

    .last-update {
        margin-top: 0.25rem;
        text-align: right;
    }

    /* Animations */
    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .status-content {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }

        .status-info {
            justify-content: center;
        }

        .status-actions {
            justify-content: center;
        }

        .last-update {
            text-align: center;
        }
    }

    /* Adjust main content when status bar is visible */
    body:has(.connection-status-bar) .app-header {
        margin-top: 60px;
    }

    body:has(.connection-status-bar) .full-map {
        margin-top: 60px;
        height: calc(100vh - 60px);
    }

    /* Connection type specific styles */
    .connection-status-bar.connected.alert-success .status-icon {
        color: #28a745;
    }

    .connection-status-bar.reconnecting .status-icon {
        color: #ffc107;
    }

    .connection-status-bar.error .status-icon,
    .connection-status-bar.disconnected .status-icon {
        color: #dc3545;
    }

    .connection-status-bar.offline .status-icon {
        color: #6c757d;
    }
</style>
@endpush