<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BusCurrentPosition extends Model
{
    protected $primaryKey = 'bus_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'bus_id',
        'latitude',
        'longitude',
        'confidence_level',
        'last_updated',
        'active_trackers',
        'trusted_trackers',
        'average_trust_score',
        'status',
        'last_known_location',
        'movement_consistency'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'confidence_level' => 'float',
        'last_updated' => 'datetime',
        'active_trackers' => 'integer',
        'trusted_trackers' => 'integer',
        'average_trust_score' => 'float',
        'last_known_location' => 'array',
        'movement_consistency' => 'float'
    ];
    
    /**
     * Get the bus associated with this position
     */
    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id', 'bus_id');
    }
    
    /**
     * Get the bus schedule associated with this position
     */
    public function busSchedule()
    {
        return $this->belongsTo(BusSchedule::class, 'bus_id', 'bus_id');
    }
    
    /**
     * Check if the bus is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->last_updated && 
               $this->last_updated->diffInMinutes(now()) <= 5;
    }
    
    /**
     * Check if the bus has reliable tracking data
     */
    public function hasReliableTracking(): bool
    {
        return $this->trusted_trackers >= 1 && 
               $this->confidence_level >= 0.6 &&
               $this->average_trust_score >= 0.5;
    }
    
    /**
     * Get formatted last seen information
     */
    public function getLastSeenAttribute(): ?string
    {
        if (!$this->last_updated) {
            return null;
        }
        
        $minutesAgo = $this->last_updated->diffInMinutes(now());
        
        if ($minutesAgo < 1) {
            return 'Just now';
        } elseif ($minutesAgo < 60) {
            return $minutesAgo . ' minutes ago';
        } else {
            return $this->last_updated->format('H:i');
        }
    }
    
    /**
     * Get status display text
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'no_data' => 'No tracking data',
            default => 'Unknown'
        };
    }
    
    /**
     * Scope to get active buses only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                    ->where('last_updated', '>=', now()->subMinutes(5));
    }
    
    /**
     * Scope to get buses with reliable tracking
     */
    public function scopeReliable(Builder $query): Builder
    {
        return $query->where('trusted_trackers', '>=', 1)
                    ->where('confidence_level', '>=', 0.6);
    }
    
    /**
     * Scope to get buses ordered by last update
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('last_updated', 'desc');
    }
}