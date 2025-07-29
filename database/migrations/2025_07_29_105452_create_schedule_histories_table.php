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
        Schema::create('schedule_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->string('action'); // created, updated, deleted, activated, deactivated
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('schedule_id')->references('id')->on('bus_schedules')->onDelete('cascade');
            $table->index(['schedule_id', 'created_at']);
            $table->index('action');
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_histories');
    }
};
