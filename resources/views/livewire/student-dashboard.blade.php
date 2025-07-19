<div class="student-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="user-info">
                <div class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                <div class="user-details">
                    <h2>Hello, {{ auth()->user()->name }}!</h2>
                    <p>{{ auth()->user()->student_id }} • {{ auth()->user()->department }}</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="refresh-btn" wire:click="refreshData">🔄</button>
                <button class="logout-btn" wire:click="logout">🚪</button>
            </div>
        </div>
        <div class="last-updated">Last updated: {{ $lastUpdated }}</div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Active Boardings -->
    @if(!empty($userBoardings))
        <div class="active-boardings">
            <h3>🎫 Your Active Trips</h3>
            @foreach($userBoardings as $boarding)
                <div class="boarding-card status-{{ $boarding['status'] }}">
                    <div class="boarding-info">
                        <div class="bus-name">{{ $boarding['bus_name'] }} - {{ $boarding['route_name'] }}</div>
                        <div class="boarding-details">
                            <span class="stop">📍 {{ $boarding['boarding_stop'] }}</span>
                            @if($boarding['destination_stop'])
                                <span class="arrow">→</span>
                                <span class="stop">🏫 {{ $boarding['destination_stop'] }}</span>
                            @endif
                        </div>
                        <div class="boarding-time">Requested at {{ $boarding['boarded_at'] }}</div>
                    </div>
                    <div class="boarding-actions">
                        <span class="status-badge status-{{ $boarding['status'] }}">
                            {{ ucfirst($boarding['status']) }}
                        </span>
                        @if($boarding['status'] === 'waiting')
                            <button wire:click="cancelBoarding({{ $boarding['id'] }})" class="btn btn-cancel">Cancel</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Live Map -->
    <div class="map-section">
        <h3>🗺️ Live Bus Locations</h3>
        <div id="bus-map" style="height: 300px; width: 100%; border-radius: 12px;"></div>
    </div>

    <!-- Available Buses -->
    <div class="buses-section">
        <h3>🚌 Available Buses</h3>
        
        @forelse($buses as $bus)
            <div class="bus-card">
                <div class="bus-header">
                    <div class="bus-info">
                        <div class="bus-name">{{ $bus['name'] }}</div>
                        <div class="route-name">{{ $bus['route_name'] }}</div>
                    </div>
                    
                    @if($bus['status'])
                        <div class="capacity-info">
                            <div class="capacity-bar">
                                <div class="capacity-fill" style="width: {{ $bus['status']['capacity_percentage'] }}%"></div>
                            </div>
                            <div class="capacity-text">
                                {{ $bus['status']['current_capacity'] }}/{{ $bus['status']['max_capacity'] }} seats
                                @if($bus['status']['is_near_capacity'])
                                    <span class="near-capacity">⚠️ Almost Full</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                @if($bus['current_location'])
                    <div class="location-status online">
                        📍 Last seen {{ $bus['current_location']['recorded_at'] }}
                        @if($bus['status'] && $bus['status']['current_stop'])
                            at {{ $bus['status']['current_stop'] }}
                        @endif
                    </div>
                @else
                    <div class="location-status offline">📍 No recent location</div>
                @endif

                <div class="trips-list">
                    @foreach($bus['trips'] as $trip)
                        <div class="trip-item status-{{ $trip['status'] }}">
                            <div class="trip-info">
                                <div class="trip-times">
                                    <span class="departure">🚌 {{ $trip['departure_time'] }}</span>
                                    <span class="return">🏫 {{ $trip['return_time'] }}</span>
                                </div>
                                <div class="trip-direction">{{ ucfirst($trip['direction']) }}</div>
                            </div>
                            
                            @if($trip['status'] === 'active' || $trip['status'] === 'scheduled')
                                <button wire:click="selectBus({{ $bus['id'] }}, {{ $trip['id'] }})" 
                                        class="btn btn-board">
                                    Board Bus
                                </button>
                            @else
                                <span class="trip-status status-{{ $trip['status'] }}">
                                    {{ ucfirst($trip['status']) }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Bus Stops Preview -->
                <div class="stops-preview">
                    <div class="stops-header">Route Stops:</div>
                    <div class="stops-list">
                        @foreach(array_slice($bus['stops'], 0, 3) as $stop)
                            <span class="stop-item">{{ $stop['name'] }}</span>
                        @endforeach
                        @if(count($bus['stops']) > 3)
                            <span class="more-stops">+{{ count($bus['stops']) - 3 }} more</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="no-buses">
                <div class="empty-state">
                    <div class="empty-icon">🚌</div>
                    <h4>No buses available</h4>
                    <p>All buses are currently offline or inactive.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Boarding Modal -->
    @if($showBoardingModal)
        <div class="modal-overlay" wire:click="closeBoardingModal">
            <div class="modal-content" wire:click.stop>
                <div class="modal-header">
                    <h3>🎫 Request Bus Boarding</h3>
                    <button wire:click="closeBoardingModal" class="close-btn">✕</button>
                </div>
                
                <div class="modal-body">
                    @php
                        $selectedBusData = collect($buses)->firstWhere('id', $selectedBus);
                    @endphp
                    
                    @if($selectedBusData)
                        <div class="selected-bus-info">
                            <div class="bus-name">{{ $selectedBusData['name'] }} - {{ $selectedBusData['route_name'] }}</div>
                            @if($selectedBusData['status'])
                                <div class="capacity-warning">
                                    Available seats: {{ $selectedBusData['status']['available_seats'] }}
                                    @if($selectedBusData['status']['is_near_capacity'])
                                        <span class="warning">⚠️ Limited seats available</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <form wire:submit.prevent="requestBoarding">
                            <div class="form-group">
                                <label>📍 Boarding Stop</label>
                                <select wire:model="selectedBoardingStop" required>
                                    <option value="">Select your boarding stop</option>
                                    @foreach($selectedBusData['stops'] as $stop)
                                        <option value="{{ $stop['id'] }}">{{ $stop['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('selectedBoardingStop') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label>🏫 Destination Stop (Optional)</label>
                                <select wire:model="selectedDestinationStop">
                                    <option value="">Select destination (optional)</option>
                                    @foreach($selectedBusData['stops'] as $stop)
                                        <option value="{{ $stop['id'] }}">{{ $stop['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="boarding-info-box">
                                <h4>📋 Important Information:</h4>
                                <ul>
                                    <li>You will receive a notification when the bus approaches your stop</li>
                                    <li>Please be ready 5 minutes before the estimated arrival</li>
                                    <li>Show this app to the driver when boarding</li>
                                    <li>You can cancel your request anytime before boarding</li>
                                </ul>
                            </div>

                            @error('boarding') <div class="error">{{ $message }}</div> @enderror

                            <div class="modal-actions">
                                <button type="button" wire:click="closeBoardingModal" class="btn btn-secondary">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <span wire:loading.remove>Request Boarding</span>
                                    <span wire:loading>Requesting...</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <style>
        .student-dashboard {
            min-height: 100vh;
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 15px;
        }

        .user-details h2 {
            margin: 0;
            font-size: 1.3rem;
        }

        .user-details p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .refresh-btn, .logout-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s;
        }

        .refresh-btn:hover, .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .last-updated {
            font-size: 0.8rem;
            opacity: 0.7;
            text-align: center;
        }

        .alert {
            margin: 20px;
            padding: 15px;
            border-radius: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .active-boardings {
            margin: 20px;
        }

        .active-boardings h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .boarding-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .boarding-card.status-waiting {
            border-left: 4px solid #ffc107;
        }

        .boarding-card.status-boarded {
            border-left: 4px solid #28a745;
        }

        .bus-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .boarding-details {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .boarding-time {
            font-size: 0.8rem;
            color: #666;
        }

        .boarding-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.status-waiting {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.status-boarded {
            background: #d4edda;
            color: #155724;
        }

        .map-section, .buses-section {
            margin: 20px;
        }

        .map-section h3, .buses-section h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .bus-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .bus-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .bus-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .route-name {
            color: #666;
            font-size: 0.9rem;
        }

        .capacity-info {
            text-align: right;
            min-width: 120px;
        }

        .capacity-bar {
            width: 100px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .capacity-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
            transition: width 0.3s ease;
        }

        .capacity-text {
            font-size: 0.8rem;
            color: #666;
        }

        .near-capacity {
            color: #dc3545;
            font-weight: bold;
        }

        .location-status {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            margin-bottom: 15px;
        }

        .location-status.online {
            background: #d4edda;
            color: #155724;
        }

        .location-status.offline {
            background: #f8d7da;
            color: #721c24;
        }

        .trip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .trip-item.status-active {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .trip-times {
            display: flex;
            gap: 15px;
        }

        .departure, .return {
            font-weight: 500;
        }

        .trip-direction {
            font-size: 0.8rem;
            color: #666;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-board {
            background: #667eea;
            color: white;
        }

        .btn-board:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .stops-preview {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .stops-header {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 8px;
        }

        .stops-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .stop-item {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .more-stops {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .no-buses {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state {
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            padding: 20px;
        }

        .selected-bus-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .capacity-warning {
            margin-top: 5px;
            font-size: 0.8rem;
        }

        .warning {
            color: #dc3545;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .boarding-info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .boarding-info-box h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }

        .boarding-info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .boarding-info-box li {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .bus-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .capacity-info {
                text-align: left;
                min-width: auto;
            }
            
            .trip-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .boarding-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }

        /* PWA Optimizations */
        @media (display-mode: standalone) {
            .dashboard-header {
                padding-top: 60px;
            }
        }

        /* Loading States */
        [wire\\:loading] {
            opacity: 0.6;
        }

        /* Animations */
        .bus-card {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }

        .toast {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid;
            display: flex;
            align-items: center;
            animation: slideInRight 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        .toast.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .toast.error {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }

        .toast.info {
            border-left-color: #17a2b8;
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        }

        .toast-icon {
            font-size: 1.2rem;
            margin-right: 12px;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 2px;
            color: #333;
        }

        .toast-message {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            margin-left: 10px;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* Mobile toast adjustments */
        @media (max-width: 768px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .dashboard-header {
                padding-top: 60px; /* Account for status bar in PWA */
            }
        }

        /* PWA Status Bar */
        @media (display-mode: standalone) {
            .dashboard-header {
                padding-top: 60px;
            }
            
            .toast-container {
                top: 60px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            if (typeof L !== 'undefined') {
                const map = L.map('bus-map').setView([23.8103, 90.4125], 11);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Update bus positions
                function updateBusPositions() {
                    @this.busPositions.forEach(cluster => {
                        const marker = L.marker([cluster.position.lat, cluster.position.lng])
                            .addTo(map);
                        
                        let popupContent = `<strong>${cluster.count} Bus(es)</strong><br>`;
                        cluster.buses.forEach(bus => {
                            popupContent += `${bus.bus_name} (${bus.route_name})<br>`;
                        });
                        
                        marker.bindPopup(popupContent);
                    });
                }

                updateBusPositions();

                // Listen for updates
                Livewire.on('data-refreshed', () => {
                    map.eachLayer(layer => {
                        if (layer instanceof L.Marker) {
                            map.removeLayer(layer);
                        }
                    });
                    updateBusPositions();
                });
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            @this.refreshData();
        }, 30000);

        // Toast notification system
        function showToast(message, type = 'info', title = '') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                info: 'ℹ️',
                warning: '⚠️'
            };

            const titles = {
                success: title || 'Success!',
                error: title || 'Error!',
                info: title || 'Info',
                warning: title || 'Warning!'
            };

            toast.innerHTML = `
                <div class="toast-icon">${icons[type]}</div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="removeToast(this.parentElement)">×</button>
            `;

            container.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                removeToast(toast);
            }, 5000);
        }

        function removeToast(toast) {
            if (toast && toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.parentElement.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Listen for Laravel flash messages
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif

            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif

            @if(session('info'))
                showToast('{{ session('info') }}', 'info');
            @endif

            @if(session('warning'))
                showToast('{{ session('warning') }}', 'warning');
            @endif

            @if(session('message'))
                showToast('{{ session('message') }}', 'success');
            @endif
        });

        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                showToast(event.message, event.type || 'info', event.title || '');
            });
        });
    </script>
</div>