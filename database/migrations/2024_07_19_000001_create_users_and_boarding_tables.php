<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update users table for students
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('student_id')->unique()->nullable();
                $table->string('phone')->nullable();
                $table->string('department')->nullable();
                $table->enum('role', ['student', 'admin', 'driver'])->default('student');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Bus boarding tracking
        Schema::create('bus_boardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('boarding_stop_id')->constrained('stops')->onDelete('cascade');
            $table->foreignId('destination_stop_id')->nullable()->constrained('stops')->onDelete('cascade');
            $table->timestamp('boarded_at');
            $table->timestamp('alighted_at')->nullable();
            $table->enum('status', ['waiting', 'boarded', 'completed', 'cancelled'])->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['bus_id', 'trip_id', 'status']);
        });

        // Bus capacity and real-time status
        Schema::create('bus_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('current_capacity')->default(0);
            $table->integer('max_capacity')->default(40);
            $table->enum('status', ['idle', 'boarding', 'in_transit', 'arrived'])->default('idle');
            $table->foreignId('current_stop_id')->nullable()->constrained('stops')->onDelete('set null');
            $table->timestamp('last_updated');
            $table->timestamps();
            
            $table->unique(['bus_id', 'trip_id']);
        });

        // Notifications for students
        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['bus_arrival', 'bus_delay', 'boarding_reminder', 'general'])->default('general');
            $table->json('data')->nullable(); // Additional data like bus_id, trip_id
            $table->boolean('is_read')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
        Schema::dropIfExists('bus_status');
        Schema::dropIfExists('bus_boardings');
        Schema::dropIfExists('users');
    }
};