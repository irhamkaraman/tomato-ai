<?php

namespace Database\Seeders;

use App\Models\TrainingData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TrainingDataSeeder - Data pelatihan berdasarkan penelitian ilmiah
 * 
 * Data ini mengacu pada:
 * - USDA Classification Standards for Tomato Ripeness (6 stages)
 * - Research: "Fuzzy Classification of Pre-harvest Tomatoes for Ripeness Estimation"
 * - Research: "Tomato Maturity Recognition with Convolutional Transformers"
 * - Research: "Comparison of Color Indexes for Tomato Ripening"
 */
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
        
        // 1. GREEN/MATURE GREEN (30 data)
        // Berdasarkan penelitian: RGB dominan hijau, nilai a* negatif (-7.1 to -6.7)
        // Karakteristik: 100% hijau, belum ada perubahan warna
        for ($i = 0; $i < 30; $i++) {
            $trainingData[] = [
                'red_value' => rand(45, 85),      // Rendah: 45-85
                'green_value' => rand(120, 180),  // Tinggi: 120-180 (dominan)
                'blue_value' => rand(40, 80),     // Rendah: 40-80
                'maturity_class' => 'mentah',
                'description' => 'Tomat hijau matang (Green/Mature Green) - 100% hijau, siap dipetik untuk transportasi jarak jauh',
                'is_active' => true
            ];
        }
        
        // 2. BREAKER (25 data)
        // Berdasarkan penelitian: Mulai ada perubahan warna, a* values -2.9 to 0.1
        // Karakteristik: <10% selain hijau, mulai ada warna pink/merah di ujung
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(70, 110),     // Mulai meningkat: 70-110
                'green_value' => rand(110, 150),  // Masih tinggi: 110-150
                'blue_value' => rand(50, 90),     // Sedang: 50-90
                'maturity_class' => 'setengah_matang',
                'description' => 'Tomat breaker - perubahan warna awal, <10% non-hijau, mulai ada pigmentasi pink-merah',
                'is_active' => true
            ];
        }
        
        // 3. TURNING (25 data)
        // Berdasarkan penelitian: 10-30% permukaan berwarna merah/pink
        // Transisi dari hijau ke merah, nilai a* mulai positif
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(90, 140),     // Meningkat: 90-140
                'green_value' => rand(100, 140),  // Menurun: 100-140
                'blue_value' => rand(60, 100),    // Sedang: 60-100
                'maturity_class' => 'setengah_matang',
                'description' => 'Tomat turning - 10-30% permukaan berwarna merah/pink, transisi aktif dari hijau ke merah',
                'is_active' => true
            ];
        }
        
        // 4. PINK (25 data)
        // Berdasarkan penelitian: 30-60% permukaan berwarna merah/pink
        // Nilai a* positif, hue angle menurun signifikan
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(120, 170),    // Tinggi: 120-170
                'green_value' => rand(80, 120),   // Menurun: 80-120
                'blue_value' => rand(70, 110),    // Sedang: 70-110
                'maturity_class' => 'setengah_matang',
                'description' => 'Tomat pink - 30-60% permukaan merah/pink, pematangan lanjut dengan lycopene mulai terbentuk',
                'is_active' => true
            ];
        }
        
        // 5. LIGHT RED (25 data)
        // Berdasarkan penelitian: 60-90% permukaan berwarna merah
        // Degradasi klorofil lanjut, akumulasi karotenoid meningkat
        for ($i = 0; $i < 25; $i++) {
            $trainingData[] = [
                'red_value' => rand(150, 200),    // Tinggi: 150-200
                'green_value' => rand(60, 100),   // Rendah: 60-100
                'blue_value' => rand(50, 90),     // Rendah: 50-90
                'maturity_class' => 'matang',
                'description' => 'Tomat light red - 60-90% permukaan merah, hampir matang dengan kandungan lycopene optimal',
                'is_active' => true
            ];
        }
        
        // 6. RED/FULLY RIPE (30 data)
        // Berdasarkan penelitian: >90% permukaan merah, nilai a* maksimal
        // Degradasi klorofil sempurna, lycopene 80% dari total karotenoid
        for ($i = 0; $i < 30; $i++) {
            $trainingData[] = [
                'red_value' => rand(180, 255),    // Sangat tinggi: 180-255
                'green_value' => rand(40, 80),    // Sangat rendah: 40-80
                'blue_value' => rand(30, 70),     // Sangat rendah: 30-70
                'maturity_class' => 'matang',
                'description' => 'Tomat merah matang (Red/Fully Ripe) - >90% merah, siap konsumsi dengan kandungan lycopene maksimal',
                'is_active' => true
            ];
        }
        
        // 7. OVERRIPE/DETERIORATING (20 data)
        // Berdasarkan penelitian: Penurunan kualitas, perubahan tekstur
        // Nilai RGB tidak konsisten, mulai ada degradasi
        for ($i = 0; $i < 20; $i++) {
            $trainingData[] = [
                'red_value' => rand(120, 180),    // Bervariasi: 120-180
                'green_value' => rand(60, 120),   // Bervariasi: 60-120
                'blue_value' => rand(40, 100),    // Bervariasi: 40-100
                'maturity_class' => 'busuk',
                'description' => 'Tomat overripe - melewati puncak kematangan, mulai mengalami penurunan kualitas dan pelunakan berlebih',
                'is_active' => true
            ];
        }
        
        // Shuffle data untuk randomisasi
        shuffle($trainingData);
        
        // Insert data ke database
        foreach ($trainingData as $data) {
            TrainingData::create($data);
        }
        
        echo "\n✅ Berhasil menambahkan " . count($trainingData) . " data training berdasarkan standar USDA dan penelitian ilmiah!\n";
        echo "📊 Distribusi data:\n";
        echo "   - Green/Mature Green: 30 data\n";
        echo "   - Breaker: 25 data\n";
        echo "   - Turning: 25 data\n";
        echo "   - Pink: 25 data\n";
        echo "   - Light Red: 25 data\n";
        echo "   - Red/Fully Ripe: 30 data\n";
        echo "   - Overripe: 20 data\n";
        echo "📚 Referensi: USDA Standards, ScienceDirect, Nature Scientific Reports\n";
    }
}
