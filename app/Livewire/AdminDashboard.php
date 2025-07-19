<?php

namespace App\Livewire;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\Stop;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

    public $activeTab = 'trips';
    
    // Trip management
    public $tripForm = [
        'bus_id' => '',
        'trip_date' => '',
        'departure_time' => '',
        'return_time' => '',
        'direction' => 'outbound',
        'status' => 'scheduled'
    ];
    public $editingTrip = null;
    
    // Bus management
    public $busForm = [
        'name' => '',
        'route_name' => '',
        'is_active' => true
    ];
    public $editingBus = null;
    
    // Settings
    public $settings = [
        'app_name' => 'BUBT Bus Tracker',
        'refresh_interval' => '30',
        'max_location_age' => '10',
        'clustering_radius' => '60'
    ];

    public function mount()
    {
        $this->tripForm['trip_date'] = today()->format('Y-m-d');
        $this->loadSettings();
    }

    public function loadSettings()
    {
        foreach ($this->settings as $key => $default) {
            $this->settings[$key] = Setting::get($key, $default);
        }
    }

    // Trip Management
    public function createTrip()
    {
        $this->validate([
            'tripForm.bus_id' => 'required|exists:buses,id',
            'tripForm.trip_date' => 'required|date',
            'tripForm.departure_time' => 'required',
            'tripForm.return_time' => 'required',
            'tripForm.direction' => 'required|in:outbound,inbound',
            'tripForm.status' => 'required|in:scheduled,active,completed,cancelled'
        ]);

        Trip::create($this->tripForm);
        
        $this->resetTripForm();
        session()->flash('message', 'Trip created successfully!');
    }

    public function editTrip($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        $this->editingTrip = $tripId;
        $this->tripForm = [
            'bus_id' => $trip->bus_id,
            'trip_date' => $trip->trip_date->format('Y-m-d'),
            'departure_time' => $trip->departure_time->format('H:i'),
            'return_time' => $trip->return_time->format('H:i'),
            'direction' => $trip->direction,
            'status' => $trip->status
        ];
    }

    public function updateTrip()
    {
        $this->validate([
            'tripForm.bus_id' => 'required|exists:buses,id',
            'tripForm.trip_date' => 'required|date',
            'tripForm.departure_time' => 'required',
            'tripForm.return_time' => 'required',
            'tripForm.direction' => 'required|in:outbound,inbound',
            'tripForm.status' => 'required|in:scheduled,active,completed,cancelled'
        ]);

        $trip = Trip::findOrFail($this->editingTrip);
        $trip->update($this->tripForm);
        
        $this->resetTripForm();
        session()->flash('message', 'Trip updated successfully!');
    }

    public function deleteTrip($tripId)
    {
        Trip::findOrFail($tripId)->delete();
        session()->flash('message', 'Trip deleted successfully!');
    }

    public function resetTripForm()
    {
        $this->tripForm = [
            'bus_id' => '',
            'trip_date' => today()->format('Y-m-d'),
            'departure_time' => '',
            'return_time' => '',
            'direction' => 'outbound',
            'status' => 'scheduled'
        ];
        $this->editingTrip = null;
    }

    // Bus Management
    public function createBus()
    {
        $this->validate([
            'busForm.name' => 'required|string|max:255',
            'busForm.route_name' => 'required|string|max:255',
            'busForm.is_active' => 'boolean'
        ]);

        Bus::create($this->busForm);
        
        $this->resetBusForm();
        session()->flash('message', 'Bus created successfully!');
    }

    public function editBus($busId)
    {
        $bus = Bus::findOrFail($busId);
        $this->editingBus = $busId;
        $this->busForm = [
            'name' => $bus->name,
            'route_name' => $bus->route_name,
            'is_active' => $bus->is_active
        ];
    }

    public function updateBus()
    {
        $this->validate([
            'busForm.name' => 'required|string|max:255',
            'busForm.route_name' => 'required|string|max:255',
            'busForm.is_active' => 'boolean'
        ]);

        $bus = Bus::findOrFail($this->editingBus);
        $bus->update($this->busForm);
        
        $this->resetBusForm();
        session()->flash('message', 'Bus updated successfully!');
    }

    public function deleteBus($busId)
    {
        Bus::findOrFail($busId)->delete();
        session()->flash('message', 'Bus deleted successfully!');
    }

    public function resetBusForm()
    {
        $this->busForm = [
            'name' => '',
            'route_name' => '',
            'is_active' => true
        ];
        $this->editingBus = null;
    }

    // Settings Management
    public function saveSettings()
    {
        foreach ($this->settings as $key => $value) {
            Setting::set($key, $value);
        }
        
        session()->flash('message', 'Settings saved successfully!');
    }

    public function render()
    {
        $buses = Bus::all();
        $trips = Trip::with('bus')->latest()->paginate(10);
        
        return view('livewire.admin-dashboard', [
            'buses' => $buses,
            'trips' => $trips
        ]);
    }
}