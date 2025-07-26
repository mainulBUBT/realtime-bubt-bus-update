<?php

namespace App\Services;

use App\Models\BusSchedule;
use App\Models\BusRoute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Bus Schedule Service
 * Handles round-trip schedule management and active bus determination
 */
class BusScheduleService
{
    // Trip direction constants
    public const DIRECTION_DEPARTURE = 'departure'; // Campus to City (morning)
    public const DIRECTION_RETURN = 'return';       // City to Campus (evening)
    
    // Schedule validation constants
    private const SCHEDULE_BUFFER_MINUTES = 15; // Allow GPS data 15 minutes before/after schedule
    private const CACHE_TTL_MINUTES = 5;        // Cache active schedules for 5 minutes
    
    /**
     * Determine if a bus is currently active based on schedule
     *
     * @param string $busId Bus identifier
     * @param Carbon|null $currentTime Current time (defaults to now)
     * @return array Active status with details
     */
    public function isBusActive(string $busId, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? now();
        $cacheKey = "bus_active_{$busId}_{$currentTime->format('Y-m-d_H-i')}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($busId, $currentTime) {
            $schedules = $this->getSchedulesForBus($busId);
            
            if ($schedules->isEmpty()) {
                return [
                    'is_active' => false,
                    'reason' => 'no_schedules',
                    'message' => 'No schedules found for this bus',
                    'next_departure' => null
                ];
            }

            // Check each schedule for current day
            $dayOfWeek = strtolower($currentTime->format('l'));
            
            foreach ($schedules as $schedule) {
                $activeCheck = $this->isScheduleActiveNow($schedule, $currentTime, $dayOfWeek);
                
                if ($activeCheck['is_active']) {
                    return $activeCheck;
                }
            }

            // If no active schedule, find next departure
            $nextDeparture = $this->getNextDeparture($schedules, $currentTime);
            
            return [
                'is_active' => false,
                'reason' => 'outside_schedule',
                'message' => 'Bus is not currently scheduled to run',
                'next_departure' => $nextDeparture
            ];
        });
    }

    /**
     * Get current trip direction for an active bus
     *
     * @param string $busId Bus identifier
     * @param Carbon|null $currentTime Current time
     * @return array Trip direction details
     */
    public function getCurrentTripDirection(string $busId, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? now();
        $activeStatus = $this->isBusActive($busId, $currentTime);
        
        if (!$activeStatus['is_active']) {
            return [
                'direction' => null,
                'trip_type' => null,
                'message' => 'Bus is not currently active',
                'route_stops' => []
            ];
        }

        $schedule = $activeStatus['schedule'];
        $currentTimeOnly = $currentTime->format('H:i:s');
        
        // Determine direction based on time
        $departureTime = Carbon::createFromFormat('H:i:s', $schedule->departure_time)->format('H:i:s');
        $returnTime = Carbon::createFromFormat('H:i:s', $schedule->return_time)->format('H:i:s');
        
        if ($currentTimeOnly >= $departureTime && $currentTimeOnly < $returnTime) {
            $direction = self::DIRECTION_DEPARTURE;
            $tripType = 'campus_to_city';
        } else {
            $direction = self::DIRECTION_RETURN;
            $tripType = 'city_to_campus';
        }

        // Get route stops for this direction
        $routeStops = $this->getRouteStopsForDirection($schedule->id, $direction);

        return [
            'direction' => $direction,
            'trip_type' => $tripType,
            'schedule_id' => $schedule->id,
            'route_stops' => $routeStops,
            'departure_time' => $schedule->departure_time,
            'return_time' => $schedule->return_time,
            'estimated_duration' => $this->calculateTripDuration($routeStops)
        ];
    }

    /**
     * Validate if GPS data should be accepted for a bus at current time
     *
     * @param string $busId Bus identifier
     * @param Carbon|null $submissionTime GPS data submission time
     * @return array Validation result
     */
    public function validateGPSDataTiming(string $busId, ?Carbon $submissionTime = null): array
    {
        $submissionTime = $submissionTime ?? now();
        $activeStatus = $this->isBusActive($busId, $submissionTime);
        
        if (!$activeStatus['is_active']) {
            return [
                'valid' => false,
                'reason' => 'bus_not_active',
                'message' => 'GPS data rejected: Bus is not currently scheduled to run',
                'next_valid_time' => $activeStatus['next_departure']
            ];
        }

        // Additional validation: check if within buffer time
        $schedule = $activeStatus['schedule'];
        $bufferStart = Carbon::createFromFormat('H:i:s', $schedule->departure_time)
            ->subMinutes(self::SCHEDULE_BUFFER_MINUTES);
        $bufferEnd = Carbon::createFromFormat('H:i:s', $schedule->return_time)
            ->addMinutes(self::SCHEDULE_BUFFER_MINUTES);

        $currentTimeOnly = $submissionTime->format('H:i:s');
        
        if ($currentTimeOnly < $bufferStart->format('H:i:s') || 
            $currentTimeOnly > $bufferEnd->format('H:i:s')) {
            return [
                'valid' => false,
                'reason' => 'outside_buffer',
                'message' => 'GPS data rejected: Outside acceptable time buffer',
                'buffer_start' => $bufferStart->format('H:i:s'),
                'buffer_end' => $bufferEnd->format('H:i:s')
            ];
        }

        return [
            'valid' => true,
            'message' => 'GPS data timing is valid',
            'trip_direction' => $this->getCurrentTripDirection($busId, $submissionTime)
        ];
    }

    /**
     * Handle schedule transition between departure and return trips
     *
     * @param string $busId Bus identifier
     * @param Carbon|null $currentTime Current time
     * @return array Transition details
     */
    public function handleScheduleTransition(string $busId, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? now();
        $tripDirection = $this->getCurrentTripDirection($busId, $currentTime);
        
        if (!$tripDirection['direction']) {
            return [
                'in_transition' => false,
                'message' => 'Bus is not active'
            ];
        }

        $schedule = BusSchedule::find($tripDirection['schedule_id']);
        $currentTimeOnly = $currentTime->format('H:i:s');
        
        // Check if we're near transition times
        $departureTime = Carbon::createFromFormat('H:i:s', $schedule->departure_time);
        $returnTime = Carbon::createFromFormat('H:i:s', $schedule->return_time);
        
        $transitionWindow = 30; // 30 minutes transition window
        
        // Check if approaching return time (departure to return transition)
        $returnTransitionStart = $returnTime->copy()->subMinutes($transitionWindow);
        if ($tripDirection['direction'] === self::DIRECTION_DEPARTURE && 
            $currentTimeOnly >= $returnTransitionStart->format('H:i:s') &&
            $currentTimeOnly < $returnTime->format('H:i:s')) {
            
            return [
                'in_transition' => true,
                'transition_type' => 'departure_to_return',
                'from_direction' => self::DIRECTION_DEPARTURE,
                'to_direction' => self::DIRECTION_RETURN,
                'transition_time' => $returnTime->format('H:i:s'),
                'minutes_until_transition' => $returnTime->diffInMinutes($currentTime),
                'message' => 'Approaching return trip transition'
            ];
        }

        // Check if completing return trip (end of service)
        $serviceEndTime = $returnTime->copy()->addHours(2); // Assume 2-hour return trip
        $serviceEndTransition = $serviceEndTime->copy()->subMinutes($transitionWindow);
        
        if ($tripDirection['direction'] === self::DIRECTION_RETURN && 
            $currentTimeOnly >= $serviceEndTransition->format('H:i:s')) {
            
            return [
                'in_transition' => true,
                'transition_type' => 'service_ending',
                'from_direction' => self::DIRECTION_RETURN,
                'to_direction' => null,
                'service_end_time' => $serviceEndTime->format('H:i:s'),
                'minutes_until_end' => $serviceEndTime->diffInMinutes($currentTime),
                'message' => 'Service ending soon'
            ];
        }

        return [
            'in_transition' => false,
            'current_direction' => $tripDirection['direction'],
            'message' => 'Normal operation, no transition'
        ];
    }

    /**
     * Get route stops with reversal logic for return trips
     *
     * @param int $scheduleId Schedule ID
     * @param string $direction Trip direction
     * @return array Route stops in correct order
     */
    public function getRouteStopsForDirection(int $scheduleId, string $direction): array
    {
        $cacheKey = "route_stops_{$scheduleId}_{$direction}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($scheduleId, $direction) {
            $baseRoute = BusRoute::where('schedule_id', $scheduleId)
                ->orderBy('stop_order')
                ->get();

            if ($baseRoute->isEmpty()) {
                return [];
            }

            $stops = $baseRoute->map(function ($stop) {
                return [
                    'id' => $stop->id,
                    'stop_name' => $stop->stop_name,
                    'stop_order' => $stop->stop_order,
                    'latitude' => $stop->latitude,
                    'longitude' => $stop->longitude,
                    'coverage_radius' => $stop->coverage_radius,
                    'estimated_time' => $stop->estimated_time
                ];
            })->toArray();

            // Reverse route for return trips
            if ($direction === self::DIRECTION_RETURN) {
                $stops = array_reverse($stops);
                
                // Update stop order for reversed route
                foreach ($stops as $index => &$stop) {
                    $stop['stop_order'] = $index + 1;
                    $stop['direction'] = 'return';
                }
            } else {
                foreach ($stops as &$stop) {
                    $stop['direction'] = 'departure';
                }
            }

            return $stops;
        });
    }

    /**
     * Get all active buses for current time
     *
     * @param Carbon|null $currentTime Current time
     * @return array List of active buses with details
     */
    public function getActiveBuses(?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? now();
        $cacheKey = "active_buses_{$currentTime->format('Y-m-d_H-i')}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($currentTime) {
            $allSchedules = BusSchedule::where('is_active', true)->get();
            $activeBuses = [];

            foreach ($allSchedules as $schedule) {
                $activeStatus = $this->isBusActive($schedule->bus_id, $currentTime);
                
                if ($activeStatus['is_active']) {
                    $tripDirection = $this->getCurrentTripDirection($schedule->bus_id, $currentTime);
                    
                    $activeBuses[] = [
                        'bus_id' => $schedule->bus_id,
                        'route_name' => $schedule->route_name,
                        'schedule_id' => $schedule->id,
                        'trip_direction' => $tripDirection,
                        'active_status' => $activeStatus,
                        'departure_time' => $schedule->departure_time,
                        'return_time' => $schedule->return_time
                    ];
                }
            }

            return $activeBuses;
        });
    }

    /**
     * Get schedule statistics for monitoring
     *
     * @return array Schedule statistics
     */
    public function getScheduleStatistics(): array
    {
        $currentTime = now();
        
        return [
            'total_schedules' => BusSchedule::count(),
            'active_schedules' => BusSchedule::where('is_active', true)->count(),
            'currently_active_buses' => count($this->getActiveBuses($currentTime)),
            'schedules_today' => BusSchedule::where('is_active', true)
                ->whereJsonContains('days_of_week', strtolower($currentTime->format('l')))
                ->count(),
            'next_departures' => $this->getUpcomingDepartures(5),
            'schedule_adherence' => $this->calculateScheduleAdherence()
        ];
    }

    /**
     * Private helper methods
     */

    /**
     * Get schedules for a specific bus
     */
    private function getSchedulesForBus(string $busId)
    {
        return BusSchedule::where('bus_id', $busId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if a specific schedule is active now
     */
    private function isScheduleActiveNow(BusSchedule $schedule, Carbon $currentTime, string $dayOfWeek): array
    {
        // Check if schedule runs on current day
        $daysOfWeek = $schedule->days_of_week ?? [];
        if (!in_array($dayOfWeek, $daysOfWeek)) {
            return [
                'is_active' => false,
                'reason' => 'not_scheduled_today',
                'scheduled_days' => $daysOfWeek
            ];
        }

        $currentTimeOnly = $currentTime->format('H:i:s');
        $departureTime = Carbon::createFromFormat('H:i:s', $schedule->departure_time)->format('H:i:s');
        $returnTime = Carbon::createFromFormat('H:i:s', $schedule->return_time)->format('H:i:s');
        
        // Add buffer time for GPS data collection
        $bufferStart = Carbon::createFromFormat('H:i:s', $schedule->departure_time)
            ->subMinutes(self::SCHEDULE_BUFFER_MINUTES)->format('H:i:s');
        $bufferEnd = Carbon::createFromFormat('H:i:s', $schedule->return_time)
            ->addMinutes(self::SCHEDULE_BUFFER_MINUTES)->format('H:i:s');

        if ($currentTimeOnly >= $bufferStart && $currentTimeOnly <= $bufferEnd) {
            return [
                'is_active' => true,
                'reason' => 'within_schedule',
                'schedule' => $schedule,
                'departure_time' => $departureTime,
                'return_time' => $returnTime,
                'buffer_start' => $bufferStart,
                'buffer_end' => $bufferEnd
            ];
        }

        return [
            'is_active' => false,
            'reason' => 'outside_time_window',
            'current_time' => $currentTimeOnly,
            'schedule_window' => "{$bufferStart} - {$bufferEnd}"
        ];
    }

    /**
     * Get next departure time for inactive bus
     */
    private function getNextDeparture($schedules, Carbon $currentTime): ?array
    {
        $nextDepartures = [];
        
        foreach ($schedules as $schedule) {
            $daysOfWeek = $schedule->days_of_week ?? [];
            
            // Check today and next 7 days
            for ($i = 0; $i < 7; $i++) {
                $checkDate = $currentTime->copy()->addDays($i);
                $checkDay = strtolower($checkDate->format('l'));
                
                if (in_array($checkDay, $daysOfWeek)) {
                    $departureDateTime = $checkDate->copy()->setTimeFromTimeString($schedule->departure_time);
                    
                    if ($departureDateTime > $currentTime) {
                        $nextDepartures[] = [
                            'bus_id' => $schedule->bus_id,
                            'route_name' => $schedule->route_name,
                            'departure_time' => $departureDateTime,
                            'days_until' => $i
                        ];
                    }
                }
            }
        }

        if (empty($nextDepartures)) {
            return null;
        }

        // Sort by departure time and return the earliest
        usort($nextDepartures, function ($a, $b) {
            return $a['departure_time']->compare($b['departure_time']);
        });

        return $nextDepartures[0];
    }

    /**
     * Calculate trip duration based on route stops
     */
    private function calculateTripDuration(array $routeStops): int
    {
        if (empty($routeStops)) {
            return 0;
        }

        // Estimate 5 minutes between stops + 2 minutes stop time
        $estimatedMinutes = (count($routeStops) - 1) * 7; // 5 min travel + 2 min stop
        
        return max(30, $estimatedMinutes); // Minimum 30 minutes
    }

    /**
     * Get upcoming departures
     */
    private function getUpcomingDepartures(int $limit = 5): array
    {
        $currentTime = now();
        $upcomingDepartures = [];
        
        $schedules = BusSchedule::where('is_active', true)->get();
        
        foreach ($schedules as $schedule) {
            $nextDeparture = $this->getNextDeparture(collect([$schedule]), $currentTime);
            if ($nextDeparture) {
                $upcomingDepartures[] = $nextDeparture;
            }
        }

        // Sort by departure time
        usort($upcomingDepartures, function ($a, $b) {
            return $a['departure_time']->compare($b['departure_time']);
        });

        return array_slice($upcomingDepartures, 0, $limit);
    }

    /**
     * Calculate schedule adherence (placeholder for future implementation)
     */
    private function calculateScheduleAdherence(): float
    {
        // This would analyze actual vs scheduled times
        // For now, return a placeholder value
        return 85.5; // 85.5% adherence
    }

    /**
     * Clear schedule caches
     */
    public function clearScheduleCache(): void
    {
        $patterns = [
            'bus_active_*',
            'active_buses_*',
            'route_stops_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Schedule caches cleared');
    }

    /**
     * Validate schedule configuration
     */
    public function validateScheduleConfiguration(array $scheduleData): array
    {
        $errors = [];

        // Validate required fields
        $required = ['bus_id', 'route_name', 'departure_time', 'return_time', 'days_of_week'];
        foreach ($required as $field) {
            if (!isset($scheduleData[$field]) || empty($scheduleData[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate time format
        if (isset($scheduleData['departure_time']) && isset($scheduleData['return_time'])) {
            try {
                $departure = Carbon::createFromFormat('H:i:s', $scheduleData['departure_time']);
                $return = Carbon::createFromFormat('H:i:s', $scheduleData['return_time']);
                
                if ($return <= $departure) {
                    $errors[] = 'Return time must be after departure time';
                }
            } catch (\Exception $e) {
                $errors[] = 'Invalid time format. Use H:i:s format (e.g., 07:00:00)';
            }
        }

        // Validate days of week
        if (isset($scheduleData['days_of_week'])) {
            $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $invalidDays = array_diff($scheduleData['days_of_week'], $validDays);
            
            if (!empty($invalidDays)) {
                $errors[] = 'Invalid days of week: ' . implode(', ', $invalidDays);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}