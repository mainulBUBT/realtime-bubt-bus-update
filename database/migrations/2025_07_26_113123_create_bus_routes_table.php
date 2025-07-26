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
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('bus_schedules')->onDelete('cascade');
            $table->string('stop_name', 100); // Asad Gate, Shyamoli, Mirpur-1, Rainkhola, BUBT
            $table->integer('stop_order'); // Sequential order: 1, 2, 3, 4, 5
            $table->decimal('latitude', 10, 8); // GPS coordinates
            $table->decimal('longitude', 11, 8); // GPS coordinates
            $table->integer('coverage_radius')->default(100); // Radius in meters
            $table->time('estimated_departure_time'); // Estimated time for departure trip
            $table->time('estimated_return_time'); // Estimated time for return trip
            $table->integer('departure_duration_minutes')->default(0); // Minutes from start for departure
            $table->integer('return_duration_minutes')->default(0); // Minutes from start for return
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['schedule_id', 'stop_order']);
            $table->index(['latitude', 'longitude']);
            $table->unique(['schedule_id', 'stop_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_routes');
    }
};
