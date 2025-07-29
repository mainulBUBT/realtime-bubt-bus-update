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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('bus_id')->unique();
            $table->string('name')->nullable();
            $table->integer('capacity')->default(40);
            $table->string('vehicle_number')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->text('maintenance_notes')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('bus_id');
            $table->index(['is_active', 'status']);
            $table->index('next_maintenance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
