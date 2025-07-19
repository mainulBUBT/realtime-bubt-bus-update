<?php

namespace App\Support;

class Cluster
{
    private float $epsilon;
    private int $minPoints;

    public function __construct(float $epsilon = 60.0, int $minPoints = 2)
    {
        $this->epsilon = $epsilon; // meters
        $this->minPoints = $minPoints;
    }

    /**
     * Perform DBSCAN clustering on GPS coordinates
     * 
     * @param array $points Array of ['lat' => float, 'lng' => float, 'bus_id' => int]
     * @return array Clustered points with centroids
     */
    public function cluster(array $points): array
    {
        if (empty($points)) {
            return [];
        }

        $clusters = [];
        $visited = [];
        $clusterId = 0;

        foreach ($points as $index => $point) {
            if (isset($visited[$index])) {
                continue;
            }

            $neighbors = $this->getNeighbors($point, $points);
            
            if (count($neighbors) < $this->minPoints) {
                // Mark as noise
                $visited[$index] = true;
                continue;
            }

            // Start new cluster
            $cluster = [];
            $this->expandCluster($point, $neighbors, $cluster, $visited, $points);
            
            if (!empty($cluster)) {
                $clusters[$clusterId] = [
                    'points' => $cluster,
                    'centroid' => $this->calculateCentroid($cluster)
                ];
                $clusterId++;
            }
        }

        return $clusters;
    }

    private function expandCluster(array $point, array $neighbors, array &$cluster, array &$visited, array $allPoints): void
    {
        $cluster[] = $point;
        $pointIndex = array_search($point, $allPoints);
        $visited[$pointIndex] = true;

        foreach ($neighbors as $neighbor) {
            $neighborIndex = array_search($neighbor, $allPoints);
            
            if (!isset($visited[$neighborIndex])) {
                $visited[$neighborIndex] = true;
                $newNeighbors = $this->getNeighbors($neighbor, $allPoints);
                
                if (count($newNeighbors) >= $this->minPoints) {
                    $neighbors = array_merge($neighbors, $newNeighbors);
                }
            }
            
            // Add to cluster if not already in any cluster
            if (!in_array($neighbor, $cluster)) {
                $cluster[] = $neighbor;
            }
        }
    }

    private function getNeighbors(array $point, array $allPoints): array
    {
        $neighbors = [];
        
        foreach ($allPoints as $otherPoint) {
            if ($point === $otherPoint) {
                continue;
            }
            
            $distance = $this->haversineDistance(
                $point['lat'], $point['lng'],
                $otherPoint['lat'], $otherPoint['lng']
            );
            
            if ($distance <= $this->epsilon) {
                $neighbors[] = $otherPoint;
            }
        }
        
        return $neighbors;
    }

    private function calculateCentroid(array $points): array
    {
        if (empty($points)) {
            return ['lat' => 0, 'lng' => 0];
        }

        $totalLat = 0;
        $totalLng = 0;
        $count = count($points);

        foreach ($points as $point) {
            $totalLat += $point['lat'];
            $totalLng += $point['lng'];
        }

        return [
            'lat' => $totalLat / $count,
            'lng' => $totalLng / $count,
            'bus_count' => $count
        ];
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * 
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in meters
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
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

    /**
     * Get clustered bus positions for display
     * 
     * @param array $locations Recent bus locations
     * @return array Processed clusters for frontend
     */
    public function getBusPositions(array $locations): array
    {
        $points = array_map(function ($location) {
            return [
                'lat' => (float) ($location['latitude'] ?? $location['lat']),
                'lng' => (float) ($location['longitude'] ?? $location['lng']),
                'bus_id' => $location['bus_id'],
                'bus_name' => $location['bus_name'] ?? 'Unknown',
                'recorded_at' => $location['recorded_at'] ?? null
            ];
        }, $locations);

        $clusters = $this->cluster($points);
        
        $result = [];
        foreach ($clusters as $cluster) {
            $result[] = [
                'position' => $cluster['centroid'],
                'buses' => $cluster['points'],
                'count' => count($cluster['points'])
            ];
        }

        return $result;
    }
}