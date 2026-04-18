<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Services\OsrmSegmentService;
use Illuminate\Console\Command;

class ComputeRouteDistances extends Command
{
    protected $signature = 'route:compute-distances 
                            {--route=* : Specific route IDs}
                            {--all : Compute for all routes (default if no --route specified)}
                            {--delay=200 : Milliseconds to wait between OSRM calls}';

    protected $description = 'Compute OSRM road distances and geometry for route segments';

    public function handle(OsrmSegmentService $service): int
    {
        $routeIds = $this->option('route');
        $delayMs = (int) $this->option('delay');

        $routes = $routeIds
            ? Route::whereIn('id', $routeIds)->with('stops')->get()
            : Route::with('stops')->get();

        $total = $routes->count();
        $current = 0;

        if ($total === 0) {
            $this->info('No routes found.');
            return 0;
        }

        $this->info("Computing distances for {$total} route(s)...");

        foreach ($routes as $route) {
            $current++;
            $stopCount = $route->stops->count();

            if ($stopCount < 2) {
                $this->warn("  [{$current}/{$total}] Route {$route->id} ({$route->name}): skipped (less than 2 stops)");
                continue;
            }

            $this->info("  [{$current}/{$total}] Route {$route->id} ({$route->name}): {$stopCount} stops...");

            $service->computeForRoute($route);

            // Rate limit protection: wait before next route
            if ($delayMs > 0 && $current < $total) {
                usleep($delayMs * 1000);
            }
        }

        $this->info('Done!');
        return 0;
    }
}
