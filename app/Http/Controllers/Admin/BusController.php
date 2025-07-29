<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusSchedule;
use App\Models\BusCurrentPosition;
use App\Models\UserTrackingSession;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;


class BusController extends Controller
{
    /**
     * Display a listing of buses
     */
    public function index(): View
    {
        $buses = Bus::with(['schedules', 'currentPosition'])
            ->orderBy('bus_id')
            ->get()
            ->map(fn($bus) => (object) [
                'id' => $bus->id,
                'bus_id' => $bus->bus_id,
                'name' => $bus->name,
                'capacity' => $bus->capacity,
                'vehicle_number' => $bus->vehicle_number,
                'status' => $bus->status,
                'status_display' => $bus->status_display,
                'status_badge_class' => $bus->status_badge_class,
                'is_active' => $bus->is_active,
                'needs_maintenance' => $bus->needsMaintenance(),
                'total_schedules' => $bus->schedules->count(),
                'active_schedules' => $bus->schedules->where('is_active', true)->count(),
                'current_status' => $bus->currentPosition->status ?? 'no_data',
                'active_trackers' => $bus->currentPosition->active_trackers ?? 0,
                'last_updated' => $bus->currentPosition->last_updated ?? null,
                'confidence_level' => $bus->currentPosition->confidence_level ?? 0,
                'driver_name' => $bus->driver_name,
                'driver_phone' => $bus->driver_phone,
            ]);

        return view('admin.buses.index', compact('buses'));
    }

    /**
     * Show the form for creating a new bus
     */
    public function create(): View
    {
        return view('admin.buses.create');
    }

    /**
     * Store a newly created bus
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id' => 'required|string|max:10|unique:buses,bus_id',
            'name' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'status' => 'required|in:active,inactive,maintenance',
            'is_active' => 'boolean',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'maintenance_notes' => 'nullable|string|max:1000',
            'last_maintenance_date' => 'nullable|date|before_or_equal:today',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
        ]);

        Bus::create($validated);

        return redirect()->route('admin.buses.index')
            ->with('success', "Bus {$validated['bus_id']} created successfully.");
    }

    /**
     * Display the specified bus
     */
    public function show(string $busId): View
    {
        $bus = Bus::where('bus_id', $busId)
            ->with(['schedules', 'currentPosition', 'trackingSessions'])
            ->firstOrFail();

        $recentSessions = $bus->trackingSessions()
            ->where('started_at', '>=', now()->subHours(24))
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Add recent_sessions to the bus object for the view
        $bus->recent_sessions = $recentSessions;

        return view('admin.buses.show', compact('bus'));
    }

    /**
     * Show the form for editing the specified bus
     */
    public function edit(string $busId): View
    {
        $bus = Bus::where('bus_id', $busId)->firstOrFail();
        
        return view('admin.buses.edit', compact('bus'));
    }

    /**
     * Update the specified bus
     */
    public function update(Request $request, string $busId): RedirectResponse
    {
        $bus = Bus::where('bus_id', $busId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'status' => 'required|in:active,inactive,maintenance',
            'is_active' => 'boolean',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'maintenance_notes' => 'nullable|string|max:1000',
            'last_maintenance_date' => 'nullable|date|before_or_equal:today',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
        ]);

        $bus->update($validated);

        return redirect()->route('admin.buses.index')
            ->with('success', "Bus {$busId} updated successfully.");
    }

    /**
     * Remove the specified bus
     */
    public function destroy(string $busId): RedirectResponse
    {
        $bus = Bus::where('bus_id', $busId)->first();
        
        if (!$bus) {
            return redirect()->route('admin.buses.index')
                ->with('error', 'Bus not found.');
        }

        // Delete all related data
        BusSchedule::where('bus_id', $busId)->delete();
        BusCurrentPosition::where('bus_id', $busId)->delete();
        UserTrackingSession::where('bus_id', $busId)->delete();
        
        // Delete the bus itself
        $bus->delete();

        return redirect()->route('admin.buses.index')
            ->with('success', "Bus {$busId} and all related data deleted successfully.");
    }

    /**
     * Toggle bus status (active/inactive)
     */
    public function toggleStatus(string $busId): RedirectResponse
    {
        $bus = Bus::where('bus_id', $busId)->first();
        
        if (!$bus) {
            return redirect()->route('admin.buses.index')
                ->with('error', 'Bus not found.');
        }

        $newStatus = !$bus->is_active;
        $bus->update(['is_active' => $newStatus]);

        // Also update related schedules if they exist
        if ($bus->schedules()->exists()) {
            $bus->schedules()->update(['is_active' => $newStatus]);
        }

        $statusText = $newStatus ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.buses.index')
            ->with('success', "Bus {$busId} has been {$statusText}.");
    }
}