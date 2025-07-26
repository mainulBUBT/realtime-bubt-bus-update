<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationService;
use App\Services\DeviceTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Bus Location API Controller
 * Handles GPS location data submission and retrieval
 */
class BusLocationController extends Controller
{
    private LocationService $locationService;
    private DeviceTokenService $deviceTokenService;

    public function __construct(LocationService $locationService, DeviceTokenService $deviceTokenService)
    {
        $this->locationService = $locationService;
        $this->deviceTokenService = $deviceTokenService;
    }

    /**
     * Submit GPS location data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limiting: max 10 requests per minute per device
        $deviceToken = $request->input('device_token');
        $rateLimitKey = 'location_submit:' . hash('sha256', $deviceToken ?? $request->ip());
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many location submissions. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($rateLimitKey)
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60); // 1 minute window

        // Validate request data
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|min:32',
            'bus_id' => 'required|string|max:10',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'required|numeric|min:0|max:1000',
            'speed' => 'nullable|numeric|min:0|max:200',
            'heading' => 'nullable|numeric|min:0|max:360',
            'timestamp' => 'required|integer',
            'session_id' => 'nullable|string|max:100'
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

            // Process location data
            $result = $this->locationService->processLocationData($request->all());

            // Update device token reputation based on result
            if ($result['success']) {
                $this->updateDeviceReputation($deviceToken, $result['validation_results']);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Location submission failed', [
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
     * Get current bus positions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentPositions(Request $request): JsonResponse
    {
        try {
            $positions = $this->locationService->getCurrentBusPositions();

            return response()->json([
                'success' => true,
                'data' => $positions,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get current positions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bus positions'
            ], 500);
        }
    }

    /**
     * Get specific bus position
     *
     * @param Request $request
     * @param string $busId
     * @return JsonResponse
     */
    public function getBusPosition(Request $request, string $busId): JsonResponse
    {
        try {
            $positions = $this->locationService->getCurrentBusPositions();
            $busPosition = $positions[$busId] ?? null;

            if (!$busPosition) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bus position not available',
                    'bus_id' => $busId
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $busPosition,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get bus position', [
                'error' => $e->getMessage(),
                'bus_id' => $busId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bus position'
            ], 500);
        }
    }

    /**
     * Get location statistics (for admin/monitoring)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->locationService->getLocationStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get location statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Validate coordinates against bus stops (utility endpoint)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCoordinates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'expected_stop' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validator = app(StoppageCoordinateValidator::class);
            $result = $validator->validateStoppageRadius(
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('expected_stop')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Coordinate validation failed', [
                'error' => $e->getMessage(),
                'coordinates' => $request->only(['latitude', 'longitude'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed'
            ], 500);
        }
    }

    /**
     * Get geofencing boundaries for map visualization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGeofencingBoundaries(Request $request): JsonResponse
    {
        try {
            $validator = app(StoppageCoordinateValidator::class);
            $boundaries = $validator->generateGeofencingBoundaries();

            return response()->json([
                'success' => true,
                'data' => $boundaries,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get geofencing boundaries', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve boundaries'
            ], 500);
        }
    }

    /**
     * Update device reputation based on validation results
     */
    private function updateDeviceReputation(string $deviceToken, array $validationResults): void
    {
        try {
            $reputationChange = 0;

            // Positive factors
            if ($validationResults['coordinates']['valid']) {
                $reputationChange += 0.1;
            }

            if ($validationResults['route']['valid']) {
                $reputationChange += 0.15;
            }

            if (isset($validationResults['speed']) && $validationResults['speed']['valid']) {
                $reputationChange += 0.1;
            }

            // Negative factors
            if (!$validationResults['coordinates']['valid']) {
                $reputationChange -= 0.2;
            }

            if (!$validationResults['route']['valid']) {
                $reputationChange -= 0.15;
            }

            if (isset($validationResults['speed']) && !$validationResults['speed']['valid']) {
                $reputationChange -= 0.3;
            }

            // Apply reputation change
            if ($reputationChange != 0) {
                $this->deviceTokenService->updateReputation($deviceToken, $reputationChange);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to update device reputation', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 8) . '...'
            ]);
        }
    }
}