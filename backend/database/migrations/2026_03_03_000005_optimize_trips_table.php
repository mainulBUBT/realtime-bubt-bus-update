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
        Schema::table('trips', function (Blueprint $table) {
            // Add current location cache fields after status
            $table->decimal('current_lat', 10, 7)->nullable()->after('status');
            $table->decimal('current_lng', 10, 7)->nullable()->after('current_lat');
            $table->timestamp('last_location_at')->nullable()->after('current_lng');

            // Performance indexes
            $table->index(['status', 'trip_date'], 'idx_status_date');
            $table->index(['bus_id', 'status'], 'idx_bus_status');
            $table->index(['driver_id', 'status'], 'idx_driver_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['status', 'trip_date']);
            $table->dropIndex(['bus_id', 'status']);
            $table->dropIndex(['driver_id', 'status']);
            $table->dropColumn(['current_lat', 'current_lng', 'last_location_at']);
        });
    }
};
