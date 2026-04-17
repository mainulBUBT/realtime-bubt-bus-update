<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bus_id',
        'route_id',
        'driver_id',
        'schedule_id',
        'trip_date',
        'status',
        'current_lat',
        'current_lng',
        'last_location_at',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'last_location_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderBy('recorded_at', 'desc');
    }

    /**
     * Scope for ongoing trips
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Scope for trips that are active today.
     */
    public function scopeActiveToday($query)
    {
        return $query->ongoing()
            ->whereDate('trip_date', today());
    }

    /**
     * Scope for ongoing trips that are stale:
     * - From a previous day, OR
     * - No location update for 2+ hours.
     */
    public function scopeStaleOngoing($query)
    {
        return $query->ongoing()
            ->where(function ($q) {
                $q->whereDate('trip_date', '<', today())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('last_location_at')
                            ->orWhere('last_location_at', '<', now()->subHours(2));
                    });
            });
    }

    /**
     * Get the latest location for this trip
     */
    public function latestLocation()
    {
        return $this->hasOne(Location::class)->latestOfMany();
    }
}
