<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RouteDistanceService
{
    public function __construct(
        private readonly RouteGeometryService $routeGeometryService,
    ) {
    }

    public function betweenPoints(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $baseUrl = (string) config('services.osrm.base_url', '');

        if ($baseUrl !== '' && !app()->environment('testing')) {
            try {
                $response = Http::timeout((float) config('services.osrm.timeout', 2.5))
                    ->get(rtrim($baseUrl, '/') . "/route/v1/driving/{$fromLng},{$fromLat};{$toLng},{$toLat}", [
                        'overview' => 'false',
                    ]);

                if ($response->successful() && $response->json('code') === 'Ok') {
                    $distance = $response->json('routes.0.distance');

                    if (is_numeric($distance)) {
                        return (float) $distance;
                    }
                }
            } catch (\Throwable) {
                // Fall back to straight-line distance when OSRM is unavailable.
            }
        }

        return $this->routeGeometryService->haversineMeters($fromLat, $fromLng, $toLat, $toLng);
    }
}
