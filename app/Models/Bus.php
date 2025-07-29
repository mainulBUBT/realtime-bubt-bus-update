<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bus extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id',
        'name',
        'capacity',
        'vehicle_number',
        'model',
        'year',
        'status',
        'is_active',
        'maintenance_notes',
        'driver_name',
        'driver_phone',
        'last_maintenance_date',
        'next_maintenance_date'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'capacity' => 'integer',
        'year' => 'integer'
    ];

    /**
     * Get the schedules for this bus
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(BusSchedule::class, 'bus_id', 'bus_id');
    }

    /**
     * Get the current position for this bus
     */
    public function currentPosition(): HasOne
    {
        return $this->hasOne(BusCurrentPosition::class, 'bus_id', 'bus_id');
    }

    /**
     * Get tracking sessions for this bus
     */
    public function trackingSessions(): HasMany
    {
        return $this->hasMany(UserTrackingSession::class, 'bus_id', 'bus_id');
    }

    /**
     * Get active schedules for this bus
     */
    public function activeSchedules(): HasMany
    {
        return $this->schedules()->where('is_active', true);
    }

    /**
     * Check if bus is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Check if bus is in maintenance
     */
    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Check if bus needs maintenance
     */
    public function needsMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date->lt(now());
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-warning',
            'maintenance' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            default => 'Unknown'
        };
    }

    /**
     * Scope to get active buses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope to get buses needing maintenance
     */
    public function scopeNeedingMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<=', now());
    }

    /**
     * Get the bus name or fallback to bus_id
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->bus_id;
    }
}