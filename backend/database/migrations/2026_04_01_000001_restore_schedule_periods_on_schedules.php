<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('schedules', 'schedule_period_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('schedule_period_id')->nullable()->after('route_id');
            });
        }

        DB::table('schedules')
            ->join('routes', 'routes.id', '=', 'schedules.route_id')
            ->whereNull('schedules.schedule_period_id')
            ->whereNotNull('routes.schedule_period_id')
            ->select('schedules.id as schedule_id', 'routes.schedule_period_id')
            ->orderBy('schedules.id')
            ->get()
            ->each(function ($schedule) {
                DB::table('schedules')
                    ->where('id', $schedule->schedule_id)
                    ->update(['schedule_period_id' => $schedule->schedule_period_id]);
            });

        if (DB::getDriverName() !== 'sqlite') {
            $busIndexes = collect(DB::select("SHOW INDEX FROM schedules WHERE Key_name = 'idx_schedules_bus_id'"));
            $schedulePeriodForeignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'schedules'
                  AND COLUMN_NAME = 'schedule_period_id'
                  AND REFERENCED_TABLE_NAME = 'schedule_periods'
            "));
            $uniqueScheduleColumns = collect(DB::select("SHOW INDEX FROM schedules WHERE Key_name = 'unique_schedule'"))
                ->sortBy('Seq_in_index')
                ->pluck('Column_name')
                ->values()
                ->all();

            if ($busIndexes->isEmpty()) {
                Schema::table('schedules', function (Blueprint $table) {
                    $table->index('bus_id', 'idx_schedules_bus_id');
                });
            }

            if ($schedulePeriodForeignKeys->isEmpty()) {
                Schema::table('schedules', function (Blueprint $table) {
                    $table->foreign('schedule_period_id')
                        ->references('id')
                        ->on('schedule_periods')
                        ->nullOnDelete();
                });
            }

            if ($uniqueScheduleColumns !== ['bus_id', 'route_id', 'schedule_period_id', 'departure_time', 'effective_date']) {
                Schema::table('schedules', function (Blueprint $table) use ($uniqueScheduleColumns) {
                    if ($uniqueScheduleColumns !== []) {
                        $table->dropUnique('unique_schedule');
                    }

                    $table->unique(
                        ['bus_id', 'route_id', 'schedule_period_id', 'departure_time', 'effective_date'],
                        'unique_schedule'
                    );
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('schedules', 'schedule_period_id')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            $busIndexes = collect(DB::select("SHOW INDEX FROM schedules WHERE Key_name = 'idx_schedules_bus_id'"));
            $schedulePeriodForeignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'schedules'
                  AND COLUMN_NAME = 'schedule_period_id'
                  AND REFERENCED_TABLE_NAME = 'schedule_periods'
            "));
            $uniqueScheduleColumns = collect(DB::select("SHOW INDEX FROM schedules WHERE Key_name = 'unique_schedule'"))
                ->sortBy('Seq_in_index')
                ->pluck('Column_name')
                ->values()
                ->all();

            if ($uniqueScheduleColumns !== ['bus_id', 'route_id', 'departure_time', 'effective_date']) {
                Schema::table('schedules', function (Blueprint $table) use ($uniqueScheduleColumns) {
                    if ($uniqueScheduleColumns !== []) {
                        $table->dropUnique('unique_schedule');
                    }

                    $table->unique(
                        ['bus_id', 'route_id', 'departure_time', 'effective_date'],
                        'unique_schedule'
                    );
                });
            }

            if ($schedulePeriodForeignKeys->isNotEmpty()) {
                Schema::table('schedules', function (Blueprint $table) {
                    $table->dropForeign(['schedule_period_id']);
                });
            }

            if ($busIndexes->isNotEmpty()) {
                Schema::table('schedules', function (Blueprint $table) {
                    $table->dropIndex('idx_schedules_bus_id');
                });
            }
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_period_id');
        });
    }
};
