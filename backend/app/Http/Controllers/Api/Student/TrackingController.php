<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const WEEKDAY_ALIASES = [
        'sun' => 'sunday',
        'sunday' => 'sunday',
        'mon' => 'monday',
        'monday' => 'monday',
        'tue' => 'tuesday',
        'tues' => 'tuesday',
        'tuesday' => 'tuesday',
        'wed' => 'wednesday',
        'weds' => 'wednesday',
        'wednesday' => 'wednesday',
        'thu' => 'thursday',
        'thur' => 'thursday',
        'thurs' => 'thursday',
        'thursday' => 'thursday',
        'fri' => 'friday',
        'friday' => 'friday',
        'sat' => 'saturday',
        'saturday' => 'saturday',
    ];

    /**
     * Get all active routes
     */
    public function routes(Request $request)
    {
        $perPage = $this->perPageFromRequest($request);
        $search = trim($request->string('q')->toString());

        $query = Route::query()
            ->active()
            ->withCount('stops')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($routeQuery) use ($search) {
                $routeQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('origin_name', 'like', '%' . $search . '%')
                    ->orWhere('destination_name', 'like', '%' . $search . '%')
                    ->orWhereHas('stops', function ($stopQuery) use ($search) {
                        $stopQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        $routes = $paginator->getCollection()->map(fn ($route) => [
            'id' => $route->id,
            'name' => $route->name,
            'code' => $route->code,
            'direction' => $route->direction,
            'origin_name' => $route->origin_name,
            'destination_name' => $route->destination_name,
            'schedule_period_id' => $route->schedule_period_id,
            'stops_count' => (int) ($route->stops_count ?? 0),
        ]);

        return response()->json([
            'data' => $routes->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * Get route details with stops
     */
    public function routeDetail(Request $request, $id)
    {
        $route = Route::with(['stops' => function ($query) {
            $query->orderBy('sequence');
        }, 'schedulePeriod'])->findOrFail($id);

        return response()->json($route);
    }

    /**
     * Get all ongoing/active trips
     */
    public function activeTrips(Request $request)
    {
        $trips = Trip::activeToday()
            ->with(['bus', 'route', 'route.stops', 'driver', 'latestLocation'])
            ->get();

        return response()->json($trips);
    }

    /**
     * Get trip details with location history
     */
    public function tripLocations(Request $request, $tripId)
    {
        $trip = Trip::with(['bus', 'route', 'route.stops', 'driver'])
            ->findOrFail($tripId);

        $locations = Location::where('trip_id', $tripId)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'trip' => $trip,
            'locations' => $locations,
        ]);
    }

    /**
     * Get latest location for a trip
     */
    public function latestLocation(Request $request, $tripId)
    {
        $location = Location::where('trip_id', $tripId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$location) {
            return response()->json(['message' => 'No location data'], 404);
        }

        return response()->json($location);
    }

    /**
     * Get today's schedules with route and bus details
     */
    public function schedules(Request $request): JsonResponse
    {
        $showAll = $request->string('filter')->toString() === 'all';
        $todayName = strtolower(now()->englishDayOfWeek);
        $perPage = $this->perPageFromRequest($request);

        $query = Schedule::query()
            ->with([
                'bus',
                'route' => fn ($routeQuery) => $routeQuery->withCount('stops'),
            ])
            ->orderBy('departure_time');

        if ($showAll) {
            $query->activeInCurrentPeriod();
        } else {
            $query->activeToday();
        }

        $paginator = $query->paginate($perPage);

        $schedules = $paginator->getCollection()->map(function ($schedule) use ($todayName) {
            $weekdays = $this->normalizeWeekdays($schedule->weekdays);
            $isToday = \in_array($todayName, $weekdays, true);

            return [
                'id' => $schedule->id,
                'departure_time' => $schedule->departure_time,
                'weekdays' => $weekdays,
                'formatted_weekdays' => $this->formatWeekdays($weekdays),
                'is_today' => $isToday,
                'bus' => $schedule->bus ? [
                    'id' => $schedule->bus->id,
                    'plate_number' => $schedule->bus->plate_number,
                    'code' => $schedule->bus->code,
                    'capacity' => $schedule->bus->capacity,
                ] : null,
                'route' => $schedule->route ? [
                    'id' => $schedule->route->id,
                    'name' => $schedule->route->name,
                    'code' => $schedule->route->code,
                    'direction' => $schedule->route->direction,
                    'origin_name' => $schedule->route->origin_name,
                    'destination_name' => $schedule->route->destination_name,
                    'stops_count' => (int) ($schedule->route->stops_count ?? 0),
                ] : null,
            ];
        });

        return response()->json([
            'data' => $schedules->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    private function perPageFromRequest(Request $request): int
    {
        return min(max($request->integer('per_page', 20), 1), 50);
    }

    /**
     * @return array<string, int|bool>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeWeekdays(mixed $value): array
    {
        $tokens = [];

        if (\is_array($value)) {
            if (array_is_list($value)) {
                $tokens = $value;
            } else {
                foreach ($value as $day => $enabled) {
                    if ($this->isTruthy($enabled)) {
                        $tokens[] = $day;
                    }
                }
            }
        } elseif (\is_string($value)) {
            $raw = trim($value);

            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeWeekdays($decoded);
            }

            $tokens = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } else {
            return [];
        }

        $normalized = [];
        foreach ($tokens as $token) {
            $day = $this->normalizeWeekdayToken($token);

            if ($day !== null) {
                $normalized[$day] = true;
            }
        }

        return array_keys($normalized);
    }

    private function normalizeWeekdayToken(mixed $token): ?string
    {
        if (!\is_string($token) && !\is_numeric($token)) {
            return null;
        }

        $candidate = strtolower(trim((string) $token));

        if ($candidate === '') {
            return null;
        }

        return self::WEEKDAY_ALIASES[$candidate] ?? null;
    }

    private function isTruthy(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_numeric($value)) {
            return (float) $value > 0;
        }

        if (\is_string($value)) {
            return \in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * @param array<int, string> $weekdays
     */
    private function formatWeekdays(array $weekdays): string
    {
        if ($weekdays === [] || \count($weekdays) === 7) {
            return 'All Days';
        }

        return implode(', ', array_map(fn ($day) => ucfirst(substr($day, 0, 3)), $weekdays));
    }
}
