<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BusSchedule;
use Carbon\Carbon;

class BusList extends Component
{
    public $selectedBusFilter = 'all';
    public $selectedStatusFilter = 'all';
    public $buses = [];
    public $filteredBuses = [];

    protected $listeners = ['busStatusUpdated' => 'refreshBuses'];

    public function mount()
    {
        $this->loadBuses();
        $this->applyFilters();
    }

    public function loadBuses()
    {
        // Get all active bus schedules with their current status
        $schedules = BusSchedule::active()->with('routes')->get();
        
        $this->buses = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->bus_id,
                'name' => $schedule->route_name,
                'schedule' => $this->formatSchedule($schedule),
                'status' => $this->getBusStatus($schedule),
                'is_active' => $schedule->isCurrentlyActive(),
                'departure_time' => $schedule->departure_time->format('g:i A'),
                'return_time' => $schedule->return_time->format('g:i A'),
                'current_trip' => $schedule->getCurrentTripDirection(),
                'next_stop' => $this->getNextStop($schedule),
                'eta' => $this->getEstimatedArrival($schedule)
            ];
        })->toArray();
    }

    public function refreshBuses()
    {
        $this->loadBuses();
        $this->applyFilters();
    }

    public function filterByBus($busId)
    {
        $this->selectedBusFilter = $busId;
        $this->applyFilters();
    }

    public function filterByStatus($status)
    {
        $this->selectedStatusFilter = $status;
        $this->applyFilters();
    }

    public function applyFilters()
    {
        $this->filteredBuses = collect($this->buses)->filter(function ($bus) {
            $matchesBusFilter = $this->selectedBusFilter === 'all' || $bus['id'] === $this->selectedBusFilter;
            $matchesStatusFilter = $this->selectedStatusFilter === 'all' || $bus['status'] === $this->selectedStatusFilter;
            
            return $matchesBusFilter && $matchesStatusFilter;
        })->values()->toArray();
    }

    public function selectBus($busId)
    {
        // Navigate to bus tracking page
        return redirect()->route('track', ['bus' => $busId]);
    }

    private function formatSchedule($schedule)
    {
        return "Departure: {$schedule->departure_time->format('g:i A')} | Return: {$schedule->return_time->format('g:i A')}";
    }

    private function getBusStatus($schedule)
    {
        if (!$schedule->isCurrentlyActive()) {
            return 'inactive';
        }

        // Use fallback service to get comprehensive tracking status
        $fallbackService = app(\App\Services\BusTrackingFallbackService::class);
        $trackingStatus = $fallbackService->getBusTrackingStatus($schedule->bus_id);
        
        switch ($trackingStatus['status']) {
            case 'active':
                return 'active';
            case 'single_tracker':
                return 'delayed'; // Show as delayed for single tracker
            case 'no_tracking':
                return 'delayed'; // Show as delayed when no tracking
            default:
                return 'inactive';
        }
    }

    private function hasActiveTracking($busId)
    {
        // Check if there are active tracking sessions for this bus
        $activeTrackers = \App\Models\UserTrackingSession::where('bus_id', $busId)
            ->where('is_active', true)
            ->where('started_at', '>', now()->subHours(2))
            ->count();
            
        return $activeTrackers > 0;
    }

    private function getNextStop($schedule)
    {
        if (!$schedule->isCurrentlyActive()) {
            return null;
        }

        $routes = $schedule->getOrderedRoutesForCurrentTrip();
        
        // Find the current or next stop based on time
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        
        foreach ($routes as $route) {
            $estimatedTime = $route->getEstimatedArrivalTime();
            if ($currentTime <= $estimatedTime) {
                return $route->stop_name;
            }
        }

        return $routes->last()->stop_name ?? null;
    }

    private function getEstimatedArrival($schedule)
    {
        if (!$schedule->isCurrentlyActive()) {
            return null;
        }

        $nextStop = $this->getNextStop($schedule);
        if (!$nextStop) {
            return null;
        }

        $routes = $schedule->getOrderedRoutesForCurrentTrip();
        $route = $routes->firstWhere('stop_name', $nextStop);
        
        return $route ? $route->getEstimatedArrivalTime() : null;
    }

    public function render()
    {
        return view('livewire.bus-list', [
            'buses' => $this->filteredBuses,
            'busOptions' => $this->getBusOptions(),
            'statusOptions' => $this->getStatusOptions()
        ]);
    }

    private function getBusOptions()
    {
        $options = [['id' => 'all', 'name' => 'All Buses']];
        
        foreach ($this->buses as $bus) {
            $options[] = [
                'id' => $bus['id'],
                'name' => $bus['name']
            ];
        }

        return $options;
    }

    private function getStatusOptions()
    {
        return [
            ['id' => 'all', 'name' => 'All Status'],
            ['id' => 'active', 'name' => 'Active'],
            ['id' => 'delayed', 'name' => 'Delayed'],
            ['id' => 'inactive', 'name' => 'Inactive']
        ];
    }
}
