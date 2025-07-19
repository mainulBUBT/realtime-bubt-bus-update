<?php

namespace App\Livewire;

use App\Models\Bus;
use App\Models\BusBoarding;
use App\Models\BusStatus;
use App\Models\Trip;
use App\Models\Location;
use App\Support\Cluster;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class StudentDashboard extends Component
{
    public $buses = [];
    public $selectedBus = null;
    public $selectedTrip = null;
    public $selectedBoardingStop = null;
    public $selectedDestinationStop = null;
    public $showBoardingModal = false;
    public $userBoardings = [];
    public $busPositions = [];
    public $lastUpdated;

    public function mount()
    {
        $this->loadData();
        $this->loadUserBoardings();
    }

    public function loadData()
    {
        // Load active buses with today's trips and real-time status
        $this->buses = Bus::with(['todayTrips', 'stops'])
            ->where('is_active', true)
            ->get()
            ->map(function ($bus) {
                $busStatus = BusStatus::where('bus_id', $bus->id)
                    ->whereHas('trip', function($q) {
                        $q->whereDate('trip_date', today())
                          ->where('status', 'active');
                    })
                    ->first();

                $currentLocation = Location::where('bus_id', $bus->id)
                    ->where('recorded_at', '>=', now()->subMinutes(10))
                    ->latest('recorded_at')
                    ->first();

                return [
                    'id' => $bus->id,
                    'name' => $bus->name,
                    'route_name' => $bus->route_name,
                    'trips' => $bus->todayTrips->map(function ($trip) {
                        return [
                            'id' => $trip->id,
                            'departure_time' => $trip->departure_time->format('H:i'),
                            'return_time' => $trip->return_time->format('H:i'),
                            'direction' => $trip->direction,
                            'status' => $trip->status,
                        ];
                    }),
                    'stops' => $bus->stops->map(function ($stop) {
                        return [
                            'id' => $stop->id,
                            'name' => $stop->name,
                            'latitude' => $stop->latitude,
                            'longitude' => $stop->longitude,
                            'order_index' => $stop->order_index,
                        ];
                    }),
                    'status' => $busStatus ? [
                        'current_capacity' => $busStatus->current_capacity,
                        'max_capacity' => $busStatus->max_capacity,
                        'capacity_percentage' => $busStatus->capacity_percentage,
                        'available_seats' => $busStatus->available_seats,
                        'status' => $busStatus->status,
                        'current_stop' => $busStatus->currentStop?->name,
                        'is_near_capacity' => $busStatus->isNearCapacity(),
                    ] : null,
                    'current_location' => $currentLocation ? [
                        'latitude' => $currentLocation->latitude,
                        'longitude' => $currentLocation->longitude,
                        'recorded_at' => $currentLocation->recorded_at->diffForHumans(),
                    ] : null,
                ];
            })
            ->toArray();

        $this->loadBusPositions();
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function loadBusPositions()
    {
        $recentLocations = Location::with('bus')
            ->where('recorded_at', '>=', now()->subMinutes(10))
            ->get()
            ->groupBy('bus_id')
            ->map(function ($locations) {
                return $locations->first();
            })
            ->values();

        $locationData = $recentLocations->map(function ($location) {
            return [
                'bus_id' => $location->bus_id,
                'bus_name' => $location->bus->name,
                'route_name' => $location->bus->route_name,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'recorded_at' => $location->recorded_at->toISOString()
            ];
        })->toArray();

        $cluster = new Cluster(60, 2);
        $this->busPositions = $cluster->getBusPositions($locationData);
    }

    public function loadUserBoardings()
    {
        $this->userBoardings = BusBoarding::with(['bus', 'trip', 'boardingStop', 'destinationStop'])
            ->where('user_id', Auth::id())
            ->active()
            ->get()
            ->map(function ($boarding) {
                return [
                    'id' => $boarding->id,
                    'bus_name' => $boarding->bus->name,
                    'route_name' => $boarding->bus->route_name,
                    'boarding_stop' => $boarding->boardingStop->name,
                    'destination_stop' => $boarding->destinationStop?->name,
                    'status' => $boarding->status,
                    'boarded_at' => $boarding->boarded_at->format('H:i'),
                ];
            })
            ->toArray();
    }

    public function selectBus($busId, $tripId)
    {
        $this->selectedBus = $busId;
        $this->selectedTrip = $tripId;
        $this->showBoardingModal = true;
        $this->resetBoardingForm();
    }

    public function requestBoarding()
    {
        $this->validate([
            'selectedBoardingStop' => 'required|exists:stops,id',
            'selectedDestinationStop' => 'nullable|exists:stops,id',
        ]);

        // Check if user already has an active boarding for this trip
        $existingBoarding = BusBoarding::where('user_id', Auth::id())
            ->where('trip_id', $this->selectedTrip)
            ->active()
            ->first();

        if ($existingBoarding) {
            $this->addError('boarding', 'You already have an active boarding request for this trip.');
            return;
        }

        BusBoarding::create([
            'user_id' => Auth::id(),
            'bus_id' => $this->selectedBus,
            'trip_id' => $this->selectedTrip,
            'boarding_stop_id' => $this->selectedBoardingStop,
            'destination_stop_id' => $this->selectedDestinationStop,
            'boarded_at' => now(),
            'status' => 'waiting',
        ]);

        $this->showBoardingModal = false;
        $this->loadUserBoardings();
        
        // Emit toast notification
        $this->dispatch('toast', [
            'message' => 'Boarding request submitted! You will be notified when the bus arrives.',
            'type' => 'success',
            'title' => 'Boarding Confirmed!'
        ]);
    }

    public function cancelBoarding($boardingId)
    {
        $boarding = BusBoarding::where('id', $boardingId)
            ->where('user_id', Auth::id())
            ->first();

        if ($boarding) {
            $boarding->update(['status' => 'cancelled']);
            $this->loadUserBoardings();
            
            // Emit toast notification
            $this->dispatch('toast', [
                'message' => 'Your boarding request has been cancelled successfully.',
                'type' => 'info',
                'title' => 'Booking Cancelled'
            ]);
        }
    }

    public function resetBoardingForm()
    {
        $this->selectedBoardingStop = null;
        $this->selectedDestinationStop = null;
        $this->resetErrorBag();
    }

    public function closeBoardingModal()
    {
        $this->showBoardingModal = false;
        $this->resetBoardingForm();
    }

    #[On('bus-moved')]
    public function handleBusMovement($data)
    {
        $this->loadBusPositions();
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function refreshData()
    {
        $this->loadData();
        $this->loadUserBoardings();
        $this->dispatch('data-refreshed');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        
        // Add logout notification
        session()->flash('info', 'You have been logged out successfully. See you soon!');
        
        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.student-dashboard');
    }
}