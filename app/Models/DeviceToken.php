<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'token_hash',
        'fingerprint_data',
        'reputation_score',
        'trust_score',
        'total_contributions',
        'accurate_contributions',
        'clustering_score',
        'movement_consistency',
        'last_activity',
        'is_trusted'
    ];
    
    protected $casts = [
        'fingerprint_data' => 'array',
        'reputation_score' => 'float',
        'trust_score' => 'float',
        'clustering_score' => 'float',
        'movement_consistency' => 'float',
        'is_trusted' => 'boolean',
        'last_activity' => 'datetime'
    ];
    
    /**
     * Check if this device is considered trusted
     */
    public function isTrustedDevice(): bool
    {
        return $this->trust_score >= 0.7 && $this->is_trusted;
    }
    
    /**
     * Update the trust score and trusted status
     */
    public function updateTrustScore(float $newScore): void
    {
        $this->trust_score = min(1.0, max(0.0, $newScore));
        $this->is_trusted = $this->trust_score >= 0.7;
        $this->save();
    }
    
    /**
     * Update reputation score based on contribution accuracy
     */
    public function updateReputationScore(bool $wasAccurate): void
    {
        $this->total_contributions++;
        
        if ($wasAccurate) {
            $this->accurate_contributions++;
        }
        
        // Calculate new reputation score based on accuracy ratio
        $accuracyRatio = $this->accurate_contributions / $this->total_contributions;
        $this->reputation_score = min(1.0, max(0.0, $accuracyRatio));
        
        $this->save();
    }
}
