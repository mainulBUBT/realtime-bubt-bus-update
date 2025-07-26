<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BusSchedule extends Model
{
    protected $fillable = [
        'bus_id',
        'route_name',
        'departure_time',
        'return_time',
        'days_of_week',
        'is_active',
        'description'
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'departure_time' => 'datetime:H:i',
        'return_time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    /**
     * Get the routes associated with this schedule
     */
    public function routes()
    {
        return $this->hasMany(BusRoute::class, 'schedule_id');
    }

    /**
     * Check if the bus is currently active based on schedule
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        $currentDay = strtolower($now->format('l'));
        
        // Check if today is in the scheduled days
        if (!in_array($currentDay, $this->days_of_week)) {
            return false;
        }

        $currentTime = $now->format('H:i');
        $departureTime = $this->departure_time->format('H:i');
        $returnTime = $this->return_time->format('H:i');

        // Bus is active between departure and return times
        return $currentTime >= $departureTime && $currentTime <= $returnTime;
    }

    /**
     * Determine if bus is on departure trip (campus → city)
     */
    public function isOnDepartureTrip(): bool
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $departureTime = $this->departure_time->format('H:i');
        $returnTime = $this->return_time->format('H:i');

        // Calculate midpoint between departure and return
        $departureMinutes = $this->timeToMinutes($departureTime);
        $returnMinutes = $this->timeToMinutes($returnTime);
        $midpointMinutes = $departureMinutes + (($returnMinutes - $departureMinutes) / 2);
        $currentMinutes = $this->timeToMinutes($currentTime);

        return $currentMinutes <= $midpointMinutes;
    }

    /**
     * Determine if bus is on return trip (city → campus)
     */
    public function isOnReturnTrip(): bool
    {
        return $this->isCurrentlyActive() && !$this->isOnDepartureTrip();
    }

    /**
     * Get the current trip direction
     */
    public function getCurrentTripDirection(): string
    {
        if (!$this->isCurrentlyActive()) {
            return 'inactive';
        }

        return $this->isOnDepartureTrip() ? 'departure' : 'return';
    }

    /**
     * Get routes ordered for current trip direction
     */
    public function getOrderedRoutesForCurrentTrip()
    {
        $routes = $this->routes()->orderBy('stop_order')->get();
        
        if ($this->isOnReturnTrip()) {
            // Reverse the order for return trip
            return $routes->reverse()->values();
        }

        return $routes;
    }

    /**
     * Get estimated time for a specific stop based on current trip
     */
    public function getEstimatedTimeForStop(int $stopOrder): ?string
    {
        $route = $this->routes()->where('stop_order', $stopOrder)->first();
        
        if (!$route) {
            return null;
        }

        if ($this->isOnDepartureTrip()) {
            return $route->estimated_departure_time->format('H:i');
        } else {
            return $route->estimated_return_time->format('H:i');
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
     * Scope to get active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules for specific bus
     */
    public function scopeForBus($query, string $busId)
    {
        return $query->where('bus_id', $busId);
    }

    /**
     * Scope to get currently running buses
     */
    public function scopeCurrentlyRunning($query)
    {
        return $query->active()->get()->filter(function ($schedule) {
            return $schedule->isCurrentlyActive();
        });
    }
}
