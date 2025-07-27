<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\UserTrackingSession;
use App\Services\DeviceTokenService;
use Carbon\Carbon;

class LocationSharing extends Component
{
    public $permissionStatus = 'unknown'; // unknown, granted, denied, prompt
    public $isSharing = false;
    public $currentLocation = null;
    public $accuracy = null;
    public $lastUpdate = null;
    public $deviceToken = null;
    public $trackingSessionId = null;
    public $errorMessage = null;
    public $batteryOptimized = true;

    protected $listeners = [
        'locationPermissionChanged' => 'handlePermissionChange',
        'locationUpdated' => 'handleLocationUpdate',
        'locationError' => 'handleLocationError'
    ];

    public function mount()
    {
        $this->initializeDeviceToken();
        $this->checkPermissionStatus();
    }

    public function requestPermission()
    {
        $this->permissionStatus = 'requesting';
        $this->errorMessage = null;
        
        $this->dispatch('request-location-permission');
    }

    public function startSharing()
    {
        if ($this->permissionStatus !== 'granted') {
            $this->requestPermission();
            return;
        }

        $this->isSharing = true;
        $this->errorMessage = null;
        
        // Create tracking session
        $this->trackingSessionId = UserTrackingSession::create([
            'device_token' => $this->deviceToken,
            'bus_id' => null, // Will be set when user selects a bus
            'started_at' => Carbon::now(),
            'is_active' => true,
            'trust_score_at_start' => $this->getTrustScore()
        ])->id;

        $this->dispatch('start-location-sharing', [
            'sessionId' => $this->trackingSessionId,
            'deviceToken' => $this->deviceToken,
            'batteryOptimized' => $this->batteryOptimized
        ]);

        $this->dispatch('show-notification', [
            'type' => 'success',
            'message' => 'Location sharing started successfully.'
        ]);
    }

    public function stopSharing()
    {
        $this->isSharing = false;
        $this->currentLocation = null;
        $this->accuracy = null;
        $this->lastUpdate = null;

        // End tracking session
        if ($this->trackingSessionId) {
            UserTrackingSession::where('id', $this->trackingSessionId)
                ->update([
                    'ended_at' => Carbon::now(),
                    'is_active' => false
                ]);
            $this->trackingSessionId = null;
        }

        $this->dispatch('stop-location-sharing');

        $this->dispatch('show-notification', [
            'type' => 'info',
            'message' => 'Location sharing stopped.'
        ]);
    }

    public function toggleBatteryOptimization()
    {
        $this->batteryOptimized = !$this->batteryOptimized;
        
        if ($this->isSharing) {
            // Restart sharing with new settings
            $this->dispatch('update-location-settings', [
                'batteryOptimized' => $this->batteryOptimized
            ]);
        }
    }

    public function handlePermissionChange($status)
    {
        $this->permissionStatus = $status;
        
        if ($status === 'granted') {
            $this->errorMessage = null;
            $this->dispatch('show-notification', [
                'type' => 'success',
                'message' => 'Location permission granted.'
            ]);
        } elseif ($status === 'denied') {
            $this->isSharing = false;
            $this->errorMessage = 'Location permission is required for bus tracking.';
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'Location permission denied. Please enable it in your browser settings.'
            ]);
        }
    }

    public function handleLocationUpdate($locationData)
    {
        $this->currentLocation = [
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude']
        ];
        $this->accuracy = $locationData['accuracy'] ?? null;
        $this->lastUpdate = Carbon::now()->format('g:i:s A');
        $this->errorMessage = null;

        // Update tracking session
        if ($this->trackingSessionId) {
            UserTrackingSession::where('id', $this->trackingSessionId)
                ->increment('locations_contributed');
        }
    }

    public function handleLocationError($error)
    {
        $this->errorMessage = $error['message'] ?? 'Location error occurred.';
        
        if ($error['code'] === 1) { // PERMISSION_DENIED
            $this->permissionStatus = 'denied';
            $this->isSharing = false;
        } elseif ($error['code'] === 2) { // POSITION_UNAVAILABLE
            $this->errorMessage = 'Location unavailable. Please check your GPS settings.';
        } elseif ($error['code'] === 3) { // TIMEOUT
            $this->errorMessage = 'Location request timed out. Retrying...';
        }

        $this->dispatch('show-notification', [
            'type' => 'error',
            'message' => $this->errorMessage
        ]);
    }

    public function refreshStatus()
    {
        $this->checkPermissionStatus();
        $this->dispatch('check-location-permission');
    }

    private function initializeDeviceToken()
    {
        $deviceTokenService = app(DeviceTokenService::class);
        $this->deviceToken = $deviceTokenService->getOrCreateToken();
    }

    private function checkPermissionStatus()
    {
        // This will be updated by JavaScript
        $this->permissionStatus = 'unknown';
    }

    private function getTrustScore()
    {
        $deviceTokenService = app(DeviceTokenService::class);
        return $deviceTokenService->getReputationScore($this->deviceToken);
    }

    public function getLocationAccuracyText()
    {
        if (!$this->accuracy) {
            return 'Unknown';
        }

        if ($this->accuracy <= 5) {
            return 'Excellent (' . round($this->accuracy) . 'm)';
        } elseif ($this->accuracy <= 10) {
            return 'Good (' . round($this->accuracy) . 'm)';
        } elseif ($this->accuracy <= 20) {
            return 'Fair (' . round($this->accuracy) . 'm)';
        } else {
            return 'Poor (' . round($this->accuracy) . 'm)';
        }
    }

    public function getLocationAccuracyClass()
    {
        if (!$this->accuracy) {
            return 'unknown';
        }

        if ($this->accuracy <= 5) {
            return 'excellent';
        } elseif ($this->accuracy <= 10) {
            return 'good';
        } elseif ($this->accuracy <= 20) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public function render()
    {
        return view('livewire.location-sharing', [
            'accuracyText' => $this->getLocationAccuracyText(),
            'accuracyClass' => $this->getLocationAccuracyClass(),
            'hasActiveSession' => $this->trackingSessionId !== null
        ]);
    }
}
