<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;

class BusController extends Controller
{
    /**
     * Display a listing of buses.
     */
    public function index()
    {
        $buses = Bus::withCount('trips')->get();
        return response()->json($buses);
    }

    /**
     * Store a newly created bus.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string|unique:buses',
            'device_id' => 'nullable|string|unique:buses',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $bus = Bus::create($request->all());
        return response()->json($bus, 201);
    }

    /**
     * Display the specified bus.
     */
    public function show(Bus $bus)
    {
        return response()->json($bus->load(['trips' => function ($query) {
            $query->with(['route', 'driver:id,name,role'])->latest()->limit(10);
        }]));
    }

    /**
     * Update the specified bus.
     */
    public function update(Request $request, Bus $bus)
    {
        $request->validate([
            'plate_number' => 'required|string|unique:buses,plate_number,' . $bus->id,
            'device_id' => 'nullable|string|unique:buses,device_id,' . $bus->id,
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $bus->update($request->all());
        return response()->json($bus);
    }

    /**
     * Remove the specified bus.
     */
    public function destroy(Bus $bus)
    {
        $bus->delete();
        return response()->json(['message' => 'Bus deleted successfully']);
    }
}
