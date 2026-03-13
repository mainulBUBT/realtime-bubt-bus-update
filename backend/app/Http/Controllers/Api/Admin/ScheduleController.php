<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     * Returns schedules active today by default.
     */
    public function index(Request $request)
    {
        // Get today's active schedules by default
        $query = Schedule::with(['bus', 'route', 'schedulePeriod'])
            ->activeToday();

        // Filter by direction if specified
        if ($request->has('direction')) {
            $query->direction($request->direction);
        }

        // Filter by route
        if ($request->has('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        $schedules = $query->orderBy('departure_time')->get();

        return response()->json($schedules);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'direction' => 'required|in:up,down',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_from' => 'nullable|date|after_or_equal:today',
            'effective_to' => 'nullable|date|after:effective_from',
            'schedule_type' => 'nullable|string|in:regular,exam,semester,holiday',
            'bus_id' => 'nullable|exists:buses,id',
            'schedule_period_id' => 'nullable|exists:schedule_periods,id',
            'is_active' => 'boolean',
        ]);

        $schedule = Schedule::create($request->all());

        return response()->json($schedule->load(['bus', 'route']), 201);
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule)
    {
        return response()->json($schedule->load(['bus', 'route', 'schedulePeriod', 'trips']));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $request->validate([
            'route_id' => 'exists:routes,id',
            'direction' => 'in:up,down',
            'departure_time' => 'date_format:H:i',
            'weekdays' => 'array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'schedule_type' => 'nullable|string|in:regular,exam,semester,holiday',
            'bus_id' => 'nullable|exists:buses,id',
            'schedule_period_id' => 'nullable|exists:schedule_periods,id',
            'is_active' => 'boolean',
        ]);

        $schedule->update($request->all());

        return response()->json($schedule->load(['bus', 'route']));
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(['message' => 'Schedule deleted successfully']);
    }
}
