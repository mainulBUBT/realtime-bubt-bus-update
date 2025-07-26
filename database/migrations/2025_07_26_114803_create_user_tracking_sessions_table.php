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
        Schema::create('user_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 255);
            $table->string('bus_id', 10);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->float('trust_score_at_start')->default(0.5);
            $table->integer('locations_contributed')->default(0);
            $table->integer('valid_locations')->default(0);
            $table->timestamps();
            
            // Indexes for session management
            $table->index(['bus_id', 'is_active', 'started_at']);
            $table->index('device_token');
            $table->index(['is_active', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tracking_sessions');
    }
};
