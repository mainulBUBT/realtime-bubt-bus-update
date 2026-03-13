<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bus extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plate_number',
        'display_name',
        'code',
        'device_id',
        'capacity',
        'status',
    ];

    /**
     * The attributes that should be hidden in serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Relationships
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(Location::class)->latestOfMany();
    }

    /**
     * Scope for active buses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
