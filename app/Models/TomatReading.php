<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TomatReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'red_value',
        'green_value',
        'blue_value',
        'clear_value',
        'temperature',
        'humidity',
        'maturity_level',
        'status',
        'confidence_score',
        'recommendations',
        'ml_analysis',
        'raw_sensor_data'
    ];

    protected $casts = [
        'raw_sensor_data' => 'array',
        'recommendations' => 'array',
        'ml_analysis' => 'array',
        'confidence_score' => 'float',
        'temperature' => 'float',
        'humidity' => 'float',
    ];
}
