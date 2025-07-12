<?php

namespace App\Services;

use App\Models\TomatReading;
use App\Models\TrainingData;
use App\Models\ModelAccuracy;
use App\Models\Classification;
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
        try {
            $trainingData = TrainingData::active()->get();

            // Tambahkan data klasifikasi yang sudah terverifikasi sebagai data training tambahan
            $verifiedClassifications = Classification::where('is_verified', true)->get();
            
            // Validasi data mencukupi
            $totalDataCount = $trainingData->count() + $verifiedClassifications->count();
            if ($totalDataCount < 5) {
                Log::warning("Insufficient data for algorithm evaluation: {$algorithm}", [
                    'training_data_count' => $trainingData->count(),
                    'verified_classifications_count' => $verifiedClassifications->count(),
                    'total_count' => $totalDataCount
                ]);
                
                return [
                    'accuracy' => 0,
                    'data_count' => $totalDataCount,
                    'status' => 'insufficient_data',
                    'confusion_matrix' => [],
                    'detailed_metrics' => []
                ];
            }
            
            $classificationTrainingData = $verifiedClassifications->map(function($data) {
                // Buat array biasa untuk menghindari masalah dengan getKey()
                return [
                    'red' => $data->red_value,
                    'green' => $data->green_value,
                    'blue' => $data->blue_value,
                    'actual_class' => $data->actual_status ?? $data->predicted_status,
                    'is_active' => true
                ];
            });

            // Konversi training data Eloquent ke array untuk konsistensi
            $trainingDataArray = $trainingData->map(function($data) {
            return [
                'red' => $data->red_value,
                'green' => $data->green_value,
                'blue' => $data->blue_value,
                'actual_class' => $data->maturity_class,
                'is_active' => $data->is_active
            ];
        });

        // Gabungkan data training dan data klasifikasi terverifikasi
        $combinedTrainingData = $trainingDataArray->concat($classificationTrainingData);
        $dataCount = $combinedTrainingData->count();

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
        $folds = $this->createKFolds($combinedTrainingData, 5);
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
        $this->saveAccuracyHistory($algorithm, $avgAccuracy, $dataCount);

        return [
            'accuracy' => round($avgAccuracy, 2),
            'data_count' => $dataCount,
            'status' => 'evaluated',
            'confusion_matrix' => $confusionMatrix,
            'detailed_metrics' => $detailedMetrics
        ];
        
        } catch (\Exception $e) {
            Log::error("Error in evaluateAlgorithm for {$algorithm}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default values jika terjadi error
            return [
                'accuracy' => 0,
                'data_count' => 0,
                'status' => 'error',
                'confusion_matrix' => [],
                'detailed_metrics' => [],
                'error' => $e->getMessage()
            ];
        }
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
            try {
                // Tangani baik array maupun objek untuk kompatibilitas
                $rgb = [
                    'red' => is_array($testData) ? $testData['red'] : $testData->red,
                    'green' => is_array($testData) ? $testData['green'] : $testData->green,
                    'blue' => is_array($testData) ? $testData['blue'] : $testData->blue
                ];

                // Validasi nilai RGB
                if (!is_numeric($rgb['red']) || !is_numeric($rgb['green']) || !is_numeric($rgb['blue'])) {
                    Log::warning('Invalid RGB values detected', ['rgb' => $rgb]);
                    continue; // Skip data yang tidak valid
                }

                $actualMaturity = is_array($testData) ? $testData['actual_class'] : $testData->actual_class;
                
                if (empty($actualMaturity)) {
                    Log::warning('Empty actual maturity detected');
                    continue; // Skip data tanpa label
                }

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
                'actual' => $actualMaturity,
                'rgb' => $rgb
            ];
            
            } catch (\Exception $e) {
                Log::error('Error in makePredictions for single prediction', [
                    'algorithm' => $algorithm,
                    'error' => $e->getMessage(),
                    'test_data' => $testData
                ]);
                // Skip data yang error, lanjutkan ke data berikutnya
                continue;
            }
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
        // Validasi input
        if (empty($trainSet) || !is_array($rgb) || !isset($rgb['red'], $rgb['green'], $rgb['blue'])) {
            return 'mentah'; // Default fallback
        }

        $distances = [];

        foreach ($trainSet as $train) {
            // Tangani baik array maupun objek untuk kompatibilitas
            $trainRed = is_array($train) ? ($train['red'] ?? $train['red_value'] ?? 0) : ($train->red ?? $train->red_value ?? 0);
            $trainGreen = is_array($train) ? ($train['green'] ?? $train['green_value'] ?? 0) : ($train->green ?? $train->green_value ?? 0);
            $trainBlue = is_array($train) ? ($train['blue'] ?? $train['blue_value'] ?? 0) : ($train->blue ?? $train->blue_value ?? 0);
            $trainClass = is_array($train) ? ($train['actual_class'] ?? $train['maturity_level'] ?? $train['maturity_class'] ?? 'mentah') : ($train->actual_class ?? $train->maturity_level ?? $train->maturity_class ?? 'mentah');

            // Skip jika data tidak valid
            if (!is_numeric($trainRed) || !is_numeric($trainGreen) || !is_numeric($trainBlue) || empty($trainClass)) {
                continue;
            }

            $distance = sqrt(
                pow($trainRed - $rgb['red'], 2) +
                pow($trainGreen - $rgb['green'], 2) +
                pow($trainBlue - $rgb['blue'], 2)
            );

            $distances[] = [
                'distance' => $distance,
                'class' => $trainClass
            ];
        }

        // Jika tidak ada data valid, return default
        if (empty($distances)) {
            return 'mentah';
        }

        // Urutkan berdasarkan jarak
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Ambil K tetangga terdekat
        $k = min($k, count($distances)); // Pastikan k tidak lebih besar dari jumlah data
        $nearest = array_slice($distances, 0, $k);
        $votes = array_count_values(array_column($nearest, 'class'));
        arsort($votes);

        $result = key($votes);
        return $result ?: 'mentah'; // Fallback jika hasil kosong
    }

    /**
     * Prediksi menggunakan Random Forest
     */
    private function predictRandomForest($rgb, $trainSet)
    {
        // Validasi input
        if (!is_array($rgb) || !isset($rgb['red'], $rgb['green'], $rgb['blue'])) {
            return 'mentah'; // Default fallback
        }

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

        // Validasi bahwa ada prediksi
        if (empty($predictions)) {
            return 'mentah';
        }

        // Majority voting
        $votes = array_count_values($predictions);
        arsort($votes);

        $result = key($votes);
        return $result ?: 'mentah'; // Fallback jika hasil kosong
    }

    /**
     * Prediksi menggunakan Ensemble
     */
    private function predictEnsemble($rgb, $trainSet)
    {
        // Validasi input
        if (!is_array($rgb) || !isset($rgb['red'], $rgb['green'], $rgb['blue'])) {
            return 'mentah'; // Default fallback
        }

        $predictions = [];

        // Dapatkan prediksi dari setiap algoritma dengan error handling
        try {
            $dtPrediction = $this->predictDecisionTree($rgb);
            if (!empty($dtPrediction)) {
                $predictions[] = $dtPrediction;
            }
        } catch (\Exception $e) {
            Log::warning('Decision Tree prediction failed: ' . $e->getMessage());
        }

        try {
            $knnPrediction = $this->predictKNN($rgb, $trainSet);
            if (!empty($knnPrediction)) {
                $predictions[] = $knnPrediction;
            }
        } catch (\Exception $e) {
            Log::warning('KNN prediction failed: ' . $e->getMessage());
        }

        try {
            $rfPrediction = $this->predictRandomForest($rgb, $trainSet);
            if (!empty($rfPrediction)) {
                $predictions[] = $rfPrediction;
            }
        } catch (\Exception $e) {
            Log::warning('Random Forest prediction failed: ' . $e->getMessage());
        }

        // Jika tidak ada prediksi yang berhasil, gunakan fallback
        if (empty($predictions)) {
            return 'mentah';
        }

        // Jika hanya ada satu prediksi, gunakan itu
        if (count($predictions) === 1) {
            return $predictions[0];
        }

        // Majority voting
        $voteCounts = array_count_values($predictions);
        arsort($voteCounts);

        $result = key($voteCounts);
        return $result ?: 'mentah'; // Fallback jika hasil kosong
    }

    /**
     * Simpan riwayat akurasi
     */
    private function saveAccuracyHistory($algorithm, $accuracy, $dataCount = null)
    {
        // Hitung total data jika tidak disediakan
        if ($dataCount === null) {
            $trainingDataCount = TrainingData::active()->count();
            $verifiedClassificationCount = Classification::where('is_verified', true)->count();
            $dataCount = $trainingDataCount + $verifiedClassificationCount;
        }

        DB::table('model_accuracies')->updateOrInsert(
            ['algorithm' => $algorithm],
            [
                'accuracy' => $accuracy,
                'calculated_at' => now(),
                'data_count' => $dataCount,
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
        $verifiedClassificationCount = Classification::where('is_verified', true)->count();
        $totalDataCount = $trainingDataCount + $verifiedClassificationCount;

        if ($totalDataCount < 10) {
            // Jika total data (training + klasifikasi terverifikasi) kurang dari 10, gunakan akurasi default
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
                        'data_count' => $totalDataCount,
                        'calculated_at' => now(),
                        'notes' => "Default accuracy - insufficient data (Training: {$trainingDataCount}, Verified Classifications: {$verifiedClassificationCount})"
                    ]
                );

                $results[$algorithm] = [
                    'accuracy' => $accuracy,
                    'data_count' => $totalDataCount,
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
                        'data_count' => $totalDataCount,
                        'calculated_at' => now(),
                        'confusion_matrix' => json_encode($evaluation['confusion_matrix'] ?? []),
                        'detailed_metrics' => json_encode($evaluation['detailed_metrics'] ?? []),
                        'notes' => "Real-time evaluation (Training: {$trainingDataCount}, Verified Classifications: {$verifiedClassificationCount})"
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

    /**
     * Dapatkan confusion matrix untuk algoritma tertentu
     */
    public function getConfusionMatrix($algorithm)
    {
        // Selalu generate confusion matrix berdasarkan data terbaru, bukan cache
        $trainingData = TrainingData::active()->get();
        $classifications = Classification::where('is_verified', true)->get();

        if ($trainingData->isEmpty() && $classifications->isEmpty()) {
            return [];
        }

        // Combine data untuk evaluasi
        $allData = collect();

        // Add training data
        foreach ($trainingData as $data) {
            $allData->push([
                'red_value' => $data->red_value,
                'green_value' => $data->green_value,
                'blue_value' => $data->blue_value,
                'actual_class' => $data->maturity_class,
            ]);
        }

        // Add classification data yang sudah terverifikasi
        foreach ($classifications as $data) {
            $allData->push([
                'red_value' => $data->red_value,
                'green_value' => $data->green_value,
                'blue_value' => $data->blue_value,
                'actual_class' => $data->actual_status ?? $data->predicted_status, // Gunakan actual_status jika ada
            ]);
        }

        if ($allData->isEmpty()) {
            return [];
        }

        // Generate confusion matrix
        $matrix = [];
        $classes = ['mentah', 'setengah_matang', 'matang', 'busuk'];

        // Initialize matrix
        foreach ($classes as $actual) {
            foreach ($classes as $predicted) {
                $matrix[$actual][$predicted] = 0;
            }
        }

        // Calculate predictions and build matrix berdasarkan semua data
        foreach ($allData as $data) {
            $rgb = [
                'red' => $data['red_value'],
                'green' => $data['green_value'],
                'blue' => $data['blue_value']
            ];

            try {
                // Buat prediksi menggunakan algoritma yang dipilih
                $predicted = $this->makeSinglePredictionInternal($algorithm, $rgb, $allData->toArray());
                $actual = $data['actual_class'];

                // Normalisasi nama kelas
                $actual = $this->normalizeClassName($actual);
                $predicted = $this->normalizeClassName($predicted);

                if (in_array($actual, $classes) && in_array($predicted, $classes)) {
                    $matrix[$actual][$predicted]++;
                }
            } catch (\Exception $e) {
                // Log error tapi lanjutkan proses
                Log::warning("Error predicting for algorithm {$algorithm}: " . $e->getMessage());
                continue;
            }
        }

        return $matrix;
    }

    /**
     * Normalisasi nama kelas untuk konsistensi
     */
    private function normalizeClassName($className)
    {
        $className = strtolower(trim($className));

        // Mapping untuk berbagai variasi nama kelas
        $mapping = [
            'ripe' => 'matang',
            'mature' => 'matang',
            'half_ripe' => 'setengah_matang',
            'semi_ripe' => 'setengah_matang',
            'half-ripe' => 'setengah_matang',
            'semi-ripe' => 'setengah_matang',
            'unripe' => 'mentah',
            'green' => 'mentah',
            'raw' => 'mentah',
            'rotten' => 'busuk',
            'spoiled' => 'busuk',
            'bad' => 'busuk'
        ];

        return $mapping[$className] ?? $className;
    }

    /**
     * Buat prediksi tunggal menggunakan algoritma tertentu (metode public)
     */
    public function makeSinglePrediction($inputData, $algorithm)
    {
        try {
            // Validasi input
            if (!is_array($inputData) || !isset($inputData['red_value'], $inputData['green_value'], $inputData['blue_value'])) {
                Log::warning('Invalid input data for single prediction', ['input' => $inputData]);
                return 'mentah';
            }

            // Format RGB data
            $rgb = [
                'red' => $inputData['red_value'],
                'green' => $inputData['green_value'],
                'blue' => $inputData['blue_value']
            ];

            // Ambil data training
            $trainingData = TrainingData::active()->get()->toArray();

            if (empty($trainingData)) {
                Log::warning('No training data available for prediction');
                return 'mentah';
            }

            // Panggil metode prediksi private
            return $this->makeSinglePredictionInternal($algorithm, $rgb, $trainingData);

        } catch (\Exception $e) {
            Log::error('Single prediction failed', [
                'algorithm' => $algorithm,
                'input' => $inputData,
                'error' => $e->getMessage()
            ]);
            return 'mentah';
        }
    }

    /**
     * Metode internal untuk prediksi tunggal
     */
    private function makeSinglePredictionInternal($algorithm, $rgb, $trainingData)
    {
        switch ($algorithm) {
            case 'decision_tree':
                return $this->predictDecisionTree($rgb);
            case 'knn':
                return $this->predictKNN($rgb, $trainingData);
            case 'random_forest':
                return $this->predictRandomForest($rgb, $trainingData);
            case 'ensemble':
                return $this->predictEnsemble($rgb, $trainingData);
            default:
                return 'mentah'; // Default prediction
        }
    }

    /**
     * Dapatkan akurasi saat ini untuk algoritma tertentu
     */
    public function getCurrentAccuracy($algorithm)
    {
        $accuracy = ModelAccuracy::where('algorithm', $algorithm)
            ->latest('calculated_at')
            ->first();

        return $accuracy ? $accuracy->accuracy : 0;
    }
}
