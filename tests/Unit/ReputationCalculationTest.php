<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DeviceTokenService;
use App\Models\DeviceToken;
use App\Models\BusLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReputationCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceTokenService $deviceTokenService;
    protected array $sampleFingerprint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->deviceTokenService = new DeviceTokenService();
        
        $this->sampleFingerprint = [
            'screen' => [
                'width' => 1920,
                'height' => 1080,
                'colorDepth' => 24,
                'pixelDepth' => 24
            ],
            'navigator' => [
                'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'platform' => 'MacIntel',
                'language' => 'en-US',
                'hardwareConcurrency' => 8,
                'maxTouchPoints' => 0
            ],
            'timezone' => [
                'timezone' => 'America/New_York',
                'offset' => -300
            ],
            'features' => [
                'localStorage' => true,
                'webWorkers' => true,
                'geolocation' => true,
                'touchSupport' => false
            ],
            'canvas' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAAAyCAYAAAAZUZThAAAAAXNSR0IArs4c6QAABjdJREFUeF7t',
            'webgl' => [
                'supported' => true,
                'vendor' => 'Intel Inc.',
                'renderer' => 'Intel Iris Pro OpenGL Engine'
            ]
        ];
    }

    public function test_reputation_increases_with_accurate_contributions()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $initialScore = $deviceToken->reputation_score;
        
        // Simulate multiple accurate contributions
        for ($i = 0; $i < 10; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        $deviceToken->refresh();
        $finalScore = $deviceToken->reputation_score;
        
        $this->assertGreaterThan($initialScore, $finalScore);
        $this->assertEquals(10, $deviceToken->total_contributions);
        $this->assertEquals(10, $deviceToken->accurate_contributions);
        $this->assertGreaterThan(0.8, $finalScore); // Should be high with all accurate
    }

    public function test_reputation_decreases_with_inaccurate_contributions()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // First establish some accurate contributions
        for ($i = 0; $i < 5; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        $deviceToken->refresh();
        $scoreAfterAccurate = $deviceToken->reputation_score;
        
        // Now add inaccurate contributions
        for ($i = 0; $i < 10; $i++) {
            $this->deviceTokenService->updateReputation($token, false);
        }
        
        $deviceToken->refresh();
        $finalScore = $deviceToken->reputation_score;
        
        $this->assertLessThan($scoreAfterAccurate, $finalScore);
        $this->assertEquals(15, $deviceToken->total_contributions);
        $this->assertEquals(5, $deviceToken->accurate_contributions);
        $this->assertLessThan(0.5, $finalScore); // Should be low with mostly inaccurate
    }

    public function test_reputation_calculation_with_mixed_accuracy_scenarios()
    {
        $scenarios = [
            // [accurate_count, inaccurate_count, expected_min_score, expected_max_score]
            [10, 0, 0.8, 1.0],    // All accurate
            [0, 10, 0.0, 0.3],    // All inaccurate
            [7, 3, 0.6, 0.8],     // Mostly accurate
            [3, 7, 0.2, 0.5],     // Mostly inaccurate
            [5, 5, 0.4, 0.6],     // Mixed
        ];

        foreach ($scenarios as $index => $scenario) {
            [$accurateCount, $inaccurateCount, $expectedMin, $expectedMax] = $scenario;
            
            // Create unique fingerprint for each scenario
            $fingerprint = $this->sampleFingerprint;
            $fingerprint['screen']['width'] = 1920 + $index;
            
            $token = $this->deviceTokenService->generateToken($fingerprint);
            $deviceToken = $this->deviceTokenService->registerToken($token, $fingerprint);
            
            // Add accurate contributions
            for ($i = 0; $i < $accurateCount; $i++) {
                $this->deviceTokenService->updateReputation($token, true);
            }
            
            // Add inaccurate contributions
            for ($i = 0; $i < $inaccurateCount; $i++) {
                $this->deviceTokenService->updateReputation($token, false);
            }
            
            $deviceToken->refresh();
            $finalScore = $deviceToken->reputation_score;
            
            $this->assertGreaterThanOrEqual($expectedMin, $finalScore, 
                "Scenario {$index}: Score {$finalScore} should be >= {$expectedMin}");
            $this->assertLessThanOrEqual($expectedMax, $finalScore, 
                "Scenario {$index}: Score {$finalScore} should be <= {$expectedMax}");
        }
    }

    public function test_trust_score_calculation_based_on_reputation_and_consistency()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Build up reputation
        for ($i = 0; $i < 8; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        // Add clustering score (indicates user is with other users)
        $this->deviceTokenService->updateClusteringScore($token, 0.9);
        
        // Add movement consistency (indicates bus-like movement)
        $this->deviceTokenService->updateMovementConsistency($token, 0.8);
        
        $deviceToken->refresh();
        
        // Trust score should be high with good reputation and consistency
        $this->assertGreaterThan(0.7, $deviceToken->trust_score);
        $this->assertTrue($deviceToken->is_trusted);
    }

    public function test_trust_score_penalized_by_poor_clustering()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Build up reputation
        for ($i = 0; $i < 8; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        // Poor clustering score (user is isolated from others)
        $this->deviceTokenService->updateClusteringScore($token, 0.2);
        
        // Good movement consistency
        $this->deviceTokenService->updateMovementConsistency($token, 0.8);
        
        $deviceToken->refresh();
        
        // Trust score should be lower due to poor clustering
        $this->assertLessThan(0.7, $deviceToken->trust_score);
        $this->assertFalse($deviceToken->is_trusted);
    }

    public function test_trust_score_penalized_by_inconsistent_movement()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Build up reputation
        for ($i = 0; $i < 8; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        // Good clustering score
        $this->deviceTokenService->updateClusteringScore($token, 0.9);
        
        // Poor movement consistency (erratic movement)
        $this->deviceTokenService->updateMovementConsistency($token, 0.3);
        
        $deviceToken->refresh();
        
        // Trust score should be lower due to inconsistent movement
        $this->assertLessThan(0.7, $deviceToken->trust_score);
        $this->assertFalse($deviceToken->is_trusted);
    }

    public function test_reputation_decay_over_time_for_inactive_devices()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Build up high reputation
        for ($i = 0; $i < 10; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        $deviceToken->refresh();
        $highScore = $deviceToken->reputation_score;
        
        // Simulate device being inactive for a long time
        $deviceToken->update(['last_activity' => Carbon::now()->subDays(30)]);
        
        // Apply reputation decay (this would typically be done by a scheduled job)
        $decayFactor = 0.8; // 20% decay for 30 days inactive
        $newScore = $highScore * $decayFactor;
        $deviceToken->update(['reputation_score' => $newScore]);
        
        $deviceToken->refresh();
        
        $this->assertLessThan($highScore, $deviceToken->reputation_score);
        $this->assertEquals($newScore, $deviceToken->reputation_score);
    }

    public function test_reputation_boost_for_consistent_long_term_contributors()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Simulate consistent contributions over time
        $baseTime = Carbon::now()->subDays(30);
        
        for ($day = 0; $day < 30; $day++) {
            // Update last activity to simulate daily usage
            $deviceToken->update(['last_activity' => $baseTime->copy()->addDays($day)]);
            
            // Add accurate contributions (simulate daily accurate GPS data)
            $this->deviceTokenService->updateReputation($token, true);
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        $deviceToken->refresh();
        
        // Long-term consistent contributors should have very high reputation
        $this->assertGreaterThan(0.9, $deviceToken->reputation_score);
        $this->assertEquals(60, $deviceToken->total_contributions);
        $this->assertEquals(60, $deviceToken->accurate_contributions);
    }

    public function test_weighted_reputation_calculation_for_bus_position()
    {
        // Create multiple devices with different reputation scores
        $devices = [];
        $reputationScores = [0.9, 0.7, 0.5, 0.3, 0.1];
        
        foreach ($reputationScores as $index => $score) {
            $fingerprint = $this->sampleFingerprint;
            $fingerprint['screen']['width'] = 1920 + $index;
            
            $token = $this->deviceTokenService->generateToken($fingerprint);
            $deviceToken = $this->deviceTokenService->registerToken($token, $fingerprint);
            
            // Set specific reputation score
            $deviceToken->update(['reputation_score' => $score]);
            
            $devices[] = [
                'token' => $token,
                'device' => $deviceToken,
                'reputation' => $score
            ];
        }
        
        // Simulate GPS locations from these devices for the same bus
        $busId = 'B1';
        $baseTime = Carbon::now();
        
        foreach ($devices as $index => $device) {
            BusLocation::create([
                'bus_id' => $busId,
                'device_token' => hash('sha256', $device['token']),
                'latitude' => 23.7937 + ($index * 0.0001), // Slightly different positions
                'longitude' => 90.3629 + ($index * 0.0001),
                'accuracy' => 20.0,
                'reputation_weight' => $device['reputation'],
                'created_at' => $baseTime
            ]);
        }
        
        // Calculate weighted average position
        $locations = BusLocation::where('bus_id', $busId)
            ->where('created_at', '>=', $baseTime->copy()->subMinutes(5))
            ->get();
        
        $totalWeight = $locations->sum('reputation_weight');
        $weightedLat = $locations->sum(function ($location) {
            return $location->latitude * $location->reputation_weight;
        }) / $totalWeight;
        
        $weightedLng = $locations->sum(function ($location) {
            return $location->longitude * $location->reputation_weight;
        }) / $totalWeight;
        
        // The weighted position should be closer to high-reputation devices
        $this->assertIsFloat($weightedLat);
        $this->assertIsFloat($weightedLng);
        
        // High reputation device (first one) should have more influence
        $highRepDevice = $locations->first();
        $latDifference = abs($weightedLat - $highRepDevice->latitude);
        $this->assertLessThan(0.0001, $latDifference); // Should be very close to high-rep device
    }

    public function test_reputation_calculation_handles_edge_cases()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Test with zero contributions
        $this->assertEquals(0.5, $deviceToken->reputation_score); // Default score
        
        // Test with single accurate contribution
        $this->deviceTokenService->updateReputation($token, true);
        $deviceToken->refresh();
        $this->assertGreaterThan(0.5, $deviceToken->reputation_score);
        
        // Test with single inaccurate contribution from fresh device
        $fingerprint2 = $this->sampleFingerprint;
        $fingerprint2['screen']['width'] = 1366;
        $token2 = $this->deviceTokenService->generateToken($fingerprint2);
        $deviceToken2 = $this->deviceTokenService->registerToken($token2, $fingerprint2);
        
        $this->deviceTokenService->updateReputation($token2, false);
        $deviceToken2->refresh();
        $this->assertLessThan(0.5, $deviceToken2->reputation_score);
    }

    public function test_reputation_bounds_are_enforced()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Test upper bound - many accurate contributions
        for ($i = 0; $i < 100; $i++) {
            $this->deviceTokenService->updateReputation($token, true);
        }
        
        $deviceToken->refresh();
        $this->assertLessThanOrEqual(1.0, $deviceToken->reputation_score);
        
        // Test lower bound - many inaccurate contributions
        for ($i = 0; $i < 200; $i++) {
            $this->deviceTokenService->updateReputation($token, false);
        }
        
        $deviceToken->refresh();
        $this->assertGreaterThanOrEqual(0.0, $deviceToken->reputation_score);
    }

    public function test_trust_score_bounds_are_enforced()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // Test upper bound
        $deviceToken->updateTrustScore(1.5); // Try to set above 1.0
        $this->assertEquals(1.0, $deviceToken->trust_score);
        $this->assertTrue($deviceToken->is_trusted);
        
        // Test lower bound
        $deviceToken->updateTrustScore(-0.5); // Try to set below 0.0
        $this->assertEquals(0.0, $deviceToken->trust_score);
        $this->assertFalse($deviceToken->is_trusted);
    }

    public function test_device_statistics_calculation_accuracy()
    {
        // Create devices with known characteristics
        $trustedCount = 3;
        $untrustedCount = 2;
        
        // Create trusted devices
        for ($i = 0; $i < $trustedCount; $i++) {
            $fingerprint = $this->sampleFingerprint;
            $fingerprint['screen']['width'] = 1920 + $i;
            
            $token = $this->deviceTokenService->generateToken($fingerprint);
            $deviceToken = $this->deviceTokenService->registerToken($token, $fingerprint);
            $deviceToken->updateTrustScore(0.8); // Make trusted
        }
        
        // Create untrusted devices
        for ($i = 0; $i < $untrustedCount; $i++) {
            $fingerprint = $this->sampleFingerprint;
            $fingerprint['screen']['width'] = 2000 + $i;
            
            $token = $this->deviceTokenService->generateToken($fingerprint);
            $deviceToken = $this->deviceTokenService->registerToken($token, $fingerprint);
            $deviceToken->updateTrustScore(0.3); // Keep untrusted
        }
        
        $stats = $this->deviceTokenService->getDeviceStatistics();
        
        $this->assertEquals($trustedCount + $untrustedCount, $stats['total_devices']);
        $this->assertEquals($trustedCount, $stats['trusted_devices']);
        $this->assertEquals($trustedCount + $untrustedCount, $stats['active_24h']);
        
        $expectedTrustPercentage = ($trustedCount / ($trustedCount + $untrustedCount)) * 100;
        $this->assertEquals($expectedTrustPercentage, $stats['trust_percentage']);
    }

    public function test_reputation_calculation_performance_with_large_dataset()
    {
        $startTime = microtime(true);
        
        // Create many devices and update their reputations
        for ($i = 0; $i < 100; $i++) {
            $fingerprint = $this->sampleFingerprint;
            $fingerprint['screen']['width'] = 1920 + $i;
            
            $token = $this->deviceTokenService->generateToken($fingerprint);
            $this->deviceTokenService->registerToken($token, $fingerprint);
            
            // Add multiple reputation updates
            for ($j = 0; $j < 10; $j++) {
                $this->deviceTokenService->updateReputation($token, $j % 3 === 0); // Mix of accurate/inaccurate
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(10.0, $executionTime, 'Reputation calculation should be performant');
        
        // Verify all devices were created and updated
        $this->assertEquals(100, DeviceToken::count());
        
        // Verify reputation scores are within bounds
        $devices = DeviceToken::all();
        foreach ($devices as $device) {
            $this->assertGreaterThanOrEqual(0.0, $device->reputation_score);
            $this->assertLessThanOrEqual(1.0, $device->reputation_score);
            $this->assertEquals(10, $device->total_contributions);
        }
    }
}