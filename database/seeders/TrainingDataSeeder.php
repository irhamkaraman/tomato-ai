<?php

namespace Database\Seeders;

use App\Models\TrainingData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrainingDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama jika ada
        TrainingData::truncate();
        
        $trainingData = [];
        
        // TOMAT MATANG (25 data) - RGB: Merah tinggi (150-200), Hijau rendah (60-100), Biru rendah (50-90)
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(150, 200),
                'green_value' => rand(60, 100),
                'blue_value' => rand(50, 90),
                'maturity_class' => 'matang',
                'description' => 'Tomat matang siap konsumsi dengan dominasi warna merah',
                'is_active' => true
            ];
        }
        
        // TOMAT SETENGAH MATANG (25 data) - RGB: Merah sedang (100-160), Hijau sedang (100-140), Biru sedang (60-100)
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(100, 160),
                'green_value' => rand(100, 140),
                'blue_value' => rand(60, 100),
                'maturity_class' => 'setengah_matang',
                'description' => 'Tomat setengah matang dalam proses pematangan',
                'is_active' => true
            ];
        }
        
        // TOMAT MENTAH (25 data) - RGB: Merah rendah (60-120), Hijau tinggi (120-180), Biru sedang (60-100)
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(60, 120),
                'green_value' => rand(120, 180),
                'blue_value' => rand(60, 100),
                'maturity_class' => 'mentah',
                'description' => 'Tomat mentah dengan dominasi warna hijau',
                'is_active' => true
            ];
        }
        
        // TOMAT BUSUK (25 data) - RGB: Nilai rendah dan tidak konsisten, cenderung gelap
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(40, 100),
                'green_value' => rand(40, 120),
                'blue_value' => rand(60, 120),
                'maturity_class' => 'busuk',
                'description' => 'Tomat busuk dengan perubahan warna abnormal',
                'is_active' => true
            ];
        }
        
        // Shuffle data untuk randomisasi
        shuffle($trainingData);
        
        // Insert data ke database
        foreach ($trainingData as $data) {
            TrainingData::create($data);
        }
        
        echo "\n✅ Berhasil menambahkan " . count($trainingData) . " data training dengan variasi yang beragam!\n";
    }
}
