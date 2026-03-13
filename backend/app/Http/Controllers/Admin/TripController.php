<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Display a listing of trips.
     */
    public function index(Request $request)
    {
        $query = Trip::with(['bus', 'route', 'driver', 'schedule']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('bus', function ($q) use ($search) {
                    $q->where('display_name', 'like', "%{$search}%");
                })
                ->orWhereHas('route', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('driver', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('trip_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('trip_date', '<=', $request->input('date_to'));
        }

        $trips = $query->orderBy('trip_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.trips.index', compact('trips'));
    }

    /**
     * Show the form for creating a new trip.
     */
    public function create()
    {
        $buses = Bus::where('status', 'active')->get();
        $routes = Route::active()->get();
        $drivers = User::where('role', 'driver')->get();
        $schedules = Schedule::active()->get();

        return view('admin.trips.create', compact('buses', 'routes', 'drivers', 'schedules'));
    }

    /**
     * Store a newly created trip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
            'driver_id' => 'nullable|exists:users,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'trip_date' => 'required|date',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        Trip::create($validated);

        return redirect()->route('admin.trips.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Trip created successfully.']]);
    }

    /**
     * Display the specified trip.
     */
    public function show(Trip $trip)
    {
        $trip->load(['bus', 'route', 'driver', 'schedule', 'locations']);

        return view('admin.trips.show', compact('trip'));
    }

    /**
     * Remove the specified trip.
     */
    public function destroy(Trip $trip)
    {
        $trip->delete();
        return redirect()->route('admin.trips.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Trip deleted successfully.']]);
    }
}
