<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulePeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    // Note: schedules relationship removed as schedules table doesn't have schedule_period_id column
    // If you need this relationship, add schedule_period_id to the schedules table
    // public function schedules(): HasMany
    // {
    //     return $this->hasMany(Schedule::class);
    // }

    /**
     * Scope for active periods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the current schedule period
     */
    public static function getCurrentPeriod()
    {
        return self::active()
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->first();
    }
}
