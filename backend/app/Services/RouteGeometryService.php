<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Support\Collection;

class RouteGeometryService
{
    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    public function normalizePolyline(Route $route, ?Collection $stops = null): array
    {
        $points = is_array($route->polyline) ? $route->polyline : [];
        if (count($points) < 2) {
            $points = ($stops ?? $route->stops ?? collect())
                ->sortBy('sequence')
                ->map(fn ($stop) => ['lat' => (float) $stop->lat, 'lng' => (float) $stop->lng])
                ->values()
                ->all();
        }

        return array_values(array_filter(array_map(function ($point) {
            $lat = is_array($point) ? ($point['lat'] ?? null) : ($point->lat ?? null);
            $lng = is_array($point) ? ($point['lng'] ?? null) : ($point->lng ?? null);

            if (!is_numeric($lat) || !is_numeric($lng)) {
                return null;
            }

            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }, $points)));
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $polyline
     * @return array<int, array{lat: float, lng: float, distance_along_route_m: float}>
     */
    public function buildShapeMetrics(array $polyline): array
    {
        $distance = 0.0;
        $shape = [];

        foreach ($polyline as $index => $point) {
            if ($index > 0) {
                $prev = $polyline[$index - 1];
                $distance += $this->haversineMeters($prev['lat'], $prev['lng'], $point['lat'], $point['lng']);
            }

            $shape[] = [
                'lat' => $point['lat'],
                'lng' => $point['lng'],
                'distance_along_route_m' => $distance,
            ];
        }

        return $shape;
    }

    /**
     * @param  array<int, array{lat: float, lng: float, distance_along_route_m: float}>  $shape
     * @return array{segment_index: int, distance: float, raw_t: float, t: float, distance_along_route_m: float}
     */
    public function projectPointOntoShape(float $lat, float $lng, array $shape, ?int $minSegmentIndex = null): array
    {
        if (count($shape) < 2) {
            return [
                'segment_index' => 0,
                'distance' => INF,
                'raw_t' => 0.0,
                't' => 0.0,
                'distance_along_route_m' => 0.0,
            ];
        }

        $startIndex = max(0, $minSegmentIndex ?? 0);
        $best = [
            'segment_index' => 0,
            'distance' => INF,
            'raw_t' => 0.0,
            't' => 0.0,
            'distance_along_route_m' => 0.0,
        ];

        for ($i = $startIndex; $i < count($shape) - 1; $i++) {
            $segment = $this->projectOntoSegment(
                $lat,
                $lng,
                $shape[$i]['lat'],
                $shape[$i]['lng'],
                $shape[$i + 1]['lat'],
                $shape[$i + 1]['lng']
            );

            $segmentLength = max(0.0, $shape[$i + 1]['distance_along_route_m'] - $shape[$i]['distance_along_route_m']);
            $candidate = [
                'segment_index' => $i,
                'distance' => $segment['distance'],
                'raw_t' => $segment['raw_t'],
                't' => $segment['t'],
                'distance_along_route_m' => $shape[$i]['distance_along_route_m'] + ($segmentLength * $segment['t']),
            ];

            if ($candidate['distance'] < $best['distance']) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, RouteStop>  $stops
     * @param  array<int, array{lat: float, lng: float, distance_along_route_m: float}>  $shape
     * @return array<int, array{id: int, distance_along_route_m: float, shape_index: int}>
     */
    public function buildStopMetrics(Collection $stops, array $shape): array
    {
        return $stops->sortBy('sequence')->values()->map(function (RouteStop $stop) use ($shape): array {
            $distance = $stop->distance_along_route_m;
            $shapeIndex = $stop->shape_index;

            if ($distance === null || $shapeIndex === null) {
                $projection = $this->projectPointOntoShape((float) $stop->lat, (float) $stop->lng, $shape);
                $distance = $projection['distance_along_route_m'];
                $shapeIndex = $projection['segment_index'];
            }

            return [
                'id' => $stop->id,
                'distance_along_route_m' => (float) $distance,
                'shape_index' => (int) $shapeIndex,
            ];
        })->all();
    }

    public function syncRouteStopMetrics(Route $route): void
    {
        $route->loadMissing(['stops' => fn ($query) => $query->orderBy('sequence')]);
        $stops = $route->stops;
        if ($stops->isEmpty()) {
            return;
        }

        $shape = $this->buildShapeMetrics($this->normalizePolyline($route, $stops));
        foreach ($stops as $stop) {
            $projection = $this->projectPointOntoShape((float) $stop->lat, (float) $stop->lng, $shape);
            $stop->update([
                'distance_along_route_m' => round($projection['distance_along_route_m'], 2),
                'shape_index' => $projection['segment_index'],
            ]);
        }
    }

    /**
     * @return array{distance: float, raw_t: float, t: float}
     */
    public function projectOntoSegment(
        float $lat,
        float $lng,
        float $startLat,
        float $startLng,
        float $endLat,
        float $endLng,
    ): array {
        $originLat = ($lat + $startLat + $endLat) / 3;
        $point = $this->projectToMeters($lat, $lng, $originLat);
        $start = $this->projectToMeters($startLat, $startLng, $originLat);
        $end = $this->projectToMeters($endLat, $endLng, $originLat);

        $segX = $end['x'] - $start['x'];
        $segY = $end['y'] - $start['y'];
        $segmentLengthSquared = ($segX ** 2) + ($segY ** 2);
        if ($segmentLengthSquared === 0.0) {
            return [
                'distance' => hypot($point['x'] - $start['x'], $point['y'] - $start['y']),
                'raw_t' => 0.0,
                't' => 0.0,
            ];
        }

        $rawT = ((($point['x'] - $start['x']) * $segX) + (($point['y'] - $start['y']) * $segY)) / $segmentLengthSquared;
        $t = max(0.0, min(1.0, $rawT));
        $projectionX = $start['x'] + ($segX * $t);
        $projectionY = $start['y'] + ($segY * $t);

        return [
            'distance' => hypot($point['x'] - $projectionX, $point['y'] - $projectionY),
            'raw_t' => $rawT,
            't' => $t,
        ];
    }

    /**
     * @return array{x: float, y: float}
     */
    public function projectToMeters(float $lat, float $lng, float $originLat): array
    {
        return [
            'x' => $lng * (111320 * cos(deg2rad($originLat))),
            'y' => $lat * 110540,
        ];
    }

    public function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusM = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusM * 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }
}
