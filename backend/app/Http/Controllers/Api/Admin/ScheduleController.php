<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     * Returns schedules active today by default.
     */
    public function index(Request $request): JsonResponse
    {
        $direction = $request->string('direction')->toString();

        $schedules = Schedule::query()
            ->with(['bus', 'route', 'schedulePeriod'])
            ->activeToday();

        $schedules = $schedules
            ->when($direction !== '', function ($query) use ($direction) {
                $query->whereHas('route', function ($query) use ($direction) {
                    $query->where('direction', $direction);
                });
            })
            ->when($request->filled('route_id'), fn ($query) => $query->where('route_id', $request->integer('route_id')))
            ->orderBy('departure_time')
            ->get();

        return response()->json($schedules);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
            'bus_id' => 'required|exists:buses,id',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'is_active' => 'boolean',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $periodId = $request->integer('schedule_period_id');
            $route = Route::query()
                ->select(['id', 'schedule_period_id'])
                ->find($request->integer('route_id'));

            if ($route?->schedule_period_id !== null && (int) $route->schedule_period_id !== $periodId) {
                $validator->errors()->add('schedule_period_id', 'Selected schedule period must match the route period.');
            }

            $effectiveDate = $request->date('effective_date');
            if ($effectiveDate !== null) {
                $period = SchedulePeriod::query()
                    ->select(['id', 'start_date', 'end_date'])
                    ->find($periodId);

                if ($period !== null && ($effectiveDate->lt($period->start_date) || $effectiveDate->gt($period->end_date))) {
                    $validator->errors()->add('effective_date', 'Effective date must fall within the selected schedule period.');
                }
            }

            $hasConflict = Schedule::query()
                ->conflicting(
                    $request->integer('bus_id'),
                    $periodId,
                    $request->string('departure_time')->toString(),
                    $request->array('weekdays')
                )
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add('departure_time', 'This bus already has a schedule at that time for at least one of the selected weekdays in this period.');
            }
        });

        $validated = $validator->validate();
        $validated['weekdays'] = array_values(array_unique($validated['weekdays']));
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $schedule = Schedule::create($validated);

        return response()->json($schedule->load(['bus', 'route', 'schedulePeriod']), 201);
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule): JsonResponse
    {
        return response()->json($schedule->load(['bus', 'route', 'schedulePeriod', 'trips']));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
            'bus_id' => 'required|exists:buses,id',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'is_active' => 'boolean',
        ]);

        $validator->after(function ($validator) use ($request, $schedule) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $periodId = $request->integer('schedule_period_id');
            $route = Route::query()
                ->select(['id', 'schedule_period_id'])
                ->find($request->integer('route_id'));

            if ($route?->schedule_period_id !== null && (int) $route->schedule_period_id !== $periodId) {
                $validator->errors()->add('schedule_period_id', 'Selected schedule period must match the route period.');
            }

            $effectiveDate = $request->date('effective_date');
            if ($effectiveDate !== null) {
                $period = SchedulePeriod::query()
                    ->select(['id', 'start_date', 'end_date'])
                    ->find($periodId);

                if ($period !== null && ($effectiveDate->lt($period->start_date) || $effectiveDate->gt($period->end_date))) {
                    $validator->errors()->add('effective_date', 'Effective date must fall within the selected schedule period.');
                }
            }

            $hasConflict = Schedule::query()
                ->conflicting(
                    $request->integer('bus_id'),
                    $periodId,
                    $request->string('departure_time')->toString(),
                    $request->array('weekdays'),
                    $schedule->getKey()
                )
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add('departure_time', 'This bus already has a schedule at that time for at least one of the selected weekdays in this period.');
            }
        });

        $validated = $validator->validate();
        $validated['weekdays'] = array_values(array_unique($validated['weekdays']));
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $schedule->update($validated);

        return response()->json($schedule->load(['bus', 'route', 'schedulePeriod']));
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted successfully']);
    }
}
