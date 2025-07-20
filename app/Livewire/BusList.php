<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class BusList extends Component
{
    public $availableBuses = [];
    public $selectedBus = null;
    public $deviceUuid;
    public $isTracking = false;
    public $activeFilter = 'All Routes';
    public $routes = ['All Routes', 'Dhanmondi', 'Uttara', 'Gulshan', 'Mirpur', 'Wari'];
    
    protected $listeners = [
        'busLocationUpdated' => 'updateBusLocation',
        'tripStatusChanged' => 'refreshBusList'
    ];
    
    public function mount()
    {
        $this->deviceUuid = $this->generateOrGetDeviceUuid();
        $this->loadAvailableBuses();
    }
    
    public function loadAvailableBuses()
    {
        // In a real implementation, this would fetch from the database
        // For now, we'll use mock data similar to the design
        $this->availableBuses = [
            [
                'id' => 1,
                'name' => 'B1',
                'route' => 'Dhanmondi → BUBT',
                'status' => 'on_time',
                'eta' => '11 min',
                'current_location' => 'Dhanmondi 27',
                'students_tracking' => rand(5, 25),
                'next_stop' => 'BUBT Campus',
                'gradient' => 'primary'
            ],
            [
                'id' => 2,
                'name' => 'B2',
                'route' => 'Uttara → BUBT',
                'status' => 'on_time',
                'eta' => '15 min',
                'current_location' => 'Uttara Sector 7',
                'students_tracking' => rand(5, 25),
                'next_stop' => 'BUBT Campus',
                'gradient' => 'success'
            ],
            [
                'id' => 3,
                'name' => 'B3',
                'route' => 'Gulshan → BUBT',
                'status' => 'delayed',
                'eta' => '20 min (Delayed)',
                'current_location' => 'Gulshan 2',
                'students_tracking' => rand(5, 25),
                'next_stop' => 'BUBT Campus',
                'gradient' => 'warning'
            ],
            [
                'id' => 4,
                'name' => 'B4',
                'route' => 'Mirpur → BUBT',
                'status' => 'on_time',
                'eta' => '7 min',
                'current_location' => 'Mirpur 10',
                'students_tracking' => rand(5, 25),
                'next_stop' => 'BUBT Campus',
                'gradient' => 'primary'
            ],
            [
                'id' => 5,
                'name' => 'B5',
                'route' => 'Wari → BUBT',
                'status' => 'on_time',
                'eta' => '9 min',
                'current_location' => 'Wari Bazar',
                'students_tracking' => rand(5, 25),
                'next_stop' => 'BUBT Campus',
                'gradient' => 'success'
            ],
        ];
    }
    
    public function filterByRoute($route)
    {
        $this->activeFilter = $route;
    }
    
    public function joinBus($busId)
    {
        // In a real implementation, this would verify device location
        // For now, we'll simulate success
        if ($this->verifyDeviceLocation($busId)) {
            $this->isTracking = true;
            $this->selectedBus = $busId;
            $this->dispatch('toastr:success', [
                'message' => 'Successfully joined bus tracking!'
            ]);
        } else {
            $this->dispatch('toastr:error', [
                'message' => 'Unable to join bus. Check your location and try again.'
            ]);
        }
    }
    
    public function leaveBus()
    {
        $this->isTracking = false;
        $this->selectedBus = null;
        $this->dispatch('toastr:info', [
            'message' => 'You have left the bus tracking.'
        ]);
    }
    
    public function updateBusLocation($data)
    {
        // This would update a specific bus's location in real-time
        // For now, we'll just refresh the list
        $this->loadAvailableBuses();
    }
    
    public function refreshBusList()
    {
        $this->loadAvailableBuses();
    }
    
    private function generateOrGetDeviceUuid()
    {
        // Check if UUID exists in local storage, otherwise generate a new one
        // For now, we'll just generate a random one
        return 'device-' . uniqid();
    }
    
    private function verifyDeviceLocation($busId)
    {
        // In a real implementation, this would check if the device is near the bus
        // For now, we'll just return true
        return true;
    }
    
    public function render()
    {
        $filteredBuses = $this->activeFilter === 'All Routes' 
            ? $this->availableBuses 
            : array_filter($this->availableBuses, function($bus) {
                return strpos($bus['route'], $this->activeFilter) !== false;
            });
            
        return view('livewire.bus-list', [
            'filteredBuses' => $filteredBuses
        ]);
    }
}
