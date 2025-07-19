<div class="bus-tracker-app">
    <!-- Header -->
    <div class="header">
        <h1>🚌 BUBT Bus Tracker</h1>
        <div class="last-updated">
            Last updated: {{ $lastUpdated }}
            <button wire:click="refreshData" class="refresh-btn">🔄</button>
        </div>
    </div>

    <!-- Live Map -->
    <div class="map-container">
        <div id="bus-map" style="height: 400px; width: 100%;"></div>
    </div>

    <!-- Bus Schedule -->
    <div class="schedule-container">
        <h2>Today's Schedule</h2>
        
        @foreach($buses as $bus)
            <div class="bus-card {{ $selectedBus == $bus['id'] ? 'selected' : '' }}" 
                 wire:click="selectBus({{ $bus['id'] }})">
                
                <div class="bus-header">
                    <div class="bus-info">
                        <span class="bus-name">{{ $bus['name'] }}</span>
                        <span class="route-name">{{ $bus['route_name'] }}</span>
                    </div>
                    
                    @if($bus['current_location'])
                        <div class="location-status online">
                            📍 {{ $bus['current_location']['recorded_at'] }}
                        </div>
                    @else
                        <div class="location-status offline">📍 No recent location</div>
                    @endif
                </div>

                <div class="trips-list">
                    @forelse($bus['trips'] as $trip)
                        <div class="trip-item status-{{ $trip['status'] }}">
                            <div class="trip-times">
                                <span class="departure">🚌 {{ $trip['departure_time'] }}</span>
                                <span class="return">🏫 {{ $trip['return_time'] }}</span>
                            </div>
                            <div class="trip-direction">
                                {{ ucfirst($trip['direction']) }}
                            </div>
                            <div class="trip-status">
                                {{ ucfirst($trip['status']) }}
                            </div>
                        </div>
                    @empty
                        <div class="no-trips">No trips scheduled for today</div>
                    @endforelse
                </div>

                <!-- Bus Stops -->
                <div class="stops-list">
                    @foreach($bus['stops'] as $stop)
                        <div class="stop-item">
                            <span class="stop-order">{{ $stop['order_index'] }}</span>
                            <span class="stop-name">{{ $stop['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .bus-tracker-app {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .last-updated {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .refresh-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .refresh-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .map-container {
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .schedule-container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .bus-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .bus-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .bus-card.selected {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .bus-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .bus-info {
            display: flex;
            flex-direction: column;
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

        .location-status {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .location-status.online {
            background: #d4edda;
            color: #155724;
        }

        .location-status.offline {
            background: #f8d7da;
            color: #721c24;
        }

        .trips-list {
            margin-bottom: 15px;
        }

        .trip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .trip-item.status-active {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .trip-item.status-completed {
            background: #e2e3e5;
            opacity: 0.7;
        }

        .trip-times {
            display: flex;
            gap: 15px;
        }

        .departure, .return {
            font-weight: 500;
        }

        .trip-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .stops-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .stop-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            background: #e9ecef;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .stop-order {
            background: #6c757d;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .no-trips {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .bus-tracker-app {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .bus-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .trip-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map (using OpenStreetMap)
            if (typeof L !== 'undefined') {
                const map = L.map('bus-map').setView([23.8103, 90.4125], 11); // Dhaka center
                
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

                // Initial load
                updateBusPositions();

                // Listen for Livewire updates
                Livewire.on('data-refreshed', () => {
                    // Clear existing markers and update
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
    </script>
</div>