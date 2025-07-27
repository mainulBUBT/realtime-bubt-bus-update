<?php

namespace App\Livewire;

use Livewire\Component;

class ConnectionStatus extends Component
{
    public $connectionStatus = 'unknown'; // unknown, connected, disconnected, reconnecting, error, offline
    public $connectionType = 'none'; // websocket, polling, none
    public $reconnectAttempts = 0;
    public $lastUpdate = null;
    public $errorMessage = null;
    public $isVisible = false;

    protected $listeners = [
        'connectionStatusChanged' => 'updateConnectionStatus',
        'connectionError' => 'handleConnectionError',
        'hideConnectionStatus' => 'hide',
        'showConnectionStatus' => 'show'
    ];

    public function mount()
    {
        $this->connectionStatus = 'unknown';
        $this->connectionType = 'none';
    }

    public function updateConnectionStatus(array $statusData = [])
    {
        $this->connectionStatus = $statusData['status'] ?? 'unknown';
        $this->connectionType = $statusData['type'] ?? 'none';
        $this->reconnectAttempts = $statusData['reconnectAttempts'] ?? 0;
        $this->errorMessage = $statusData['message'] ?? null;
        $this->lastUpdate = now()->format('g:i:s A');

        // Show status bar for important status changes
        if (in_array($this->connectionStatus, ['disconnected', 'reconnecting', 'error', 'offline'])) {
            $this->isVisible = true;
        } elseif ($this->connectionStatus === 'connected') {
            // Hide after a brief success message
            $this->isVisible = true;
            $this->dispatch('auto-hide-status', ['delay' => 3000]);
        }
    }

    public function handleConnectionError(array $error = [])
    {
        $this->connectionStatus = 'error';
        $this->errorMessage = $error['message'] ?? 'Connection error occurred';
        $this->isVisible = true;
    }

    public function hide()
    {
        $this->isVisible = false;
    }

    public function show()
    {
        $this->isVisible = true;
    }

    public function retry()
    {
        $this->dispatch('retry-connection');
        $this->connectionStatus = 'reconnecting';
    }

    public function dismiss()
    {
        $this->isVisible = false;
    }

    public function getStatusText()
    {
        switch ($this->connectionStatus) {
            case 'connected':
                return $this->connectionType === 'websocket' 
                    ? 'Connected (Real-time)' 
                    : 'Connected (Polling)';
            
            case 'disconnected':
                return 'Connection lost';
            
            case 'reconnecting':
                return $this->reconnectAttempts > 0 
                    ? "Reconnecting... (attempt {$this->reconnectAttempts})"
                    : 'Reconnecting...';
            
            case 'error':
                return 'Connection error';
            
            case 'offline':
                return 'You are offline';
            
            default:
                return 'Connecting...';
        }
    }

    public function getStatusIcon()
    {
        switch ($this->connectionStatus) {
            case 'connected':
                return $this->connectionType === 'websocket' ? 'bi-wifi' : 'bi-arrow-clockwise';
            
            case 'disconnected':
                return 'bi-wifi-off';
            
            case 'reconnecting':
                return 'bi-arrow-clockwise';
            
            case 'error':
                return 'bi-exclamation-triangle';
            
            case 'offline':
                return 'bi-cloud-slash';
            
            default:
                return 'bi-three-dots';
        }
    }

    public function getStatusClass()
    {
        switch ($this->connectionStatus) {
            case 'connected':
                return 'success';
            
            case 'disconnected':
            case 'error':
                return 'danger';
            
            case 'reconnecting':
                return 'warning';
            
            case 'offline':
                return 'secondary';
            
            default:
                return 'info';
        }
    }

    public function shouldShowRetryButton()
    {
        return in_array($this->connectionStatus, ['disconnected', 'error', 'offline']);
    }

    public function render()
    {
        return view('livewire.connection-status', [
            'statusText' => $this->getStatusText(),
            'statusIcon' => $this->getStatusIcon(),
            'statusClass' => $this->getStatusClass(),
            'showRetryButton' => $this->shouldShowRetryButton()
        ]);
    }
}