<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;

class BusController extends Controller
{
    /**
     * Display a listing of buses.
     */
    public function index(Request $request)
    {
        $query = Bus::withCount('trips');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $buses = $query->orderBy('display_name')->get();
        return view('admin.buses.index', compact('buses'));
    }

    /**
     * Show the form for creating a new bus.
     */
    public function create()
    {
        return view('admin.buses.create');
    }

    /**
     * Store a newly created bus.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|unique:buses',
            'display_name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:buses',
            'device_id' => 'nullable|string|unique:buses',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        Bus::create($validated);

        return redirect()->route('admin.buses.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Bus created successfully!']]);
    }

    /**
     * Show the form for editing the specified bus.
     */
    public function edit(Bus $bus)
    {
        return view('admin.buses.edit', compact('bus'));
    }

    /**
     * Update the specified bus.
     */
    public function update(Request $request, Bus $bus)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|unique:buses,plate_number,' . $bus->id,
            'display_name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:buses,code,' . $bus->id,
            'device_id' => 'nullable|string|unique:buses,device_id,' . $bus->id,
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $bus->update($validated);

        return redirect()->route('admin.buses.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Bus updated successfully!']]);
    }

    /**
     * Remove the specified bus.
     */
    public function destroy(Bus $bus)
    {
        $bus->delete();
        return redirect()->route('admin.buses.index')
            ->with('toastr', [['type' => 'success', 'message' => 'Bus deleted successfully!']]);
    }
}
