<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ClassificationSeeder - Data klasifikasi berdasarkan penelitian ilmiah
 * 
 * Data ini mengacu pada:
 * - USDA Classification Standards for Tomato Ripeness
 * - Research: "Fuzzy Classification of Pre-harvest Tomatoes for Ripeness Estimation"
 * - Research: "Changes in color-related compounds in tomato fruit during ripening"
 * - Research: "Comparison of color indexes for tomato ripening"
 */
class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $classifications = [
            // === KLASIFIKASI BENAR - BERDASARKAN STANDAR USDA ===
            
            // 1. GREEN/MATURE GREEN - Klasifikasi Benar
            [
                'red_value' => 65,
                'green_value' => 145,
                'blue_value' => 55,
                'clear_value' => 800,
                'actual_status' => 'Mentah',
                'predicted_status' => 'Mentah',
                'classification_result' => 'Benar',
                'notes' => 'Tomat hijau matang (Green) - 100% hijau, nilai a* negatif (-7.1), siap dipetik untuk transportasi',
                'device_id' => 'ESP32_001',
                'is_verified' => true
            ],
            [
                'red_value' => 75,
                'green_value' => 160,
                'blue_value' => 65,
                'clear_value' => 850,
                'actual_status' => 'Mentah',
                'predicted_status' => 'Mentah',
                'classification_result' => 'Benar',
                'notes' => 'Tomat mature green dengan dominasi klorofil, belum ada perubahan warna',
                'device_id' => 'ESP32_002',
                'is_verified' => true
            ],
            
            // 2. BREAKER - Klasifikasi Benar
            [
                'red_value' => 85,
                'green_value' => 130,
                'blue_value' => 70,
                'clear_value' => 900,
                'actual_status' => 'Setengah Matang',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat breaker - <10% non-hijau, mulai ada pigmentasi pink di ujung buah',
                'device_id' => 'ESP32_001',
                'is_verified' => true
            ],
            
            // 3. TURNING - Klasifikasi Benar
            [
                'red_value' => 115,
                'green_value' => 120,
                'blue_value' => 80,
                'clear_value' => 950,
                'actual_status' => 'Setengah Matang',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat turning - 10-30% permukaan merah/pink, transisi aktif dari hijau ke merah',
                'device_id' => 'ESP32_003',
                'is_verified' => true
            ],
            
            // 4. PINK - Klasifikasi Benar
            [
                'red_value' => 145,
                'green_value' => 100,
                'blue_value' => 90,
                'clear_value' => 1000,
                'actual_status' => 'Setengah Matang',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat pink - 30-60% permukaan merah/pink, lycopene mulai terbentuk',
                'device_id' => 'ESP32_002',
                'is_verified' => true
            ],
            
            // 5. LIGHT RED - Klasifikasi Benar
            [
                'red_value' => 175,
                'green_value' => 80,
                'blue_value' => 70,
                'clear_value' => 1150,
                'actual_status' => 'Matang',
                'predicted_status' => 'Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat light red - 60-90% permukaan merah, kandungan lycopene optimal',
                'device_id' => 'ESP32_001',
                'is_verified' => true
            ],
            
            // 6. RED/FULLY RIPE - Klasifikasi Benar
            [
                'red_value' => 210,
                'green_value' => 55,
                'blue_value' => 45,
                'clear_value' => 1250,
                'actual_status' => 'Matang',
                'predicted_status' => 'Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat red/fully ripe - >90% merah, degradasi klorofil sempurna, siap konsumsi',
                'device_id' => 'ESP32_002',
                'is_verified' => true
            ],
            [
                'red_value' => 235,
                'green_value' => 65,
                'blue_value' => 50,
                'clear_value' => 1300,
                'actual_status' => 'Matang',
                'predicted_status' => 'Matang',
                'classification_result' => 'Benar',
                'notes' => 'Tomat sangat matang dengan warna merah cerah, kandungan lycopene maksimal',
                'device_id' => 'ESP32_003',
                'is_verified' => true
            ],
            
            // 7. OVERRIPE - Klasifikasi Benar
            [
                'red_value' => 150,
                'green_value' => 90,
                'blue_value' => 70,
                'clear_value' => 900,
                'actual_status' => 'Busuk',
                'predicted_status' => 'Busuk',
                'classification_result' => 'Benar',
                'notes' => 'Tomat overripe - melewati puncak kematangan, mulai pelunakan berlebih',
                'device_id' => 'ESP32_001',
                'is_verified' => true
            ],
            
            // === KLASIFIKASI SALAH - UNTUK EVALUASI ALGORITMA ===
            
            // Kesalahan: Mature Green diprediksi sebagai Breaker
            [
                'red_value' => 70,
                'green_value' => 155,
                'blue_value' => 60,
                'clear_value' => 820,
                'actual_status' => 'Mentah',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Salah',
                'notes' => 'Tomat masih mature green tapi diprediksi breaker - perlu penyesuaian threshold',
                'device_id' => 'ESP32_002',
                'is_verified' => true
            ],
            
            // Kesalahan: Light Red diprediksi sebagai Pink
            [
                'red_value' => 165,
                'green_value' => 85,
                'blue_value' => 65,
                'clear_value' => 1100,
                'actual_status' => 'Matang',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Salah',
                'notes' => 'Tomat light red diprediksi pink - algoritma perlu perbaikan untuk transisi matang',
                'device_id' => 'ESP32_003',
                'is_verified' => true
            ],
            
            // Kesalahan: Turning diprediksi sebagai Green
            [
                'red_value' => 105,
                'green_value' => 125,
                'blue_value' => 75,
                'clear_value' => 880,
                'actual_status' => 'Setengah Matang',
                'predicted_status' => 'Mentah',
                'classification_result' => 'Salah',
                'notes' => 'Tomat turning diprediksi green - sensitivitas deteksi perubahan warna perlu ditingkatkan',
                'device_id' => 'ESP32_001',
                'is_verified' => true
            ],
            
            // === DATA BELUM DIVERIFIKASI - UNTUK VALIDASI ===
            
            [
                'red_value' => 190,
                'green_value' => 70,
                'blue_value' => 55,
                'clear_value' => 1180,
                'actual_status' => 'Matang',
                'predicted_status' => 'Matang',
                'classification_result' => 'Benar',
                'notes' => 'Data baru tomat red - perlu verifikasi manual untuk konfirmasi akurasi',
                'device_id' => 'ESP32_002',
                'is_verified' => false
            ],
            [
                'red_value' => 95,
                'green_value' => 140,
                'blue_value' => 75,
                'clear_value' => 920,
                'actual_status' => 'Setengah Matang',
                'predicted_status' => 'Setengah Matang',
                'classification_result' => 'Benar',
                'notes' => 'Data breaker/turning - memerlukan verifikasi ahli untuk validasi tahap pematangan',
                'device_id' => 'ESP32_003',
                'is_verified' => false
            ]
        ];
        
        foreach ($classifications as $classification) {
            Classification::create($classification);
        }
    }
}
