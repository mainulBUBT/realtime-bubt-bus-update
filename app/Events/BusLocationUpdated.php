<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Bus Location Updated Event
 * Fired when a bus location is updated and needs to be broadcast
 */
class BusLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $busId;
    public $locationData;
    public $timestamp;
    public $activeTrackers;

    /**
     * Create a new event instance
     *
     * @param string $busId
     * @param array $locationData
     * @param int $activeTrackers
     */
    public function __construct(string $busId, array $locationData, int $activeTrackers = 0)
    {
        $this->busId = $busId;
        $this->locationData = $locationData;
        $this->activeTrackers = $activeTrackers;
        $this->timestamp = now()->toISOString();

        Log::debug('BusLocationUpdated event created', [
            'bus_id' => $busId,
            'active_trackers' => $activeTrackers
        ]);
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
            new PresenceChannel("bus.{$this->busId}.tracking")
        ];
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
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
            'location' => $this->locationData,
            'active_trackers' => $this->activeTrackers,
            'timestamp' => $this->timestamp,
            'server_time' => now()->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if there are active trackers or subscribers
        return $this->activeTrackers > 0 || $this->hasSubscribers();
    }

    /**
     * Check if there are subscribers to this bus
     *
     * @return bool
     */
    private function hasSubscribers(): bool
    {
        // This would check if there are WebSocket connections subscribed to this bus
        // For now, we'll assume there are subscribers if the bus is active
        return true;
    }
}