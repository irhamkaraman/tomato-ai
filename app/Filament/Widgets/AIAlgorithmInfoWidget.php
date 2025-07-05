<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\ModelEvaluationService;
use App\Models\ModelAccuracy;
use App\Models\TrainingData;

class AIAlgorithmInfoWidget extends Widget
{
    protected static string $view = 'filament.widgets.ai-algorithm-info';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    // Refresh widget setiap 5 menit untuk akurasi real-time
    protected static ?string $pollingInterval = '300s';
    
    public function getViewData(): array
    {
        $evaluationService = new ModelEvaluationService();
        $latestAccuracies = ModelAccuracy::getLatestAccuracies();
        $trainingDataCount = TrainingData::active()->count();
        
        return [
            'algorithms' => [
                [
                    'name' => 'Decision Tree',
                    'description' => 'Algoritma pohon keputusan yang menggunakan aturan berbasis database untuk klasifikasi kematangan tomat berdasarkan nilai RGB.',
                    'features' => [
                        'Dynamic rule management dari database',
                        'Tree traversal dari root ke leaf node',
                        'Threshold-based classification',
                        'Transparent decision path tracking'
                    ],
                    'accuracy' => $this->getFormattedAccuracy('decision_tree', $latestAccuracies, $evaluationService),
                    'accuracy_trend' => $this->getAccuracyTrend('decision_tree', $latestAccuracies),
                    'last_updated' => $this->getLastUpdated('decision_tree', $latestAccuracies),
                    'data_count' => $trainingDataCount,
                    'icon' => 'heroicon-o-squares-2x2',
                    'color' => 'success'
                ],
                [
                    'name' => 'K-Nearest Neighbors (KNN)',
                    'description' => 'Algoritma pembelajaran mesin yang mengklasifikasikan berdasarkan k tetangga terdekat menggunakan jarak Euclidean.',
                    'features' => [
                        'K=3 untuk optimal balance',
                        'Euclidean distance calculation',
                        'Majority voting mechanism',
                        'Training data dari database'
                    ],
                    'accuracy' => $this->getFormattedAccuracy('knn', $latestAccuracies, $evaluationService),
                    'accuracy_trend' => $this->getAccuracyTrend('knn', $latestAccuracies),
                    'last_updated' => $this->getLastUpdated('knn', $latestAccuracies),
                    'data_count' => $trainingDataCount,
                    'icon' => 'heroicon-o-map-pin',
                    'color' => 'info'
                ],
                [
                    'name' => 'Random Forest',
                    'description' => 'Ensemble method yang menggunakan multiple decision trees dengan variasi parameter untuk meningkatkan akurasi.',
                    'features' => [
                        '3 decision trees (standard, conservative, liberal)',
                        'Tree diversity untuk robustness',
                        'Majority voting dari ensemble',
                        'Agreement analysis antar trees'
                    ],
                    'accuracy' => $this->getFormattedAccuracy('random_forest', $latestAccuracies, $evaluationService),
                    'accuracy_trend' => $this->getAccuracyTrend('random_forest', $latestAccuracies),
                    'last_updated' => $this->getLastUpdated('random_forest', $latestAccuracies),
                    'data_count' => $trainingDataCount,
                    'icon' => 'heroicon-o-squares-plus',
                    'color' => 'warning'
                ],
                [
                    'name' => 'Ensemble Learning',
                    'description' => 'Kombinasi ketiga algoritma menggunakan weighted voting untuk hasil klasifikasi yang optimal.',
                    'features' => [
                        'Weighted voting mechanism',
                        'Confidence score calculation',
                        'Fuzzy logic integration',
                        'Multi-algorithm consensus'
                    ],
                    'accuracy' => $this->getFormattedAccuracy('ensemble', $latestAccuracies, $evaluationService),
                    'accuracy_trend' => $this->getAccuracyTrend('ensemble', $latestAccuracies),
                    'last_updated' => $this->getLastUpdated('ensemble', $latestAccuracies),
                    'data_count' => $trainingDataCount,
                    'icon' => 'heroicon-o-cpu-chip',
                    'color' => 'danger'
                ]
            ],
            'maturity_levels' => [
                ['name' => 'Mentah', 'color' => 'bg-green-500', 'description' => 'Tomat hijau, belum matang'],
                ['name' => 'Setengah Matang', 'color' => 'bg-yellow-500', 'description' => 'Tomat mulai berubah warna'],
                ['name' => 'Matang', 'color' => 'bg-red-500', 'description' => 'Tomat merah, siap konsumsi'],
                ['name' => 'Busuk', 'color' => 'bg-gray-500', 'description' => 'Tomat tidak layak konsumsi']
            ],
            'last_evaluation' => $this->getLastEvaluationTime($latestAccuracies),
            'total_training_data' => $trainingDataCount
        ];
    }
    
    /**
     * Dapatkan akurasi yang diformat untuk algoritma
     */
    private function getFormattedAccuracy($algorithm, $latestAccuracies, $evaluationService)
    {
        if (isset($latestAccuracies[$algorithm])) {
            return number_format($latestAccuracies[$algorithm]->accuracy, 1) . '%';
        }
        
        // Jika belum ada data, ambil dari getCurrentAccuracies
        $accuracies = $evaluationService->getCurrentAccuracies();
        $accuracy = $accuracies[$algorithm] ?? 0;
        return number_format($accuracy, 1) . '%';
    }
    
    /**
     * Dapatkan trend akurasi (naik/turun/stabil)
     */
    private function getAccuracyTrend($algorithm, $latestAccuracies)
    {
        if (!isset($latestAccuracies[$algorithm])) {
            return 'new';
        }
        
        $current = $latestAccuracies[$algorithm];
        $previous = ModelAccuracy::forAlgorithm($algorithm)
            ->where('calculated_at', '<', $current->calculated_at)
            ->latest()
            ->first();
            
        if (!$previous) {
            return 'new';
        }
        
        $diff = $current->accuracy - $previous->accuracy;
        
        if ($diff > 1) {
            return 'up';
        } elseif ($diff < -1) {
            return 'down';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Dapatkan waktu update terakhir
     */
    private function getLastUpdated($algorithm, $latestAccuracies)
    {
        if (isset($latestAccuracies[$algorithm])) {
            return $latestAccuracies[$algorithm]->calculated_at->diffForHumans();
        }
        
        return 'Belum dievaluasi';
    }
    
    /**
     * Dapatkan waktu evaluasi terakhir secara keseluruhan
     */
    private function getLastEvaluationTime($latestAccuracies)
    {
        if ($latestAccuracies->isEmpty()) {
            return 'Belum pernah dievaluasi';
        }
        
        $latest = $latestAccuracies->sortByDesc('calculated_at')->first();
        return $latest->calculated_at->diffForHumans();
    }
    
    /**
     * Trigger evaluasi ulang akurasi
     */
    public function refreshAccuracy()
    {
        $evaluationService = new ModelEvaluationService();
        $evaluationService->resetAccuracyCache();
        $evaluationService->calculateRealTimeAccuracy();
        
        // Refresh widget
        $this->dispatch('$refresh');
        
        // Notifikasi
        \Filament\Notifications\Notification::make()
            ->title('Akurasi Berhasil Diperbarui')
            ->body('Semua algoritma telah dievaluasi ulang dengan data training terbaru.')
            ->success()
            ->send();
    }
}