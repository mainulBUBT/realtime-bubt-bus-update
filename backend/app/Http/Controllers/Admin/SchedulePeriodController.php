<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchedulePeriod;
use Illuminate\Http\Request;

class SchedulePeriodController extends Controller
{
    /**
     * Display a listing of schedule periods.
     */
    public function index(Request $request)
    {
        $query = SchedulePeriod::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by status (active/inactive based on dates)
        if ($request->filled('status')) {
            $today = now()->toDateString();
            if ($request->input('status') === 'active') {
                $query->where('start_date', '<=', $today)
                      ->where('end_date', '>=', $today);
            } elseif ($request->input('status') === 'upcoming') {
                $query->where('start_date', '>', $today);
            } elseif ($request->input('status') === 'past') {
                $query->where('end_date', '<', $today);
            }
        }

        $periods = $query
            ->orderBy('start_date', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('admin.schedule-periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new schedule period.
     */
    public function create()
    {
        return view('admin.schedule-periods.create');
    }

    /**
     * Store a newly created schedule period.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // If setting this as active, deactivate others
        if (($validated['is_active'] ?? false) === true) {
            SchedulePeriod::where('is_active', true)->update(['is_active' => false]);
        }

        SchedulePeriod::create($validated);

        return redirect()->route('admin.schedule-periods.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule period created successfully.']]);
    }

    /**
     * Show the form for editing the specified schedule period.
     */
    public function edit($schedule_period)
    {
        $period = SchedulePeriod::findOrFail($schedule_period);
        return view('admin.schedule-periods.edit', compact('period'));
    }

    /**
     * Update the specified schedule period.
     */
    public function update(Request $request, $schedule_period)
    {
        $period = SchedulePeriod::findOrFail($schedule_period);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // If setting this as active, deactivate others
        if (($validated['is_active'] ?? false) === true && !$period->is_active) {
            SchedulePeriod::where('id', '!=', $period->id)->update(['is_active' => false]);
        }

        $period->update($validated);

        return redirect()->route('admin.schedule-periods.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule period updated successfully.']]);
    }

    /**
     * Remove the specified schedule period.
     */
    public function destroy(SchedulePeriod $schedule_period)
    {
        $schedule_period->delete();

        return redirect()
            ->route('admin.schedule-periods.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Schedule period deleted successfully.']]);
    }
}
