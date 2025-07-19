<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buses table
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // B1, B2, B3, B4, B5
            $table->string('route_name'); // Buriganga, Brahmaputra, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bus stops table
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('order_index');
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Trips table
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->date('trip_date');
            $table->time('departure_time');
            $table->time('return_time');
            $table->enum('direction', ['outbound', 'inbound']); // to campus or from campus
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        // Live locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('recorded_at');
            $table->string('source')->default('api'); // api, manual, etc.
            $table->timestamps();
            
            $table->index(['bus_id', 'recorded_at']);
        });

        // Settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        // Push subscriptions table
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->json('subscribed_stops')->nullable(); // Array of stop IDs
            $table->timestamps();
            
            $table->unique('endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('stops');
        Schema::dropIfExists('buses');
    }
};