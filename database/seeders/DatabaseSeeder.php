<?php

namespace Database\Seeders;

use App\Models\User;
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
            PhaseOneSeeder::class,
            PhaseTwoSeeder::class,
            PhaseThreeSeeder::class,
            PhaseFourSeeder::class,
        ]);

        // Factory removed.
    }
}
