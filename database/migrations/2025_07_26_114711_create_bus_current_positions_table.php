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
        Schema::create('bus_current_positions', function (Blueprint $table) {
            $table->string('bus_id', 10)->primary();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->float('confidence_level')->default(0.0);
            $table->timestamp('last_updated')->nullable();
            $table->integer('active_trackers')->default(0);
            $table->integer('trusted_trackers')->default(0);
            $table->float('average_trust_score')->default(0.0);
            $table->enum('status', ['active', 'inactive', 'no_data'])->default('no_data');
            $table->json('last_known_location')->nullable();
            $table->float('movement_consistency')->default(0.0);
            $table->timestamps();
            
            // Indexes for fast cache retrieval
            $table->index('status');
            $table->index('last_updated');
            $table->index(['status', 'confidence_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_current_positions');
    }
};
