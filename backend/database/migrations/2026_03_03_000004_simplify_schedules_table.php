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
        // First, drop foreign keys
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['schedule_period_id']);
        });

        // Now drop columns and modify the table
        Schema::table('schedules', function (Blueprint $table) {
            // Drop unused columns
            $table->dropColumn(['schedule_period_id', 'direction', 'effective_to', 'schedule_type']);

            // Rename effective_from to effective_date and make nullable
            $table->renameColumn('effective_from', 'effective_date');

            // Make effective_date nullable (it already should be based on previous migration)
            $table->date('effective_date')->nullable()->change();
        });

        // Add unique constraint and indexes
        Schema::table('schedules', function (Blueprint $table) {
            // Unique constraint to prevent duplicate schedules
            $table->unique(['bus_id', 'route_id', 'departure_time', 'effective_date'], 'unique_schedule');

            // Index for active route schedules
            $table->index(['route_id', 'is_active'], 'idx_route_active');

            // Index for departure_time queries
            $table->index('departure_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Drop indexes and unique constraint
            $table->dropIndex('unique_schedule');
            $table->dropIndex('idx_route_active');
            $table->dropIndex('departure_time');

            // Rename effective_date back to effective_from
            $table->renameColumn('effective_date', 'effective_from');
        });

        Schema::table('schedules', function (Blueprint $table) {
            // Add back the dropped columns
            $table->foreignId('schedule_period_id')->nullable()->after('route_id');
            $table->enum('direction', ['outbound', 'inbound'])->after('route_id');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->string('schedule_type')->default('regular')->after('effective_to');

            // Add back foreign key
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('set null');
        });
    }
};
