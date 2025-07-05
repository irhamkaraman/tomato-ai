<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\ModelEvaluationService;
use App\Models\ModelAccuracy;
use App\Models\TrainingData;

class AccuracyMetricsWidget extends Widget
{
    protected static string $view = 'filament.widgets.accuracy-metrics';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    // Refresh setiap 10 menit
    protected static ?string $pollingInterval = '600s';
    
    public function getViewData(): array
    {
        $evaluationService = new ModelEvaluationService();
        $latestAccuracies = ModelAccuracy::getLatestAccuracies();
        $trainingDataCount = TrainingData::active()->count();
        
        $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];
        $metricsData = [];
        
        foreach ($algorithms as $algorithm) {
            $accuracy = $latestAccuracies[$algorithm] ?? null;
            $detailedMetrics = $accuracy ? $accuracy->detailed_metrics : null;
            $confusionMatrix = $accuracy ? $accuracy->confusion_matrix : null;
            
            // Jika belum ada data, hitung metrics baru
            if (!$detailedMetrics && $trainingDataCount >= 10) {
                $detailedMetrics = $evaluationService->getDetailedMetrics($algorithm);
                $confusionMatrix = $evaluationService->getConfusionMatrix($algorithm);
            }
            
            $metricsData[$algorithm] = [
                'name' => $this->getAlgorithmName($algorithm),
                'accuracy' => $accuracy ? $accuracy->accuracy : $evaluationService->getCurrentAccuracy($algorithm),
                'detailed_metrics' => $detailedMetrics,
                'confusion_matrix' => $confusionMatrix,
                'last_updated' => $accuracy ? $accuracy->calculated_at->diffForHumans() : 'Belum dievaluasi',
                'data_count' => $accuracy ? $accuracy->data_count : $trainingDataCount,
                'trend' => $this->getAccuracyTrend($algorithm, $accuracy)
            ];
        }
        
        return [
            'metrics' => $metricsData,
            'training_data_count' => $trainingDataCount,
            'evaluation_status' => $this->getEvaluationStatus($trainingDataCount),
            'best_algorithm' => $this->getBestAlgorithm($metricsData),
            'accuracy_history' => $this->getAccuracyHistory()
        ];
    }
    
    /**
     * Dapatkan nama algoritma yang readable
     */
    private function getAlgorithmName($algorithm)
    {
        return match($algorithm) {
            'decision_tree' => 'Decision Tree',
            'knn' => 'K-Nearest Neighbors',
            'random_forest' => 'Random Forest',
            'ensemble' => 'Ensemble Learning',
            default => ucfirst(str_replace('_', ' ', $algorithm))
        };
    }
    
    /**
     * Dapatkan trend akurasi
     */
    private function getAccuracyTrend($algorithm, $currentAccuracy)
    {
        if (!$currentAccuracy) {
            return ['direction' => 'new', 'change' => 0];
        }
        
        $previous = ModelAccuracy::forAlgorithm($algorithm)
            ->where('calculated_at', '<', $currentAccuracy->calculated_at)
            ->latest()
            ->first();
            
        if (!$previous) {
            return ['direction' => 'new', 'change' => 0];
        }
        
        $change = round($currentAccuracy->accuracy - $previous->accuracy, 2);
        
        if ($change > 1) {
            $direction = 'up';
        } elseif ($change < -1) {
            $direction = 'down';
        } else {
            $direction = 'stable';
        }
        
        return ['direction' => $direction, 'change' => $change];
    }
    
    /**
     * Dapatkan status evaluasi
     */
    private function getEvaluationStatus($trainingDataCount)
    {
        if ($trainingDataCount < 10) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Data training kurang dari 10. Diperlukan minimal 10 data untuk evaluasi akurat.',
                'color' => 'warning'
            ];
        }
        
        if ($trainingDataCount < 50) {
            return [
                'status' => 'limited_data',
                'message' => 'Data training terbatas. Disarankan minimal 50 data untuk hasil optimal.',
                'color' => 'info'
            ];
        }
        
        return [
            'status' => 'sufficient_data',
            'message' => 'Data training mencukupi untuk evaluasi akurat.',
            'color' => 'success'
        ];
    }
    
    /**
     * Dapatkan algoritma terbaik
     */
    private function getBestAlgorithm($metricsData)
    {
        $best = null;
        $highestAccuracy = 0;
        
        foreach ($metricsData as $algorithm => $data) {
            if ($data['accuracy'] > $highestAccuracy) {
                $highestAccuracy = $data['accuracy'];
                $best = [
                    'algorithm' => $algorithm,
                    'name' => $data['name'],
                    'accuracy' => $data['accuracy']
                ];
            }
        }
        
        return $best;
    }
    
    /**
     * Dapatkan riwayat akurasi untuk chart
     */
    private function getAccuracyHistory($limit = 10)
    {
        $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];
        $history = [];
        
        foreach ($algorithms as $algorithm) {
            $records = ModelAccuracy::forAlgorithm($algorithm)
                ->latest()
                ->limit($limit)
                ->get()
                ->reverse();
                
            $history[$algorithm] = $records->map(function($record) {
                return [
                    'date' => $record->calculated_at->format('M d'),
                    'accuracy' => $record->accuracy,
                    'data_count' => $record->data_count
                ];
            })->toArray();
        }
        
        return $history;
    }
    
    /**
     * Trigger evaluasi ulang semua algoritma
     */
    public function evaluateAllAlgorithms()
    {
        $evaluationService = new ModelEvaluationService();
        
        try {
            $results = $evaluationService->calculateRealTimeAccuracy();
            
            // Simpan detailed metrics
            foreach ($results as $algorithm => $accuracy) {
                $detailedMetrics = $evaluationService->getDetailedMetrics($algorithm);
                $confusionMatrix = $evaluationService->getConfusionMatrix($algorithm);
                
                ModelAccuracy::updateOrCreate(
                    ['algorithm' => $algorithm],
                    [
                        'accuracy' => $accuracy,
                        'calculated_at' => now(),
                        'data_count' => TrainingData::active()->count(),
                        'detailed_metrics' => $detailedMetrics,
                        'confusion_matrix' => $confusionMatrix,
                        'notes' => 'Evaluasi manual dari dashboard'
                    ]
                );
            }
            
            // Refresh widget
            $this->dispatch('$refresh');
            
            \Filament\Notifications\Notification::make()
                ->title('Evaluasi Berhasil')
                ->body('Semua algoritma telah dievaluasi dengan data training terbaru.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Evaluasi Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Export metrics ke Excel
     */
    public function exportMetrics()
    {
        // TODO: Implementasi export metrics ke Excel
        \Filament\Notifications\Notification::make()
            ->title('Export Metrics')
            ->body('Fitur export akan segera tersedia.')
            ->info()
            ->send();
    }
}