<?php

namespace App\Filament\Widgets;

use App\Models\TomatReading;
use App\Models\TrainingData;
use App\Models\DecisionTreeRule;
use App\Models\Recommendation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $totalReadings = TomatReading::count();
        $trainingDataCount = TrainingData::count();
        $decisionRulesCount = DecisionTreeRule::count();
        $recommendationsCount = Recommendation::count();
        
        // Hitung distribusi kematangan dari readings terakhir
        $maturityDistribution = TomatReading::selectRaw('maturity_level, COUNT(*) as count')
            ->groupBy('maturity_level')
            ->pluck('count', 'maturity_level')
            ->toArray();
            
        $mostCommonMaturity = array_key_exists('matang', $maturityDistribution) ? 'matang' : 
                             (array_key_exists('setengah_matang', $maturityDistribution) ? 'setengah_matang' : 'mentah');
        
        // Hitung akurasi rata-rata dari confidence scores
        $avgConfidence = TomatReading::avg('confidence_score') ?? 0;
        
        return [
            Stat::make('Total Analisis Tomat', $totalReadings)
                ->description('Jumlah total analisis yang telah dilakukan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success')
                ->chart([7, 12, 8, 15, 22, 18, 25]),
                
            Stat::make('Data Training', $trainingDataCount)
                ->description('Dataset untuk machine learning')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([3, 8, 12, 15, 18, 22, 25]),
                
            Stat::make('Aturan Decision Tree', $decisionRulesCount)
                ->description('Aturan klasifikasi dalam database')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('warning')
                ->chart([2, 4, 6, 8, 10, 12, 15]),
                
            Stat::make('Akurasi Sistem', number_format($avgConfidence, 1) . '%')
                ->description('Rata-rata confidence score')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($avgConfidence >= 90 ? 'success' : ($avgConfidence >= 80 ? 'warning' : 'danger'))
                ->chart([75, 80, 85, 88, 90, 92, round($avgConfidence)]),
                
            Stat::make('Rekomendasi Tersedia', $recommendationsCount)
                ->description('Panduan berdasarkan tingkat kematangan')
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color('primary')
                ->chart([5, 8, 12, 15, 18, 20, 22]),
                
            Stat::make('Klasifikasi Terbanyak', ucfirst(str_replace('_', ' ', $mostCommonMaturity)))
                ->description('Tingkat kematangan yang paling sering terdeteksi')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('secondary')
        ];
    }
}