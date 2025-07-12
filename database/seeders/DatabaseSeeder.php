<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil semua seeder
        $this->call([
            DeviceSeeder::class,
            TrainingDataSeeder::class,
            DecisionTreeRuleSeeder::class,
            RecommendationSeeder::class,
            ClassificationSeeder::class,
        ]);
    }
}
