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
        Schema::table('schedules', function (Blueprint $table) {
            // Add direction field (up = Campus to City, down = City to Campus)
            $table->enum('direction', ['up', 'down'])->after('route_id')->default('up');

            // Add effective date range for dynamic scheduling
            $table->date('effective_from')->after('weekdays')->default(now()->toDateString());
            $table->date('effective_to')->nullable()->after('effective_from');

            // Add schedule type for categorization (regular, exam, semester, etc.)
            $table->string('schedule_type')->default('regular')->after('effective_to')->comment('regular, exam, semester, holiday');
        });

        // Make bus_id and schedule_period_id nullable in a separate step
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->dropForeign(['schedule_period_id']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('bus_id')->nullable()->change();
            $table->foreignId('schedule_period_id')->nullable()->change();

            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('set null');
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->dropForeign(['schedule_period_id']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('bus_id')->nullable(false)->change();
            $table->unsignedBigInteger('schedule_period_id')->nullable(false)->change();

            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('cascade');
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('cascade');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['direction', 'effective_from', 'effective_to', 'schedule_type']);
        });
    }
};
