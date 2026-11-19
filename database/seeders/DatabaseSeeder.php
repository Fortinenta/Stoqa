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
        $this->call([
            InitialDataSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Seeder dummy data hanya dijalankan di environment local atau testing
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DummyDataSeeder::class,
            ]);
        }
    }
}
