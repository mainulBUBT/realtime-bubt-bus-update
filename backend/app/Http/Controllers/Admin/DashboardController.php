<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Get system statistics
        $stats = [
            'buses' => [
                'total' => Bus::count(),
                'active' => Bus::where('status', 'active')->count(),
            ],
            'routes' => [
                'total' => Route::count(),
                'active' => Route::where('is_active', true)->count(),
            ],
            'schedules' => [
                'total' => Schedule::count(),
                'active' => Schedule::where('is_active', true)->count(),
            ],
            'trips' => [
                'today' => Trip::whereDate('trip_date', today())->count(),
                'ongoing' => Trip::where('status', 'ongoing')->count(),
            ],
            'users' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'drivers' => User::where('role', 'driver')->count(),
                'students' => User::where('role', 'student')->count(),
            ],
        ];

        // Get recent activity
        $recentSchedules = Schedule::with(['bus', 'route'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentTrips = Trip::with(['bus', 'route', 'driver'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get active/upcoming schedule periods
        $activePeriod = SchedulePeriod::active()
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->first();

        $upcomingPeriods = SchedulePeriod::where('start_date', '>', today())
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        // Setup progress
        $setupProgress = $this->calculateSetupProgress();

        return view('admin.dashboard.index', compact(
            'stats',
            'recentSchedules',
            'recentTrips',
            'activePeriod',
            'upcomingPeriods',
            'setupProgress'
        ));
    }

    /**
     * Calculate setup progress percentage.
     */
    private function calculateSetupProgress(): array
    {
        $hasPeriod = SchedulePeriod::count() > 0;
        $hasRoutes = Route::count() > 0;
        $hasBuses = Bus::count() > 0;
        $hasSchedules = Schedule::count() > 0;

        $steps = [
            'period' => ['label' => 'Create Schedule Period', 'done' => $hasPeriod],
            'routes' => ['label' => 'Add Routes', 'done' => $hasRoutes],
            'buses' => ['label' => 'Add Buses', 'done' => $hasBuses],
            'schedules' => ['label' => 'Create Schedules', 'done' => $hasSchedules],
        ];

        $completed = collect($steps)->filter(fn($step) => $step['done'])->count();
        $percentage = ($completed / count($steps)) * 100;

        return [
            'steps' => $steps,
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => count($steps),
        ];
    }
}
