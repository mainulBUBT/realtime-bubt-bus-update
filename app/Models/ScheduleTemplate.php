<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'template_data',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Scope to get active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Create schedules from this template
     */
    public function createSchedules(array $busIds): array
    {
        $createdSchedules = [];
        
        foreach ($busIds as $busId) {
            $scheduleData = $this->template_data;
            $scheduleData['bus_id'] = $busId;
            
            $schedule = BusSchedule::create($scheduleData);
            
            // Create routes if they exist in template
            if (isset($this->template_data['routes'])) {
                foreach ($this->template_data['routes'] as $routeData) {
                    $schedule->routes()->create($routeData);
                }
            }
            
            $createdSchedules[] = $schedule;
        }
        
        return $createdSchedules;
    }

    /**
     * Get template usage count
     */
    public function getUsageCountAttribute(): int
    {
        // Count schedules that match this template's pattern
        return BusSchedule::where('route_name', $this->template_data['route_name'] ?? '')
            ->where('departure_time', $this->template_data['departure_time'] ?? '')
            ->where('return_time', $this->template_data['return_time'] ?? '')
            ->count();
    }
}
