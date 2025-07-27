<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        AdminUser::updateOrCreate(
            ['email' => 'admin@bustracker.com'],
            [
                'name' => 'Super Administrator',
                'email' => 'admin@bustracker.com',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create Regular Admin
        AdminUser::updateOrCreate(
            ['email' => 'manager@bustracker.com'],
            [
                'name' => 'Bus Manager',
                'email' => 'manager@bustracker.com',
                'password' => Hash::make('manager123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create Monitor User
        AdminUser::updateOrCreate(
            ['email' => 'monitor@bustracker.com'],
            [
                'name' => 'System Monitor',
                'email' => 'monitor@bustracker.com',
                'password' => Hash::make('monitor123'),
                'role' => 'monitor',
                'is_active' => true,
            ]
        );

        $this->command->info('Admin users created successfully!');
        $this->command->info('Super Admin: admin@bustracker.com / admin123');
        $this->command->info('Admin: manager@bustracker.com / manager123');
        $this->command->info('Monitor: monitor@bustracker.com / monitor123');
    }
}