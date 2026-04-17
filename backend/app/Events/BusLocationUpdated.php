<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $busId;
    public $location;

    /**
     * Create a new event instance.
     */
    public function __construct(int $busId, array $location)
    {
        $this->busId = $busId;
        $this->location = $location;
    }

    /**
     * Use a simple literal name so the frontend can listen with a dot-prefix (e.g. '.BusLocationUpdated').
     */
    public function broadcastAs(): string
    {
        return 'BusLocationUpdated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bus.' . $this->busId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'bus_id'     => $this->busId,
            'trip_id'    => $this->location['trip_id'] ?? null,
            'lat'        => $this->location['lat'],
            'lng'        => $this->location['lng'],
            'speed'      => $this->location['speed'] ?? null,
            'updated_at' => $this->location['recorded_at'],
            'tracking_status' => $this->location['tracking_status'] ?? null,
            'current_stop_id' => $this->location['current_stop_id'] ?? null,
            'next_stop_id' => $this->location['next_stop_id'] ?? null,
            'progress_distance_m' => $this->location['progress_distance_m'] ?? null,
            'distance_to_next_stop_m' => $this->location['distance_to_next_stop_m'] ?? null,
            'osrm_distance_to_next_stop_m' => $this->location['osrm_distance_to_next_stop_m'] ?? null,
            'eta_to_next_stop_seconds' => $this->location['eta_to_next_stop_seconds'] ?? null,
            'eta_to_destination_seconds' => $this->location['eta_to_destination_seconds'] ?? null,
            'stop_states' => $this->location['stop_states'] ?? [],
        ];
    }
}
