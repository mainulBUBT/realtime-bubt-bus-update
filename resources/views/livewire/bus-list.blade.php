<div>
    <!-- Modern Mobile Header -->
    <div class="mobile-header">
        <div class="header-top">
            <button class="menu-btn" id="menu-btn">
                <i class="bi bi-list"></i>
            </button>
            <div class="header-title">
                <h1>BUBT Bus Tracker</h1>
                <span class="location-indicator">
                    <i class="bi bi-geo-alt"></i>
                    Dhaka, Bangladesh
                </span>
            </div>
            <button class="notification-btn" id="notification-btn">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </button>
        </div>

        <!-- Bus Dropdown Selector -->
        <div class="bus-dropdown-container">
            <div class="bus-dropdown" id="bus-dropdown">
                <div class="dropdown-header" id="dropdown-header">
                    <span>{{ collect($busOptions)->firstWhere('id', $selectedBusFilter)['name'] ?? 'Select Bus Route' }}</span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-menu" id="dropdown-menu">
                    @foreach($busOptions as $option)
                        <div class="dropdown-item {{ $selectedBusFilter === $option['id'] ? 'active' : '' }}" 
                             wire:click="filterByBus('{{ $option['id'] }}')"
                             data-bus-id="{{ $option['id'] }}">
                            @if($option['id'] !== 'all')
                                <div class="bus-badge-mini">{{ $option['id'] }}</div>
                            @endif
                            <span>{{ $option['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Bus Cards -->
    <div class="home-content">
        <h2 class="home-section-title">Available Buses</h2>

        <div class="home-bus-cards-container">
            @forelse($buses as $bus)
                <div class="home-bus-card" 
                     data-bus-id="{{ $bus['id'] }}"
                     wire:click="selectBus('{{ $bus['id'] }}')"
                     style="cursor: pointer;">
                    <div class="home-bus-left">
                        <div class="home-bus-badge">{{ $bus['id'] }}</div>
                    </div>
                    <div class="home-bus-middle">
                        <h3 class="home-bus-name">{{ $bus['name'] }}</h3>
                        <p class="home-bus-schedule">{{ $bus['schedule'] }}</p>
                        
                        @if($bus['is_active'])
                            <div class="bus-live-info">
                                <small class="text-muted">
                                    <i class="bi bi-arrow-right"></i>
                                    {{ ucfirst($bus['current_trip']) }} trip
                                    @if($bus['next_stop'])
                                        • Next: {{ $bus['next_stop'] }}
                                        @if($bus['eta'])
                                            ({{ $bus['eta'] }})
                                        @endif
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="home-bus-right">
                        <span class="home-bus-status {{ $bus['status'] }}"></span>
                        @if($bus['status'] === 'active')
                            <i class="bi bi-chevron-right"></i>
                        @endif
                    </div>
                </div>
            @empty
                <div class="no-buses-message">
                    <div class="text-center py-4">
                        <i class="bi bi-bus-front" style="font-size: 3rem; color: #6c757d;"></i>
                        <h4 class="mt-3 text-muted">No buses found</h4>
                        <p class="text-muted">
                            @if($selectedBusFilter !== 'all' || $selectedStatusFilter !== 'all')
                                Try adjusting your filters or check back later.
                            @else
                                No buses are currently scheduled to run.
                            @endif
                        </p>
                        @if($selectedBusFilter !== 'all' || $selectedStatusFilter !== 'all')
                            <button class="btn btn-outline-primary btn-sm" 
                                    wire:click="filterByBus('all')"
                                    onclick="this.dispatchEvent(new CustomEvent('filter-reset'))">
                                Clear Filters
                            </button>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Status Legend -->
    @if(count($buses) > 0)
        <div class="status-legend">
            <div class="legend-item">
                <span class="home-bus-status active"></span>
                <small>Active & Tracking</small>
            </div>
            <div class="legend-item">
                <span class="home-bus-status delayed"></span>
                <small>Scheduled but No Tracking</small>
            </div>
            <div class="legend-item">
                <span class="home-bus-status inactive"></span>
                <small>Not Scheduled</small>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    let busListConnectionManager = null;
    let busSubscriptions = new Map();

    // Initialize dropdown functionality for Livewire component
    document.addEventListener('livewire:navigated', function() {
        initBusListDropdown();
        initBusListConnectionManager();
    });

    document.addEventListener('DOMContentLoaded', function() {
        initBusListDropdown();
        initBusListConnectionManager();
    });

    function initBusListDropdown() {
        const dropdownHeader = document.getElementById('dropdown-header');
        const dropdownMenu = document.getElementById('dropdown-menu');
        
        if (dropdownHeader && dropdownMenu) {
            // Toggle dropdown
            dropdownHeader.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownHeader.classList.toggle('active');
                dropdownMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.bus-dropdown')) {
                    dropdownHeader.classList.remove('active');
                    dropdownMenu.classList.remove('show');
                }
            });
        }
    }

    function initBusListConnectionManager() {
        // Initialize connection manager for bus list updates
        if (window.ConnectionManager) {
            busListConnectionManager = new window.ConnectionManager({
                pollingInterval: 15000, // 15 seconds for bus list (less frequent than individual tracking)
                reconnectInterval: 5000,
                maxReconnectAttempts: 10,
                enableWebSocket: false,
                debug: false
            });

            // Subscribe to all active buses
            subscribeToActiveBuses();

            // Listen for connection status changes
            busListConnectionManager.onConnectionStatusChange((status) => {
                @this.dispatch('connectionStatusChanged', status);
            });
        }
    }

    function subscribeToActiveBuses() {
        // Get all bus cards and subscribe to their updates
        const busCards = document.querySelectorAll('.home-bus-card[data-bus-id]');
        
        busCards.forEach(card => {
            const busId = card.getAttribute('data-bus-id');
            
            if (!busSubscriptions.has(busId)) {
                const unsubscribe = busListConnectionManager.subscribe(busId, (locationData, busId) => {
                    handleBusStatusUpdate(busId, locationData);
                });
                
                busSubscriptions.set(busId, unsubscribe);
            }
        });
    }

    function handleBusStatusUpdate(busId, locationData) {
        // Update bus card status based on location data
        const busCard = document.querySelector(`[data-bus-id="${busId}"]`);
        if (!busCard) return;

        const statusElement = busCard.querySelector('.home-bus-status');
        if (!statusElement) return;

        // Update status based on location data
        if (locationData.status === 'active') {
            statusElement.className = 'home-bus-status active';
            
            // Update live info if available
            const liveInfo = busCard.querySelector('.bus-live-info small');
            if (liveInfo && locationData.active_trackers) {
                const trackingText = locationData.active_trackers === 1 
                    ? '1 person tracking' 
                    : `${locationData.active_trackers} people tracking`;
                
                // You could update the live info text here
                // liveInfo.innerHTML = `<i class="bi bi-people"></i> ${trackingText}`;
            }
            
        } else if (locationData.status === 'no_tracking') {
            statusElement.className = 'home-bus-status delayed';
        } else {
            statusElement.className = 'home-bus-status inactive';
        }
    }

    // Handle filter reset event
    document.addEventListener('filter-reset', function() {
        @this.call('filterByStatus', 'all');
    });

    // Refresh subscriptions when buses are updated
    document.addEventListener('livewire:updated', function() {
        // Re-subscribe to any new buses
        setTimeout(subscribeToActiveBuses, 100);
    });

    // Auto-refresh buses every 30 seconds (reduced since we have real-time updates)
    setInterval(function() {
        @this.call('refreshBuses');
    }, 30000);

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (busListConnectionManager) {
            busListConnectionManager.destroy();
            busListConnectionManager = null;
        }
        busSubscriptions.clear();
    });
</script>
@endpush

@push('styles')
<style>
    .bus-live-info {
        margin-top: 4px;
    }

    .bus-live-info small {
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .no-buses-message {
        background: white;
        border-radius: 12px;
        margin: 1rem 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .status-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(255,255,255,0.9);
        border-radius: 8px;
        margin: 1rem;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .legend-item small {
        font-size: 0.7rem;
        color: #6c757d;
    }

    /* Dropdown improvements for Livewire */
    .dropdown-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .dropdown-item:hover {
        background-color: rgba(0,0,0,0.05);
    }

    .dropdown-item.active {
        background-color: var(--primary-color, #007bff);
        color: white;
    }

    .dropdown-item.active .bus-badge-mini {
        background-color: rgba(255,255,255,0.2);
        color: white;
    }

    /* Loading state */
    .home-bus-card[wire\\:loading] {
        opacity: 0.7;
        pointer-events: none;
    }

    /* Real-time status indicators */
    .home-bus-status.active {
        background-color: #28a745;
        animation: pulse 2s infinite;
    }

    .home-bus-status.delayed {
        background-color: #ffc107;
    }

    .home-bus-status.inactive {
        background-color: #6c757d;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
</style>
@endpush
