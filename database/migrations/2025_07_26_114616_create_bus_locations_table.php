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
        Schema::create('bus_locations', function (Blueprint $table) {
            $table->id();
            $table->string('bus_id', 10);
            $table->string('device_token', 255);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('reputation_weight')->default(0.5);
            $table->boolean('is_validated')->default(false);
            $table->timestamps();
            
            // Indexes for real-time location queries
            $table->index(['bus_id', 'created_at']);
            $table->index(['bus_id', 'created_at', 'is_validated']);
            $table->index('device_token');
            $table->index(['bus_id', 'is_validated', 'reputation_weight']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_locations');
    }
};
