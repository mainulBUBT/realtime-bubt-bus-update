<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order
        $this->call([
            UserSeeder::class,       // Create basic users
            BusRoutesSeeder::class,  // Create production bus routes with real data
        ]);
    }
}
