<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // Student App Settings
            ['key' => 'student_app_name', 'value' => 'BUBT Bus Tracker', 'type' => 'text', 'group' => 'student_app', 'description' => 'Student app name displayed on splash screen and headers'],
            ['key' => 'student_app_tagline', 'value' => 'Your Campus Shuttle Companion', 'type' => 'text', 'group' => 'student_app', 'description' => 'Tagline displayed on student app splash screen'],
            ['key' => 'student_splash_primary_color', 'value' => '#4F46E5', 'type' => 'text', 'group' => 'student_app', 'description' => 'Primary color for student app splash screen gradient'],
            ['key' => 'student_splash_secondary_color', 'value' => '#4338CA', 'type' => 'text', 'group' => 'student_app', 'description' => 'Secondary color for student app splash screen gradient'],

            // Driver App Settings
            ['key' => 'driver_app_name', 'value' => 'BUBT Bus Tracker - Driver', 'type' => 'text', 'group' => 'driver_app', 'description' => 'Driver app name displayed on splash screen and headers'],
            ['key' => 'driver_app_tagline', 'value' => 'Campus Shuttle Driver App', 'type' => 'text', 'group' => 'driver_app', 'description' => 'Tagline displayed on driver app splash screen'],
            ['key' => 'driver_splash_primary_color', 'value' => '#059669', 'type' => 'text', 'group' => 'driver_app', 'description' => 'Primary color for driver app splash screen gradient'],
            ['key' => 'driver_splash_secondary_color', 'value' => '#047857', 'type' => 'text', 'group' => 'driver_app', 'description' => 'Secondary color for driver app splash screen gradient'],
        ];

        DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'student_app_name',
            'student_app_tagline',
            'student_splash_primary_color',
            'student_splash_secondary_color',
            'driver_app_name',
            'driver_app_tagline',
            'driver_splash_primary_color',
            'driver_splash_secondary_color',
        ])->delete();
    }
};
