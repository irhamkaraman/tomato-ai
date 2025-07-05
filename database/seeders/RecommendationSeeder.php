<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Recommendation;

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
            // Rekomendasi untuk tomat mentah
            [
                'maturity_level' => 'mentah',
                'category' => 'storage',
                'content' => 'Simpan di suhu ruangan (20-25°C) dengan sedikit sinar matahari untuk mempercepat pematangan',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal untuk tomat mentah'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'handling',
                'content' => 'Tangani dengan hati-hati, hindari benturan karena mudah memar',
                'order' => 2,
                'is_active' => true,
                'description' => 'Cara penanganan tomat mentah'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'use',
                'content' => 'Belum disarankan untuk konsumsi langsung, tunggu hingga matang',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan tomat mentah'
            ],
            [
                'maturity_level' => 'mentah',
                'category' => 'timeframe',
                'content' => 'Perkiraan 5-7 hari untuk mencapai kematangan optimal',
                'order' => 4,
                'is_active' => true,
                'description' => 'Estimasi waktu pematangan'
            ],

            // Rekomendasi untuk tomat setengah matang
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'storage',
                'content' => 'Simpan pada suhu 15-20°C untuk memperlambat pematangan',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal untuk tomat setengah matang'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'handling',
                'content' => 'Dapat ditangani dengan normal, tahan terhadap transportasi',
                'order' => 2,
                'is_active' => true,
                'description' => 'Cara penanganan tomat setengah matang'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'use',
                'content' => 'Ideal untuk dikirim ke pasar, akan matang dalam beberapa hari',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan tomat setengah matang'
            ],
            [
                'maturity_level' => 'setengah_matang',
                'category' => 'timeframe',
                'content' => 'Perkiraan 2-4 hari untuk mencapai kematangan optimal',
                'order' => 4,
                'is_active' => true,
                'description' => 'Estimasi waktu pematangan'
            ],

            // Rekomendasi untuk tomat matang
            [
                'maturity_level' => 'matang',
                'category' => 'storage',
                'content' => 'Simpan di lemari es (7-10°C) untuk memperpanjang kesegaran',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan optimal untuk tomat matang'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'handling',
                'content' => 'Tangani dengan hati-hati, sudah lebih lunak dan mudah rusak',
                'order' => 2,
                'is_active' => true,
                'description' => 'Cara penanganan tomat matang'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'use',
                'content' => 'Siap untuk konsumsi langsung, ideal untuk salad segar atau saji langsung',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan tomat matang'
            ],
            [
                'maturity_level' => 'matang',
                'category' => 'timeframe',
                'content' => 'Optimal untuk dikonsumsi dalam 2-3 hari',
                'order' => 4,
                'is_active' => true,
                'description' => 'Estimasi waktu konsumsi optimal'
            ],

            // Rekomendasi untuk tomat busuk
            [
                'maturity_level' => 'busuk',
                'category' => 'storage',
                'content' => 'Tidak disarankan untuk disimpan',
                'order' => 1,
                'is_active' => true,
                'description' => 'Penyimpanan untuk tomat busuk'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'handling',
                'content' => 'Pisahkan dari produk lain untuk mencegah kontaminasi',
                'order' => 2,
                'is_active' => true,
                'description' => 'Cara penanganan tomat busuk'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'use',
                'content' => 'Tidak layak konsumsi, sebaiknya dibuang',
                'order' => 3,
                'is_active' => true,
                'description' => 'Penggunaan tomat busuk'
            ],
            [
                'maturity_level' => 'busuk',
                'category' => 'timeframe',
                'content' => 'Segera dibuang untuk menghindari kontaminasi pada produk lain',
                'order' => 4,
                'is_active' => true,
                'description' => 'Tindakan segera untuk tomat busuk'
            ],
        ];

        foreach ($recommendations as $recommendation) {
            Recommendation::create($recommendation);
        }

        $this->command->info('Data rekomendasi berhasil ditambahkan!');
    }
}
