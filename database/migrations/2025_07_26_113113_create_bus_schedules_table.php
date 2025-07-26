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
        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('bus_id', 10)->index(); // B1, B2, B3, B4, B5
            $table->string('route_name', 100);
            $table->time('departure_time'); // Campus to city departure time
            $table->time('return_time'); // City to campus return time
            $table->json('days_of_week'); // ['monday', 'tuesday', etc.]
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['bus_id', 'is_active']);
            $table->index(['departure_time', 'return_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_schedules');
    }
};
