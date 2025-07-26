<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeviceTokenService
{
    /**
     * Generate a unique device token from fingerprint data
     *
     * @param array $fingerprintData
     * @return string
     */
    public function generateToken(array $fingerprintData): string
    {
        // Create a unique string from fingerprint data
        $fingerprintString = $this->createFingerprintString($fingerprintData);
        
        // Generate a hash from the fingerprint
        $tokenHash = hash('sha256', $fingerprintString . config('app.key'));
        
        return $tokenHash;
    }

    /**
     * Create a consistent string representation of fingerprint data
     *
     * @param array $fingerprintData
     * @return string
     */
    private function createFingerprintString(array $fingerprintData): string
    {
        // Sort the fingerprint data to ensure consistency
        ksort($fingerprintData);
        
        // Create a string representation
        $parts = [];
        
        // Screen data
        if (isset($fingerprintData['screen'])) {
            $screen = $fingerprintData['screen'];
            $parts[] = sprintf(
                'screen:%dx%d:%d:%d',
                $screen['width'] ?? 0,
                $screen['height'] ?? 0,
                $screen['colorDepth'] ?? 0,
                $screen['pixelDepth'] ?? 0
            );
        }
        
        // Navigator data (most stable parts)
        if (isset($fingerprintData['navigator'])) {
            $nav = $fingerprintData['navigator'];
            $parts[] = 'nav:' . ($nav['platform'] ?? '');
            $parts[] = 'lang:' . ($nav['language'] ?? '');
            $parts[] = 'hw:' . ($nav['hardwareConcurrency'] ?? 0);
            $parts[] = 'touch:' . ($nav['maxTouchPoints'] ?? 0);
        }
        
        // Timezone
        if (isset($fingerprintData['timezone'])) {
            $tz = $fingerprintData['timezone'];
            $parts[] = 'tz:' . ($tz['timezone'] ?? '');
        }
        
        // Canvas fingerprint (if available)
        if (isset($fingerprintData['canvas']) && $fingerprintData['canvas'] !== 'canvas_not_supported') {
            $parts[] = 'canvas:' . substr(md5($fingerprintData['canvas']), 0, 16);
        }
        
        // WebGL data
        if (isset($fingerprintData['webgl']) && $fingerprintData['webgl']['supported']) {
            $webgl = $fingerprintData['webgl'];
            $parts[] = 'webgl:' . ($webgl['renderer'] ?? '');
        }
        
        // Features
        if (isset($fingerprintData['features'])) {
            $features = $fingerprintData['features'];
            $parts[] = sprintf(
                'feat:%d%d%d%d',
                $features['localStorage'] ? 1 : 0,
                $features['webWorkers'] ? 1 : 0,
                $features['geolocation'] ? 1 : 0,
                $features['touchSupport'] ? 1 : 0
            );
        }
        
        return implode('|', $parts);
    }

    /**
     * Validate a device token
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        // Check token format
        if (!$this->isValidTokenFormat($token)) {
            return false;
        }
        
        // Check if token exists in database
        $deviceToken = DeviceToken::where('token_hash', $token)->first();
        
        if (!$deviceToken) {
            return false;
        }
        
        // Update last activity
        $deviceToken->update(['last_activity' => Carbon::now()]);
        
        return true;
    }

    /**
     * Check if token format is valid
     *
     * @param string $token
     * @return bool
     */
    private function isValidTokenFormat(string $token): bool
    {
        // Token should be a 64-character hex string (SHA-256)
        return preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
    }

    /**
     * Register a new device token
     *
     * @param string $token
     * @param array $fingerprintData
     * @return DeviceToken
     */
    public function registerToken(string $token, array $fingerprintData): DeviceToken
    {
        // Check if token already exists
        $existingToken = DeviceToken::where('token_hash', $token)->first();
        
        if ($existingToken) {
            // Update fingerprint data and last activity
            $existingToken->update([
                'fingerprint_data' => $fingerprintData,
                'last_activity' => Carbon::now()
            ]);
            
            return $existingToken;
        }
        
        // Create new device token
        return DeviceToken::create([
            'token_hash' => $token,
            'fingerprint_data' => $fingerprintData,
            'reputation_score' => 0.5, // Start with neutral reputation
            'trust_score' => 0.5, // Start with neutral trust
            'total_contributions' => 0,
            'accurate_contributions' => 0,
            'clustering_score' => 0.0,
            'movement_consistency' => 0.0,
            'last_activity' => Carbon::now(),
            'is_trusted' => false
        ]);
    }

    /**
     * Get device token by token hash
     *
     * @param string $token
     * @return DeviceToken|null
     */
    public function getDeviceToken(string $token): ?DeviceToken
    {
        return DeviceToken::where('token_hash', $token)->first();
    }

    /**
     * Get reputation score for a device token
     *
     * @param string $token
     * @return float
     */
    public function getReputationScore(string $token): float
    {
        $deviceToken = $this->getDeviceToken($token);
        
        return $deviceToken ? $deviceToken->reputation_score : 0.0;
    }

    /**
     * Update reputation score based on location data accuracy
     *
     * @param string $token
     * @param bool $wasAccurate
     * @return void
     */
    public function updateReputation(string $token, bool $wasAccurate): void
    {
        $deviceToken = $this->getDeviceToken($token);
        
        if (!$deviceToken) {
            Log::warning("Attempted to update reputation for non-existent token: " . substr($token, 0, 8));
            return;
        }
        
        $deviceToken->updateReputationScore($wasAccurate);
        
        // Also update trust score based on reputation
        $this->updateTrustScore($deviceToken);
    }

    /**
     * Update trust score based on various factors
     *
     * @param DeviceToken $deviceToken
     * @return void
     */
    private function updateTrustScore(DeviceToken $deviceToken): void
    {
        $factors = [];
        
        // Reputation factor (40% weight)
        $factors['reputation'] = $deviceToken->reputation_score * 0.4;
        
        // Contribution count factor (20% weight)
        $contributionFactor = min(1.0, $deviceToken->total_contributions / 100); // Max at 100 contributions
        $factors['contributions'] = $contributionFactor * 0.2;
        
        // Clustering score factor (20% weight)
        $factors['clustering'] = $deviceToken->clustering_score * 0.2;
        
        // Movement consistency factor (20% weight)
        $factors['movement'] = $deviceToken->movement_consistency * 0.2;
        
        // Calculate weighted trust score
        $newTrustScore = array_sum($factors);
        
        $deviceToken->updateTrustScore($newTrustScore);
    }

    /**
     * Update clustering score based on proximity to other users
     *
     * @param string $token
     * @param float $clusteringScore
     * @return void
     */
    public function updateClusteringScore(string $token, float $clusteringScore): void
    {
        $deviceToken = $this->getDeviceToken($token);
        
        if (!$deviceToken) {
            return;
        }
        
        $deviceToken->clustering_score = max(0.0, min(1.0, $clusteringScore));
        $deviceToken->save();
        
        // Update trust score
        $this->updateTrustScore($deviceToken);
    }

    /**
     * Update movement consistency score
     *
     * @param string $token
     * @param float $consistencyScore
     * @return void
     */
    public function updateMovementConsistency(string $token, float $consistencyScore): void
    {
        $deviceToken = $this->getDeviceToken($token);
        
        if (!$deviceToken) {
            return;
        }
        
        $deviceToken->movement_consistency = max(0.0, min(1.0, $consistencyScore));
        $deviceToken->save();
        
        // Update trust score
        $this->updateTrustScore($deviceToken);
    }

    /**
     * Get trusted devices for a specific time period
     *
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrustedDevices(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return DeviceToken::where('is_trusted', true)
            ->where('last_activity', '>=', Carbon::now()->subHours($hours))
            ->get();
    }

    /**
     * Clean up old inactive device tokens
     *
     * @param int $daysInactive
     * @return int Number of deleted tokens
     */
    public function cleanupInactiveTokens(int $daysInactive = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysInactive);
        
        return DeviceToken::where('last_activity', '<', $cutoffDate)
            ->where('total_contributions', '<', 10) // Keep tokens with significant contributions
            ->delete();
    }

    /**
     * Get device statistics
     *
     * @return array
     */
    public function getDeviceStatistics(): array
    {
        $total = DeviceToken::count();
        $trusted = DeviceToken::where('is_trusted', true)->count();
        $active24h = DeviceToken::where('last_activity', '>=', Carbon::now()->subHours(24))->count();
        $activeWeek = DeviceToken::where('last_activity', '>=', Carbon::now()->subWeek())->count();
        
        return [
            'total_devices' => $total,
            'trusted_devices' => $trusted,
            'active_24h' => $active24h,
            'active_week' => $activeWeek,
            'trust_percentage' => $total > 0 ? round(($trusted / $total) * 100, 2) : 0
        ];
    }

    /**
     * Validate fingerprint data structure
     *
     * @param array $fingerprintData
     * @return bool
     */
    public function validateFingerprintData(array $fingerprintData): bool
    {
        // Check required fields
        $requiredFields = ['screen', 'navigator', 'timezone', 'features'];
        
        foreach ($requiredFields as $field) {
            if (!isset($fingerprintData[$field])) {
                return false;
            }
        }
        
        // Validate screen data
        if (!isset($fingerprintData['screen']['width']) || !isset($fingerprintData['screen']['height'])) {
            return false;
        }
        
        // Validate navigator data
        if (!isset($fingerprintData['navigator']['userAgent']) || !isset($fingerprintData['navigator']['platform'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Generate token from fingerprint and register it
     *
     * @param array $fingerprintData
     * @return array
     */
    public function processFingerprint(array $fingerprintData): array
    {
        try {
            // Validate fingerprint data
            if (!$this->validateFingerprintData($fingerprintData)) {
                throw new \InvalidArgumentException('Invalid fingerprint data structure');
            }
            
            // Generate token
            $token = $this->generateToken($fingerprintData);
            
            // Register or update token
            $deviceToken = $this->registerToken($token, $fingerprintData);
            
            return [
                'success' => true,
                'token' => $token,
                'device_id' => $deviceToken->id,
                'reputation_score' => $deviceToken->reputation_score,
                'trust_score' => $deviceToken->trust_score,
                'is_trusted' => $deviceToken->is_trusted
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to process fingerprint: ' . $e->getMessage(), [
                'fingerprint_keys' => array_keys($fingerprintData)
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to process device fingerprint',
                'message' => $e->getMessage()
            ];
        }
    }
}