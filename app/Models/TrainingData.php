<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'red_value',
        'green_value',
        'blue_value',
        'maturity_class',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk data training yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter berdasarkan kelas kematangan
     */
    public function scopeByMaturityClass($query, $class)
    {
        return $query->where('maturity_class', $class);
    }
}
