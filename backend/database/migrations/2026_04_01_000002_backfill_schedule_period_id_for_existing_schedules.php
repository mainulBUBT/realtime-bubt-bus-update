<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fallbackPeriodId = DB::table('schedule_periods')
            ->where('is_active', true)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->value('id');

        if ($fallbackPeriodId === null) {
            $fallbackPeriodId = DB::table('schedule_periods')
                ->where('is_active', true)
                ->orderByDesc('start_date')
                ->value('id');
        }

        if ($fallbackPeriodId === null) {
            $fallbackPeriodId = DB::table('schedule_periods')
                ->orderByDesc('start_date')
                ->value('id');
        }

        if ($fallbackPeriodId === null) {
            return;
        }

        DB::table('schedules')
            ->leftJoin('routes', 'routes.id', '=', 'schedules.route_id')
            ->whereNull('schedules.schedule_period_id')
            ->select('schedules.id as schedule_id', 'routes.schedule_period_id as route_schedule_period_id')
            ->orderBy('schedules.id')
            ->get()
            ->each(function ($schedule) use ($fallbackPeriodId) {
                DB::table('schedules')
                    ->where('id', $schedule->schedule_id)
                    ->update([
                        'schedule_period_id' => $schedule->route_schedule_period_id ?: $fallbackPeriodId,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty so we do not erase backfilled production data.
    }
};
