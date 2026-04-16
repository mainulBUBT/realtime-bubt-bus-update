<?php

namespace App\Console\Commands;

use App\Models\Trip;
use Illuminate\Console\Command;

class EndStaleTrips extends Command
{
    protected $signature = 'trips:end-stale {--hours=2 : Trips with no location update for this many hours}';

    protected $description = 'End ongoing trips that have had no location update for the specified hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $stale = Trip::where('status', 'ongoing')
            ->where(function ($q) use ($hours) {
                $q->whereNull('last_location_at')
                    ->orWhere('last_location_at', '<', now()->subHours($hours));
            })
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale trips found.');
            return self::SUCCESS;
        }

        foreach ($stale as $trip) {
            $trip->update([
                'status' => 'completed',
                'ended_at' => $trip->ended_at ?? now(),
            ]);
            $this->line("Ended trip {$trip->id} (bus {$trip->bus_id}, started {$trip->started_at})");
        }

        $this->info("Ended {$stale->count()} stale trip(s).");
        return self::SUCCESS;
    }
}
