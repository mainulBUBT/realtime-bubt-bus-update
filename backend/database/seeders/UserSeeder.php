<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@bustracker.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+880171234567',
            ]
        );

        // Create driver user
        User::updateOrCreate(
            ['email' => 'driver@bustracker.com'],
            [
                'name' => 'Driver User',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'phone' => '+880171234568',
            ]
        );

        // Create student user
        User::updateOrCreate(
            ['email' => 'student@bustracker.com'],
            [
                'name' => 'Student User',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+880171234569',
            ]
        );

        $this->command->info('Users seeded successfully!');
        $this->command->info('  Admin: admin@bustracker.com / password');
        $this->command->info('  Driver: driver@bustracker.com / password');
        $this->command->info('  Student: student@bustracker.com / password');
    }
}
