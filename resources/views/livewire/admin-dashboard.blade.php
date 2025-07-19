<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1>🚌 Bus Tracker Admin</h1>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <button class="tab-btn {{ $activeTab === 'trips' ? 'active' : '' }}" 
                wire:click="$set('activeTab', 'trips')">
            Trips Management
        </button>
        <button class="tab-btn {{ $activeTab === 'buses' ? 'active' : '' }}" 
                wire:click="$set('activeTab', 'buses')">
            Bus Management
        </button>
        <button class="tab-btn {{ $activeTab === 'settings' ? 'active' : '' }}" 
                wire:click="$set('activeTab', 'settings')">
            Settings
        </button>
    </div>

    <!-- Trips Management -->
    @if($activeTab === 'trips')
        <div class="tab-content">
            <div class="form-section">
                <h3>{{ $editingTrip ? 'Edit Trip' : 'Add New Trip' }}</h3>
                
                <form wire:submit.prevent="{{ $editingTrip ? 'updateTrip' : 'createTrip' }}">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Bus</label>
                            <select wire:model="tripForm.bus_id" required>
                                <option value="">Select Bus</option>
                                @foreach($buses as $bus)
                                    <option value="{{ $bus->id }}">{{ $bus->name }} - {{ $bus->route_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Trip Date</label>
                            <input type="date" wire:model="tripForm.trip_date" required>
                        </div>

                        <div class="form-group">
                            <label>Departure Time</label>
                            <input type="time" wire:model="tripForm.departure_time" required>
                        </div>

                        <div class="form-group">
                            <label>Return Time</label>
                            <input type="time" wire:model="tripForm.return_time" required>
                        </div>

                        <div class="form-group">
                            <label>Direction</label>
                            <select wire:model="tripForm.direction" required>
                                <option value="outbound">Outbound (To Campus)</option>
                                <option value="inbound">Inbound (From Campus)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select wire:model="tripForm.status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            {{ $editingTrip ? 'Update Trip' : 'Create Trip' }}
                        </button>
                        @if($editingTrip)
                            <button type="button" wire:click="resetTripForm" class="btn btn-secondary">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-section">
                <h3>Recent Trips</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Bus</th>
                                <th>Date</th>
                                <th>Departure</th>
                                <th>Return</th>
                                <th>Direction</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trips as $trip)
                                <tr>
                                    <td>{{ $trip->bus->name }}</td>
                                    <td>{{ $trip->trip_date->format('M d, Y') }}</td>
                                    <td>{{ $trip->departure_time->format('H:i') }}</td>
                                    <td>{{ $trip->return_time->format('H:i') }}</td>
                                    <td>{{ ucfirst($trip->direction) }}</td>
                                    <td>
                                        <span class="status-badge status-{{ $trip->status }}">
                                            {{ ucfirst($trip->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <button wire:click="editTrip({{ $trip->id }})" class="btn btn-sm btn-edit">
                                            Edit
                                        </button>
                                        <button wire:click="deleteTrip({{ $trip->id }})" 
                                                onclick="return confirm('Are you sure?')" 
                                                class="btn btn-sm btn-delete">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $trips->links() }}
            </div>
        </div>
    @endif

    <!-- Bus Management -->
    @if($activeTab === 'buses')
        <div class="tab-content">
            <div class="form-section">
                <h3>{{ $editingBus ? 'Edit Bus' : 'Add New Bus' }}</h3>
                
                <form wire:submit.prevent="{{ $editingBus ? 'updateBus' : 'createBus' }}">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Bus Name</label>
                            <input type="text" wire:model="busForm.name" placeholder="e.g., B1" required>
                        </div>

                        <div class="form-group">
                            <label>Route Name</label>
                            <input type="text" wire:model="busForm.route_name" placeholder="e.g., Buriganga" required>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" wire:model="busForm.is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            {{ $editingBus ? 'Update Bus' : 'Create Bus' }}
                        </button>
                        @if($editingBus)
                            <button type="button" wire:click="resetBusForm" class="btn btn-secondary">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-section">
                <h3>All Buses</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($buses as $bus)
                                <tr>
                                    <td>{{ $bus->name }}</td>
                                    <td>{{ $bus->route_name }}</td>
                                    <td>
                                        <span class="status-badge {{ $bus->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $bus->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button wire:click="editBus({{ $bus->id }})" class="btn btn-sm btn-edit">
                                            Edit
                                        </button>
                                        <button wire:click="deleteBus({{ $bus->id }})" 
                                                onclick="return confirm('Are you sure?')" 
                                                class="btn btn-sm btn-delete">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Settings -->
    @if($activeTab === 'settings')
        <div class="tab-content">
            <div class="form-section">
                <h3>Global Settings</h3>
                
                <form wire:submit.prevent="saveSettings">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>App Name</label>
                            <input type="text" wire:model="settings.app_name">
                        </div>

                        <div class="form-group">
                            <label>Refresh Interval (seconds)</label>
                            <input type="number" wire:model="settings.refresh_interval" min="10" max="300">
                        </div>

                        <div class="form-group">
                            <label>Max Location Age (minutes)</label>
                            <input type="number" wire:model="settings.max_location_age" min="1" max="60">
                        </div>

                        <div class="form-group">
                            <label>Clustering Radius (meters)</label>
                            <input type="number" wire:model="settings.clustering_radius" min="10" max="500">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        .admin-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-header {
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            text-align: center;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 2rem;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .nav-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
            font-weight: 500;
        }

        .tab-btn:hover {
            background: #f8f9fa;
        }

        .tab-btn.active {
            background: #667eea;
            color: white;
        }

        .tab-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 40px;
        }

        .form-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .table-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-scheduled {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .admin-dashboard {
                padding: 10px;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</div>