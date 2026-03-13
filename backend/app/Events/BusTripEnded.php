<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusTripEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $busId;
    public int $tripId;

    public function __construct(int $busId, int $tripId)
    {
        $this->busId   = $busId;
        $this->tripId  = $tripId;
    }

    /**
     * Broadcast on the same per-bus public channel the student app is already subscribed to.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bus.' . $this->busId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'BusTripEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'bus_id'  => $this->busId,
            'trip_id' => $this->tripId,
        ];
    }
}
