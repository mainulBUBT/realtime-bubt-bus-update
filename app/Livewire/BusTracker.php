<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\UserTrackingSession;
use App\Models\DeviceToken;
use App\Services\DeviceTokenService;
use App\Services\BusLastSeenService;
use Carbon\Carbon;

class BusTracker extends Component
{
    public $busId;
    public $busName;
    public $busSchedule;
    public $routes = [];
    public $currentLocation = null;
    public $isTracking = false;
    public $trackingStatus = 'inactive';
    public $confidenceLevel = 0;
    public $activeTrackers = 0;
    public $currentStop = null;
    public $nextStop = null;
    public $eta = null;
    public $speed = 0;
    public $lastUpdated = null;
    public $tripStatus = 'inactive';
    public $deviceToken = null;
    public $lastSeenInfo = null;
    public $lastSeenMessage = '';
    public $trackingGapInfo = [];

    protected $listeners = [
        'locationUpdated' => 'updateLocation',
        'trackingStatusChanged' => 'updateTrackingStatus'
    ];

    public function mount($busId)
    {
        $this->busId = $busId;
        $this->loadBusData();
        $this->initializeDeviceToken();
    }

    public function loadBusData()
    {
        $this->busSchedule = BusSchedule::where('bus_id', $this->busId)
            ->active()
            ->with('routes')
            ->first();

        if ($this->busSchedule) {
            $this->busName = $this->busSchedule->route_name;
            $this->loadRouteTimeline();
            $this->updateBusStatus();
        } else {
            $this->busName = 'Unknown Bus';
            $this->tripStatus = 'inactive';
        }
    }

    public function loadRouteTimeline()
    {
        if (!$this->busSchedule) {
            return;
        }

        $orderedRoutes = $this->busSchedule->getOrderedRoutesForCurrentTrip();
        
        $this->routes = $orderedRoutes->map(function ($route) {
            return [
                'id' => $route->id,
                'stop_name' => $route->stop_name,
                'stop_order' => $route->stop_order,
                'estimated_time' => $route->getEstimatedArrivalTime(),
                'status' => $route->getTimelineStatus(),
                'progress_percentage' => $route->getProgressPercentage(),
                'latitude' => $route->latitude,
                'longitude' => $route->longitude,
                'is_current' => $route->isCurrentStop()
            ];
        })->toArray();

        $this->updateCurrentAndNextStops();
    }

    public function updateCurrentAndNextStops()
    {
        $currentRoute = collect($this->routes)->firstWhere('status', 'current');
        $this->currentStop = $currentRoute ? $currentRoute['stop_name'] : null;

        // Find next stop
        if ($currentRoute) {
            $currentIndex = collect($this->routes)->search(function ($route) use ($currentRoute) {
                return $route['id'] === $currentRoute['id'];
            });
            
            $nextRoute = collect($this->routes)->get($currentIndex + 1);
            $this->nextStop = $nextRoute ? $nextRoute['stop_name'] : null;
            $this->eta = $nextRoute ? $nextRoute['estimated_time'] : null;
        } else {
            // If no current stop, find the first upcoming stop
            $upcomingRoute = collect($this->routes)->firstWhere('status', 'upcoming');
            $this->nextStop = $upcomingRoute ? $upcomingRoute['stop_name'] : null;
            $this->eta = $upcomingRoute ? $upcomingRoute['estimated_time'] : null;
        }
    }

    public function updateBusStatus()
    {
        if (!$this->busSchedule) {
            $this->tripStatus = 'inactive';
            return;
        }

        if ($this->busSchedule->isCurrentlyActive()) {
            $this->tripStatus = 'active';
            
            // Use fallback service for comprehensive status
            $fallbackService = app(\App\Services\BusTrackingFallbackService::class);
            $trackingStatus = $fallbackService->getBusTrackingStatus($this->busId);
            
            $this->trackingStatus = $trackingStatus['status'];
            $this->confidenceLevel = $trackingStatus['confidence_level'] ?? 0.0;
            $this->activeTrackers = $trackingStatus['active_trackers'] ?? 0;
            
            // Update current location if available
            if (isset($trackingStatus['current_location'])) {
                $this->currentLocation = $trackingStatus['current_location'];
            }
            
            // Update last seen information
            $this->updateLastSeenInfo();
            
        } else {
            $this->tripStatus = 'inactive';
            $this->trackingStatus = 'inactive';
        }

        $this->lastUpdated = Carbon::now()->format('g:i A');
    }

    public function startTracking()
    {
        if (!$this->busSchedule || !$this->busSchedule->isCurrentlyActive()) {
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'This bus is not currently scheduled to run.'
            ]);
            return;
        }

        if (!$this->deviceToken) {
            $this->initializeDeviceToken();
        }

        // Start GPS tracking
        $this->isTracking = true;
        $this->trackingStatus = 'tracking';
        
        // Create or update tracking session
        UserTrackingSession::updateOrCreate(
            [
                'device_token' => $this->deviceToken,
                'bus_id' => $this->busId,
                'is_active' => true
            ],
            [
                'started_at' => Carbon::now(),
                'trust_score_at_start' => $this->getTrustScore()
            ]
        );

        $this->dispatch('start-gps-tracking', [
            'busId' => $this->busId,
            'deviceToken' => $this->deviceToken
        ]);

        $this->dispatch('show-notification', [
            'type' => 'success',
            'message' => 'Started tracking your location for this bus.'
        ]);
    }

    public function stopTracking()
    {
        $this->isTracking = false;
        $this->trackingStatus = $this->getTrackingStatus();

        // End tracking session
        UserTrackingSession::where('device_token', $this->deviceToken)
            ->where('bus_id', $this->busId)
            ->where('is_active', true)
            ->update([
                'ended_at' => Carbon::now(),
                'is_active' => false
            ]);

        $this->dispatch('stop-gps-tracking');

        $this->dispatch('show-notification', [
            'type' => 'info',
            'message' => 'Stopped tracking your location.'
        ]);
    }

    public function updateLocation($locationData)
    {
        if (!$this->isTracking) {
            return;
        }

        $this->currentLocation = $locationData;
        $this->lastUpdated = Carbon::now()->format('g:i A');
        
        // Update tracking status and confidence
        $this->updateTrackingMetrics();
        
        // Broadcast location update
        $this->dispatch('location-broadcast', [
            'busId' => $this->busId,
            'location' => $locationData,
            'deviceToken' => $this->deviceToken
        ]);
    }

    public function updateTrackingStatus($statusData)
    {
        $this->trackingStatus = $statusData['status'] ?? 'inactive';
        $this->confidenceLevel = $statusData['confidence'] ?? 0;
        $this->activeTrackers = $statusData['active_trackers'] ?? 0;
        $this->speed = $statusData['speed'] ?? 0;
    }

    private function initializeDeviceToken()
    {
        $deviceTokenService = app(DeviceTokenService::class);
        $this->deviceToken = $deviceTokenService->getOrCreateToken();
    }

    private function getTrackingStatus()
    {
        // Check if there are active tracking sessions for this bus
        $activeTrackers = UserTrackingSession::where('bus_id', $this->busId)
            ->where('is_active', true)
            ->count();

        $this->activeTrackers = $activeTrackers;

        if ($activeTrackers === 0) {
            return 'no_tracking';
        } elseif ($activeTrackers === 1) {
            return 'single_tracker';
        } else {
            return 'multiple_trackers';
        }
    }

    private function getTrustScore()
    {
        if (!$this->deviceToken) {
            return 0.5; // Default trust score
        }

        $deviceToken = DeviceToken::where('token_hash', hash('sha256', $this->deviceToken))->first();
        return $deviceToken ? $deviceToken->trust_score : 0.5;
    }

    private function updateTrackingMetrics()
    {
        $this->confidenceLevel = $this->calculateConfidenceLevel();
        $this->speed = $this->calculateSpeed();
    }

    private function calculateConfidenceLevel()
    {
        $baseConfidence = 0.5;
        
        // Increase confidence based on number of active trackers
        if ($this->activeTrackers > 1) {
            $baseConfidence += min(0.3, $this->activeTrackers * 0.1);
        }
        
        // Adjust based on trust score
        $trustScore = $this->getTrustScore();
        $baseConfidence += ($trustScore - 0.5) * 0.4;
        
        return min(1.0, max(0.0, $baseConfidence));
    }

    private function calculateSpeed()
    {
        // This would typically calculate speed from GPS data
        // For now, return a simulated value
        return rand(20, 50);
    }

    public function refreshData()
    {
        $this->loadBusData();
        $this->updateBusStatus();
    }

    private function updateLastSeenInfo()
    {
        $lastSeenService = app(BusLastSeenService::class);
        
        // Get last seen information with context
        $this->lastSeenInfo = $lastSeenService->getLastSeenWithContext($this->busId);
        
        // Get formatted message for display
        $this->lastSeenMessage = $lastSeenService->getLastSeenMessage($this->busId);
        
        // Get tracking gap information
        $this->trackingGapInfo = $lastSeenService->getTrackingGapInfo($this->busId);
    }

    public function render()
    {
        return view('livewire.bus-tracker', [
            'busSchedule' => $this->busSchedule,
            'routes' => $this->routes,
            'isActive' => $this->busSchedule && $this->busSchedule->isCurrentlyActive(),
            'currentTrip' => $this->busSchedule ? $this->busSchedule->getCurrentTripDirection() : 'inactive'
        ]);
    }
}
