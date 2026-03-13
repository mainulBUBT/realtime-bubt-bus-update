<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['bus', 'route']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('bus', function ($q) use ($search) {
                    $q->where('display_name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhereHas('route', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $schedules = $query->orderBy('departure_time')->get();
        return view('admin.schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create()
    {
        $buses = Bus::active()->get();
        $routes = Route::active()->get();
        return view('admin.schedules.create', compact('buses', 'routes'));
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        Schedule::create([
            'bus_id' => $validated['bus_id'],
            'route_id' => $validated['route_id'],
            'departure_time' => $validated['departure_time'],
            'weekdays' => $validated['weekdays'],
            'effective_date' => $validated['effective_date'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule created successfully.']]);
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(Schedule $schedule)
    {
        $buses = Bus::active()->get();
        $routes = Route::active()->get();
        return view('admin.schedules.edit', compact('schedule', 'buses', 'routes'));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date_format:H:i',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'effective_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $schedule->update([
            'bus_id' => $validated['bus_id'],
            'route_id' => $validated['route_id'],
            'departure_time' => $validated['departure_time'],
            'weekdays' => $validated['weekdays'],
            'effective_date' => $validated['effective_date'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule updated successfully.']]);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule deleted successfully.']]);
    }
}
