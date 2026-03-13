<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\SchedulePeriod;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Show the login page
     */
    public function login()
    {
        if (auth()->check()) {
            if (auth()->user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('app');
        }
        return redirect()->route('admin.login');
    }

    /**
     * Show the main app page
     */
    public function app()
    {
        // Pre-load initial data for the view
        $period = SchedulePeriod::getCurrentPeriod();
        $buses = Bus::active()
            ->with([
                'latestLocation',
                'schedules' => function($query) {
                    $query->where('is_active', true)
                        ->orderBy('departure_time');
                },
                'schedules.route',
                'schedules.route.stops' => function($query) {
                    $query->orderBy('sequence');
                }
            ])
            ->get();

        return view('index', [
            'period' => $period,
            'buses' => $buses,
            'google_client_id' => config('services.google.client_id'),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->save();

        return redirect()->route('app')->with('success', 'Profile updated successfully!');
    }
}
