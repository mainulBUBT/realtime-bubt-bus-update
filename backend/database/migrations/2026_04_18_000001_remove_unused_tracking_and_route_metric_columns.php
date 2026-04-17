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
            if (Schema::hasColumn('trips', 'last_confirmed_stop_id')) {
                $table->dropConstrainedForeignId('last_confirmed_stop_id');
            }

            if (Schema::hasColumn('trips', 'current_stop_id')) {
                $table->dropConstrainedForeignId('current_stop_id');
            }

            if (Schema::hasColumn('trips', 'next_stop_id')) {
                $table->dropConstrainedForeignId('next_stop_id');
            }

            if (Schema::hasColumn('trips', 'progress_segment_index')) {
                $table->dropIndex('idx_trips_route_segment');
            }

            if (Schema::hasColumn('trips', 'is_off_route')) {
                $table->dropIndex('idx_trips_route_status');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('trips', 'last_confirmed_stop_sequence') ? 'last_confirmed_stop_sequence' : null,
                Schema::hasColumn('trips', 'progress_segment_index') ? 'progress_segment_index' : null,
                Schema::hasColumn('trips', 'progress_distance_m') ? 'progress_distance_m' : null,
                Schema::hasColumn('trips', 'previous_progress_distance_m') ? 'previous_progress_distance_m' : null,
                Schema::hasColumn('trips', 'tracking_status') ? 'tracking_status' : null,
                Schema::hasColumn('trips', 'is_off_route') ? 'is_off_route' : null,
                Schema::hasColumn('trips', 'off_route_since') ? 'off_route_since' : null,
                Schema::hasColumn('trips', 'off_route_counter') ? 'off_route_counter' : null,
                Schema::hasColumn('trips', 'last_gps_lat') ? 'last_gps_lat' : null,
                Schema::hasColumn('trips', 'last_gps_lng') ? 'last_gps_lng' : null,
                Schema::hasColumn('trips', 'last_gps_at') ? 'last_gps_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('route_stops', function (Blueprint $table) {
            if (Schema::hasColumn('route_stops', 'distance_along_route_m')) {
                $table->dropIndex('idx_route_stops_route_distance');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('route_stops', 'distance_along_route_m') ? 'distance_along_route_m' : null,
                Schema::hasColumn('route_stops', 'shape_index') ? 'shape_index' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('last_confirmed_stop_id')->nullable()->after('schedule_id')->constrained('route_stops')->nullOnDelete();
            $table->unsignedInteger('last_confirmed_stop_sequence')->nullable()->after('last_confirmed_stop_id');
            $table->foreignId('current_stop_id')->nullable()->after('last_confirmed_stop_sequence')->constrained('route_stops')->nullOnDelete();
            $table->foreignId('next_stop_id')->nullable()->after('current_stop_id')->constrained('route_stops')->nullOnDelete();
            $table->unsignedInteger('progress_segment_index')->nullable()->after('next_stop_id');
            $table->decimal('progress_distance_m', 10, 2)->nullable()->after('progress_segment_index');
            $table->decimal('previous_progress_distance_m', 10, 2)->nullable()->after('progress_distance_m');
            $table->string('tracking_status', 20)->nullable()->after('previous_progress_distance_m');
            $table->boolean('is_off_route')->default(false)->after('tracking_status');
            $table->timestamp('off_route_since')->nullable()->after('is_off_route');
            $table->unsignedInteger('off_route_counter')->default(0)->after('off_route_since');
            $table->decimal('last_gps_lat', 10, 7)->nullable()->after('off_route_counter');
            $table->decimal('last_gps_lng', 10, 7)->nullable()->after('last_gps_lat');
            $table->timestamp('last_gps_at')->nullable()->after('last_gps_lng');

            $table->index(['route_id', 'progress_segment_index'], 'idx_trips_route_segment');
            $table->index(['is_off_route', 'tracking_status'], 'idx_trips_route_status');
        });

        Schema::table('route_stops', function (Blueprint $table) {
            $table->decimal('distance_along_route_m', 10, 2)->nullable()->after('sequence');
            $table->unsignedInteger('shape_index')->nullable()->after('distance_along_route_m');
            $table->index(['route_id', 'distance_along_route_m'], 'idx_route_stops_route_distance');
        });
    }
};
