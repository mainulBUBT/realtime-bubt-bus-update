<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GPSLocationCollectionService;
use App\Services\DeviceTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * GPS Location Collection API Controller
 * Handles GPS location data collection endpoints
 */
class GPSLocationController extends Controller
{
    private GPSLocationCollectionService $gpsService;
    private DeviceTokenService $deviceTokenService;

    public function __construct(
        GPSLocationCollectionService $gpsService,
        DeviceTokenService $deviceTokenService
    ) {
        $this->gpsService = $gpsService;
        $this->deviceTokenService = $deviceTokenService;
    }

    /**
     * Start a GPS tracking session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function startSession(Request $request): JsonResponse
    {
        // Rate limiting: max 5 session starts per minute per device
        $deviceToken = $request->input('device_token');
        $rateLimitKey = 'gps_session_start:' . hash('sha256', $deviceToken ?? $request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many session start attempts. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($rateLimitKey)
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|min:32',
            'bus_id' => 'required|string|max:10',
            'metadata' => 'nullable|array',
            'metadata.user_agent' => 'nullable|string|max:500',
            'metadata.screen_resolution' => 'nullable|string|max:50',
            'metadata.gps_accuracy_requested' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate device token
            $tokenValidation = $this->deviceTokenService->validateToken($deviceToken);
            if (!$tokenValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device token',
                    'token_validation' => $tokenValidation
                ], 401);
            }

            // Start tracking session
            $result = $this->gpsService->startTrackingSession(
                $deviceToken,
                $request->input('bus_id'),
                $request->input('metadata', [])
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('GPS session start failed', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 8) . '...',
                'bus_id' => $request->input('bus_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Submit batch of GPS location data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitBatch(Request $request): JsonResponse
    {
        // Rate limiting: max 20 batch submissions per minute per device
        $deviceToken = $request->input('device_token');
        $rateLimitKey = 'gps_batch_submit:' . hash('sha256', $deviceToken ?? $request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many batch submissions. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($rateLimitKey)
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|min:32',
            'session_id' => 'required|string|max:100',
            'locations' => 'required|array|min:1|max:10',
            'locations.*.latitude' => 'required|numeric|between:-90,90',
            'locations.*.longitude' => 'required|numeric|between:-180,180',
            'locations.*.accuracy' => 'required|numeric|min:0|max:1000',
            'locations.*.speed' => 'nullable|numeric|min:0|max:200',
            'locations.*.heading' => 'nullable|numeric|min:0|max:360',
            'locations.*.timestamp' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate device token
            $tokenValidation = $this->deviceTokenService->validateToken($deviceToken);
            if (!$tokenValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device token',
                    'token_validation' => $tokenValidation
                ], 401);
            }

            // Process location batch
            $result = $this->gpsService->processBatchLocationData(
                $request->input('locations'),
                $deviceToken,
                $request->input('session_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('GPS batch submission failed', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 8) . '...',
                'session_id' => $request->input('session_id'),
                'locations_count' => count($request->input('locations', []))
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * End a GPS tracking session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function endSession(Request $request): JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|min:32',
            'session_id' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate device token
            $deviceToken = $request->input('device_token');
            $tokenValidation = $this->deviceTokenService->validateToken($deviceToken);
            if (!$tokenValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device token',
                    'token_validation' => $tokenValidation
                ], 401);
            }

            // End tracking session
            $result = $this->gpsService->endTrackingSession($request->input('session_id'));

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('GPS session end failed', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 8) . '...',
                'session_id' => $request->input('session_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get GPS collection statistics (for monitoring)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->gpsService->getCollectionStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get GPS collection statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Get active tracking sessions for a bus (for admin monitoring)
     *
     * @param Request $request
     * @param string $busId
     * @return JsonResponse
     */
    public function getActiveSessions(Request $request, string $busId): JsonResponse
    {
        try {
            $sessions = \App\Models\UserTrackingSession::getActiveSessionsForBus($busId);

            $sessionData = $sessions->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'started_at' => $session->started_at,
                    'duration_minutes' => $session->getDurationMinutes(),
                    'locations_contributed' => $session->locations_contributed,
                    'valid_locations' => $session->valid_locations,
                    'accuracy_rate' => $session->getAccuracyRate(),
                    'quality_score' => $session->getQualityScore(),
                    'trust_score' => $session->trust_score_at_start,
                    'average_accuracy' => $session->average_accuracy
                ];
            });

            return response()->json([
                'success' => true,
                'bus_id' => $busId,
                'active_sessions' => $sessionData,
                'total_sessions' => $sessions->count(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get active sessions', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active sessions'
            ], 500);
        }
    }

    /**
     * Health check endpoint for GPS collection system
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function healthCheck(Request $request): JsonResponse
    {
        try {
            $stats = $this->gpsService->getCollectionStatistics();

            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'active_sessions' => $stats['active_sessions'],
                'locations_today' => $stats['locations_today'],
                'system_load' => [
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true)
                ]
            ];

            // Check for potential issues
            if ($stats['active_sessions'] > 300) {
                $health['warnings'][] = 'High number of active sessions';
            }

            if ($stats['locations_today'] > 50000) {
                $health['warnings'][] = 'High location data volume today';
            }

            return response()->json($health);

        } catch (\Exception $e) {
            Log::error('GPS health check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'unhealthy',
                'error' => 'Health check failed',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Clean up old GPS data (admin endpoint)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            // This should be protected by admin middleware in production
            $result = $this->gpsService->cleanupOldData();

            Log::info('GPS data cleanup completed', $result);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed successfully',
                'data' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('GPS data cleanup failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed'
            ], 500);
        }
    }
}