<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(
                ['schedule_period_id', 'is_active', 'departure_time'],
                'idx_schedules_period_active_departure'
            );
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->index(['is_active', 'name'], 'idx_routes_active_name');
            $table->index(['is_active', 'code'], 'idx_routes_active_code');
        });

        Schema::table('route_stops', function (Blueprint $table) {
            $table->index(['route_id', 'name'], 'idx_route_stops_route_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropIndex('idx_route_stops_route_name');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex('idx_routes_active_code');
            $table->dropIndex('idx_routes_active_name');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_period_active_departure');
        });
    }
};
