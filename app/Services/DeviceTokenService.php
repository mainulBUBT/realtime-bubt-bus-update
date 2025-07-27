<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Str;

class DeviceTokenService
{
    public function getOrCreateToken(): string
    {
        // For now, generate a simple token
        // In a real implementation, this would use browser fingerprinting
        $sessionToken = session('device_token');
        
        if (!$sessionToken) {
            $sessionToken = Str::random(32);
            session(['device_token' => $sessionToken]);
        }
        
        return $sessionToken;
    }
    
    public function generateToken(array $fingerprint): string
    {
        $fingerprintString = json_encode($fingerprint);
        $token = hash('sha256', $fingerprintString . time());
        
        // Store or update device token
        DeviceToken::updateOrCreate(
            ['token_hash' => hash('sha256', $token)],
            [
                'fingerprint_data' => $fingerprint,
                'reputation_score' => 0.5,
                'trust_score' => 0.5,
                'total_contributions' => 0,
                'accurate_contributions' => 0,
                'last_activity' => now()
            ]
        );
        
        return $token;
    }
    
    public function validateToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        return DeviceToken::where('token_hash', $tokenHash)->exists();
    }
    
    public function getReputationScore(string $token): float
    {
        $tokenHash = hash('sha256', $token);
        $deviceToken = DeviceToken::where('token_hash', $tokenHash)->first();
        
        return $deviceToken ? $deviceToken->reputation_score : 0.5;
    }
    
    public function updateReputation(string $token, float $score): void
    {
        $tokenHash = hash('sha256', $token);
        DeviceToken::where('token_hash', $tokenHash)
            ->update(['reputation_score' => $score]);
    }
}