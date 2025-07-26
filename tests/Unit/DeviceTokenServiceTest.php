<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DeviceTokenService;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DeviceTokenServiceTest extends TestCase
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

    public function test_generate_token_creates_consistent_hash()
    {
        $token1 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $token2 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        
        $this->assertEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1)); // SHA-256 produces 64 character hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $token1);
    }

    public function test_generate_token_creates_different_hash_for_different_fingerprints()
    {
        $fingerprint2 = $this->sampleFingerprint;
        $fingerprint2['screen']['width'] = 1366; // Change screen width
        
        $token1 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $token2 = $this->deviceTokenService->generateToken($fingerprint2);
        
        $this->assertNotEquals($token1, $token2);
    }

    public function test_validate_token_returns_false_for_invalid_format()
    {
        $this->assertFalse($this->deviceTokenService->validateToken('invalid'));
        $this->assertFalse($this->deviceTokenService->validateToken(''));
        $this->assertFalse($this->deviceTokenService->validateToken('123')); // Too short
        $this->assertFalse($this->deviceTokenService->validateToken(str_repeat('g', 64))); // Invalid hex
    }

    public function test_validate_token_returns_false_for_non_existent_token()
    {
        $validFormatToken = str_repeat('a', 64);
        $this->assertFalse($this->deviceTokenService->validateToken($validFormatToken));
    }

    public function test_register_token_creates_new_device_token()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $this->assertInstanceOf(DeviceToken::class, $deviceToken);
        $this->assertEquals($token, $deviceToken->token_hash);
        $this->assertEquals($this->sampleFingerprint, $deviceToken->fingerprint_data);
        $this->assertEquals(0.5, $deviceToken->reputation_score);
        $this->assertEquals(0.5, $deviceToken->trust_score);
        $this->assertFalse($deviceToken->is_trusted);
    }

    public function test_register_token_updates_existing_device_token()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        
        // Create initial token
        $deviceToken1 = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        $initialId = $deviceToken1->id;
        
        // Update fingerprint data
        $updatedFingerprint = $this->sampleFingerprint;
        $updatedFingerprint['screen']['width'] = 1366;
        
        // Register again with same token but updated fingerprint
        $deviceToken2 = $this->deviceTokenService->registerToken($token, $updatedFingerprint);
        
        $this->assertEquals($initialId, $deviceToken2->id); // Same device token
        $this->assertEquals($updatedFingerprint, $deviceToken2->fingerprint_data);
    }

    public function test_validate_token_returns_true_for_existing_token()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $this->assertTrue($this->deviceTokenService->validateToken($token));
    }

    public function test_get_reputation_score_returns_correct_score()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $score = $this->deviceTokenService->getReputationScore($token);
        $this->assertEquals($deviceToken->reputation_score, $score);
    }

    public function test_get_reputation_score_returns_zero_for_non_existent_token()
    {
        $nonExistentToken = str_repeat('a', 64);
        $score = $this->deviceTokenService->getReputationScore($nonExistentToken);
        $this->assertEquals(0.0, $score);
    }

    public function test_update_reputation_increases_score_for_accurate_data()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $initialScore = $deviceToken->reputation_score;
        
        // Simulate accurate contributions
        $this->deviceTokenService->updateReputation($token, true);
        $this->deviceTokenService->updateReputation($token, true);
        $this->deviceTokenService->updateReputation($token, true);
        
        $deviceToken->refresh();
        $this->assertGreaterThan($initialScore, $deviceToken->reputation_score);
        $this->assertEquals(3, $deviceToken->total_contributions);
        $this->assertEquals(3, $deviceToken->accurate_contributions);
    }

    public function test_update_reputation_decreases_score_for_inaccurate_data()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken = $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        // First make some accurate contributions to establish a baseline
        $this->deviceTokenService->updateReputation($token, true);
        $this->deviceTokenService->updateReputation($token, true);
        $deviceToken->refresh();
        $scoreAfterAccurate = $deviceToken->reputation_score;
        
        // Now add inaccurate contributions
        $this->deviceTokenService->updateReputation($token, false);
        $this->deviceTokenService->updateReputation($token, false);
        
        $deviceToken->refresh();
        $this->assertLessThan($scoreAfterAccurate, $deviceToken->reputation_score);
        $this->assertEquals(4, $deviceToken->total_contributions);
        $this->assertEquals(2, $deviceToken->accurate_contributions);
    }

    public function test_update_clustering_score_updates_device_token()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $this->deviceTokenService->updateClusteringScore($token, 0.8);
        
        $deviceToken = $this->deviceTokenService->getDeviceToken($token);
        $this->assertEquals(0.8, $deviceToken->clustering_score);
    }

    public function test_update_movement_consistency_updates_device_token()
    {
        $token = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $this->deviceTokenService->registerToken($token, $this->sampleFingerprint);
        
        $this->deviceTokenService->updateMovementConsistency($token, 0.9);
        
        $deviceToken = $this->deviceTokenService->getDeviceToken($token);
        $this->assertEquals(0.9, $deviceToken->movement_consistency);
    }

    public function test_get_trusted_devices_returns_only_trusted_devices()
    {
        // Create trusted device
        $token1 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken1 = $this->deviceTokenService->registerToken($token1, $this->sampleFingerprint);
        $deviceToken1->updateTrustScore(0.8); // Make it trusted
        
        // Create untrusted device
        $fingerprint2 = $this->sampleFingerprint;
        $fingerprint2['screen']['width'] = 1366;
        $token2 = $this->deviceTokenService->generateToken($fingerprint2);
        $deviceToken2 = $this->deviceTokenService->registerToken($token2, $fingerprint2);
        $deviceToken2->updateTrustScore(0.3); // Keep it untrusted
        
        $trustedDevices = $this->deviceTokenService->getTrustedDevices();
        
        $this->assertCount(1, $trustedDevices);
        $this->assertEquals($token1, $trustedDevices->first()->token_hash);
    }

    public function test_cleanup_inactive_tokens_removes_old_tokens()
    {
        // Create old inactive token
        $token1 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken1 = $this->deviceTokenService->registerToken($token1, $this->sampleFingerprint);
        $deviceToken1->update(['last_activity' => Carbon::now()->subDays(35)]);
        
        // Create recent token
        $fingerprint2 = $this->sampleFingerprint;
        $fingerprint2['screen']['width'] = 1366;
        $token2 = $this->deviceTokenService->generateToken($fingerprint2);
        $this->deviceTokenService->registerToken($token2, $fingerprint2);
        
        $deletedCount = $this->deviceTokenService->cleanupInactiveTokens(30);
        
        $this->assertEquals(1, $deletedCount);
        $this->assertNull($this->deviceTokenService->getDeviceToken($token1));
        $this->assertNotNull($this->deviceTokenService->getDeviceToken($token2));
    }

    public function test_get_device_statistics_returns_correct_data()
    {
        // Create some test devices
        $token1 = $this->deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceToken1 = $this->deviceTokenService->registerToken($token1, $this->sampleFingerprint);
        $deviceToken1->updateTrustScore(0.8); // Trusted
        
        $fingerprint2 = $this->sampleFingerprint;
        $fingerprint2['screen']['width'] = 1366;
        $token2 = $this->deviceTokenService->generateToken($fingerprint2);
        $deviceToken2 = $this->deviceTokenService->registerToken($token2, $fingerprint2);
        $deviceToken2->updateTrustScore(0.3); // Not trusted
        
        $stats = $this->deviceTokenService->getDeviceStatistics();
        
        $this->assertEquals(2, $stats['total_devices']);
        $this->assertEquals(1, $stats['trusted_devices']);
        $this->assertEquals(2, $stats['active_24h']);
        $this->assertEquals(50.0, $stats['trust_percentage']);
    }

    public function test_validate_fingerprint_data_validates_structure()
    {
        $this->assertTrue($this->deviceTokenService->validateFingerprintData($this->sampleFingerprint));
        
        // Test missing required fields
        $invalidFingerprint = $this->sampleFingerprint;
        unset($invalidFingerprint['screen']);
        $this->assertFalse($this->deviceTokenService->validateFingerprintData($invalidFingerprint));
        
        // Test missing screen dimensions
        $invalidFingerprint = $this->sampleFingerprint;
        unset($invalidFingerprint['screen']['width']);
        $this->assertFalse($this->deviceTokenService->validateFingerprintData($invalidFingerprint));
        
        // Test missing navigator data
        $invalidFingerprint = $this->sampleFingerprint;
        unset($invalidFingerprint['navigator']['userAgent']);
        $this->assertFalse($this->deviceTokenService->validateFingerprintData($invalidFingerprint));
    }

    public function test_process_fingerprint_returns_success_result()
    {
        $result = $this->deviceTokenService->processFingerprint($this->sampleFingerprint);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('device_id', $result);
        $this->assertArrayHasKey('reputation_score', $result);
        $this->assertArrayHasKey('trust_score', $result);
        $this->assertArrayHasKey('is_trusted', $result);
        
        $this->assertEquals(64, strlen($result['token']));
        $this->assertEquals(0.5, $result['reputation_score']);
        $this->assertEquals(0.5, $result['trust_score']);
        $this->assertFalse($result['is_trusted']);
    }

    public function test_process_fingerprint_returns_error_for_invalid_data()
    {
        $invalidFingerprint = ['invalid' => 'data'];
        $result = $this->deviceTokenService->processFingerprint($invalidFingerprint);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
    }
}