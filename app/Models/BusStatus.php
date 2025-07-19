<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id',
        'trip_id',
        'current_capacity',
        'max_capacity',
        'status',
        'current_stop_id',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function currentStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'current_stop_id');
    }

    public function getCapacityPercentageAttribute(): int
    {
        return $this->max_capacity > 0 ? round(($this->current_capacity / $this->max_capacity) * 100) : 0;
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->max_capacity - $this->current_capacity);
    }

    public function isNearCapacity(): bool
    {
        return $this->getCapacityPercentageAttribute() >= 80;
    }
}