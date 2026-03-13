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
        // Drop foreign key constraint
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['schedule_period_id']);
        });

        // Make schedule_period_id nullable and set default to null
        Schema::table('routes', function (Blueprint $table) {
            $table->foreignId('schedule_period_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to NOT NULL
        Schema::table('routes', function (Blueprint $table) {
            $table->foreignId('schedule_period_id')->nullable(false)->change();
        });

        // Re-add foreign key
        Schema::table('routes', function (Blueprint $table) {
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('cascade');
        });
    }
};
