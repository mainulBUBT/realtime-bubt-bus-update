<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * User Tracking Session Model
 * Manages GPS tracking sessions for individual users
 */
class UserTrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_token',
        'device_token_hash',
        'bus_id',
        'session_id',
        'started_at',
        'ended_at',
        'is_active',
        'trust_score_at_start',
        'locations_contributed',
        'valid_locations',
        'average_accuracy',
        'total_distance_covered',
        'session_metadata'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
        'trust_score_at_start' => 'float',
        'locations_contributed' => 'integer',
        'valid_locations' => 'integer',
        'average_accuracy' => 'float',
        'total_distance_covered' => 'float',
        'session_metadata' => 'array'
    ];

    /**
     * Get the device token associated with this session
     */
    public function deviceToken()
    {
        return $this->belongsTo(DeviceToken::class, 'device_token_hash', 'token_hash');
    }
    
    /**
     * Get the device token record by the plain token
     */
    public function deviceTokenRecord()
    {
        return $this->belongsTo(DeviceToken::class, 'device_token', 'token_hash');
    }

    /**
     * Get the bus schedule for this session
     */
    public function busSchedule()
    {
        return $this->belongsTo(BusSchedule::class, 'bus_id', 'bus_id');
    }

    /**
     * Get all bus locations for this session
     */
    public function busLocations()
    {
        return $this->hasMany(BusLocation::class, 'session_id', 'session_id');
    }

    /**
     * Check if session is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->ended_at === null;
    }

    /**
     * Calculate session duration in minutes
     */
    public function getDurationMinutes(): ?float
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    /**
     * Calculate accuracy rate for this session
     */
    public function getAccuracyRate(): float
    {
        if ($this->locations_contributed === 0) {
            return 0.0;
        }

        return ($this->valid_locations / $this->locations_contributed) * 100;
    }

    /**
     * Get session quality score
     */
    public function getQualityScore(): float
    {
        $accuracyRate = $this->getAccuracyRate();
        $durationMinutes = $this->getDurationMinutes() ?? 0;
        $avgAccuracy = $this->average_accuracy ?? 100;

        // Base score from accuracy rate
        $score = ($accuracyRate / 100) * 0.4;

        // Bonus for longer sessions (up to 60 minutes)
        $durationScore = min(1.0, $durationMinutes / 60) * 0.3;
        $score += $durationScore;

        // Bonus for better GPS accuracy
        $gpsAccuracyScore = max(0, (100 - $avgAccuracy) / 100) * 0.3;
        $score += $gpsAccuracyScore;

        return min(1.0, $score);
    }

    /**
     * End the tracking session
     */
    public function endSession(): void
    {
        $this->update([
            'ended_at' => now(),
            'is_active' => false
        ]);
    }

    /**
     * Update session statistics
     */
    public function updateStats(array $stats): void
    {
        $this->update([
            'locations_contributed' => $stats['locations_contributed'] ?? $this->locations_contributed,
            'valid_locations' => $stats['valid_locations'] ?? $this->valid_locations,
            'average_accuracy' => $stats['average_accuracy'] ?? $this->average_accuracy,
            'total_distance_covered' => $stats['total_distance_covered'] ?? $this->total_distance_covered
        ]);
    }

    /**
     * Get active sessions for a specific bus
     */
    public static function getActiveSessionsForBus(string $busId)
    {
        return static::where('bus_id', $busId)
            ->where('is_active', true)
            ->where('started_at', '>', now()->subHours(2))
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get active sessions for a device
     */
    public static function getActiveSessionsForDevice(string $deviceToken)
    {
        return static::where('device_token', $deviceToken)
            ->where('is_active', true)
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Clean up old inactive sessions
     */
    public static function cleanupOldSessions(): int
    {
        // End sessions that have been inactive for more than 2 hours
        $staleActiveSessions = static::where('is_active', true)
            ->where('started_at', '<', now()->subHours(2))
            ->get();

        foreach ($staleActiveSessions as $session) {
            $session->endSession();
        }

        // Delete very old sessions (older than 30 days)
        $deletedCount = static::where('created_at', '<', now()->subDays(30))->delete();

        return $staleActiveSessions->count() + $deletedCount;
    }

    /**
     * Get session statistics for monitoring
     */
    public static function getSessionStatistics(): array
    {
        return [
            'active_sessions' => static::where('is_active', true)->count(),
            'sessions_today' => static::whereDate('started_at', today())->count(),
            'sessions_this_week' => static::where('started_at', '>', now()->subWeek())->count(),
            'average_session_duration' => static::whereNotNull('ended_at')
                ->where('started_at', '>', now()->subWeek())
                ->get()
                ->avg(function ($session) {
                    return $session->getDurationMinutes();
                }),
            'average_accuracy_rate' => static::where('started_at', '>', now()->subWeek())
                ->get()
                ->avg(function ($session) {
                    return $session->getAccuracyRate();
                }),
            'high_quality_sessions' => static::where('started_at', '>', now()->subWeek())
                ->get()
                ->filter(function ($session) {
                    return $session->getQualityScore() > 0.7;
                })
                ->count()
        ];
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('ended_at');
    }

    /**
     * Scope for sessions within time range
     */
    public function scopeWithinTimeRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('started_at', [$start, $end]);
    }

    /**
     * Scope for high quality sessions
     */
    public function scopeHighQuality($query, float $threshold = 0.7)
    {
        return $query->where('valid_locations', '>', 0)
            ->whereRaw('(valid_locations / locations_contributed) >= ?', [$threshold]);
    }
}