<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecisionTreeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'node_type',
        'node_order',
        'condition_field',
        'condition_operator',
        'condition_value',
        'true_action',
        'false_action',
        'true_result',
        'false_result',
        'maturity_class',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'condition_value' => 'decimal:2'
    ];

    /**
     * Scope untuk aturan yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk aturan berdasarkan tipe node
     */
    public function scopeByNodeType($query, $nodeType)
    {
        return $query->where('node_type', $nodeType);
    }

    /**
     * Scope untuk aturan berdasarkan nama aturan
     */
    public function scopeByRuleName($query, $ruleName)
    {
        return $query->where('rule_name', $ruleName);
    }

    /**
     * Scope untuk aturan yang diurutkan berdasarkan node_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('node_order', 'asc');
    }

    /**
     * Evaluasi kondisi berdasarkan data RGB
     */
    public function evaluateCondition($colorData)
    {
        if ($this->node_type !== 'condition') {
            return null;
        }

        $fieldValue = $this->getFieldValue($colorData);
        $conditionValue = $this->condition_value;
        
        switch ($this->condition_operator) {
            case '>':
                return $fieldValue > $conditionValue;
            case '<':
                return $fieldValue < $conditionValue;
            case '>=':
                return $fieldValue >= $conditionValue;
            case '<=':
                return $fieldValue <= $conditionValue;
            case '==':
                return $fieldValue == $conditionValue;
            default:
                return false;
        }
    }

    /**
     * Ambil nilai field dari data warna
     */
    private function getFieldValue($colorData)
    {
        switch ($this->condition_field) {
            case 'red':
                return $colorData['red'];
            case 'green':
                return $colorData['green'];
            case 'blue':
                return $colorData['blue'];
            case 'ratio_red_green':
                return $colorData['green'] > 0 ? $colorData['red'] / $colorData['green'] : 0;
            case 'ratio_red_blue':
                return $colorData['blue'] > 0 ? $colorData['red'] / $colorData['blue'] : 0;
            case 'ratio_green_blue':
                return $colorData['blue'] > 0 ? $colorData['green'] / $colorData['blue'] : 0;
            default:
                return 0;
        }
    }
}
