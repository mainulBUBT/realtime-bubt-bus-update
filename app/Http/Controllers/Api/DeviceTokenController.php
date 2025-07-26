<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeviceTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    protected DeviceTokenService $deviceTokenService;

    public function __construct(DeviceTokenService $deviceTokenService)
    {
        $this->deviceTokenService = $deviceTokenService;
    }

    /**
     * Register a new device token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
            'fingerprint' => 'required|array',
            'fingerprint.screen' => 'required|array',
            'fingerprint.navigator' => 'required|array',
            'fingerprint.timezone' => 'required|array',
            'fingerprint.features' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $token = $request->input('token');
            $fingerprintData = $request->input('fingerprint');

            // Process the fingerprint and register token
            $result = $this->deviceTokenService->processFingerprint($fingerprintData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'message' => $result['message'] ?? 'Unknown error'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $result['token'],
                    'device_id' => $result['device_id'],
                    'reputation_score' => $result['reputation_score'],
                    'trust_score' => $result['trust_score'],
                    'is_trusted' => $result['is_trusted']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed',
                'message' => 'An error occurred while registering the device token'
            ], 500);
        }
    }

    /**
     * Validate a device token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid token format'
            ], 422);
        }

        try {
            $token = $request->input('token');
            $isValid = $this->deviceTokenService->validateToken($token);

            if ($isValid) {
                $deviceToken = $this->deviceTokenService->getDeviceToken($token);
                
                return response()->json([
                    'valid' => true,
                    'data' => [
                        'reputation_score' => $deviceToken->reputation_score,
                        'trust_score' => $deviceToken->trust_score,
                        'is_trusted' => $deviceToken->is_trusted,
                        'total_contributions' => $deviceToken->total_contributions,
                        'last_activity' => $deviceToken->last_activity
                    ]
                ]);
            }

            return response()->json([
                'valid' => false,
                'error' => 'Token not found or invalid'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => 'Validation failed',
                'message' => 'An error occurred while validating the token'
            ], 500);
        }
    }

    /**
     * Get device token statistics
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->deviceTokenService->getDeviceStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve statistics',
                'message' => 'An error occurred while fetching device statistics'
            ], 500);
        }
    }
}