<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BusTimelineProgression extends Model
{
    protected $table = 'bus_timeline_progression';
    
    protected $fillable = [
        'bus_id',
        'schedule_id',
        'route_id',
        'trip_direction',
        'status',
        'reached_at',
        'estimated_arrival',
        'progress_percentage',
        'distance_from_previous',
        'eta_minutes',
        'confidence_score',
        'location_data',
        'is_active_trip'
    ];

    protected $casts = [
        'reached_at' => 'datetime',
        'estimated_arrival' => 'datetime',
        'progress_percentage' => 'integer',
        'distance_from_previous' => 'decimal:2',
        'eta_minutes' => 'integer',
        'confidence_score' => 'float',
        'location_data' => 'array',
        'is_active_trip' => 'boolean'
    ];

    // Status constants
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CURRENT = 'current';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_SKIPPED = 'skipped';

    // Trip direction constants
    public const DIRECTION_DEPARTURE = 'departure';
    public const DIRECTION_RETURN = 'return';

    /**
     * Get the schedule this progression belongs to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BusSchedule::class, 'schedule_id');
    }

    /**
     * Get the route this progression belongs to
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    /**
     * Check if this stop is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this is the current stop
     */
    public function isCurrent(): bool
    {
        return $this->status === self::STATUS_CURRENT;
    }

    /**
     * Check if this stop is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === self::STATUS_UPCOMING;
    }

    /**
     * Mark stop as completed
     */
    public function markAsCompleted(?Carbon $reachedAt = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'reached_at' => $reachedAt ?? now(),
            'progress_percentage' => 100
        ]);
    }

    /**
     * Mark stop as current
     */
    public function markAsCurrent(): void
    {
        $this->update([
            'status' => self::STATUS_CURRENT
        ]);
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(int $percentage, ?int $etaMinutes = null): void
    {
        $this->update([
            'progress_percentage' => max(0, min(100, $percentage)),
            'eta_minutes' => $etaMinutes
        ]);
    }

    /**
     * Update ETA
     */
    public function updateETA(int $etaMinutes, float $confidenceScore = 0.0): void
    {
        $this->update([
            'eta_minutes' => $etaMinutes,
            'estimated_arrival' => now()->addMinutes($etaMinutes),
            'confidence_score' => $confidenceScore
        ]);
    }

    /**
     * Update location data
     */
    public function updateLocationData(array $locationData): void
    {
        $this->update([
            'location_data' => $locationData
        ]);
    }

    /**
     * Get formatted ETA
     */
    public function getFormattedETA(): ?string
    {
        if (!$this->eta_minutes) {
            return null;
        }

        if ($this->eta_minutes < 60) {
            return "{$this->eta_minutes} min";
        }

        $hours = intval($this->eta_minutes / 60);
        $minutes = $this->eta_minutes % 60;
        
        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }

    /**
     * Get time since reached (for completed stops)
     */
    public function getTimeSinceReached(): ?string
    {
        if (!$this->reached_at) {
            return null;
        }

        $diffInMinutes = now()->diffInMinutes($this->reached_at);
        
        if ($diffInMinutes < 60) {
            return "{$diffInMinutes} min ago";
        }

        $hours = intval($diffInMinutes / 60);
        $minutes = $diffInMinutes % 60;
        
        return $minutes > 0 ? "{$hours}h {$minutes}m ago" : "{$hours}h ago";
    }

    /**
     * Scope for active trips
     */
    public function scopeActiveTrip($query)
    {
        return $query->where('is_active_trip', true);
    }

    /**
     * Scope for specific bus
     */
    public function scopeForBus($query, string $busId)
    {
        return $query->where('bus_id', $busId);
    }

    /**
     * Scope for specific direction
     */
    public function scopeForDirection($query, string $direction)
    {
        return $query->where('trip_direction', $direction);
    }

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for current stop
     */
    public function scopeCurrent($query)
    {
        return $query->where('status', self::STATUS_CURRENT);
    }

    /**
     * Scope for completed stops
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for upcoming stops
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_UPCOMING);
    }

    /**
     * Scope ordered by route stop order
     */
    public function scopeOrderedByRoute($query)
    {
        return $query->join('bus_routes', 'bus_timeline_progression.route_id', '=', 'bus_routes.id')
            ->orderBy('bus_routes.stop_order');
    }
}