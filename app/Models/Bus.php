<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class)->orderBy('order_index');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function currentLocation()
    {
        return $this->hasOne(Location::class)->latestOfMany('recorded_at');
    }

    public function todayTrips()
    {
        return $this->hasMany(Trip::class)->whereDate('trip_date', today());
    }
}