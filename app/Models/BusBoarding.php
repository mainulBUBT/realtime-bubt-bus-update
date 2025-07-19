<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusBoarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bus_id',
        'trip_id',
        'boarding_stop_id',
        'destination_stop_id',
        'boarded_at',
        'alighted_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'boarded_at' => 'datetime',
        'alighted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function boardingStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'boarding_stop_id');
    }

    public function destinationStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'destination_stop_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'boarded']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('boarded_at', today());
    }
}