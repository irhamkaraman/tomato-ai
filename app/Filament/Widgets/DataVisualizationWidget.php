<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\ModelEvaluationService;
use App\Models\ModelAccuracy;
use App\Models\TrainingData;
use App\Models\Classification;
use Illuminate\Support\Facades\DB;

class DataVisualizationWidget extends Widget
{
    protected static string $view = 'filament.widgets.data-visualization';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    // Refresh setiap 10 menit
    protected static ?string $pollingInterval = '600s';
    
    public function getViewData(): array
    {
        $evaluationService = new ModelEvaluationService();
        
        return [
            'distribution_data' => $this->getDistributionData(),
            'confusion_matrices' => $this->getConfusionMatrices($evaluationService),
            'algorithm_performance' => $this->getAlgorithmPerformance(),
            'rgb_analysis' => $this->getRGBAnalysis(),
        ];
    }
    
    private function getDistributionData(): array
    {
        $trainingData = TrainingData::active()
            ->select('maturity_class', DB::raw('count(*) as count'))
            ->groupBy('maturity_class')
            ->get();
            
        $classificationData = Classification::where('is_verified', true)
            ->select('predicted_class', DB::raw('count(*) as count'))
            ->groupBy('predicted_class')
            ->get();
            
        return [
            'training' => $trainingData->mapWithKeys(function ($item) {
                return [$item->maturity_class => $item->count];
            })->toArray(),
            'classification' => $classificationData->mapWithKeys(function ($item) {
                return [$item->predicted_class => $item->count];
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
            $accuracy = ModelAccuracy::where('algorithm', $algorithm)
                ->latest('calculated_at')
                ->first();
                
            if ($accuracy && $accuracy->confusion_matrix) {
                $matrices[$algorithm] = [
                    'name' => $this->getAlgorithmName($algorithm),
                    'matrix' => $accuracy->confusion_matrix,
                    'accuracy' => $accuracy->accuracy,
                ];
            } else {
                // Generate confusion matrix jika belum ada
                $matrix = $evaluationService->getConfusionMatrix($algorithm);
                $matrices[$algorithm] = [
                    'name' => $this->getAlgorithmName($algorithm),
                    'matrix' => $matrix,
                    'accuracy' => $evaluationService->getCurrentAccuracy($algorithm),
                ];
            }
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
                ->limit(10)
                ->get()
                ->reverse()
                ->values();
                
            $performance[$algorithm] = [
                'name' => $this->getAlgorithmName($algorithm),
                'current_accuracy' => $latestAccuracy ? $latestAccuracy->accuracy : 0,
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
                ],
                'green' => [
                    'avg' => round(array_sum($greenValues) / count($greenValues), 2),
                    'min' => min($greenValues),
                    'max' => max($greenValues),
                ],
                'blue' => [
                    'avg' => round(array_sum($blueValues) / count($blueValues), 2),
                    'min' => min($blueValues),
                    'max' => max($blueValues),
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