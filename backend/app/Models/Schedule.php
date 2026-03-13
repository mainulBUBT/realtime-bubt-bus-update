<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
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
        'departure_time',
        'weekdays',
        'effective_date',
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
            'weekdays' => 'array',
            'is_active' => 'boolean',
            'effective_date' => 'date',
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

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for schedules active today
     */
    public function scopeActiveToday($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', $today);
            })
            ->whereJsonContains('weekdays', [strtolower(now()->englishDayOfWeek)]);
    }

    /**
     * Get formatted weekdays for display.
     */
    public function getFormattedWeekdaysAttribute(): string
    {
        if (empty($this->weekdays) || !is_array($this->weekdays)) {
            return 'All Days';
        }

        // If all 7 days are selected, show "All Days"
        if (count($this->weekdays) === 7) {
            return 'All Days';
        }

        return implode(', ', array_map(fn($day) => ucfirst(substr($day, 0, 3)), $this->weekdays));
    }
}
