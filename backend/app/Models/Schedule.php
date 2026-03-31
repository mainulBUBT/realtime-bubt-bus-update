<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'schedule_period_id',
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

    public function schedulePeriod(): BelongsTo
    {
        return $this->belongsTo(SchedulePeriod::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope for schedules active today.
     */
    public function scopeActiveToday(Builder $query): void
    {
        $today = now()->toDateString();
        $day = strtolower(now()->englishDayOfWeek);

        $query->active()
            ->whereNotNull('schedule_period_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_date')
                    ->orWhereDate('effective_date', '<=', $today);
            })
            ->whereJsonContains('weekdays', $day)
            ->whereHas('schedulePeriod', function ($q) use ($today) {
                $q->currentOn($today);
            });
    }

    /**
     * Scope for same-bus same-time schedules that overlap on at least one weekday.
     */
    public function scopeConflicting(Builder $query, int $busId, int $schedulePeriodId, string $departureTime, array $weekdays, ?int $ignoreId = null): void
    {
        $weekdays = array_values(array_unique($weekdays));

        $query->where('bus_id', $busId)
            ->where('schedule_period_id', $schedulePeriodId)
            ->where('departure_time', $departureTime)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where(function ($q) use ($weekdays) {
                foreach ($weekdays as $weekday) {
                    $q->orWhereJsonContains('weekdays', $weekday);
                }
            });
    }

    /**
     * Get formatted weekdays for display.
     */
    public function getFormattedWeekdaysAttribute(): string
    {
        if (empty($this->weekdays) || !is_array($this->weekdays)) {
            return 'All Days';
        }

        if (count($this->weekdays) === 7) {
            return 'All Days';
        }

        return implode(', ', array_map(fn ($day) => ucfirst(substr($day, 0, 3)), $this->weekdays));
    }
}
