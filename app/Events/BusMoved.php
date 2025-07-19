<?php

namespace App\Events;

use App\Models\Bus;
use App\Models\Location;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Bus $bus;
    public Location $location;

    public function __construct(Bus $bus, Location $location)
    {
        $this->bus = $bus;
        $this->location = $location;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bus-tracking'),
            new Channel('bus-tracking.' . $this->bus->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'bus.moved';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'bus' => [
                'id' => $this->bus->id,
                'name' => $this->bus->name,
                'route_name' => $this->bus->route_name,
            ],
            'location' => [
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude,
                'recorded_at' => $this->location->recorded_at->toISOString(),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}