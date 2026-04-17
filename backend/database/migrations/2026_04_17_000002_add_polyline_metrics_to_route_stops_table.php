<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->decimal('distance_along_route_m', 10, 2)->nullable()->after('sequence');
            $table->unsignedInteger('shape_index')->nullable()->after('distance_along_route_m');
            $table->index(['route_id', 'distance_along_route_m'], 'idx_route_stops_route_distance');
        });

        $routes = DB::table('routes')->select('id', 'polyline')->get();
        foreach ($routes as $route) {
            $stops = collect(DB::table('route_stops')
                ->where('route_id', $route->id)
                ->orderBy('sequence')
                ->get());

            if ($stops->isEmpty()) {
                continue;
            }

            $polyline = $this->normalizePolyline($route->polyline, $stops);
            $shape = $this->buildShapeMetrics($polyline);

            foreach ($stops as $stop) {
                $projection = $this->projectPointOntoShape((float) $stop->lat, (float) $stop->lng, $shape);

                DB::table('route_stops')
                    ->where('id', $stop->id)
                    ->update([
                        'distance_along_route_m' => round($projection['distance_along_route_m'], 2),
                        'shape_index' => $projection['shape_index'],
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropIndex('idx_route_stops_route_distance');
            $table->dropColumn(['distance_along_route_m', 'shape_index']);
        });
    }

    /**
     * @param  mixed  $rawPolyline
     * @param  Collection<int, object>  $stops
     * @return array<int, array{lat: float, lng: float}>
     */
    private function normalizePolyline(mixed $rawPolyline, Collection $stops): array
    {
        $decoded = is_string($rawPolyline) ? json_decode($rawPolyline, true) : $rawPolyline;
        if (!is_array($decoded) || count($decoded) < 2) {
            $decoded = $stops->map(fn ($stop) => [
                'lat' => (float) $stop->lat,
                'lng' => (float) $stop->lng,
            ])->values()->all();
        }

        return array_values(array_filter(array_map(function ($point) {
            $lat = is_array($point) ? ($point['lat'] ?? null) : ($point->lat ?? null);
            $lng = is_array($point) ? ($point['lng'] ?? null) : ($point->lng ?? null);

            if (!is_numeric($lat) || !is_numeric($lng)) {
                return null;
            }

            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }, $decoded)));
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $polyline
     * @return array<int, array{lat: float, lng: float, distance_along_route_m: float}>
     */
    private function buildShapeMetrics(array $polyline): array
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
     * @return array{distance_along_route_m: float, shape_index: int}
     */
    private function projectPointOntoShape(float $lat, float $lng, array $shape): array
    {
        if (count($shape) < 2) {
            return ['distance_along_route_m' => 0.0, 'shape_index' => 0];
        }

        $best = ['distance' => INF, 'distance_along_route_m' => 0.0, 'shape_index' => 0];
        for ($i = 0; $i < count($shape) - 1; $i++) {
            $segment = $this->projectOntoSegment(
                $lat,
                $lng,
                $shape[$i]['lat'],
                $shape[$i]['lng'],
                $shape[$i + 1]['lat'],
                $shape[$i + 1]['lng']
            );

            if ($segment['distance'] < $best['distance']) {
                $segmentLength = max(
                    0.0,
                    $shape[$i + 1]['distance_along_route_m'] - $shape[$i]['distance_along_route_m']
                );
                $best = [
                    'distance' => $segment['distance'],
                    'distance_along_route_m' => $shape[$i]['distance_along_route_m'] + ($segmentLength * $segment['t']),
                    'shape_index' => $i,
                ];
            }
        }

        return [
            'distance_along_route_m' => $best['distance_along_route_m'],
            'shape_index' => $best['shape_index'],
        ];
    }

    /**
     * @return array{distance: float, t: float}
     */
    private function projectOntoSegment(
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
        $segLenSq = ($segX ** 2) + ($segY ** 2);
        if ($segLenSq === 0.0) {
            return [
                'distance' => hypot($point['x'] - $start['x'], $point['y'] - $start['y']),
                't' => 0.0,
            ];
        }

        $rawT = ((($point['x'] - $start['x']) * $segX) + (($point['y'] - $start['y']) * $segY)) / $segLenSq;
        $t = max(0.0, min(1.0, $rawT));
        $projectionX = $start['x'] + ($segX * $t);
        $projectionY = $start['y'] + ($segY * $t);

        return [
            'distance' => hypot($point['x'] - $projectionX, $point['y'] - $projectionY),
            't' => $t,
        ];
    }

    /**
     * @return array{x: float, y: float}
     */
    private function projectToMeters(float $lat, float $lng, float $originLat): array
    {
        return [
            'x' => $lng * (111320 * cos(deg2rad($originLat))),
            'y' => $lat * 110540,
        ];
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusM = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusM * 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }
};
