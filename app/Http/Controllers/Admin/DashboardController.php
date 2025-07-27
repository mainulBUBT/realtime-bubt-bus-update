<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\BusSchedule;
use App\Models\BusCurrentPosition;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index(): View
    {
        // Get dashboard statistics
        $stats = [
            'total_buses' => BusSchedule::distinct('bus_id')->count(),
            'active_buses' => BusCurrentPosition::where('status', 'active')->count(),
            'total_devices' => DeviceToken::count(),
            'trusted_devices' => DeviceToken::where('is_trusted', true)->count(),
            'active_tracking_sessions' => UserTrackingSession::where('is_active', true)->count(),
        ];

        // Get recent activity
        $recentSessions = UserTrackingSession::with(['busSchedule'])
            ->where('started_at', '>=', now()->subHours(24))
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Get bus status overview
        $busStatuses = BusCurrentPosition::select('bus_id', 'status', 'active_trackers', 'last_updated')
            ->orderBy('bus_id')
            ->get();

        // Get system health metrics
        $systemHealth = [
            'database_connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
            'avg_trust_score' => DeviceToken::avg('trust_score') ?? 0,
            'location_updates_today' => UserTrackingSession::where('started_at', '>=', now()->startOfDay())->sum('locations_contributed'),
        ];

        return view('admin.dashboard', compact('stats', 'recentSessions', 'busStatuses', 'systemHealth'));
    }
}