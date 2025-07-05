<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DecisionTreeRule;

class DecisionTreeRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            // Node 1: Root - Evaluasi nilai merah
            [
                'rule_name' => 'Evaluasi Nilai Merah Tinggi',
                'node_type' => 'condition',
                'node_order' => 1,
                'condition_field' => 'red',
                'condition_operator' => '>',
                'condition_value' => 150,
                'true_action' => 'next_node',
                'true_result' => '2',
                'false_action' => 'next_node',
                'false_result' => '3',
                'maturity_class' => null,
                'description' => 'Node root untuk mengevaluasi apakah nilai merah tinggi (>150). Jika ya, lanjut ke node 2, jika tidak ke node 3.',
                'is_active' => true
            ],
            
            // Node 2: Evaluasi rasio merah/hijau untuk nilai merah tinggi
            [
                'rule_name' => 'Evaluasi Rasio Merah/Hijau Tinggi',
                'node_type' => 'condition',
                'node_order' => 2,
                'condition_field' => 'ratio_red_green',
                'condition_operator' => '>',
                'condition_value' => 1.2,
                'true_action' => 'classify',
                'true_result' => 'matang',
                'false_action' => 'next_node',
                'false_result' => '4',
                'maturity_class' => null,
                'description' => 'Untuk nilai merah tinggi, evaluasi rasio merah/hijau. Jika >1.2 maka matang, jika tidak lanjut ke node 4.',
                'is_active' => true
            ],
            
            // Node 3: Evaluasi nilai hijau untuk nilai merah rendah
            [
                'rule_name' => 'Evaluasi Nilai Hijau Rendah',
                'node_type' => 'condition',
                'node_order' => 3,
                'condition_field' => 'green',
                'condition_operator' => '<',
                'condition_value' => 100,
                'true_action' => 'classify',
                'true_result' => 'busuk',
                'false_action' => 'next_node',
                'false_result' => '5',
                'maturity_class' => null,
                'description' => 'Untuk nilai merah rendah, evaluasi nilai hijau. Jika <100 maka busuk, jika tidak lanjut ke node 5.',
                'is_active' => true
            ],
            
            // Node 4: Evaluasi nilai biru untuk kasus merah tinggi tapi rasio rendah
            [
                'rule_name' => 'Evaluasi Nilai Biru Sedang',
                'node_type' => 'condition',
                'node_order' => 4,
                'condition_field' => 'blue',
                'condition_operator' => '>',
                'condition_value' => 80,
                'true_action' => 'classify',
                'true_result' => 'setengah_matang',
                'false_action' => 'classify',
                'false_result' => 'mentah',
                'maturity_class' => null,
                'description' => 'Evaluasi nilai biru. Jika >80 maka setengah matang, jika tidak maka mentah.',
                'is_active' => true
            ],
            
            // Node 5: Evaluasi rasio hijau/biru untuk nilai merah rendah tapi hijau tinggi
            [
                'rule_name' => 'Evaluasi Rasio Hijau/Biru',
                'node_type' => 'condition',
                'node_order' => 5,
                'condition_field' => 'ratio_green_blue',
                'condition_operator' => '>',
                'condition_value' => 1.5,
                'true_action' => 'classify',
                'true_result' => 'mentah',
                'false_action' => 'classify',
                'false_result' => 'setengah_matang',
                'maturity_class' => null,
                'description' => 'Evaluasi rasio hijau/biru. Jika >1.5 maka mentah, jika tidak maka setengah matang.',
                'is_active' => true
            ],
            
            // Leaf nodes untuk hasil klasifikasi langsung
            [
                'rule_name' => 'Hasil Matang',
                'node_type' => 'leaf',
                'node_order' => 10,
                'condition_field' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'true_action' => null,
                'true_result' => null,
                'false_action' => null,
                'false_result' => null,
                'maturity_class' => 'matang',
                'description' => 'Leaf node untuk klasifikasi matang - tomat dengan warna merah dominan dan rasio optimal.',
                'is_active' => true
            ],
            
            [
                'rule_name' => 'Hasil Setengah Matang',
                'node_type' => 'leaf',
                'node_order' => 11,
                'condition_field' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'true_action' => null,
                'true_result' => null,
                'false_action' => null,
                'false_result' => null,
                'maturity_class' => 'setengah_matang',
                'description' => 'Leaf node untuk klasifikasi setengah matang - tomat dengan kombinasi warna transisi.',
                'is_active' => true
            ],
            
            [
                'rule_name' => 'Hasil Mentah',
                'node_type' => 'leaf',
                'node_order' => 12,
                'condition_field' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'true_action' => null,
                'true_result' => null,
                'false_action' => null,
                'false_result' => null,
                'maturity_class' => 'mentah',
                'description' => 'Leaf node untuk klasifikasi mentah - tomat dengan dominasi warna hijau.',
                'is_active' => true
            ],
            
            [
                'rule_name' => 'Hasil Busuk',
                'node_type' => 'leaf',
                'node_order' => 13,
                'condition_field' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'true_action' => null,
                'true_result' => null,
                'false_action' => null,
                'false_result' => null,
                'maturity_class' => 'busuk',
                'description' => 'Leaf node untuk klasifikasi busuk - tomat dengan nilai warna rendah atau tidak normal.',
                'is_active' => true
            ]
        ];
        
        foreach ($rules as $rule) {
            DecisionTreeRule::create($rule);
        }
    }
}
