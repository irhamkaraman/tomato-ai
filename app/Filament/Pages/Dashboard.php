<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AIAlgorithmInfoWidget;
use App\Filament\Widgets\SystemStatsWidget;
use App\Filament\Widgets\RGBTestWidget;
use App\Services\ModelEvaluationService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        return 'Dashboard Sistem Pakar Kematangan Tomat';
    }

    public function getSubheading(): ?string
    {
        return 'Sistem Kecerdasan Buatan untuk Klasifikasi Kematangan Tomat Berbasis Ensemble Learning';
    }

    public function getWidgets(): array
    {
        return [
            SystemStatsWidget::class,
            AIAlgorithmInfoWidget::class,
            RGBTestWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }

    /**
     * Mendapatkan akurasi ensemble learning untuk ditampilkan di dashboard
     */
    public function getEnsembleAccuracy(): float
    {
        try {
            $modelEvaluationService = app(ModelEvaluationService::class);
            $accuracy = $modelEvaluationService->getCurrentAccuracy('ensemble');
            return round($accuracy, 1);
        } catch (\Exception $e) {
            // Fallback ke nilai default jika terjadi error
            return 88.0;
        }
    }

    /**
     * Mengirim data ke view
     */
    protected function getViewData(): array
    {
        return [
            'ensembleAccuracy' => $this->getEnsembleAccuracy(),
        ];
    }
}
