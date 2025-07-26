<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BusLocation extends Model
{
    protected $fillable = [
        'bus_id',
        'device_token',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'reputation_weight',
        'is_validated'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'speed' => 'float',
        'reputation_weight' => 'float',
        'is_validated' => 'boolean'
    ];
    
    /**
     * Get the device token associated with this location
     */
    public function deviceToken()
    {
        return $this->belongsTo(DeviceToken::class, 'device_token', 'token_hash');
    }
    
    /**
     * Validate GPS coordinates are within reasonable bounds for Bangladesh
     */
    public function isValidCoordinates(): bool
    {
        // Bangladesh approximate bounds
        $minLat = 20.670883;
        $maxLat = 26.446526;
        $minLng = 88.084422;
        $maxLng = 92.672721;
        
        return $this->latitude >= $minLat && $this->latitude <= $maxLat &&
               $this->longitude >= $minLng && $this->longitude <= $maxLng;
    }
    
    /**
     * Validate speed is within reasonable limits (max 80 km/h for buses)
     */
    public function isValidSpeed(): bool
    {
        if ($this->speed === null) {
            return true; // Speed is optional
        }
        
        // Convert km/h to m/s: 80 km/h = 22.22 m/s
        return $this->speed >= 0 && $this->speed <= 22.22;
    }
    
    /**
     * Calculate distance to another location in meters
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
    
    /**
     * Check if this location is within a bus route's coverage area
     */
    public function isWithinRouteRadius(BusRoute $route): bool
    {
        return $this->distanceTo($route->latitude, $route->longitude) <= $route->coverage_radius;
    }
    
    /**
     * Scope to get locations for a specific bus
     */
    public function scopeForBus(Builder $query, string $busId): Builder
    {
        return $query->where('bus_id', $busId);
    }
    
    /**
     * Scope to get validated locations only
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }
    
    /**
     * Scope to get recent locations (within last hour)
     */
    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
    
    /**
     * Scope to get locations with high reputation weight
     */
    public function scopeTrusted(Builder $query, float $minWeight = 0.7): Builder
    {
        return $query->where('reputation_weight', '>=', $minWeight);
    }
    
    /**
     * Scope to get locations ordered by timestamp (newest first)
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
