<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Device Token Service
 * Handles device token generation, validation, and reputation management
 */
class DeviceTokenService
{
    private const TOKEN_EXPIRY_DAYS = 30;
    private const MIN_REPUTATION = 0.0;
    private const MAX_REPUTATION = 1.0;
    private const DEFAULT_REPUTATION = 0.5;
    private const REPUTATION_DECAY_RATE = 0.01; // Daily decay for inactive devices

    /**
     * Generate a new device token from fingerprint data
     *
     * @param array $fingerprintData Device fingerprint information
     * @return array Token generation result
     */
    public function generateToken(array $fingerprintData): array
    {
        try {
            // Create a unique token from fingerprint
            $tokenString = $this->createTokenFromFingerprint($fingerprintData);
            $tokenHash = hash('sha256', $tokenString);

            // Check if device already exists
            $existingDevice = DeviceToken::where('token_hash', $tokenHash)->first();

            if ($existingDevice) {
                // Update existing device
                $existingDevice->update([
                    'fingerprint_data' => $fingerprintData,
                    'last_activity' => now(),
                    'total_contributions' => $existingDevice->total_contributions + 1
                ]);

                return [
                    'success' => true,
                    'token' => $tokenString,
                    'is_new' => false,
                    'device_id' => $existingDevice->id,
                    'reputation_score' => $existingDevice->reputation_score
                ];
            }

            // Create new device token
            $device = DeviceToken::create([
                'token_hash' => $tokenHash,
                'fingerprint_data' => $fingerprintData,
                'reputation_score' => self::DEFAULT_REPUTATION,
                'trust_score' => self::DEFAULT_REPUTATION,
                'total_contributions' => 1,
                'accurate_contributions' => 0,
                'last_activity' => now(),
                'created_at' => now()
            ]);

            Log::info('New device token generated', [
                'device_id' => $device->id,
                'token_hash' => substr($tokenHash, 0, 8) . '...'
            ]);

            return [
                'success' => true,
                'token' => $tokenString,
                'is_new' => true,
                'device_id' => $device->id,
                'reputation_score' => $device->reputation_score
            ];

        } catch (\Exception $e) {
            Log::error('Token generation failed', [
                'error' => $e->getMessage(),
                'fingerprint_keys' => array_keys($fingerprintData)
            ]);

            return [
                'success' => false,
                'message' => 'Token generation failed'
            ];
        }
    }

    /**
     * Validate a device token
     *
     * @param string $token Device token to validate
     * @return array Validation result
     */
    public function validateToken(string $token): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            
            // Check cache first
            $cacheKey = "device_validation:{$tokenHash}";
            $cachedResult = Cache::get($cacheKey);
            
            if ($cachedResult) {
                return $cachedResult;
            }

            // Get device from database
            $device = DeviceToken::where('token_hash', $tokenHash)->first();

            if (!$device) {
                $result = [
                    'valid' => false,
                    'reason' => 'token_not_found',
                    'message' => 'Device token not found'
                ];
            } elseif ($this->isTokenExpired($device)) {
                $result = [
                    'valid' => false,
                    'reason' => 'token_expired',
                    'message' => 'Device token has expired',
                    'expired_at' => $device->created_at->addDays(self::TOKEN_EXPIRY_DAYS)
                ];
            } elseif ($device->is_blocked) {
                $result = [
                    'valid' => false,
                    'reason' => 'device_blocked',
                    'message' => 'Device has been blocked due to suspicious activity'
                ];
            } else {
                // Valid token
                $result = [
                    'valid' => true,
                    'device_id' => $device->id,
                    'reputation_score' => $device->reputation_score,
                    'trust_score' => $device->trust_score,
                    'is_trusted' => $device->trust_score >= 0.7,
                    'last_activity' => $device->last_activity,
                    'total_contributions' => $device->total_contributions
                ];

                // Update last activity
                $device->update(['last_activity' => now()]);
            }

            // Cache result for 5 minutes
            Cache::put($cacheKey, $result, now()->addMinutes(5));

            return $result;

        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'error' => $e->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 8) . '...'
            ]);

            return [
                'valid' => false,
                'reason' => 'validation_error',
                'message' => 'Token validation failed'
            ];
        }
    }

    /**
     * Update device reputation score
     *
     * @param string $token Device token
     * @param float $change Reputation change (positive or negative)
     * @return array Update result
     */
    public function updateReputation(string $token, float $change): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $device = DeviceToken::where('token_hash', $tokenHash)->first();

            if (!$device) {
                return [
                    'success' => false,
                    'message' => 'Device not found'
                ];
            }

            $oldReputation = $device->reputation_score;
            $newReputation = max(self::MIN_REPUTATION, min(self::MAX_REPUTATION, $oldReputation + $change));

            // Update reputation and related metrics
            $device->update([
                'reputation_score' => $newReputation,
                'last_activity' => now()
            ]);

            // Update contribution counters
            if ($change > 0) {
                $device->increment('accurate_contributions');
            }
            $device->increment('total_contributions');

            // Clear cache
            $cacheKey = "device_validation:{$tokenHash}";
            Cache::forget($cacheKey);

            Log::info('Device reputation updated', [
                'device_id' => $device->id,
                'old_reputation' => $oldReputation,
                'new_reputation' => $newReputation,
                'change' => $change
            ]);

            return [
                'success' => true,
                'old_reputation' => $oldReputation,
                'new_reputation' => $newReputation,
                'change' => $change
            ];

        } catch (\Exception $e) {
            Log::error('Reputation update failed', [
                'error' => $e->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 8) . '...',
                'change' => $change
            ]);

            return [
                'success' => false,
                'message' => 'Reputation update failed'
            ];
        }
    }

    /**
     * Get device reputation score
     *
     * @param string $token Device token
     * @return float Reputation score
     */
    public function getReputationScore(string $token): float
    {
        $tokenHash = hash('sha256', $token);
        $device = DeviceToken::where('token_hash', $tokenHash)->first();
        
        return $device ? $device->reputation_score : self::DEFAULT_REPUTATION;
    }

    /**
     * Block a device due to suspicious activity
     *
     * @param string $token Device token
     * @param string $reason Reason for blocking
     * @return array Block result
     */
    public function blockDevice(string $token, string $reason): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $device = DeviceToken::where('token_hash', $tokenHash)->first();

            if (!$device) {
                return [
                    'success' => false,
                    'message' => 'Device not found'
                ];
            }

            $device->update([
                'is_blocked' => true,
                'blocked_reason' => $reason,
                'blocked_at' => now()
            ]);

            // Clear cache
            $cacheKey = "device_validation:{$tokenHash}";
            Cache::forget($cacheKey);

            Log::warning('Device blocked', [
                'device_id' => $device->id,
                'reason' => $reason,
                'reputation_score' => $device->reputation_score
            ]);

            return [
                'success' => true,
                'message' => 'Device blocked successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Device blocking failed', [
                'error' => $e->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 8) . '...',
                'reason' => $reason
            ]);

            return [
                'success' => false,
                'message' => 'Device blocking failed'
            ];
        }
    }

    /**
     * Unblock a device
     *
     * @param string $token Device token
     * @return array Unblock result
     */
    public function unblockDevice(string $token): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $device = DeviceToken::where('token_hash', $tokenHash)->first();

            if (!$device) {
                return [
                    'success' => false,
                    'message' => 'Device not found'
                ];
            }

            $device->update([
                'is_blocked' => false,
                'blocked_reason' => null,
                'blocked_at' => null
            ]);

            // Clear cache
            $cacheKey = "device_validation:{$tokenHash}";
            Cache::forget($cacheKey);

            Log::info('Device unblocked', [
                'device_id' => $device->id
            ]);

            return [
                'success' => true,
                'message' => 'Device unblocked successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Device unblocking failed', [
                'error' => $e->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 8) . '...'
            ]);

            return [
                'success' => false,
                'message' => 'Device unblocking failed'
            ];
        }
    }

    /**
     * Clean up old and inactive device tokens
     *
     * @return array Cleanup result
     */
    public function cleanupOldTokens(): array
    {
        try {
            // Delete expired tokens
            $expiredCount = DeviceToken::where('created_at', '<', now()->subDays(self::TOKEN_EXPIRY_DAYS))
                ->delete();

            // Apply reputation decay to inactive devices
            $inactiveDevices = DeviceToken::where('last_activity', '<', now()->subDays(7))
                ->where('reputation_score', '>', self::MIN_REPUTATION)
                ->get();

            $decayedCount = 0;
            foreach ($inactiveDevices as $device) {
                $newReputation = max(
                    self::MIN_REPUTATION,
                    $device->reputation_score - self::REPUTATION_DECAY_RATE
                );
                
                $device->update(['reputation_score' => $newReputation]);
                $decayedCount++;
            }

            Log::info('Device token cleanup completed', [
                'expired_deleted' => $expiredCount,
                'reputation_decayed' => $decayedCount
            ]);

            return [
                'success' => true,
                'expired_deleted' => $expiredCount,
                'reputation_decayed' => $decayedCount
            ];

        } catch (\Exception $e) {
            Log::error('Token cleanup failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Token cleanup failed'
            ];
        }
    }

    /**
     * Get device statistics for monitoring
     *
     * @return array Device statistics
     */
    public function getDeviceStatistics(): array
    {
        try {
            $stats = [
                'total_devices' => DeviceToken::count(),
                'active_devices_24h' => DeviceToken::where('last_activity', '>', now()->subHours(24))->count(),
                'active_devices_7d' => DeviceToken::where('last_activity', '>', now()->subDays(7))->count(),
                'trusted_devices' => DeviceToken::where('trust_score', '>=', 0.7)->count(),
                'blocked_devices' => DeviceToken::where('is_blocked', true)->count(),
                'average_reputation' => DeviceToken::avg('reputation_score'),
                'high_reputation_devices' => DeviceToken::where('reputation_score', '>', 0.8)->count(),
                'low_reputation_devices' => DeviceToken::where('reputation_score', '<', 0.3)->count()
            ];

            return array_map(function ($value) {
                return is_numeric($value) ? round($value, 2) : $value;
            }, $stats);

        } catch (\Exception $e) {
            Log::error('Failed to get device statistics', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Create token string from fingerprint data
     */
    private function createTokenFromFingerprint(array $fingerprintData): string
    {
        // Create a deterministic string from fingerprint data
        $fingerprintString = json_encode($fingerprintData, JSON_SORT_KEYS);
        
        // Add some randomness to prevent easy token prediction
        $salt = config('app.key', 'default_salt');
        
        return hash('sha256', $fingerprintString . $salt);
    }

    /**
     * Check if token is expired
     */
    private function isTokenExpired(DeviceToken $device): bool
    {
        return $device->created_at->addDays(self::TOKEN_EXPIRY_DAYS)->isPast();
    }

    /**
     * Detect suspicious device behavior
     *
     * @param string $token Device token
     * @return array Suspicion analysis
     */
    public function detectSuspiciousBehavior(string $token): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $device = DeviceToken::where('token_hash', $tokenHash)->first();

            if (!$device) {
                return [
                    'suspicious' => false,
                    'reason' => 'device_not_found'
                ];
            }

            $suspiciousFactors = [];

            // Check reputation drop rate
            if ($device->reputation_score < 0.2) {
                $suspiciousFactors[] = 'very_low_reputation';
            }

            // Check contribution accuracy
            $accuracyRate = $device->total_contributions > 0 ? 
                ($device->accurate_contributions / $device->total_contributions) : 1;
            
            if ($accuracyRate < 0.3 && $device->total_contributions > 10) {
                $suspiciousFactors[] = 'low_accuracy_rate';
            }

            // Check for spam-like behavior (too many contributions in short time)
            $recentContributions = \DB::table('bus_locations')
                ->where('device_token', $tokenHash)
                ->where('created_at', '>', now()->subHour())
                ->count();

            if ($recentContributions > 100) {
                $suspiciousFactors[] = 'excessive_contributions';
            }

            $isSuspicious = !empty($suspiciousFactors);

            return [
                'suspicious' => $isSuspicious,
                'factors' => $suspiciousFactors,
                'reputation_score' => $device->reputation_score,
                'accuracy_rate' => round($accuracyRate, 3),
                'recent_contributions' => $recentContributions
            ];

        } catch (\Exception $e) {
            Log::error('Suspicious behavior detection failed', [
                'error' => $e->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 8) . '...'
            ]);

            return [
                'suspicious' => false,
                'reason' => 'detection_error'
            ];
        }
    }
}