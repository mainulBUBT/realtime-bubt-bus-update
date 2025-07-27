<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BusLocationHistory extends Model
{
    protected $table = 'bus_location_history';
    
    protected $fillable = [
        'bus_id',
        'trip_date',
        'location_data',
        'trip_summary'
    ];
    
    protected $casts = [
        'trip_date' => 'date',
        'location_data' => 'array',
        'trip_summary' => 'array'
    ];
    
    /**
     * Get historical data for a specific bus
     */
    public function scopeForBus(Builder $query, string $busId): Builder
    {
        return $query->where('bus_id', $busId);
    }
    
    /**
     * Get historical data for a specific date
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('trip_date', $date->format('Y-m-d'));
    }
    
    /**
     * Get historical data within a date range
     */
    public function scopeDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('trip_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ]);
    }
    
    /**
     * Get recent historical data (last N days)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('trip_date', '>=', now()->subDays($days)->format('Y-m-d'));
    }
    
    /**
     * Get old historical data beyond retention period
     */
    public function scopeOldData(Builder $query, int $retentionDays = 90): Builder
    {
        return $query->where('trip_date', '<', now()->subDays($retentionDays)->format('Y-m-d'));
    }
    
    /**
     * Get trip summary statistics
     */
    public function getTripStats(): array
    {
        $summary = $this->trip_summary ?? [];
        
        return [
            'total_locations' => $summary['total_locations'] ?? 0,
            'trusted_locations' => $summary['trusted_locations'] ?? 0,
            'average_trust' => $summary['average_trust'] ?? 0,
            'first_location' => isset($summary['first_location']) ? Carbon::parse($summary['first_location']) : null,
            'last_location' => isset($summary['last_location']) ? Carbon::parse($summary['last_location']) : null,
            'trip_duration' => $this->getTripDuration(),
            'trust_percentage' => $this->getTrustPercentage()
        ];
    }
    
    /**
     * Calculate trip duration in minutes
     */
    public function getTripDuration(): ?int
    {
        $summary = $this->trip_summary ?? [];
        
        if (!isset($summary['first_location']) || !isset($summary['last_location'])) {
            return null;
        }
        
        $start = Carbon::parse($summary['first_location']);
        $end = Carbon::parse($summary['last_location']);
        
        return $start->diffInMinutes($end);
    }
    
    /**
     * Calculate trust percentage
     */
    public function getTrustPercentage(): float
    {
        $summary = $this->trip_summary ?? [];
        $total = $summary['total_locations'] ?? 0;
        $trusted = $summary['trusted_locations'] ?? 0;
        
        return $total > 0 ? round(($trusted / $total) * 100, 2) : 0;
    }
    
    /**
     * Get location data with filtering options
     */
    public function getLocationData(array $filters = []): array
    {
        $locations = $this->location_data ?? [];
        
        if (empty($filters)) {
            return $locations;
        }
        
        return array_filter($locations, function ($location) use ($filters) {
            // Filter by trust score
            if (isset($filters['min_trust']) && ($location['reputation_weight'] ?? 0) < $filters['min_trust']) {
                return false;
            }
            
            // Filter by validation status
            if (isset($filters['validated_only']) && $filters['validated_only'] && !($location['is_validated'] ?? false)) {
                return false;
            }
            
            // Filter by time range
            if (isset($filters['start_time']) || isset($filters['end_time'])) {
                $locationTime = Carbon::parse($location['created_at'] ?? null);
                
                if (isset($filters['start_time']) && $locationTime->lt(Carbon::parse($filters['start_time']))) {
                    return false;
                }
                
                if (isset($filters['end_time']) && $locationTime->gt(Carbon::parse($filters['end_time']))) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Get route analysis from historical data
     */
    public function getRouteAnalysis(): array
    {
        $locations = $this->location_data ?? [];
        
        if (empty($locations)) {
            return [
                'total_points' => 0,
                'route_coverage' => [],
                'speed_analysis' => [],
                'trust_distribution' => []
            ];
        }
        
        $speeds = array_filter(array_column($locations, 'speed'));
        $trustScores = array_column($locations, 'reputation_weight');
        
        return [
            'total_points' => count($locations),
            'route_coverage' => $this->calculateRouteCoverage($locations),
            'speed_analysis' => [
                'average_speed' => !empty($speeds) ? round(array_sum($speeds) / count($speeds), 2) : 0,
                'max_speed' => !empty($speeds) ? max($speeds) : 0,
                'min_speed' => !empty($speeds) ? min($speeds) : 0
            ],
            'trust_distribution' => [
                'average_trust' => round(array_sum($trustScores) / count($trustScores), 3),
                'high_trust_count' => count(array_filter($trustScores, fn($score) => $score >= 0.7)),
                'low_trust_count' => count(array_filter($trustScores, fn($score) => $score < 0.3))
            ]
        ];
    }
    
    /**
     * Calculate route coverage from location points
     */
    private function calculateRouteCoverage(array $locations): array
    {
        if (count($locations) < 2) {
            return ['total_distance' => 0, 'coverage_points' => 0];
        }
        
        $totalDistance = 0;
        $coveragePoints = 0;
        
        for ($i = 1; $i < count($locations); $i++) {
            $prev = $locations[$i - 1];
            $curr = $locations[$i];
            
            if (isset($prev['latitude'], $prev['longitude'], $curr['latitude'], $curr['longitude'])) {
                $distance = $this->calculateDistance(
                    $prev['latitude'], $prev['longitude'],
                    $curr['latitude'], $curr['longitude']
                );
                
                // Only count reasonable distances (not GPS jumps)
                if ($distance <= 1000) { // Max 1km between points
                    $totalDistance += $distance;
                    $coveragePoints++;
                }
            }
        }
        
        return [
            'total_distance' => round($totalDistance, 2),
            'coverage_points' => $coveragePoints
        ];
    }
    
    /**
     * Calculate distance between two GPS points in meters
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}