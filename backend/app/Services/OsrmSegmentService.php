<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Support\Facades\Http;

class OsrmSegmentService
{
    public function __construct(
        private readonly RouteGeometryService $geometry,
    ) {}

    public function computeForRoute(Route $route): void
    {
        $stops = $route->stops()->orderBy('sequence')->get();
        if ($stops->count() < 2) {
            return;
        }

        $baseUrl = (string) config('services.osrm.base_url', '');

        if ($baseUrl === '') {
            $this->computeHaversineFallback($stops);
            return;
        }

        foreach ($stops as $i => $stop) {
            if ($i >= $stops->count() - 1) {
                break;
            }

            $next = $stops[$i + 1];
            $this->computeSegment($stop, $next, $baseUrl);
        }

        $stops->last()->update([
            'distance_to_next_m' => null,
            'geometry_to_next' => null,
        ]);
    }

    private function computeSegment($from, $to, string $baseUrl): void
    {
        try {
            $response = Http::timeout((float) config('services.osrm.timeout', 5))
                ->get(rtrim($baseUrl, '/') . '/route/v1/driving/' . sprintf('%.7f,%.7f;%.7f,%.7f',
                    (float) $from->lng, (float) $from->lat,
                    (float) $to->lng, (float) $to->lat
                ), [
                    'overview' => 'full',
                    'geometries' => 'polyline',
                ]);

            if (!$response->successful()) {
                $this->computeHaversineFallbackSegment($from, $to);
                return;
            }

            $data = $response->json();
            if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0])) {
                $this->computeHaversineFallbackSegment($from, $to);
                return;
            }

            $routeData = $data['routes'][0];
            $from->update([
                'distance_to_next_m' => round($routeData['distance'], 2),
                'geometry_to_next' => $routeData['geometry'],
            ]);
        } catch (\Throwable) {
            $this->computeHaversineFallbackSegment($from, $to);
        }
    }

    private function computeHaversineFallback($stops): void
    {
        foreach ($stops as $i => $stop) {
            if ($i >= $stops->count() - 1) {
                break;
            }
            $this->computeHaversineFallbackSegment($stop, $stops[$i + 1]);
        }
        $stops->last()->update([
            'distance_to_next_m' => null,
            'geometry_to_next' => null,
        ]);
    }

    private function computeHaversineFallbackSegment($from, $to): void
    {
        $distance = $this->geometry->haversineMeters(
            (float) $from->lat, (float) $from->lng,
            (float) $to->lat, (float) $to->lng,
        );
        $from->update([
            'distance_to_next_m' => round($distance, 2),
            'geometry_to_next' => null,
        ]);
    }
}
