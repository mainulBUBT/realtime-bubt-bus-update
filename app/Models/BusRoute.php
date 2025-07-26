<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BusRoute extends Model
{
    protected $fillable = [
        'schedule_id',
        'stop_name',
        'stop_order',
        'latitude',
        'longitude',
        'coverage_radius',
        'estimated_departure_time',
        'estimated_return_time',
        'departure_duration_minutes',
        'return_duration_minutes'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'coverage_radius' => 'integer',
        'estimated_departure_time' => 'datetime:H:i',
        'estimated_return_time' => 'datetime:H:i',
        'departure_duration_minutes' => 'integer',
        'return_duration_minutes' => 'integer'
    ];

    /**
     * Get the schedule this route belongs to
     */
    public function schedule()
    {
        return $this->belongsTo(BusSchedule::class, 'schedule_id');
    }

    /**
     * Check if given coordinates are within this stop's coverage radius
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        $distance = $this->calculateDistance($lat, $lng, $this->latitude, $this->longitude);
        return $distance <= $this->coverage_radius;
    }

    /**
     * Calculate distance between two GPS coordinates in meters
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get estimated arrival time based on current trip direction
     */
    public function getEstimatedArrivalTime(): string
    {
        $schedule = $this->schedule;
        
        if (!$schedule) {
            return 'N/A';
        }

        if ($schedule->isOnDepartureTrip()) {
            return $this->estimated_departure_time->format('H:i');
        } else {
            return $this->estimated_return_time->format('H:i');
        }
    }

    /**
     * Get the timeline status for this stop (completed, current, upcoming)
     */
    public function getTimelineStatus(): string
    {
        $schedule = $this->schedule;
        
        if (!$schedule || !$schedule->isCurrentlyActive()) {
            return 'inactive';
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $estimatedTime = $this->getEstimatedArrivalTime();

        if ($currentTime > $estimatedTime) {
            return 'completed';
        } elseif ($this->isCurrentStop()) {
            return 'current';
        } else {
            return 'upcoming';
        }
    }

    /**
     * Check if this is the current stop based on time
     */
    public function isCurrentStop(): bool
    {
        $schedule = $this->schedule;
        
        if (!$schedule || !$schedule->isCurrentlyActive()) {
            return false;
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $estimatedTime = $this->getEstimatedArrivalTime();

        // Consider current if within 15 minutes window
        $estimatedMinutes = $this->timeToMinutes($estimatedTime);
        $currentMinutes = $this->timeToMinutes($currentTime);
        
        return abs($currentMinutes - $estimatedMinutes) <= 15;
    }

    /**
     * Get progress percentage for current stop (0-100)
     */
    public function getProgressPercentage(): int
    {
        if ($this->getTimelineStatus() === 'completed') {
            return 100;
        }

        if ($this->getTimelineStatus() !== 'current') {
            return 0;
        }

        $schedule = $this->schedule;
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        
        // Get previous stop time
        $previousStop = $this->schedule->routes()
            ->where('stop_order', '<', $this->stop_order)
            ->orderBy('stop_order', 'desc')
            ->first();

        if (!$previousStop) {
            // First stop, calculate from departure time
            $startTime = $schedule->isOnDepartureTrip() 
                ? $schedule->departure_time->format('H:i')
                : $schedule->return_time->format('H:i');
        } else {
            $startTime = $previousStop->getEstimatedArrivalTime();
        }

        $endTime = $this->getEstimatedArrivalTime();
        
        $startMinutes = $this->timeToMinutes($startTime);
        $endMinutes = $this->timeToMinutes($endTime);
        $currentMinutes = $this->timeToMinutes($currentTime);

        if ($endMinutes <= $startMinutes) {
            return 0;
        }

        $progress = (($currentMinutes - $startMinutes) / ($endMinutes - $startMinutes)) * 100;
        return max(0, min(100, (int) $progress));
    }

    /**
     * Get next stop in the route
     */
    public function getNextStop()
    {
        $schedule = $this->schedule;
        
        if ($schedule->isOnDepartureTrip()) {
            return $this->schedule->routes()
                ->where('stop_order', '>', $this->stop_order)
                ->orderBy('stop_order')
                ->first();
        } else {
            // For return trip, next stop has lower order
            return $this->schedule->routes()
                ->where('stop_order', '<', $this->stop_order)
                ->orderBy('stop_order', 'desc')
                ->first();
        }
    }

    /**
     * Get previous stop in the route
     */
    public function getPreviousStop()
    {
        $schedule = $this->schedule;
        
        if ($schedule->isOnDepartureTrip()) {
            return $this->schedule->routes()
                ->where('stop_order', '<', $this->stop_order)
                ->orderBy('stop_order', 'desc')
                ->first();
        } else {
            // For return trip, previous stop has higher order
            return $this->schedule->routes()
                ->where('stop_order', '>', $this->stop_order)
                ->orderBy('stop_order')
                ->first();
        }
    }

    /**
     * Convert time string to minutes for calculations
     */
    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return ($hours * 60) + $minutes;
    }

    /**
     * Scope to get routes ordered by stop order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('stop_order');
    }

    /**
     * Scope to get routes for specific schedule
     */
    public function scopeForSchedule($query, int $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    /**
     * Scope to get routes within radius of coordinates
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radius = null)
    {
        return $query->get()->filter(function ($route) use ($lat, $lng, $radius) {
            $checkRadius = $radius ?? $route->coverage_radius;
            return $route->calculateDistance($lat, $lng, $route->latitude, $route->longitude) <= $checkRadius;
        });
    }
}
