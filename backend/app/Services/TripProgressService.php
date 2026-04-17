<?php

namespace App\Services;

use App\Models\Location;
use App\Models\RouteStop;
use App\Models\Trip;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TripProgressService
{
    private const MIN_GPS_MOVEMENT_METERS = 10.0;
    private const MAX_SPEED_KMH = 120.0;
    private const OFF_ROUTE_DISTANCE_METERS = 100.0;
    private const OFF_ROUTE_CONFIRMATION_COUNT = 3;
    private const BACKWARD_THRESHOLD_METERS = 500.0;
    private const CURRENT_STOP_RADIUS_METERS = 160.0;
    private const TERMINAL_STOP_RADIUS_METERS = 220.0;
    private const PASSED_BUFFER_METERS = 30.0;
    private const DESTINATION_BUFFER_METERS = 50.0;

    public function __construct(
        private readonly RouteGeometryService $routeGeometryService,
    ) {
    }

    public function updateTripProgress(Trip $trip, Location $location): void
    {
        $route = $trip->route()->with(['stops' => fn ($query) => $query->orderBy('sequence')])->first();
        $stops = $route?->stops ?? collect();

        if ($stops->count() === 0) {
            $trip->forceFill([
                'tracking_status' => 'no_gps',
                'current_stop_id' => null,
                'next_stop_id' => null,
                'progress_segment_index' => null,
                'is_off_route' => false,
                'off_route_since' => null,
                'off_route_counter' => 0,
            ])->save();

            return;
        }

        $shape = $this->routeGeometryService->buildShapeMetrics(
            $this->routeGeometryService->normalizePolyline($route, $stops)
        );
        $stopMetrics = collect($this->routeGeometryService->buildStopMetrics($stops, $shape))
            ->keyBy('id');
        $busLat = (float) $location->lat;
        $busLng = (float) $location->lng;

        if ($this->shouldIgnorePoint($trip, $busLat, $busLng, $location->recorded_at)) {
            return;
        }

        $bestProjectedSegment = $this->routeGeometryService->projectPointOntoShape($busLat, $busLng, $shape);
        $bestForwardSegment = null;
        $nearestRouteDistance = $bestProjectedSegment['distance'];
        $lastSegmentIndex = $trip->progress_segment_index;

        if ($bestProjectedSegment['raw_t'] >= 0 && $bestProjectedSegment['raw_t'] <= 1) {
            if ($lastSegmentIndex === null || $bestProjectedSegment['segment_index'] >= $lastSegmentIndex) {
                $bestForwardSegment = $bestProjectedSegment;
            } else {
                $bestForwardSegment = $this->routeGeometryService->projectPointOntoShape($busLat, $busLng, $shape, $lastSegmentIndex);
            }
        }

        $progressDistance = $trip->progress_distance_m !== null ? (float) $trip->progress_distance_m : 0.0;
        $previousProgressDistance = $progressDistance;
        $trackingStatus = 'on_route';
        $lastConfirmedSequence = (int) ($trip->last_confirmed_stop_sequence ?? 0);
        $terminalStopIds = array_values(array_filter([
            $stops->first()?->id,
            $stops->last()?->id,
        ]));

        if ($this->isNearTerminalStop($stops, $busLat, $busLng)) {
            $nearestRouteDistance = 0.0;
        }

        if ($nearestRouteDistance > self::OFF_ROUTE_DISTANCE_METERS) {
            $trip->off_route_counter = (int) $trip->off_route_counter + 1;
            if ((int) $trip->off_route_counter >= self::OFF_ROUTE_CONFIRMATION_COUNT) {
                $trip->is_off_route = true;
                $trip->off_route_since ??= $location->recorded_at;
            }
        } else {
            $trip->off_route_counter = 0;
            $trip->is_off_route = false;
            $trip->off_route_since = null;
        }

        if ($trip->is_off_route) {
            $trackingStatus = 'off_route';
        } elseif ($bestProjectedSegment['distance_along_route_m'] < ($progressDistance - self::BACKWARD_THRESHOLD_METERS)) {
            $trackingStatus = 'backward';
        } elseif ($bestForwardSegment !== null) {
            $progressDistance = max($progressDistance, $bestForwardSegment['distance_along_route_m']);
        }

        $totalRouteDistance = (float) (count($shape) > 0 ? $shape[count($shape) - 1]['distance_along_route_m'] : 0.0);
        if ($progressDistance >= max(0.0, $totalRouteDistance - self::DESTINATION_BUFFER_METERS)) {
            $progressDistance = $totalRouteDistance;
        }

        $stopState = $this->resolveStopProgressState(
            $stops,
            $stopMetrics,
            $busLat,
            $busLng,
            $progressDistance,
            $trackingStatus !== 'backward',
            $lastConfirmedSequence,
            $terminalStopIds,
        );

        if ($stopState['recovered_from_stop_match']) {
            $trip->off_route_counter = 0;
            $trip->is_off_route = false;
            $trip->off_route_since = null;
            $trackingStatus = 'on_route';
        }

        if ($trackingStatus === 'on_route' && $stopState['current_stop_id'] !== null) {
            $trackingStatus = 'at_stop';
        }

        if ($stopState['next_stop_id'] === null && $progressDistance >= max(0.0, $totalRouteDistance - self::DESTINATION_BUFFER_METERS)) {
            $trackingStatus = $stopState['current_stop_id'] !== null ? 'at_stop' : 'on_route';
        }

        $trip->forceFill([
            'last_confirmed_stop_id' => $stopState['last_confirmed_stop_id'],
            'last_confirmed_stop_sequence' => $stopState['last_confirmed_stop_sequence'],
            'current_stop_id' => $stopState['current_stop_id'],
            'next_stop_id' => $stopState['next_stop_id'],
            'progress_segment_index' => $bestForwardSegment['segment_index'] ?? $trip->progress_segment_index,
            'previous_progress_distance_m' => $previousProgressDistance,
            'progress_distance_m' => $progressDistance,
            'tracking_status' => $trackingStatus,
            'last_gps_lat' => $busLat,
            'last_gps_lng' => $busLng,
            'last_gps_at' => $location->recorded_at,
        ])->save();
    }

    public function shouldIgnoreIncomingPoint(Trip $trip, float $busLat, float $busLng, CarbonInterface $recordedAt): bool
    {
        if ($this->isStalePoint($trip, $recordedAt)) {
            return true;
        }

        return $this->shouldIgnorePoint($trip, $busLat, $busLng, $recordedAt);
    }

    private function isStalePoint(Trip $trip, CarbonInterface $recordedAt): bool
    {
        if ($trip->last_gps_at !== null && $recordedAt->lessThanOrEqualTo($trip->last_gps_at)) {
            return true;
        }

        return $trip->last_location_at !== null
            && $recordedAt->lessThanOrEqualTo($trip->last_location_at);
    }

    private function shouldIgnorePoint(Trip $trip, float $busLat, float $busLng, CarbonInterface $recordedAt): bool
    {
        if ($trip->last_gps_lat === null || $trip->last_gps_lng === null || $trip->last_gps_at === null) {
            return false;
        }

        $distanceMeters = $this->routeGeometryService->haversineMeters(
            (float) $trip->last_gps_lat,
            (float) $trip->last_gps_lng,
            $busLat,
            $busLng,
        );

        if ($distanceMeters < self::MIN_GPS_MOVEMENT_METERS) {
            return true;
        }

        $deltaSeconds = max(1, abs($recordedAt->diffInSeconds($trip->last_gps_at, false)));
        $speedKmh = ($distanceMeters / $deltaSeconds) * 3.6;

        return $speedKmh > self::MAX_SPEED_KMH;
    }

    private function resolveStopProgressState(
        Collection $stops,
        Collection $stopMetrics,
        float $busLat,
        float $busLng,
        float $progressDistance,
        bool $allowCurrentStop,
        int $lastConfirmedSequence,
        array $terminalStopIds,
    ): array {
        $passedStop = null;
        $nextStop = null;
        $currentStop = null;
        $bestRadiusMatch = null;
        $bestRadiusDistance = INF;
        $lastIndex = $stops->count() - 1;

        foreach ($stops->sortBy('sequence')->values() as $index => $stop) {
            $metric = $stopMetrics->get($stop->id);
            $distanceAlongRoute = (float) ($metric['distance_along_route_m'] ?? 0.0);
            $radius = ($index === 0 || $index === $lastIndex)
                ? self::TERMINAL_STOP_RADIUS_METERS
                : self::CURRENT_STOP_RADIUS_METERS;
            $distanceToStop = $this->routeGeometryService->haversineMeters($busLat, $busLng, (float) $stop->lat, (float) $stop->lng);

            if ($distanceAlongRoute <= ($progressDistance - self::PASSED_BUFFER_METERS)) {
                $passedStop = $stop;
            }

            if ($allowCurrentStop && $distanceToStop <= $radius && $distanceToStop < $bestRadiusDistance) {
                $bestRadiusDistance = $distanceToStop;
                $bestRadiusMatch = $stop;
            }

            if ($nextStop === null && $distanceAlongRoute > $progressDistance) {
                $nextStop = $stop;
            }
        }

        $recoveredFromStopMatch = false;

        if ($bestRadiusMatch !== null) {
            $isTerminalStop = in_array($bestRadiusMatch->id, $terminalStopIds, true);
            $isNextLogicalStop = $bestRadiusMatch->sequence >= max(1, $lastConfirmedSequence + 1);

            if ($isTerminalStop || $isNextLogicalStop) {
                $currentStop = $bestRadiusMatch;
                $recoveredFromStopMatch = true;
            }
        }

        $lastConfirmedStop = $currentStop ?? $passedStop;

        return [
            'last_confirmed_stop_id' => $lastConfirmedStop?->id,
            'last_confirmed_stop_sequence' => $lastConfirmedStop?->sequence,
            'current_stop_id' => $currentStop?->id,
            'next_stop_id' => $nextStop?->id,
            'recovered_from_stop_match' => $recoveredFromStopMatch,
        ];
    }

    private function isNearTerminalStop(Collection $stops, float $busLat, float $busLng): bool
    {
        $firstStop = $stops->first();
        $lastStop = $stops->last();

        if (!$firstStop || !$lastStop) {
            return false;
        }

        return $this->routeGeometryService->haversineMeters($busLat, $busLng, (float) $firstStop->lat, (float) $firstStop->lng) <= self::TERMINAL_STOP_RADIUS_METERS
            || $this->routeGeometryService->haversineMeters($busLat, $busLng, (float) $lastStop->lat, (float) $lastStop->lng) <= self::TERMINAL_STOP_RADIUS_METERS;
    }
}
