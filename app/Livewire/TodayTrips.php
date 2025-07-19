<?php

namespace App\Livewire;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\Location;
use App\Support\Cluster;
use Livewire\Component;
use Livewire\Attributes\On;

class TodayTrips extends Component
{
    public $buses = [];
    public $busPositions = [];
    public $selectedBus = null;
    public $lastUpdated;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Load buses with today's trips
        $this->buses = Bus::with(['todayTrips', 'stops', 'currentLocation'])
            ->where('is_active', true)
            ->get()
            ->map(function ($bus) {
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
                    'current_location' => $bus->currentLocation ? [
                        'latitude' => $bus->currentLocation->latitude,
                        'longitude' => $bus->currentLocation->longitude,
                        'recorded_at' => $bus->currentLocation->recorded_at->diffForHumans(),
                    ] : null,
                ];
            })
            ->toArray();

        $this->loadBusPositions();
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function loadBusPositions()
    {
        // Get recent locations for clustering
        $recentLocations = Location::with('bus')
            ->recent(10) // Last 10 minutes
            ->get()
            ->groupBy('bus_id')
            ->map(function ($locations) {
                return $locations->first(); // Most recent per bus
            })
            ->values();

        // Format for clustering
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

        // Apply clustering
        $cluster = new Cluster(60, 2); // 60m radius, min 2 points
        $this->busPositions = $cluster->getBusPositions($locationData);
    }

    #[On('bus-moved')]
    public function handleBusMovement($data)
    {
        // Refresh positions when bus moves
        $this->loadBusPositions();
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function selectBus($busId)
    {
        $this->selectedBus = $busId;
    }

    public function refreshData()
    {
        $this->loadData();
        $this->dispatch('data-refreshed');
    }

    public function render()
    {
        return view('livewire.today-trips');
    }
}