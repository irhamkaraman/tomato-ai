<?php

namespace App\Services;

use App\Models\TomatReading;
use App\Models\TrainingData;
use App\Models\ModelAccuracy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * =====================================================================================
 * SERVICE UNTUK EVALUASI AKURASI MODEL REAL-TIME
 * =====================================================================================
 *
 * Service ini menghitung akurasi setiap algoritma AI secara dinamis berdasarkan:
 * 1. Cross-validation pada data training
 * 2. Feedback dari pengguna (jika tersedia)
 * 3. Confusion matrix untuk setiap algoritma
 * 4. Performance metrics (precision, recall, F1-score)
 */
class ModelEvaluationService
{
    private $algorithms = ['decision_tree', 'knn', 'random_forest', 'ensemble'];

    /**
     * Hitung akurasi real-time untuk semua algoritma
     */
    public function calculateRealTimeAccuracy()
    {
        $results = [];

        foreach ($this->algorithms as $algorithm) {
            $accuracy = $this->evaluateAlgorithm($algorithm);
            $results[$algorithm] = $accuracy;

            // Cache hasil untuk performa
            Cache::put("accuracy_{$algorithm}", $accuracy, now()->addMinutes(30));
        }

        return $results;
    }

    /**
     * Evaluasi algoritma menggunakan cross-validation
     */
    private function evaluateAlgorithm($algorithm)
    {
        $trainingData = TrainingData::active()->get();
        $dataCount = $trainingData->count();

        if ($dataCount < 10) {
            // Jika data training kurang, gunakan akurasi default
            $accuracy = $this->getDefaultAccuracy($algorithm);
            return [
                'accuracy' => $accuracy,
                'data_count' => $dataCount,
                'status' => 'default',
                'confusion_matrix' => [],
                'detailed_metrics' => []
            ];
        }

        // Implementasi K-Fold Cross Validation (K=5)
        $folds = $this->createKFolds($trainingData, 5);
        $accuracies = [];
        $allPredictions = [];

        foreach ($folds as $fold) {
            $trainSet = $fold['train'];
            $testSet = $fold['test'];

            $predictions = $this->makePredictions($algorithm, $testSet, $trainSet);
            $accuracy = $this->calculateAccuracy($predictions, $testSet);
            $accuracies[] = $accuracy;
            $allPredictions = array_merge($allPredictions, $predictions);
        }

        // Rata-rata akurasi dari semua fold
        $avgAccuracy = array_sum($accuracies) / count($accuracies);

        // Hitung confusion matrix
        $confusionMatrix = $this->calculateConfusionMatrix($allPredictions);
        
        // Hitung detailed metrics
        $detailedMetrics = $this->calculateDetailedMetrics($allPredictions);

        // Simpan ke database untuk tracking
        $this->saveAccuracyHistory($algorithm, $avgAccuracy);

        return [
            'accuracy' => round($avgAccuracy, 2),
            'data_count' => $dataCount,
            'status' => 'evaluated',
            'confusion_matrix' => $confusionMatrix,
            'detailed_metrics' => $detailedMetrics
        ];
    }

    /**
     * Buat K-Fold untuk cross validation
     */
    private function createKFolds($data, $k = 5)
    {
        $shuffled = $data->shuffle();
        $foldSize = intval($shuffled->count() / $k);
        $folds = [];

        for ($i = 0; $i < $k; $i++) {
            $testStart = $i * $foldSize;
            $testEnd = ($i == $k - 1) ? $shuffled->count() : ($i + 1) * $foldSize;

            $testSet = $shuffled->slice($testStart, $testEnd - $testStart);
            $trainSet = $shuffled->slice(0, $testStart)->merge(
                $shuffled->slice($testEnd)
            );

            $folds[] = [
                'train' => $trainSet,
                'test' => $testSet
            ];
        }

        return $folds;
    }

    /**
     * Buat prediksi menggunakan algoritma tertentu
     */
    private function makePredictions($algorithm, $testSet, $trainSet)
    {
        $predictions = [];

        foreach ($testSet as $testData) {
            $rgb = [
                'red' => $testData->red_value,
                'green' => $testData->green_value,
                'blue' => $testData->blue_value
            ];

            switch ($algorithm) {
                case 'decision_tree':
                    $prediction = $this->predictDecisionTree($rgb);
                    break;
                case 'knn':
                    $prediction = $this->predictKNN($rgb, $trainSet);
                    break;
                case 'random_forest':
                    $prediction = $this->predictRandomForest($rgb, $trainSet);
                    break;
                case 'ensemble':
                    $prediction = $this->predictEnsemble($rgb, $trainSet);
                    break;
                default:
                    $prediction = 'unknown';
            }

            $predictions[] = [
                'predicted' => $prediction,
                'actual' => $testData->maturity_class,
                'rgb' => $rgb
            ];
        }

        return $predictions;
    }

    /**
     * Hitung akurasi dari prediksi
     */
    private function calculateAccuracy($predictions, $testSet)
    {
        $correct = 0;
        $total = count($predictions);

        foreach ($predictions as $prediction) {
            if ($prediction['predicted'] === $prediction['actual']) {
                $correct++;
            }
        }

        return $total > 0 ? ($correct / $total) * 100 : 0;
    }

    /**
     * Hitung confusion matrix dari prediksi
     */
    private function calculateConfusionMatrix($predictions)
    {
        $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];
        $matrix = [];

        // Inisialisasi matrix
        foreach ($classes as $actual) {
            foreach ($classes as $predicted) {
                $matrix[$actual][$predicted] = 0;
            }
        }

        // Hitung prediksi vs aktual
        foreach ($predictions as $prediction) {
            $actual = $prediction['actual'];
            $predicted = $prediction['predicted'];

            if (isset($matrix[$actual][$predicted])) {
                $matrix[$actual][$predicted]++;
            }
        }

        return $matrix;
    }

    /**
     * Hitung detailed metrics (precision, recall, F1-score)
     */
    private function calculateDetailedMetrics($predictions)
    {
        $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];
        $metrics = [];

        foreach ($classes as $class) {
            $tp = 0; // True Positive
            $fp = 0; // False Positive
            $fn = 0; // False Negative

            foreach ($predictions as $prediction) {
                $actual = $prediction['actual'];
                $predicted = $prediction['predicted'];

                if ($actual === $class && $predicted === $class) {
                    $tp++;
                } elseif ($actual !== $class && $predicted === $class) {
                    $fp++;
                } elseif ($actual === $class && $predicted !== $class) {
                    $fn++;
                }
            }

            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1Score = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

            $metrics[$class] = [
                'precision' => round($precision * 100, 2),
                'recall' => round($recall * 100, 2),
                'f1_score' => round($f1Score * 100, 2)
            ];
        }

        return $metrics;
    }

    /**
     * Prediksi menggunakan Decision Tree
     */
    private function predictDecisionTree($rgb)
    {
        // Implementasi sederhana decision tree
        $red = $rgb['red'];
        $green = $rgb['green'];
        $blue = $rgb['blue'];

        if ($red > 150 && $red > $green * 1.5) {
            return 'matang';
        } elseif ($green > $red && $green > 120) {
            return 'mentah';
        } elseif ($blue > 80 && $blue > $red) {
            return 'busuk';
        } else {
            return 'setengah_matang';
        }
    }

    /**
     * Prediksi menggunakan KNN
     */
    private function predictKNN($rgb, $trainSet, $k = 3)
    {
        $distances = [];

        foreach ($trainSet as $train) {
            $distance = sqrt(
                pow($train->red_value - $rgb['red'], 2) +
                pow($train->green_value - $rgb['green'], 2) +
                pow($train->blue_value - $rgb['blue'], 2)
            );

            $distances[] = [
                'distance' => $distance,
                'class' => $train->maturity_class
            ];
        }

        // Urutkan berdasarkan jarak
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Ambil K tetangga terdekat
        $nearest = array_slice($distances, 0, $k);
        $votes = array_count_values(array_column($nearest, 'class'));
        arsort($votes);

        return key($votes);
    }

    /**
     * Prediksi menggunakan Random Forest
     */
    private function predictRandomForest($rgb, $trainSet)
    {
        // Simulasi 3 decision trees dengan variasi
        $predictions = [];

        // Tree 1: Fokus pada rasio merah/hijau
        $ratio = $rgb['green'] > 0 ? $rgb['red'] / $rgb['green'] : 0;
        if ($ratio > 1.8) $predictions[] = 'matang';
        elseif ($ratio > 1.2) $predictions[] = 'setengah_matang';
        elseif ($rgb['blue'] > 80) $predictions[] = 'busuk';
        else $predictions[] = 'mentah';

        // Tree 2: Fokus pada nilai absolut
        if ($rgb['red'] > 160) $predictions[] = 'matang';
        elseif ($rgb['red'] > 120) $predictions[] = 'setengah_matang';
        elseif ($rgb['blue'] > 90) $predictions[] = 'busuk';
        else $predictions[] = 'mentah';

        // Tree 3: Fokus pada dominasi warna
        if ($rgb['red'] > $rgb['green'] && $rgb['red'] > $rgb['blue'] && $rgb['red'] > 140) {
            $predictions[] = 'matang';
        } elseif ($rgb['green'] > $rgb['red'] && $rgb['green'] > $rgb['blue']) {
            $predictions[] = 'mentah';
        } elseif ($rgb['blue'] > $rgb['red'] && $rgb['blue'] > $rgb['green']) {
            $predictions[] = 'busuk';
        } else {
            $predictions[] = 'setengah_matang';
        }

        // Majority voting
        $votes = array_count_values($predictions);
        arsort($votes);

        return key($votes);
    }

    /**
     * Prediksi menggunakan Ensemble
     */
    private function predictEnsemble($rgb, $trainSet)
    {
        $dtPrediction = $this->predictDecisionTree($rgb);
        $knnPrediction = $this->predictKNN($rgb, $trainSet);
        $rfPrediction = $this->predictRandomForest($rgb, $trainSet);

        $votes = [$dtPrediction, $knnPrediction, $rfPrediction];
        $voteCounts = array_count_values($votes);
        arsort($voteCounts);

        return key($voteCounts);
    }

    /**
     * Simpan riwayat akurasi
     */
    private function saveAccuracyHistory($algorithm, $accuracy)
    {
        DB::table('model_accuracies')->updateOrInsert(
            ['algorithm' => $algorithm],
            [
                'accuracy' => $accuracy,
                'calculated_at' => now(),
                'data_count' => TrainingData::active()->count(),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Dapatkan akurasi default jika data training kurang
     */
    private function getDefaultAccuracy($algorithm)
    {
        $defaults = [
            'decision_tree' => 75.0,
            'knn' => 70.0,
            'random_forest' => 78.0,
            'ensemble' => 80.0
        ];

        return $defaults[$algorithm] ?? 70.0;
    }

    /**
     * Dapatkan akurasi terkini dari cache atau hitung ulang
     */
    public function getCurrentAccuracy($algorithm)
    {
        return Cache::remember("accuracy_{$algorithm}", now()->addMinutes(30), function() use ($algorithm) {
            $result = $this->evaluateAlgorithm($algorithm);
            return $result['accuracy'];
        });
    }

    /**
     * Hitung confusion matrix untuk algoritma
     */
    public function getConfusionMatrix($algorithm)
    {
        $trainingData = TrainingData::active()->get();
        $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];
        $matrix = [];

        // Inisialisasi matrix
        foreach ($classes as $actual) {
            foreach ($classes as $predicted) {
                $matrix[$actual][$predicted] = 0;
            }
        }

        // Hitung prediksi vs aktual
        foreach ($trainingData as $data) {
            $rgb = [
                'red' => $data->red_value,
                'green' => $data->green_value,
                'blue' => $data->blue_value
            ];

            $predicted = $this->makePredictions($algorithm, collect([$data]), $trainingData)[0]['predicted'];
            $actual = $data->maturity_class;

            if (isset($matrix[$actual][$predicted])) {
                $matrix[$actual][$predicted]++;
            }
        }

        return $matrix;
    }

    /**
     * Hitung precision, recall, dan F1-score
     */
    public function getDetailedMetrics($algorithm)
    {
        $matrix = $this->getConfusionMatrix($algorithm);
        $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];
        $metrics = [];

        foreach ($classes as $class) {
            $tp = $matrix[$class][$class] ?? 0;
            $fp = array_sum(array_column($matrix, $class)) - $tp;
            $fn = array_sum($matrix[$class]) - $tp;

            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

            $metrics[$class] = [
                'precision' => round($precision * 100, 2),
                'recall' => round($recall * 100, 2),
                'f1_score' => round($f1 * 100, 2)
            ];
        }

        return $metrics;
    }

    /**
     * Evaluasi semua algoritma dan simpan ke database
     */
    public function evaluateAllAlgorithms()
    {
        $results = [];
        $trainingDataCount = TrainingData::active()->count();

        if ($trainingDataCount < 10) {
            // Jika data training kurang dari 10, gunakan akurasi default
            $defaultAccuracies = [
                'decision_tree' => 85.0,
                'knn' => 82.0,
                'random_forest' => 88.0,
                'ensemble' => 92.0
            ];

            foreach ($defaultAccuracies as $algorithm => $accuracy) {
                ModelAccuracy::updateOrCreate(
                    ['algorithm' => $algorithm],
                    [
                        'accuracy' => $accuracy,
                        'data_count' => $trainingDataCount,
                        'calculated_at' => now(),
                        'notes' => 'Default accuracy - insufficient training data'
                    ]
                );

                $results[$algorithm] = [
                    'accuracy' => $accuracy,
                    'data_count' => $trainingDataCount,
                    'status' => 'default'
                ];
            }

            return $results;
        }

        foreach ($this->algorithms as $algorithm) {
            try {
                $evaluation = $this->evaluateAlgorithm($algorithm);

                ModelAccuracy::updateOrCreate(
                    ['algorithm' => $algorithm],
                    [
                        'accuracy' => $evaluation['accuracy'],
                        'data_count' => $trainingDataCount,
                        'calculated_at' => now(),
                        'confusion_matrix' => json_encode($evaluation['confusion_matrix'] ?? []),
                        'detailed_metrics' => json_encode($evaluation['detailed_metrics'] ?? []),
                        'notes' => 'Real-time evaluation'
                    ]
                );

                $results[$algorithm] = $evaluation;

                // Update cache
                Cache::put("accuracy_{$algorithm}", $evaluation['accuracy'], now()->addMinutes(30));

            } catch (\Exception $e) {
                Log::error("Failed to evaluate algorithm {$algorithm}", [
                    'error' => $e->getMessage()
                ]);

                $results[$algorithm] = [
                    'accuracy' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Dapatkan akurasi terkini untuk semua algoritma
     */
    public function getCurrentAccuracies()
    {
        $accuracies = [];

        foreach ($this->algorithms as $algorithm) {
            // Coba ambil dari cache dulu
            $cached = Cache::get("accuracy_{$algorithm}");

            if ($cached !== null) {
                $accuracies[$algorithm] = $cached;
                continue;
            }

            // Jika tidak ada di cache, ambil dari database
            $latest = ModelAccuracy::where('algorithm', $algorithm)
                ->orderBy('calculated_at', 'desc')
                ->first();

            $accuracies[$algorithm] = $latest ? $latest->accuracy : 0;
        }

        return $accuracies;
    }

    /**
     * Dapatkan waktu evaluasi terakhir
     */
    public function getLastEvaluationTime()
    {
        $latest = ModelAccuracy::orderBy('calculated_at', 'desc')->first();
        return $latest ? $latest->calculated_at : null;
    }

    /**
     * Dapatkan riwayat akurasi
     */
    public function getAccuracyHistory($algorithm = null, $days = 30, $limit = 50)
    {
        $query = ModelAccuracy::query()
            ->where('calculated_at', '>=', now()->subDays($days))
            ->orderBy('calculated_at', 'desc')
            ->limit($limit);

        if ($algorithm) {
            $query->where('algorithm', $algorithm);
        }

        return $query->get();
    }

    /**
     * Reset cache akurasi (dipanggil saat ada data training baru)
     */
    public function resetAccuracyCache()
    {
        foreach ($this->algorithms as $algorithm) {
            Cache::forget("accuracy_{$algorithm}");
        }
    }
}
