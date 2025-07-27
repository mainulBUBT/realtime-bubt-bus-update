<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bus_timeline_progression', function (Blueprint $table) {
            $table->id();
            $table->string('bus_id', 10);
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('route_id');
            $table->enum('trip_direction', ['departure', 'return']);
            $table->enum('status', ['completed', 'current', 'upcoming', 'skipped'])->default('upcoming');
            $table->timestamp('reached_at')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->decimal('distance_from_previous', 8, 2)->nullable();
            $table->integer('eta_minutes')->nullable();
            $table->float('confidence_score')->default(0.0);
            $table->json('location_data')->nullable();
            $table->boolean('is_active_trip')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('schedule_id')->references('id')->on('bus_schedules')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('bus_routes')->onDelete('cascade');

            // Indexes for efficient queries
            $table->index(['bus_id', 'trip_direction', 'is_active_trip'], 'idx_bus_trip_active');
            $table->index(['bus_id', 'status', 'is_active_trip'], 'idx_bus_status_active');
            $table->index(['schedule_id', 'trip_direction'], 'idx_schedule_direction');
            $table->index(['bus_id', 'created_at'], 'idx_bus_created');
            $table->unique(['bus_id', 'route_id', 'trip_direction', 'is_active_trip'], 'unq_active_progression');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_timeline_progression');
    }
};
