<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DecisionTreeRule;

/**
 * DecisionTreeRuleSeeder - Aturan decision tree berdasarkan penelitian ilmiah
 * 
 * Referensi:
 * - USDA Classification Standards for Tomato Ripeness (7 stages)
 * - Research: "Color-based classification of tomato ripeness using machine learning"
 * - Research: "RGB color space analysis for tomato maturity detection"
 * - Research: "Decision tree algorithms for fruit quality assessment"
 * 
 * Algoritma menggunakan hierarchical decision tree dengan threshold yang dioptimasi
 * berdasarkan analisis statistik dari dataset penelitian.
 */
class DecisionTreeRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            // === NODE 1: ROOT - EVALUASI PRIMER BERDASARKAN NILAI MERAH ===
            [
                'rule_name' => 'Primary Red Value Assessment',
                'node_type' => 'condition',
                'node_order' => 1,
                'condition_field' => 'red',
                'condition_operator' => '>',
                'condition_value' => 130,
                'true_action' => 'next_node',
                'true_result' => '2',
                'false_action' => 'next_node',
                'false_result' => '6',
                'maturity_class' => null,
                'description' => 'Root node: Threshold 130 berdasarkan analisis statistik untuk memisahkan tomat dengan pigmentasi merah (>130) vs hijau dominan (≤130)',
                'is_active' => true
            ],
            
            // === NODE 2: EVALUASI KEMATANGAN LANJUT (RED > 130) ===
            [
                'rule_name' => 'Advanced Ripeness Assessment',
                'node_type' => 'condition',
                'node_order' => 2,
                'condition_field' => 'red',
                'condition_operator' => '>',
                'condition_value' => 180,
                'true_action' => 'next_node',
                'true_result' => '3',
                'false_action' => 'next_node',
                'false_result' => '4',
                'maturity_class' => null,
                'description' => 'Untuk red >130: Threshold 180 memisahkan fully ripe (>180) vs transitional stages (130-180)',
                'is_active' => true
            ],
            
            // === NODE 3: KLASIFIKASI FULLY RIPE (RED > 180) ===
            [
                'rule_name' => 'Fully Ripe Classification',
                'node_type' => 'condition',
                'node_order' => 3,
                'condition_field' => 'green',
                'condition_operator' => '<',
                'condition_value' => 80,
                'true_action' => 'classify',
                'true_result' => 'matang',
                'false_action' => 'classify',
                'false_result' => 'busuk',
                'maturity_class' => null,
                'description' => 'Red >180: Green <80 = matang (degradasi klorofil sempurna), Green ≥80 = busuk (overripe/deteriorating)',
                'is_active' => true
            ],
            
            // === NODE 4: KLASIFIKASI TRANSITIONAL STAGES (RED 130-180) ===
            [
                'rule_name' => 'Transitional Stages Classification',
                'node_type' => 'condition',
                'node_order' => 4,
                'condition_field' => 'green',
                'condition_operator' => '<',
                'condition_value' => 110,
                'true_action' => 'next_node',
                'true_result' => '5',
                'false_action' => 'classify',
                'false_result' => 'setengah_matang',
                'maturity_class' => null,
                'description' => 'Red 130-180: Green <110 = lanjut evaluasi (node 5), Green ≥110 = setengah_matang (turning/pink stage)',
                'is_active' => true
            ],
            
            // === NODE 5: FINE-TUNING LIGHT RED vs SETENGAH MATANG ===
            [
                'rule_name' => 'Light Red vs Setengah Matang',
                'node_type' => 'condition',
                'node_order' => 5,
                'condition_field' => 'red',
                'condition_operator' => '>',
                'condition_value' => 155,
                'true_action' => 'classify',
                'true_result' => 'matang',
                'false_action' => 'classify',
                'false_result' => 'setengah_matang',
                'maturity_class' => null,
                'description' => 'Red 130-180 & Green <110: Red >155 = matang (light red stage), Red ≤155 = setengah_matang (breaker/early turning)',
                'is_active' => true
            ],
            
            // === NODE 6: EVALUASI TOMAT HIJAU DOMINAN (RED ≤ 130) ===
            [
                'rule_name' => 'Green Dominant Assessment',
                'node_type' => 'condition',
                'node_order' => 6,
                'condition_field' => 'green',
                'condition_operator' => '>',
                'condition_value' => 120,
                'true_action' => 'next_node',
                'true_result' => '7',
                'false_action' => 'next_node',
                'false_result' => '8',
                'maturity_class' => null,
                'description' => 'Red ≤130: Green >120 = mature green candidates (node 7), Green ≤120 = potential deterioration (node 8)',
                'is_active' => true
            ],
            
            // === NODE 7: KLASIFIKASI MATURE GREEN ===
            [
                'rule_name' => 'Mature Green Classification',
                'node_type' => 'condition',
                'node_order' => 7,
                'condition_field' => 'blue',
                'condition_operator' => '>',
                'condition_value' => 50,
                'true_action' => 'classify',
                'true_result' => 'mentah',
                'false_action' => 'classify',
                'false_result' => 'busuk',
                'maturity_class' => null,
                'description' => 'Red ≤130 & Green >120: Blue >50 = mentah (healthy mature green), Blue ≤50 = busuk (poor quality)',
                'is_active' => true
            ],
            
            // === NODE 8: EVALUASI DETERIORATION vs EARLY BREAKER ===
            [
                'rule_name' => 'Deterioration vs Early Breaker',
                'node_type' => 'condition',
                'node_order' => 8,
                'condition_field' => 'red',
                'condition_operator' => '>',
                'condition_value' => 90,
                'true_action' => 'classify',
                'true_result' => 'setengah_matang',
                'false_action' => 'classify',
                'false_result' => 'busuk',
                'maturity_class' => null,
                'description' => 'Red ≤130 & Green ≤120: Red >90 = setengah_matang (early breaker), Red ≤90 = busuk (deteriorating)',
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
