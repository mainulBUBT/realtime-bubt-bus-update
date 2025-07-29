<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusCurrentPosition;
use App\Models\BusLocation;
use App\Models\DeviceToken;
use App\Models\UserTrackingSession;
use App\Models\BusSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    /**
     * Display monitoring dashboard
     */
    public function index(): View
    {
        // Real-time statistics
        $stats = [
            'active_buses' => BusCurrentPosition::where('status', 'active')->count(),
            'total_trackers' => UserTrackingSession::where('is_active', true)->count(),
            'trusted_devices' => DeviceToken::where('is_trusted', true)->count(),
            'suspicious_devices' => DeviceToken::where('trust_score', '<', 0.3)->count(),
            'avg_trust_score' => DeviceToken::avg('trust_score') ?? 0,
            'location_updates_today' => BusLocation::whereDate('created_at', today())->count(),
        ];

        // Active tracking sessions by bus
        $activeSessions = UserTrackingSession::select('bus_id', DB::raw('COUNT(*) as session_count'))
            ->where('is_active', true)
            ->groupBy('bus_id')
            ->orderBy('session_count', 'desc')
            ->get();

        // Recent alerts (low trust devices, off-route users, etc.)
        $alerts = $this->getSystemAlerts();

        // Trust score distribution
        $trustDistribution = DeviceToken::select(
            DB::raw('CASE 
                WHEN trust_score >= 0.8 THEN "High (0.8+)"
                WHEN trust_score >= 0.6 THEN "Medium (0.6-0.8)"
                WHEN trust_score >= 0.4 THEN "Low (0.4-0.6)"
                ELSE "Very Low (<0.4)"
            END as trust_level'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('trust_level')
            ->get();

        return view('admin.monitoring.index', compact('stats', 'activeSessions', 'alerts', 'trustDistribution'));
    }

    /**
     * Live tracking dashboard with map
     */
    public function liveTracking(Request $request): View
    {
        $selectedBus = $request->get('bus');

        // Get all active bus positions
        $busPositions = BusCurrentPosition::where('status', 'active')
            ->when($selectedBus, function ($query, $busId) {
                return $query->where('bus_id', $busId);
            })
            ->get();

        // Get active tracking sessions for selected bus
        $activeSessions = [];
        if ($selectedBus) {
            $activeSessions = UserTrackingSession::where('bus_id', $selectedBus)
                ->where('is_active', true)
                ->with(['deviceToken'])
                ->get();
        }

        // Get all bus IDs for filter
        $busIds = BusSchedule::distinct('bus_id')->pluck('bus_id')->sort();

        return view('admin.monitoring.live-tracking', compact('busPositions', 'activeSessions', 'selectedBus', 'busIds'));
    }

    /**
     * Device trust management
     */
    public function deviceTrust(): View
    {
        $devices = DeviceToken::select('*')
            ->addSelect(DB::raw('(accurate_contributions / GREATEST(total_contributions, 1)) * 100 as accuracy_percentage'))
            ->orderBy('trust_score', 'desc')
            ->paginate(20);

        // Suspicious devices (low trust score or unusual patterns)
        $suspiciousDevices = DeviceToken::where(function ($query) {
            $query->where('trust_score', '<', 0.3)
                ->orWhere('movement_consistency', '<', 0.4)
                ->orWhere('clustering_score', '<', 0.3);
        })
            ->orderBy('trust_score', 'asc')
            ->limit(10)
            ->get();

        return view('admin.monitoring.device-trust', compact('devices', 'suspiciousDevices'));
    }

    /**
     * Adjust device trust score
     */
    public function adjustTrustScore(Request $request, string $token)
    {
        $request->validate([
            'trust_score' => 'required|numeric|min:0|max:1',
            'reason' => 'required|string|max:255'
        ]);

        $device = DeviceToken::where('token_hash', $token)->firstOrFail();

        $oldScore = $device->trust_score;
        $device->updateTrustScore($request->trust_score);

        // Log the manual adjustment
        DB::table('admin_actions')->insert([
            'admin_id' => auth('admin')->id(),
            'action' => 'trust_score_adjustment',
            'target_type' => 'device_token',
            'target_id' => $device->id,
            'details' => json_encode([
                'old_score' => $oldScore,
                'new_score' => $request->trust_score,
                'reason' => $request->reason
            ]),
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trust score updated successfully',
            'new_score' => $device->trust_score
        ]);
    }

    /**
     * Analytics dashboard
     */
    public function analytics(): View
    {
        // Daily tracking statistics for the last 30 days
        $dailyStats = BusLocation::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_locations'),
            DB::raw('COUNT(DISTINCT device_token) as unique_devices'),
            DB::raw('COUNT(DISTINCT bus_id) as active_buses'),
            DB::raw('AVG(reputation_weight) as avg_reputation')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Bus usage statistics
        $busUsage = UserTrackingSession::select(
            'bus_id',
            DB::raw('COUNT(*) as total_sessions'),
            DB::raw('AVG(locations_contributed) as avg_locations'),
            DB::raw('AVG(valid_locations) as avg_valid_locations'),
            DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_sessions')
        )
            ->groupBy('bus_id')
            ->orderBy('total_sessions', 'desc')
            ->get();

        // Hourly activity pattern
        $hourlyActivity = BusLocation::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as location_count')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return view('admin.monitoring.analytics', compact('dailyStats', 'busUsage', 'hourlyActivity'));
    }

    /**
     * Get real-time data for AJAX updates
     */
    public function getRealTimeData(Request $request): JsonResponse
    {
        $busId = $request->get('bus_id');

        if ($busId) {
            // Get specific bus data
            $busPosition = BusCurrentPosition::where('bus_id', $busId)->first();
            $activeSessions = UserTrackingSession::where('bus_id', $busId)
                ->where('is_active', true)
                ->with(['deviceToken'])
                ->get();

            return response()->json([
                'bus_position' => $busPosition,
                'active_sessions' => $activeSessions,
                'timestamp' => now()->toISOString()
            ]);
        } else {
            // Get all active buses
            $busPositions = BusCurrentPosition::where('status', 'active')->get();

            return response()->json([
                'bus_positions' => $busPositions,
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Get system alerts
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];

        // Low trust devices actively tracking
        $lowTrustActive = UserTrackingSession::join('device_tokens', 'user_tracking_sessions.device_token', '=', 'device_tokens.token_hash')
            ->where('user_tracking_sessions.is_active', true)
            ->where('device_tokens.trust_score', '<', 0.3)
            ->count();

        if ($lowTrustActive > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Trust Devices Active',
                'message' => "{$lowTrustActive} low-trust devices are currently tracking",
                'action' => 'Review device trust scores',
                'url' => route('admin.monitoring.device-trust')
            ];
        }

        // Buses with no active tracking
        $inactiveBuses = BusSchedule::whereNotIn('bus_id', function ($query) {
            $query->select('bus_id')
                ->from('bus_current_positions')
                ->where('status', 'active');
        })
            ->where('is_active', true)
            ->count();

        if ($inactiveBuses > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Buses Not Being Tracked',
                'message' => "{$inactiveBuses} active buses have no current tracking",
                'action' => 'Check bus schedules',
                'url' => route('admin.buses.index')
            ];
        }

        // High number of location updates (potential spam)
        $recentUpdates = BusLocation::where('created_at', '>=', now()->subMinutes(5))->count();
        if ($recentUpdates > 500) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'High Location Update Volume',
                'message' => "{$recentUpdates} location updates in the last 5 minutes",
                'action' => 'Check for spam or system issues',
                'url' => route('admin.monitoring.analytics')
            ];
        }

        return $alerts;
    }
}