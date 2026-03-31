<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $schedules = Schedule::query()
            ->with(['bus', 'route', 'schedulePeriod'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('bus', function ($query) use ($search) {
                        $query->where('display_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('route', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('departure_time')
            ->paginate(12)
            ->withQueryString();

        return view('admin.schedules.index', ['schedules' => $schedules]);
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create(): View
    {
        $buses = Bus::query()->active()->orderBy('display_name')->get();
        $routes = Route::query()->active()->orderBy('name')->get();
        $periods = SchedulePeriod::query()->orderByDesc('start_date')->get();

        return view('admin.schedules.create', [
            'buses' => $buses,
            'routes' => $routes,
            'periods' => $periods,
        ]);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
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

        Schedule::create($validated);

        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule created successfully.']]);
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(Schedule $schedule): View
    {
        $buses = Bus::query()->active()->orderBy('display_name')->get();
        $routes = Route::query()->active()->orderBy('name')->get();
        $periods = SchedulePeriod::query()->orderByDesc('start_date')->get();

        return view('admin.schedules.edit', [
            'schedule' => $schedule,
            'buses' => $buses,
            'routes' => $routes,
            'periods' => $periods,
        ]);
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
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

        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule updated successfully.']]);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule deleted successfully.']]);
    }
}
