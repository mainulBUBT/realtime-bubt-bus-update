<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Bus Tracking Status Changed Event
 * Fired when the tracking status of a bus changes
 */
class BusTrackingStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $busId;
    public $status;
    public $activeTrackers;
    public $lastUpdate;
    public $metadata;

    /**
     * Create a new event instance
     *
     * @param string $busId
     * @param string $status
     * @param int $activeTrackers
     * @param array $metadata
     */
    public function __construct(string $busId, string $status, int $activeTrackers = 0, array $metadata = [])
    {
        $this->busId = $busId;
        $this->status = $status;
        $this->activeTrackers = $activeTrackers;
        $this->lastUpdate = now()->toISOString();
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("bus.{$this->busId}"),
            new Channel('bus.all'),
            new Channel('tracking.status')
        ];
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'tracking.status.changed';
    }

    /**
     * Get the data to broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'bus_id' => $this->busId,
            'status' => $this->status,
            'active_trackers' => $this->activeTrackers,
            'last_update' => $this->lastUpdate,
            'metadata' => $this->metadata,
            'server_time' => now()->toISOString()
        ];
    }
}