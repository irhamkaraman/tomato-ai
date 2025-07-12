<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Recommendation;

/**
 * RecommendationSeeder - Rekomendasi berdasarkan standar USDA dan penelitian ilmiah
 * 
 * Referensi:
 * - USDA Standards for Grades of Fresh Tomatoes
 * - Research: "Postharvest handling of tomatoes"
 * - Research: "Storage and ripening of tomatoes"
 * - Research: "Quality changes during tomato fruit development and ripening"
 */
class RecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama jika ada
        Recommendation::truncate();

        $recommendations = [
            // === REKOMENDASI UNTUK TOMAT MENTAH (GREEN/MATURE GREEN) ===
            [
                'maturity_level' => 'mentah',
                'category' => 'storage',
                'content' => 'Simpan pada suhu 18-21°C dengan kelembaban 85-90%. Hindari suhu <13°C untuk mencegah chilling injury',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal berdasarkan standar USDA untuk mature green tomatoes'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'handling',
                'content' => 'Tangani dengan sangat hati-hati. Tomat mature green memiliki dinding sel yang kuat namun rentan memar internal',
                'order' => 2,
                'is_active' => true,
                'description' => 'Penanganan berdasarkan karakteristik fisiologis tomat hijau'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'use',
                'content' => 'Ideal untuk transportasi jarak jauh. Dapat digunakan untuk green tomato recipes atau tunggu pematangan alami',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan komersial dan kuliner tomat mature green'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'timeframe',
                'content' => 'Pematangan alami 7-14 hari pada suhu ruang. Dapat dipercepat dengan etilen (100 ppm) menjadi 4-6 hari',
                'order' => 4,
                'is_active' => true,
                'description' => 'Estimasi berdasarkan penelitian fisiologi pematangan tomat'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'quality',
                'content' => 'Kandungan klorofil tinggi, asam sitrat 0.4-0.6%, gula 2-3%. Tekstur keras dengan firmness >6 kg/cm²',
                'order' => 5,
                'is_active' => true,
                'description' => 'Karakteristik kualitas tomat mature green'
            ],

            // === REKOMENDASI UNTUK TOMAT SETENGAH MATANG (BREAKER-TURNING-PINK) ===
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'storage',
                'content' => 'Simpan pada suhu 16-18°C dengan kelembaban 85-90%. Tahap optimal untuk penyimpanan jangka menengah',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal untuk tahap breaker hingga pink'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'handling',
                'content' => 'Masih cukup tahan transportasi. Hindari tekanan berlebih karena mulai terjadi pelunakan dinding sel',
                'order' => 2,
                'is_active' => true,
                'description' => 'Penanganan berdasarkan perubahan struktur selular'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'use',
                'content' => 'Ideal untuk distribusi retail. Tahap turning-pink optimal untuk penjualan dengan shelf life 3-5 hari',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan komersial tahap transisi pematangan'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'timeframe',
                'content' => 'Breaker: 3-5 hari ke pink. Turning: 2-3 hari ke light red. Pink: 1-2 hari ke red',
                'order' => 4,
                'is_active' => true,
                'description' => 'Timeline pematangan berdasarkan tahap USDA'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'quality',
                'content' => 'Lycopene mulai terbentuk (5-15 mg/kg), gula meningkat 3-4%, asam menurun. Firmness 4-6 kg/cm²',
                'order' => 5,
                'is_active' => true,
                'description' => 'Perubahan biokimia selama transisi pematangan'
            ],

            // === REKOMENDASI UNTUK TOMAT MATANG (LIGHT RED - RED) ===
            [
                'maturity_level' => 'matang',
                'category' => 'storage',
                'content' => 'Simpan pada suhu 10-13°C dengan kelembaban 85-90%. Hindari suhu <7°C untuk mencegah kehilangan flavor',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal berdasarkan penelitian flavor retention'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'handling',
                'content' => 'Tangani dengan sangat hati-hati. Firmness rendah (2-4 kg/cm²), rentan terhadap mechanical damage',
                'order' => 2,
                'is_active' => true,
                'description' => 'Penanganan berdasarkan karakteristik fisik tomat matang'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'use',
                'content' => 'Optimal untuk konsumsi segar. Kandungan lycopene maksimal (30-50 mg/kg), vitamin C tinggi (15-25 mg/100g)',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan dengan nilai nutrisi optimal'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'timeframe',
                'content' => 'Light red: 2-3 hari shelf life. Red: 1-2 hari pada suhu ruang, 5-7 hari pada suhu dingin',
                'order' => 4,
                'is_active' => true,
                'description' => 'Estimasi berdasarkan penelitian postharvest physiology'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'quality',
                'content' => 'Rasio gula/asam optimal (6-8), aroma volatiles maksimal, tekstur juicy dengan firmness 2-4 kg/cm²',
                'order' => 5,
                'is_active' => true,
                'description' => 'Karakteristik kualitas sensori tomat matang'
            ],

            // === REKOMENDASI UNTUK TOMAT BUSUK (OVERRIPE/DETERIORATING) ===
            [
                'maturity_level' => 'busuk',
                'category' => 'storage',
                'content' => 'TIDAK disimpan. Segera pisahkan untuk mencegah penyebaran etilen dan mikroorganisme patogen',
                'order' => 1,
                'is_active' => true,
                'description' => 'Protokol keamanan pangan untuk tomat deteriorating'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'handling',
                'content' => 'Gunakan APD, pisahkan segera. Risiko kontaminasi Salmonella, E.coli, dan jamur patogen',
                'order' => 2,
                'is_active' => true,
                'description' => 'Penanganan berdasarkan protokol keamanan mikrobiologi'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'use',
                'content' => 'TIDAK LAYAK KONSUMSI. Dapat dikompos dengan proper composting (60°C, 15 hari) untuk menghilangkan patogen',
                'order' => 3,
                'is_active' => true,
                'description' => 'Alternatif penggunaan ramah lingkungan'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'timeframe',
                'content' => 'Tindakan SEGERA dalam 1-2 jam. Produksi etilen tinggi dapat mempercepat deteriorasi produk sekitar',
                'order' => 4,
                'is_active' => true,
                'description' => 'Timeline kritis berdasarkan fisiologi deteriorasi'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'safety',
                'content' => 'Identifikasi penyebab: overripening, mechanical damage, atau infeksi patogen untuk pencegahan future loss',
                'order' => 5,
                'is_active' => true,
                'description' => 'Analisis penyebab untuk quality management'
            ],
        ];

        foreach ($recommendations as $recommendation) {
            Recommendation::create($recommendation);
        }

        $this->command->info('Data rekomendasi berhasil ditambahkan!');
    }
}
