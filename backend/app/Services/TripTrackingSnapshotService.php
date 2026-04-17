<?php

namespace App\Services;

use App\Models\RouteStop;
use App\Models\Trip;
use Illuminate\Support\Collection;

class TripTrackingSnapshotService
{
    private const CURRENT_STOP_RADIUS_METERS = 160.0;
    private const TERMINAL_STOP_RADIUS_METERS = 220.0;
    private const PASSED_BUFFER_METERS = 30.0;
    private const MIN_ETA_SPEED_MPS = 2.5;

    public function __construct(
        private readonly RouteDistanceService $routeDistanceService,
        private readonly RouteGeometryService $routeGeometryService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Trip $trip): array
    {
        $route = $trip->route;
        $stops = collect($route?->stops)->sortBy('sequence')->values();
        $progressDistance = $trip->progress_distance_m !== null ? (float) $trip->progress_distance_m : 0.0;
        $location = $trip->latestLocation ?? null;
        $currentLat = $location?->lat ?? $trip->current_lat;
        $currentLng = $location?->lng ?? $trip->current_lng;
        $shape = $route
            ? $this->routeGeometryService->buildShapeMetrics($this->routeGeometryService->normalizePolyline($route, $stops))
            : [];
        $stopMetrics = collect($this->routeGeometryService->buildStopMetrics($stops, $shape))->keyBy('id');
        $stopStates = $this->buildStopStates($trip, $stops, $stopMetrics, $progressDistance, $currentLat, $currentLng);
        $distanceSnapshot = $this->buildDistanceSnapshot(
            $stops,
            $stopMetrics,
            $trip,
            $progressDistance,
            $currentLat,
            $currentLng,
        );

        return [
            'tracking_status' => $this->resolveTrackingStatus($trip),
            'current_stop_id' => $trip->current_stop_id,
            'next_stop_id' => $trip->next_stop_id,
            'progress_distance_m' => $trip->progress_distance_m !== null ? (float) $trip->progress_distance_m : null,
            ...$distanceSnapshot,
            'stop_states' => $stopStates,
        ];
    }

    /**
     * @param  Collection<int, RouteStop>  $stops
     * @return array<int, array<string, mixed>>
     */
    private function buildStopStates(
        Trip $trip,
        Collection $stops,
        Collection $stopMetrics,
        float $progressDistance,
        mixed $currentLat,
        mixed $currentLng,
    ): array {
        $states = [];
        $lastIndex = $stops->count() - 1;
        $nextStopId = $trip->next_stop_id;
        $currentStopId = $trip->current_stop_id;

        foreach ($stops as $index => $stop) {
            $distanceAlongRoute = (float) (($stopMetrics->get($stop->id)['distance_along_route_m'] ?? $stop->distance_along_route_m) ?? 0.0);
            $state = 'upcoming';
            $statusText = 'Upcoming';

            if ($distanceAlongRoute <= ($progressDistance - self::PASSED_BUFFER_METERS)) {
                $state = 'passed';
                $statusText = 'Passed';
            }

            if ($stop->id === $currentStopId && !$trip->is_off_route) {
                $state = 'current';
                $statusText = 'Currently Here';
            } elseif ($stop->id === $nextStopId) {
                $state = 'approaching';
                $statusText = $trip->is_off_route ? 'Waiting to rejoin route' : 'Approaching';
            } elseif ($index === $lastIndex && $state === 'upcoming') {
                $state = 'destination';
                $statusText = 'Final Stop';
            }

            if ($trip->is_off_route && $stop->id === $nextStopId) {
                $statusText = 'Off route - progress frozen';
            }

            if ($currentLat !== null && $currentLng !== null && $stop->id === $currentStopId) {
                $radius = ($index === 0 || $index === $lastIndex)
                    ? self::TERMINAL_STOP_RADIUS_METERS
                    : self::CURRENT_STOP_RADIUS_METERS;
                $distanceToStop = $this->routeGeometryService->haversineMeters(
                    (float) $currentLat,
                    (float) $currentLng,
                    (float) $stop->lat,
                    (float) $stop->lng,
                );

                if ($distanceToStop > $radius && $trip->tracking_status === 'backward') {
                    $statusText = 'Returning';
                }
            }

            $states[] = [
                'stop_id' => $stop->id,
                'state' => $state,
                'status_text' => $statusText,
                'distance_along_route_m' => round($distanceAlongRoute, 2),
            ];
        }

        return $states;
    }

    /**
     * @return array{distance_to_next_stop_m: ?float, eta_to_next_stop_seconds: ?int, eta_to_destination_seconds: ?int, osrm_distance_to_next_stop_m: ?float}
     */
    private function buildDistanceSnapshot(
        Collection $stops,
        Collection $stopMetrics,
        Trip $trip,
        float $progressDistance,
        mixed $currentLat,
        mixed $currentLng,
    ): array {
        $nextStop = $stops->firstWhere('id', $trip->next_stop_id);
        $distanceToNextStop = $nextStop
            ? max(0.0, (float) (($stopMetrics->get($nextStop->id)['distance_along_route_m'] ?? 0.0)) - $progressDistance)
            : null;
        $distanceToDestination = $stops->isNotEmpty()
            ? max(0.0, (float) (($stopMetrics->get($stops->last()?->id)['distance_along_route_m'] ?? 0.0)) - $progressDistance)
            : null;
        $smoothedSpeedMps = $this->estimateSmoothedSpeedMps($trip);

        return [
            'distance_to_next_stop_m' => $distanceToNextStop !== null ? round($distanceToNextStop, 2) : null,
            'eta_to_next_stop_seconds' => $this->estimateEtaSeconds($distanceToNextStop, $smoothedSpeedMps),
            'eta_to_destination_seconds' => $this->estimateEtaSeconds($distanceToDestination, $smoothedSpeedMps),
            'osrm_distance_to_next_stop_m' => $this->optionalOsrmDistance($currentLat, $currentLng, $nextStop),
        ];
    }

    private function resolveTrackingStatus(Trip $trip): string
    {
        if ($trip->last_location_at === null || $trip->current_lat === null || $trip->current_lng === null) {
            return 'no_gps';
        }

        if ($trip->is_off_route) {
            return 'off_route';
        }

        if ($trip->tracking_status === 'backward') {
            return 'backward';
        }

        if ($trip->current_stop_id !== null) {
            return 'at_stop';
        }

        return 'on_route';
    }

    private function optionalOsrmDistance(mixed $currentLat, mixed $currentLng, ?RouteStop $nextStop): ?float
    {
        if ($nextStop === null || $currentLat === null || $currentLng === null) {
            return null;
        }

        return round($this->routeDistanceService->betweenPoints(
            (float) $currentLat,
            (float) $currentLng,
            (float) $nextStop->lat,
            (float) $nextStop->lng,
        ), 2);
    }

    private function estimateEtaSeconds(?float $distanceMeters, ?float $smoothedSpeedMps): ?int
    {
        if ($distanceMeters === null || $distanceMeters <= 0 || $smoothedSpeedMps === null || $smoothedSpeedMps <= 0) {
            return null;
        }

        return (int) ceil($distanceMeters / max(self::MIN_ETA_SPEED_MPS, $smoothedSpeedMps));
    }

    private function estimateSmoothedSpeedMps(Trip $trip): ?float
    {
        $locations = $trip->locations()
            ->orderBy('recorded_at', 'desc')
            ->limit(6)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        if ($locations->count() < 2) {
            $latestSpeed = $trip->latestLocation?->speed;

            return $latestSpeed !== null ? max(0.0, (float) $latestSpeed) : null;
        }

        $speeds = [];
        foreach ($locations as $index => $location) {
            if ($index === 0) {
                continue;
            }

            $previous = $locations[$index - 1];
            $deltaSeconds = max(1, $location->recorded_at->diffInSeconds($previous->recorded_at));
            $distanceMeters = $this->routeGeometryService->haversineMeters(
                (float) $previous->lat,
                (float) $previous->lng,
                (float) $location->lat,
                (float) $location->lng,
            );
            $speedMps = $distanceMeters / $deltaSeconds;
            if ($speedMps > 0) {
                $speeds[] = $speedMps;
            }
        }

        if ($speeds === []) {
            $latestSpeed = $trip->latestLocation?->speed;

            return $latestSpeed !== null ? max(0.0, (float) $latestSpeed) : null;
        }

        return array_sum($speeds) / count($speeds);
    }
}
