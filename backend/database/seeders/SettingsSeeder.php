<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'Bus Tracker',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Application name displayed in UI',
            ],
            [
                'key' => 'app_logo',
                'value' => null,
                'type' => 'text',
                'group' => 'general',
                'description' => 'Application logo path',
            ],
            [
                'key' => 'timezone',
                'value' => 'America/New_York',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Application timezone',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Enable maintenance mode to take the application offline',
            ],
            [
                'key' => 'items_per_page',
                'value' => '15',
                'type' => 'number',
                'group' => 'general',
                'description' => 'Default number of items per page in listings',
            ],

            // Email Settings
            [
                'key' => 'mail_host',
                'value' => 'smtp.mailtrap.io',
                'type' => 'text',
                'group' => 'email',
                'description' => 'SMTP host for sending emails',
            ],
            [
                'key' => 'mail_port',
                'value' => '2525',
                'type' => 'number',
                'group' => 'email',
                'description' => 'SMTP port',
            ],
            [
                'key' => 'mail_username',
                'value' => null,
                'type' => 'text',
                'group' => 'email',
                'description' => 'SMTP username',
            ],
            [
                'key' => 'mail_password',
                'value' => null,
                'type' => 'text',
                'group' => 'email',
                'description' => 'SMTP password',
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'type' => 'text',
                'group' => 'email',
                'description' => 'SMTP encryption method',
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'noreply@bustracker.com',
                'type' => 'text',
                'group' => 'email',
                'description' => 'Default from email address',
            ],
            [
                'key' => 'mail_from_name',
                'value' => 'Bus Tracker',
                'type' => 'text',
                'group' => 'email',
                'description' => 'Default from name',
            ],
            [
                'key' => 'notifications_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'email',
                'description' => 'Enable email notifications',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
