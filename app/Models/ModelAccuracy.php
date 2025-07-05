<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ModelAccuracy extends Model
{
    use HasFactory;

    protected $fillable = [
        'algorithm',
        'accuracy',
        'data_count',
        'calculated_at',
        'confusion_matrix',
        'detailed_metrics',
        'notes'
    ];

    protected $casts = [
        'accuracy' => 'decimal:2',
        'calculated_at' => 'datetime',
        'confusion_matrix' => 'array',
        'detailed_metrics' => 'array'
    ];

    /**
     * Scope untuk mendapatkan akurasi terbaru per algoritma
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }

    /**
     * Scope untuk algoritma tertentu
     */
    public function scopeForAlgorithm($query, $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    /**
     * Dapatkan akurasi terbaru untuk semua algoritma
     */
    public static function getLatestAccuracies()
    {
        // Menggunakan pendekatan yang kompatibel dengan MySQL
        $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];
        $results = collect();
        
        foreach ($algorithms as $algorithm) {
            $latest = self::select('algorithm', 'accuracy', 'calculated_at', 'data_count')
                ->where('algorithm', $algorithm)
                ->orderBy('calculated_at', 'desc')
                ->first();
                
            if ($latest) {
                $results->put($algorithm, $latest);
            }
        }
        
        return $results;
    }

    /**
     * Dapatkan riwayat akurasi untuk algoritma tertentu
     */
    public static function getAccuracyHistory($algorithm, $limit = 10)
    {
        return self::forAlgorithm($algorithm)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Accessor untuk format akurasi dengan persen
     */
    protected function formattedAccuracy(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->accuracy, 1) . '%'
        );
    }

    /**
     * Accessor untuk nama algoritma yang lebih readable
     */
    protected function algorithmName(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->algorithm) {
                'decision_tree' => 'Decision Tree',
                'knn' => 'K-Nearest Neighbors',
                'random_forest' => 'Random Forest',
                'ensemble' => 'Ensemble Learning',
                default => ucfirst(str_replace('_', ' ', $this->algorithm))
            }
        );
    }

    /**
     * Dapatkan trend akurasi (naik/turun/stabil)
     */
    public function getTrend()
    {
        $previous = self::forAlgorithm($this->algorithm)
            ->where('calculated_at', '<', $this->calculated_at)
            ->latest()
            ->first();

        if (!$previous) {
            return 'new';
        }

        $diff = $this->accuracy - $previous->accuracy;

        if ($diff > 1) {
            return 'up';
        } elseif ($diff < -1) {
            return 'down';
        } else {
            return 'stable';
        }
    }

    /**
     * Dapatkan perubahan akurasi dari evaluasi sebelumnya
     */
    public function getAccuracyChange()
    {
        $previous = self::forAlgorithm($this->algorithm)
            ->where('calculated_at', '<', $this->calculated_at)
            ->latest()
            ->first();

        if (!$previous) {
            return null;
        }

        return round($this->accuracy - $previous->accuracy, 2);
    }

    /**
     * Cek apakah akurasi ini adalah yang terbaik untuk algoritma
     */
    public function isBestAccuracy()
    {
        $best = self::forAlgorithm($this->algorithm)
            ->orderBy('accuracy', 'desc')
            ->first();

        return $best && $best->id === $this->id;
    }
}