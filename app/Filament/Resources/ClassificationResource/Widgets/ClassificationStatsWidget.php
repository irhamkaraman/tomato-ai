<?php

namespace App\Filament\Resources\ClassificationResource\Widgets;

use App\Models\Classification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClassificationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalClassifications = Classification::verified()->count();
        $correctClassifications = Classification::verified()->correct()->count();
        $incorrectClassifications = Classification::verified()->incorrect()->count();
        $unverifiedClassifications = Classification::unverified()->count();
        $accuracyPercentage = Classification::getAccuracyPercentage();
        
        return [
            Stat::make('Total Data Terverifikasi', $totalClassifications)
                ->description('Data klasifikasi yang sudah diverifikasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
                
            Stat::make('Akurasi AI', $accuracyPercentage . '%')
                ->description($correctClassifications . ' benar dari ' . $totalClassifications . ' data')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($accuracyPercentage >= 80 ? 'success' : ($accuracyPercentage >= 60 ? 'warning' : 'danger')),
                
            Stat::make('Prediksi Benar', $correctClassifications)
                ->description('Klasifikasi yang tepat')
                ->descriptionIcon('heroicon-m-check')
                ->color('success'),
                
            Stat::make('Prediksi Salah', $incorrectClassifications)
                ->description('Klasifikasi yang tidak tepat')
                ->descriptionIcon('heroicon-m-x-mark')
                ->color('danger'),
                
            Stat::make('Belum Diverifikasi', $unverifiedClassifications)
                ->description('Data yang perlu diverifikasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
