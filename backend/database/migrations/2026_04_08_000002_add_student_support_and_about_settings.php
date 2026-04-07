<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'student_support_email', 'value' => '', 'type' => 'text', 'group' => 'student_app', 'description' => 'Help & Support email for students'],
            ['key' => 'student_support_phone', 'value' => '', 'type' => 'text', 'group' => 'student_app', 'description' => 'Help & Support phone for students'],
            ['key' => 'student_support_url', 'value' => '', 'type' => 'text', 'group' => 'student_app', 'description' => 'Help & Support website URL for students'],
            ['key' => 'student_about_text', 'value' => '', 'type' => 'text', 'group' => 'student_app', 'description' => 'About page text shown in student app'],
        ];

        foreach ($settings as $row) {
            $exists = DB::table('settings')
                ->where('key', $row['key'])
                ->exists();
            if (!$exists) {
                DB::table('settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'student_support_email',
            'student_support_phone',
            'student_support_url',
            'student_about_text',
        ])->delete();
    }
};

