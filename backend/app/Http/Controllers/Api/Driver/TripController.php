<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Schedule;
use App\Models\Location;
use App\Events\BusTripEnded;
use Carbon\Carbon;

class TripController extends Controller
{
    /**
     * Start a new trip
     */
    public function start(Request $request)
    {
        $request->validate([
            'schedule_id' => 'nullable|exists:schedules,id',
            'bus_id' => 'required|exists:buses,id',
            'route_id' => 'required|exists:routes,id',
        ]);

        // Check if driver has an ongoing trip
        $driverTrip = Trip::where('driver_id', $request->user()->id)
            ->where('status', 'ongoing')
            ->first();

        if ($driverTrip) {
            return response()->json([
                'message' => 'You already have an ongoing trip',
                'existing_trip_id' => $driverTrip->id
            ], 409); // Conflict
        }

        // Check if bus already has ongoing trip
        $busTrip = Trip::where('bus_id', $request->bus_id)
            ->where('status', 'ongoing')
            ->first();

        if ($busTrip) {
            return response()->json([
                'message' => 'This bus already has an ongoing trip',
                'existing_trip_id' => $busTrip->id
            ], 409); // Conflict
        }

        // Get schedule if provided
        $schedule = null;
        if ($request->schedule_id) {
            $schedule = Schedule::find($request->schedule_id);
        }

        $trip = Trip::create([
            'bus_id' => $request->bus_id,
            'route_id' => $request->route_id,
            'driver_id' => $request->user()->id,
            'schedule_id' => $request->schedule_id,
            'trip_date' => Carbon::today(),
            'status' => 'ongoing',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Trip started successfully',
            'trip' => $trip->load(['bus', 'route', 'route.stops']),
        ], 201);
    }

    /**
     * End current trip
     */
    public function end(Request $request, Trip $trip)
    {
        // Validate trip belongs to driver
        if ($trip->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($trip->status !== 'ongoing') {
            return response()->json(['message' => 'Trip is not ongoing'], 400);
        }

        $trip->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        // Remove the location row — bus is no longer active
        Location::where('trip_id', $trip->id)
                ->where('bus_id', $trip->bus_id)
                ->delete();

        // Notify student apps in real-time so the bus disappears immediately
        broadcast(new BusTripEnded($trip->bus_id, $trip->id));

        return response()->json([
            'message' => 'Trip ended successfully',
            'trip' => $trip,
        ]);
    }

    /**
     * Get current driver's active trip
     */
    public function current(Request $request)
    {
        $trip = Trip::where('driver_id', $request->user()->id)
            ->where('status', 'ongoing')
            ->with(['bus', 'route', 'route.stops', 'schedule'])
            ->first();

        if (!$trip) {
            return response()->json(['message' => 'No active trip'], 404);
        }

        return response()->json($trip);
    }

    /**
     * Get driver's trip history
     */
    public function history(Request $request)
    {
        $trips = Trip::where('driver_id', $request->user()->id)
            ->with(['bus', 'route'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($trips);
    }
}
