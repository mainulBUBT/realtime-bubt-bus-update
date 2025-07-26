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
        Schema::create('bus_location_history', function (Blueprint $table) {
            $table->id();
            $table->string('bus_id', 10);
            $table->date('trip_date');
            $table->json('location_data');
            $table->json('trip_summary')->nullable();
            $table->timestamps();
            
            // Indexes for historical data retrieval
            $table->index(['bus_id', 'trip_date']);
            $table->index('trip_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_location_history');
    }
};
