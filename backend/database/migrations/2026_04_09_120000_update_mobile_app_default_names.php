<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'student_app_name')
            ->where('value', 'BUBT Bus Tracker')
            ->update(['value' => 'BUBT Tracker']);

        DB::table('settings')
            ->where('key', 'driver_app_name')
            ->where('value', 'BUBT Bus Tracker - Driver')
            ->update(['value' => 'BUBT Driver']);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'student_app_name')
            ->where('value', 'BUBT Tracker')
            ->update(['value' => 'BUBT Bus Tracker']);

        DB::table('settings')
            ->where('key', 'driver_app_name')
            ->where('value', 'BUBT Driver')
            ->update(['value' => 'BUBT Bus Tracker - Driver']);
    }
};
