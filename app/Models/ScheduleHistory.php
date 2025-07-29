<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleHistory extends Model
{
    protected $fillable = [
        'schedule_id',
        'action',
        'old_data',
        'new_data',
        'changed_by',
        'notes'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array'
    ];

    /**
     * Get the schedule this history belongs to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BusSchedule::class, 'schedule_id');
    }

    /**
     * Get action display name
     */
    public function getActionDisplayAttribute(): string
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'activated' => 'Activated',
            'deactivated' => 'Deactivated',
            'route_added' => 'Route Added',
            'route_removed' => 'Route Removed',
            'route_updated' => 'Route Updated',
            default => ucfirst($this->action)
        };
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'bi-plus-circle',
            'updated' => 'bi-pencil',
            'deleted' => 'bi-trash',
            'activated' => 'bi-play-circle',
            'deactivated' => 'bi-pause-circle',
            'route_added' => 'bi-geo-alt-fill',
            'route_removed' => 'bi-geo-alt',
            'route_updated' => 'bi-geo-alt',
            default => 'bi-clock-history'
        };
    }

    /**
     * Get action badge class
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match($this->action) {
            'created' => 'bg-success',
            'updated' => 'bg-primary',
            'deleted' => 'bg-danger',
            'activated' => 'bg-success',
            'deactivated' => 'bg-warning',
            'route_added' => 'bg-info',
            'route_removed' => 'bg-secondary',
            'route_updated' => 'bg-primary',
            default => 'bg-secondary'
        };
    }

    /**
     * Create history record
     */
    public static function createRecord(int $scheduleId, string $action, array $oldData = null, array $newData = null, string $changedBy = null, string $notes = null): self
    {
        return self::create([
            'schedule_id' => $scheduleId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'changed_by' => $changedBy ?: auth('admin')->user()?->name,
            'notes' => $notes
        ]);
    }

    /**
     * Get changes summary
     */
    public function getChangesSummary(): array
    {
        if (!$this->old_data || !$this->new_data) {
            return [];
        }

        $changes = [];
        $oldData = $this->old_data;
        $newData = $this->new_data;

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }
}
