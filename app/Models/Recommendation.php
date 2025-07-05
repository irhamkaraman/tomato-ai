<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'maturity_level',
        'category',
        'content',
        'order',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMaturityLevel($query, $maturityLevel)
    {
        return $query->where('maturity_level', $maturityLevel);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    // Constants untuk maturity levels
    public const MATURITY_LEVELS = [
        'mentah' => 'Mentah',
        'setengah_matang' => 'Setengah Matang',
        'matang' => 'Matang',
        'busuk' => 'Busuk'
    ];

    // Constants untuk categories
    public const CATEGORIES = [
        'storage' => 'Penyimpanan',
        'handling' => 'Penanganan',
        'use' => 'Penggunaan',
        'timeframe' => 'Waktu'
    ];

    // Helper method untuk mendapatkan rekomendasi berdasarkan maturity level
    public static function getRecommendationsByMaturityLevel($maturityLevel)
    {
        return self::active()
            ->byMaturityLevel($maturityLevel)
            ->ordered()
            ->get()
            ->groupBy('category')
            ->map(function ($recommendations) {
                return $recommendations->pluck('content')->toArray();
            })
            ->toArray();
    }
}
