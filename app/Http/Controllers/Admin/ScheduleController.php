<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusSchedule;
use App\Models\BusRoute;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleHistory;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules
     */
    public function index(): View
    {
        $schedules = BusSchedule::with(['routes'])
            ->orderBy('bus_id')
            ->orderBy('departure_time')
            ->paginate(15);

        return view('admin.schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new schedule
     */
    public function create(Request $request): View
    {
        $busId = $request->get('bus_id');
        
        return view('admin.schedules.create', compact('busId'));
    }

    /**
     * Store a newly created schedule
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id' => 'required|string|max:10',
            'route_name' => 'required|string|max:100',
            'departure_time' => 'required|date_format:H:i',
            'return_time' => 'required|date_format:H:i|after:departure_time',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500'
        ]);

        $schedule = BusSchedule::create($validated);

        // Create history record
        ScheduleHistory::createRecord(
            $schedule->id,
            'created',
            null,
            $schedule->toArray(),
            auth('admin')->user()?->name
        );

        return redirect()->route('admin.schedules.show', $schedule)
            ->with('success', "Schedule for bus {$validated['bus_id']} created successfully.");
    }

    /**
     * Display the specified schedule
     */
    public function show(BusSchedule $schedule): View
    {
        $schedule->load(['routes' => function($query) {
            $query->orderBy('stop_order');
        }]);

        return view('admin.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified schedule
     */
    public function edit(BusSchedule $schedule): View
    {
        return view('admin.schedules.edit', compact('schedule'));
    }

    /**
     * Update the specified schedule
     */
    public function update(Request $request, BusSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:100',
            'departure_time' => 'required|date_format:H:i',
            'return_time' => 'required|date_format:H:i|after:departure_time',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500'
        ]);

        $oldData = $schedule->toArray();
        $schedule->update($validated);

        // Create history record
        ScheduleHistory::createRecord(
            $schedule->id,
            'updated',
            $oldData,
            $schedule->fresh()->toArray(),
            auth('admin')->user()?->name
        );

        return redirect()->route('admin.schedules.show', $schedule)
            ->with('success', "Schedule updated successfully.");
    }

    /**
     * Remove the specified schedule
     */
    public function destroy(BusSchedule $schedule): RedirectResponse
    {
        $busId = $schedule->bus_id;
        
        // Create history record before deletion
        ScheduleHistory::createRecord(
            $schedule->id,
            'deleted',
            $schedule->toArray(),
            null,
            auth('admin')->user()?->name
        );
        
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('success', "Schedule for bus {$busId} deleted successfully.");
    }

    /**
     * Show route management interface
     */
    public function manageRoutes(BusSchedule $schedule): View
    {
        $schedule->load(['routes' => function($query) {
            $query->orderBy('stop_order');
        }]);

        // Predefined stops for BUBT bus system
        $predefinedStops = [
            ['name' => 'BUBT Campus', 'lat' => 23.7956, 'lng' => 90.3537],
            ['name' => 'Rainkhola', 'lat' => 23.7850, 'lng' => 90.3700],
            ['name' => 'Mirpur-1', 'lat' => 23.7956, 'lng' => 90.3537],
            ['name' => 'Shyamoli', 'lat' => 23.7693, 'lng' => 90.3563],
            ['name' => 'Asad Gate', 'lat' => 23.7550, 'lng' => 90.3850],
        ];

        return view('admin.schedules.routes', compact('schedule', 'predefinedStops'));
    }

    /**
     * Store a new route stop
     */
    public function storeRoute(Request $request, BusSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'stop_name' => 'required|string|max:100',
            'stop_order' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'coverage_radius' => 'required|integer|min:50|max:1000',
            'estimated_departure_time' => 'nullable|date_format:H:i',
            'estimated_return_time' => 'nullable|date_format:H:i'
        ]);

        // Check for duplicate stop order
        $existingRoute = $schedule->routes()->where('stop_order', $validated['stop_order'])->first();
        if ($existingRoute) {
            return back()->withErrors(['stop_order' => 'A stop with this order already exists.']);
        }

        $schedule->routes()->create($validated);

        return back()->with('success', "Stop '{$validated['stop_name']}' added successfully.");
    }

    /**
     * Remove a route stop
     */
    public function destroyRoute(BusSchedule $schedule, BusRoute $route): RedirectResponse
    {
        if ($route->schedule_id !== $schedule->id) {
            abort(404);
        }

        $stopName = $route->stop_name;
        $route->delete();

        return back()->with('success', "Stop '{$stopName}' removed successfully.");
    }

    /**
     * Check for schedule conflicts
     */
    public function checkConflicts(Request $request)
    {
        $busId = $request->get('bus_id');
        $departureTime = $request->get('departure_time');
        $returnTime = $request->get('return_time');
        $daysOfWeek = $request->get('days_of_week', []);
        $excludeId = $request->get('exclude_id');

        $query = BusSchedule::where('bus_id', $busId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflicts = $query->where(function($q) use ($departureTime, $returnTime) {
            $q->whereBetween('departure_time', [$departureTime, $returnTime])
              ->orWhereBetween('return_time', [$departureTime, $returnTime])
              ->orWhere(function($subQ) use ($departureTime, $returnTime) {
                  $subQ->where('departure_time', '<=', $departureTime)
                       ->where('return_time', '>=', $returnTime);
              });
        })->get()->filter(function($schedule) use ($daysOfWeek) {
            return !empty(array_intersect($schedule->days_of_week, $daysOfWeek));
        });

        return response()->json([
            'has_conflicts' => $conflicts->count() > 0,
            'conflicts' => $conflicts->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'departure_time' => $schedule->departure_time->format('H:i'),
                    'return_time' => $schedule->return_time->format('H:i'),
                    'days_of_week' => $schedule->days_of_week,
                    'route_name' => $schedule->route_name
                ];
            })
        ]);
    }

    /**
     * Show bulk schedule creation form
     */
    public function bulkCreate(): View
    {
        $buses = Bus::active()->orderBy('bus_id')->get();
        $templates = ScheduleTemplate::active()->orderBy('name')->get();
        
        return view('admin.schedules.bulk-create', compact('buses', 'templates'));
    }

    /**
     * Store bulk schedules
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_ids' => 'required|array|min:1',
            'bus_ids.*' => 'exists:buses,bus_id',
            'template_id' => 'nullable|exists:schedule_templates,id',
            'route_name' => 'required|string|max:100',
            'departure_time' => 'required|date_format:H:i',
            'return_time' => 'required|date_format:H:i|after:departure_time',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500'
        ]);

        $createdSchedules = [];
        
        foreach ($validated['bus_ids'] as $busId) {
            $scheduleData = $validated;
            $scheduleData['bus_id'] = $busId;
            unset($scheduleData['bus_ids'], $scheduleData['template_id']);
            
            $schedule = BusSchedule::create($scheduleData);
            
            // Create history record
            ScheduleHistory::createRecord(
                $schedule->id,
                'created',
                null,
                $schedule->toArray(),
                auth('admin')->user()?->name,
                'Created via bulk operation'
            );
            
            $createdSchedules[] = $schedule;
        }

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Successfully created ' . count($createdSchedules) . ' schedules.');
    }

    /**
     * Show schedule templates
     */
    public function templates(): View
    {
        $templates = ScheduleTemplate::with(['usage_count'])
            ->orderBy('name')
            ->paginate(10);
            
        return view('admin.schedules.templates', compact('templates'));
    }

    /**
     * Store schedule template
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:schedule_templates,name',
            'description' => 'nullable|string|max:500',
            'route_name' => 'required|string|max:100',
            'departure_time' => 'required|date_format:H:i',
            'return_time' => 'required|date_format:H:i|after:departure_time',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500'
        ]);

        $templateData = $validated;
        unset($templateData['name'], $templateData['description']);

        ScheduleTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'template_data' => $templateData,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth('admin')->user()?->name
        ]);

        return redirect()->route('admin.schedules.templates')
            ->with('success', 'Schedule template created successfully.');
    }

    /**
     * Show schedule history
     */
    public function history(BusSchedule $schedule): View
    {
        $history = $schedule->history()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.schedules.history', compact('schedule', 'history'));
    }
}