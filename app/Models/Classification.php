<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = [
        'red_value',
        'green_value',
        'blue_value',
        'clear_value',
        'actual_status',
        'predicted_status',
        'classification_result',
        'notes',
        'device_id',
        'is_verified'
    ];

    protected $casts = [
        'red_value' => 'integer',
        'green_value' => 'integer',
        'blue_value' => 'integer',
        'clear_value' => 'integer',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Konstanta untuk status kematangan
    public const STATUS_MENTAH = 'Mentah';
    public const STATUS_SETENGAH_MATANG = 'Setengah Matang';
    public const STATUS_MATANG = 'Matang';
    public const STATUS_BUSUK = 'Busuk';

    // Konstanta untuk hasil klasifikasi
    public const RESULT_BENAR = 'Benar';
    public const RESULT_SALAH = 'Salah';

    /**
     * Mendapatkan semua status kematangan yang tersedia
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_MENTAH => 'Mentah',
            self::STATUS_SETENGAH_MATANG => 'Setengah Matang',
            self::STATUS_MATANG => 'Matang',
            self::STATUS_BUSUK => 'Busuk'
        ];
    }

    /**
     * Mendapatkan opsi hasil klasifikasi
     */
    public static function getClassificationResultOptions(): array
    {
        return [
            self::RESULT_BENAR => 'Benar',
            self::RESULT_SALAH => 'Salah'
        ];
    }

    /**
     * Scope untuk data yang sudah diverifikasi
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope untuk data yang belum diverifikasi
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope untuk klasifikasi yang benar
     */
    public function scopeCorrect(Builder $query): Builder
    {
        return $query->where('classification_result', self::RESULT_BENAR);
    }

    /**
     * Scope untuk klasifikasi yang salah
     */
    public function scopeIncorrect(Builder $query): Builder
    {
        return $query->where('classification_result', self::RESULT_SALAH);
    }

    /**
     * Mendapatkan akurasi klasifikasi dalam persentase
     */
    public static function getAccuracyPercentage(): float
    {
        $total = self::verified()->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $correct = self::verified()->correct()->count();
        
        return round(($correct / $total) * 100, 2);
    }

    /**
     * Mendapatkan statistik klasifikasi per status
     */
    public static function getStatusStatistics(): array
    {
        $statistics = [];
        
        foreach (self::getStatusOptions() as $status => $label) {
            $total = self::verified()->where('actual_status', $status)->count();
            $correct = self::verified()->where('actual_status', $status)->correct()->count();
            
            $statistics[$status] = [
                'label' => $label,
                'total' => $total,
                'correct' => $correct,
                'incorrect' => $total - $correct,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 2) : 0
            ];
        }
        
        return $statistics;
    }

    /**
     * Menentukan apakah klasifikasi benar atau salah secara otomatis
     */
    public function determineClassificationResult(): string
    {
        return $this->actual_status === $this->predicted_status 
            ? self::RESULT_BENAR 
            : self::RESULT_SALAH;
    }

    /**
     * Boot method untuk model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Otomatis set classification_result saat creating atau updating
        static::saving(function ($classification) {
            if ($classification->actual_status && $classification->predicted_status) {
                $classification->classification_result = $classification->actual_status === $classification->predicted_status ? 'Benar' : 'Salah';
            }
        });
    }

    /**
     * Accessor untuk mendapatkan warna RGB dalam format hex
     */
    public function getRgbHexAttribute(): string
    {
        return sprintf('#%02x%02x%02x', $this->red_value, $this->green_value, $this->blue_value);
    }

    /**
     * Accessor untuk mendapatkan warna RGB dalam format string
     */
    public function getRgbStringAttribute(): string
    {
        return "RGB({$this->red_value}, {$this->green_value}, {$this->blue_value})";
    }
}