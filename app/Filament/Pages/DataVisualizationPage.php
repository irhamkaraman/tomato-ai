<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\ModelEvaluationService;
use App\Models\ModelAccuracy;
use App\Models\TrainingData;
use App\Models\Classification;
use Illuminate\Support\Facades\DB;

class DataVisualizationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.data-visualization';

    protected static ?string $title = 'Visualisasi Data & Analisis';

    protected static ?string $navigationLabel = 'Visualisasi Data';

    protected static ?string $navigationGroup = 'Klasifikasi AI';

    protected static ?int $navigationSort = 3;

    // Refresh setiap 5 detik
    protected static ?string $pollingInterval = '5s';

    // Cache untuk data view
    protected ?array $cachedViewData = null;

    public function getViewData(): array
    {
        // Gunakan cache jika sudah ada
        if ($this->cachedViewData !== null) {
            return $this->cachedViewData;
        }

        $evaluationService = new ModelEvaluationService();

        $this->cachedViewData = [
            'distribution_data' => $this->getDistributionData(),
            'confusion_matrices' => $this->getConfusionMatrices($evaluationService),
            'algorithm_performance' => $this->getAlgorithmPerformance(),
            'rgb_analysis' => $this->getRGBAnalysis(),
            'evaluation_summary' => $this->getEvaluationSummary(),
        ];

        return $this->cachedViewData;
    }

    // Method untuk mendapatkan data yang sudah di-cache
    public function getCachedData(): array
    {
        return $this->getViewData();
    }

    private function getDistributionData(): array
    {
        $trainingData = TrainingData::active()
            ->select('maturity_class', DB::raw('count(*) as count'))
            ->groupBy('maturity_class')
            ->get();

        $classificationData = Classification::where('is_verified', true)
            ->select('predicted_status', DB::raw('count(*) as count'))
            ->groupBy('predicted_status')
            ->get();

        return [
            'training' => $trainingData->mapWithKeys(function ($item) {
                return [$item->maturity_class => $item->count];
            })->toArray(),
            'classification' => $classificationData->mapWithKeys(function ($item) {
                return [$item->predicted_status => $item->count];
            })->toArray(),
            'total_training' => $trainingData->sum('count'),
            'total_classification' => $classificationData->sum('count'),
        ];
    }

    private function getConfusionMatrices(ModelEvaluationService $evaluationService): array
    {
        $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];
        $matrices = [];

        foreach ($algorithms as $algorithm) {
            // Selalu generate confusion matrix berdasarkan data terbaru
            $matrix = $evaluationService->getConfusionMatrix($algorithm);
            $accuracy = $evaluationService->getCurrentAccuracy($algorithm);

            // Cek apakah ada data training atau klasifikasi yang tersedia
            $hasTrainingData = \App\Models\TrainingData::active()->exists();
            $hasClassificationData = \App\Models\Classification::where('is_verified', true)->exists();
            $hasAnyData = $hasTrainingData || $hasClassificationData;
            
            // Jika tidak ada matrix yang dihasilkan, buat matrix kosong dengan struktur yang benar
            if (empty($matrix)) {
                $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];
                $matrix = [];
                foreach ($classes as $actual) {
                    foreach ($classes as $predicted) {
                        $matrix[$actual][$predicted] = 0;
                    }
                }
            }

            // Hitung total prediksi dari matrix
            $totalPredictions = array_sum(array_map('array_sum', $matrix));

            $matrices[$algorithm] = [
                'name' => $this->getAlgorithmName($algorithm),
                'matrix' => $matrix,
                'accuracy' => $accuracy,
                'calculated_at' => now(),
                'has_data' => $hasAnyData, // Berdasarkan ketersediaan data, bukan matrix
                'total_predictions' => $totalPredictions,
            ];
        }

        return $matrices;
    }

    private function getAlgorithmPerformance(): array
    {
        $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];
        $performance = [];

        foreach ($algorithms as $algorithm) {
            $latestAccuracy = ModelAccuracy::where('algorithm', $algorithm)
                ->latest('calculated_at')
                ->first();

            $historicalData = ModelAccuracy::where('algorithm', $algorithm)
                ->orderBy('calculated_at', 'desc')
                ->limit(15)
                ->get()
                ->reverse()
                ->values();

            $performance[$algorithm] = [
                'name' => $this->getAlgorithmName($algorithm),
                'current_accuracy' => $latestAccuracy ? $latestAccuracy->accuracy : 0,
                'data_count' => $latestAccuracy ? $latestAccuracy->data_count : 0,
                'last_updated' => $latestAccuracy ? $latestAccuracy->calculated_at : null,
                'historical_data' => $historicalData->map(function ($item) {
                    return [
                        'accuracy' => $item->accuracy,
                        'date' => $item->calculated_at->format('Y-m-d H:i'),
                        'data_count' => $item->data_count,
                    ];
                })->toArray(),
            ];
        }

        return $performance;
    }

    private function getRGBAnalysis(): array
    {
        $rgbData = TrainingData::active()
            ->select('red_value', 'green_value', 'blue_value', 'maturity_class')
            ->get()
            ->groupBy('maturity_class');

        $analysis = [];

        foreach ($rgbData as $class => $data) {
            $redValues = $data->pluck('red_value')->toArray();
            $greenValues = $data->pluck('green_value')->toArray();
            $blueValues = $data->pluck('blue_value')->toArray();

            $analysis[$class] = [
                'count' => $data->count(),
                'red' => [
                    'avg' => round(array_sum($redValues) / count($redValues), 2),
                    'min' => min($redValues),
                    'max' => max($redValues),
                    'std' => round($this->calculateStandardDeviation($redValues), 2),
                ],
                'green' => [
                    'avg' => round(array_sum($greenValues) / count($greenValues), 2),
                    'min' => min($greenValues),
                    'max' => max($greenValues),
                    'std' => round($this->calculateStandardDeviation($greenValues), 2),
                ],
                'blue' => [
                    'avg' => round(array_sum($blueValues) / count($blueValues), 2),
                    'min' => min($blueValues),
                    'max' => max($blueValues),
                    'std' => round($this->calculateStandardDeviation($blueValues), 2),
                ],
                'data_points' => $data->map(function ($item) {
                    return [
                        'red' => $item->red_value,
                        'green' => $item->green_value,
                        'blue' => $item->blue_value,
                    ];
                })->toArray(),
            ];
        }

        return $analysis;
    }

    private function getEvaluationSummary(): array
    {
        $totalTrainingData = TrainingData::active()->count();
        $totalClassifications = Classification::where('is_verified', true)->count();
        $lastEvaluation = ModelAccuracy::latest('calculated_at')->first();

        $bestAlgorithm = ModelAccuracy::orderBy('accuracy', 'desc')
            ->latest('calculated_at')
            ->first();

        return [
            'total_training_data' => $totalTrainingData,
            'total_classifications' => $totalClassifications,
            'total_data_points' => $totalTrainingData + $totalClassifications,
            'last_evaluation' => $lastEvaluation ? $lastEvaluation->calculated_at : null,
            'best_algorithm' => $bestAlgorithm ? [
                'name' => $this->getAlgorithmName($bestAlgorithm->algorithm),
                'accuracy' => $bestAlgorithm->accuracy,
                'algorithm' => $bestAlgorithm->algorithm,
            ] : null,
        ];
    }

    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count === 0) return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }

    private function getAlgorithmName(string $algorithm): string
    {
        return match($algorithm) {
            'decision_tree' => 'Decision Tree',
            'knn' => 'K-Nearest Neighbors',
            'random_forest' => 'Random Forest',
            'ensemble' => 'Ensemble Method',
            default => ucfirst(str_replace('_', ' ', $algorithm))
        };
    }
}
