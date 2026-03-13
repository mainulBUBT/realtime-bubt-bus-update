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
        Schema::table('locations', function (Blueprint $table) {
            // Add composite index for trip_id and recorded_at for time-series queries
            $table->index(['trip_id', 'recorded_at'], 'idx_trip_time');

            // Add composite index for bus_id and recorded_at for cleanup queries
            $table->index(['bus_id', 'recorded_at'], 'idx_bus_recorded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('idx_trip_time');
            $table->dropIndex('idx_bus_recorded');
        });
    }
};
