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
        // Buat device untuk testing (gunakan updateOrCreate untuk menghindari duplikasi)
        Device::updateOrCreate(
            ['device_id' => 'TEST_DEVICE'],
            [
                'name' => 'Test Device',
                'location' => 'Test Location'
            ]
        );
        
        // Panggil semua seeder
        $this->call([
            TrainingDataSeeder::class,
            DecisionTreeRuleSeeder::class,
            RecommendationSeeder::class,
        ]);
    }
}
