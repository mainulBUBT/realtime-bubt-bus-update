<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DeviceToken;
use App\Services\DeviceTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected array $sampleFingerprint;

    protected function setUp(): void
    {
        parent::setUp();
        
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
            ]
        ];
    }

    public function test_register_endpoint_creates_device_token()
    {
        $deviceTokenService = new DeviceTokenService();
        $token = $deviceTokenService->generateToken($this->sampleFingerprint);

        $response = $this->postJson('/api/device-token/register', [
            'token' => $token,
            'fingerprint' => $this->sampleFingerprint
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'device_id',
                        'reputation_score',
                        'trust_score',
                        'is_trusted'
                    ]
                ]);

        $this->assertDatabaseHas('device_tokens', [
            'token_hash' => $token
        ]);
    }

    public function test_register_endpoint_validates_input()
    {
        $response = $this->postJson('/api/device-token/register', [
            'token' => 'invalid',
            'fingerprint' => []
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => 'Validation failed'
                ]);
    }

    public function test_validate_endpoint_validates_existing_token()
    {
        $deviceTokenService = new DeviceTokenService();
        $token = $deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceTokenService->registerToken($token, $this->sampleFingerprint);

        $response = $this->postJson('/api/device-token/validate', [
            'token' => $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'valid' => true
                ])
                ->assertJsonStructure([
                    'valid',
                    'data' => [
                        'reputation_score',
                        'trust_score',
                        'is_trusted',
                        'total_contributions',
                        'last_activity'
                    ]
                ]);
    }

    public function test_validate_endpoint_rejects_invalid_token()
    {
        $response = $this->postJson('/api/device-token/validate', [
            'token' => str_repeat('a', 64) // Valid format but non-existent
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'valid' => false,
                    'error' => 'Token not found or invalid'
                ]);
    }

    public function test_stats_endpoint_returns_statistics()
    {
        // Create some test data
        $deviceTokenService = new DeviceTokenService();
        $token = $deviceTokenService->generateToken($this->sampleFingerprint);
        $deviceTokenService->registerToken($token, $this->sampleFingerprint);

        $response = $this->getJson('/api/device-token/stats');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_devices',
                        'trusted_devices',
                        'active_24h',
                        'active_week',
                        'trust_percentage'
                    ]
                ]);
    }
}