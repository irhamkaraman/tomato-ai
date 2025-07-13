<?php

namespace App\Http\Controllers;

use App\Models\TomatReading;
use App\Models\TrainingData;
use App\Models\DecisionTreeRule;
use App\Models\Recommendation;
use App\Models\Classification;
use App\Services\ModelEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $modelEvaluationService;

    public function __construct(ModelEvaluationService $modelEvaluationService)
    {
        $this->modelEvaluationService = $modelEvaluationService;
    }
    /**
     * Tampilkan dashboard utama - menerima data dari ESP32 dan melakukan analisis AI
     */
    public function index(Request $request)
    {
        $sensorData = null;
        $analysisResult = null;
        $error = null;

        // Jika ada data sensor dari ESP32
        if ($request->has(['device_id', 'red_value', 'green_value', 'blue_value'])) {
            try {
                // Validasi data sensor
                $validator = Validator::make($request->all(), [
                    'device_id' => 'required|string|max:255',
                    'red_value' => 'required|numeric|min:0|max:255',
                    'green_value' => 'required|numeric|min:0|max:255',
                    'blue_value' => 'required|numeric|min:0|max:255',
                    'clear_value' => 'nullable|numeric|min:0',
                    'temperature' => 'nullable|numeric',
                    'humidity' => 'nullable|numeric|min:0|max:100'
                ]);

                if ($validator->fails()) {
                    $error = 'Data sensor tidak valid: ' . implode(', ', $validator->errors()->all());
                } else {
                    // Proses data sensor
                    $sensorData = $this->processSensorData($request);
                    
                    // Lakukan analisis AI
                    $analysisResult = $this->performAIAnalysis(
                        $sensorData['red_value'],
                        $sensorData['green_value'],
                        $sensorData['blue_value']
                    );
                    
                    // Update reading dengan hasil analisis
                    $this->updateReadingWithAnalysis($sensorData['id'], $analysisResult);
                }
            } catch (\Exception $e) {
                Log::error('Error processing sensor data in dashboard', [
                    'error' => $e->getMessage(),
                    'request_data' => $request->all()
                ]);
                $error = 'Terjadi kesalahan saat memproses data sensor';
            }
        }

        // Data untuk dashboard
        $dashboardData = $this->getDashboardData();
        
        // Ambil data terbaru dari ESP32
        $latestReading = TomatReading::latest()->first();
        
        // Cek status ESP32 (online jika ada data dalam 5 menit terakhir)
        $esp32Status = 'offline';
        if ($latestReading && $latestReading->created_at->diffInMinutes(now()) <= 5) {
            $esp32Status = 'online';
        }
        
        // Ambil 10 data terbaru untuk ditampilkan
        $recentReadings = TomatReading::with([])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Statistik sistem pakar
        $totalReadings = TomatReading::count();
        $totalTrainingData = TrainingData::where('is_active', true)->count();
        
        // Distribusi tingkat kematangan
        $maturityDistribution = TomatReading::select('maturity_level', DB::raw('count(*) as count'))
            ->whereNotNull('maturity_level')
            ->groupBy('maturity_level')
            ->get();
        
        // Akurasi rata-rata
        $averageConfidence = TomatReading::whereNotNull('confidence_score')
            ->avg('confidence_score');
        
        return view('dashboard.index', compact(
            'latestReading',
            'esp32Status', 
            'recentReadings',
            'totalReadings',
            'totalTrainingData',
            'maturityDistribution',
            'averageConfidence',
            'sensorData',
            'analysisResult',
            'error',
            'dashboardData'
        ));
    }
    
    /**
     * API untuk mendapatkan data terbaru (untuk real-time update)
     */
    public function getLatestData()
    {
        $latestReading = TomatReading::latest()->first();
        
        // Cek status ESP32
        $esp32Status = 'offline';
        if ($latestReading && $latestReading->created_at->diffInMinutes(now()) <= 5) {
            $esp32Status = 'online';
        }
        
        return response()->json([
            'status' => 'success',
            'esp32_status' => $esp32Status,
            'latest_reading' => $latestReading,
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * API untuk mendapatkan data readings terbaru
     */
    public function getRecentReadings()
    {
        $recentReadings = TomatReading::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return response()->json([
            'status' => 'success',
            'readings' => $recentReadings
        ]);
    }
    
    /**
     * Simpan data reading sebagai training data
     */
    public function saveAsTrainingData(Request $request)
    {
        try {
            $request->validate([
                'reading_id' => 'required|exists:tomat_readings,id',
                'maturity_class' => 'required|in:mentah,setengah_matang,matang,busuk',
                'description' => 'nullable|string|max:500'
            ]);
            
            $reading = TomatReading::findOrFail($request->reading_id);
            
            // Cek apakah data sudah ada di training data
            $existingTraining = TrainingData::where('red_value', $reading->red_value)
                ->where('green_value', $reading->green_value)
                ->where('blue_value', $reading->blue_value)
                ->where('maturity_class', $request->maturity_class)
                ->first();
                
            if ($existingTraining) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data training dengan nilai RGB dan kelas yang sama sudah ada'
                ], 400);
            }
            
            // Simpan sebagai training data
            $trainingData = TrainingData::create([
                'red_value' => $reading->red_value,
                'green_value' => $reading->green_value,
                'blue_value' => $reading->blue_value,
                'maturity_class' => $request->maturity_class,
                'description' => $request->description ?? "Data dari ESP32 - {$reading->device_id} pada {$reading->created_at->format('Y-m-d H:i:s')}",
                'is_active' => true
            ]);
            
            Log::info('Data training baru ditambahkan', [
                'training_id' => $trainingData->id,
                'source_reading_id' => $reading->id,
                'rgb_values' => [
                    'red' => $reading->red_value,
                    'green' => $reading->green_value,
                    'blue' => $reading->blue_value
                ],
                'maturity_class' => $request->maturity_class
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan sebagai training data',
                'training_data' => $trainingData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error saving training data', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data training'
            ], 500);
        }
    }
    
    /**
     * Hapus data reading
     */
    public function deleteReading(Request $request)
    {
        try {
            $request->validate([
                'reading_id' => 'required|exists:tomat_readings,id'
            ]);
            
            $reading = TomatReading::findOrFail($request->reading_id);
            $reading->delete();
            
            Log::info('Data reading dihapus', [
                'reading_id' => $request->reading_id,
                'device_id' => $reading->device_id
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data reading berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting reading', [
                'error' => $e->getMessage(),
                'reading_id' => $request->reading_id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data'
            ], 500);
        }
    }
    
    /**
     * Dapatkan statistik sistem pakar
     */
    public function getSystemStats()
    {
        try {
            $stats = [
                'total_readings' => TomatReading::count(),
                'total_training_data' => TrainingData::where('is_active', true)->count(),
                'readings_today' => TomatReading::whereDate('created_at', today())->count(),
                'average_confidence' => round(TomatReading::whereNotNull('confidence_score')->avg('confidence_score'), 2),
                'maturity_distribution' => TomatReading::select('maturity_level', DB::raw('count(*) as count'))
                    ->whereNotNull('maturity_level')
                    ->groupBy('maturity_level')
                    ->get(),
                'recent_devices' => TomatReading::select('device_id', DB::raw('count(*) as count'))
                    ->groupBy('device_id')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get()
            ];
            
            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting system stats', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik'
            ], 500);
        }
    }

    /**
     * Proses dan simpan data sensor dari ESP32
     */
    private function processSensorData(Request $request)
    {
        // Simpan data ke database
        $reading = TomatReading::create([
            'device_id' => $request->device_id,
            'red_value' => $request->red_value,
            'green_value' => $request->green_value,
            'blue_value' => $request->blue_value,
            'clear_value' => $request->clear_value ?? null,
            'temperature' => $request->temperature ?? null,
            'humidity' => $request->humidity ?? null
        ]);

        Log::info('Sensor data received and saved in dashboard', [
            'reading_id' => $reading->id,
            'device_id' => $request->device_id,
            'rgb' => [
                'red' => $request->red_value,
                'green' => $request->green_value,
                'blue' => $request->blue_value
            ]
        ]);

        return [
            'id' => $reading->id,
            'device_id' => $reading->device_id,
            'red_value' => $reading->red_value,
            'green_value' => $reading->green_value,
            'blue_value' => $reading->blue_value,
            'clear_value' => $reading->clear_value,
            'temperature' => $reading->temperature,
            'humidity' => $reading->humidity,
            'reading_time' => $reading->created_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Lakukan analisis AI pada data RGB
     */
    private function performAIAnalysis($red, $green, $blue)
    {
        try {
            // 1. Decision Tree Analysis
            $decisionTreeResult = $this->analyzeWithDecisionTree($red, $green, $blue);
            
            // 2. KNN Analysis
            $knnResult = $this->analyzeWithKNN($red, $green, $blue);
            
            // 3. Random Forest Analysis
            $randomForestResult = $this->analyzeWithRandomForest($red, $green, $blue);
            
            // 4. Ensemble Voting
            $ensembleResult = $this->calculateEnsembleVoting(
                $decisionTreeResult['classification'],
                $knnResult['prediction'],
                $randomForestResult['prediction']
            );

            // 5. Generate Recommendations
            $recommendations = $this->generateRecommendations($ensembleResult['prediction']);

            return [
                'rgb_values' => [
                    'red' => $red,
                    'green' => $green,
                    'blue' => $blue
                ],
                'algorithms' => [
                    'decision_tree' => $decisionTreeResult,
                    'knn' => $knnResult,
                    'random_forest' => $randomForestResult
                ],
                'ensemble' => $ensembleResult,
                'recommendations' => $recommendations,
                'analysis_time' => now()->format('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Log::error('AI Analysis failed in dashboard', [
                'error' => $e->getMessage(),
                'rgb' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            
            return [
                'error' => 'Analisis AI gagal',
                'fallback_prediction' => 'mentah',
                'recommendations' => $this->generateRecommendations('mentah')
            ];
        }
    }

    /**
     * Analisis menggunakan Decision Tree
     */
    private function analyzeWithDecisionTree($red, $green, $blue)
    {
        // Ekstrak fitur dari data RGB
        $features = [
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
            'red_green_ratio' => $green > 0 ? $red / $green : 0,
            'red_blue_ratio' => $blue > 0 ? $red / $blue : 0,
            'green_blue_ratio' => $blue > 0 ? $green / $blue : 0
        ];

        // Ambil aturan Decision Tree dari database
        $rules = DecisionTreeRule::active()
            ->byNodeType('root')
            ->ordered()
            ->get();

        if ($rules->isEmpty()) {
            // Fallback ke aturan hardcoded
            return $this->fallbackDecisionTree($features);
        }

        // Evaluasi Decision Tree
        $result = $this->evaluateDecisionTreeRules($features, $rules);

        return [
            'algorithm' => 'Decision Tree',
            'classification' => $result['classification'],
            'confidence' => $result['confidence'] ?? 0.8,
            'path' => $result['path'] ?? [],
            'rules_used' => $result['rules_used'] ?? []
        ];
    }

    /**
     * Analisis menggunakan KNN
     */
    private function analyzeWithKNN($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'knn');

            $accuracy = $this->modelEvaluationService->getCurrentAccuracy('knn');

            return [
                'algorithm' => 'K-Nearest Neighbors',
                'prediction' => $prediction,
                'confidence' => $accuracy / 100,
                'k_value' => 3
            ];
        } catch (\Exception $e) {
            Log::warning('KNN analysis failed in dashboard: ' . $e->getMessage());
            return [
                'algorithm' => 'K-Nearest Neighbors',
                'prediction' => 'mentah',
                'confidence' => 0.7,
                'k_value' => 3,
                'error' => 'Fallback prediction'
            ];
        }
    }

    /**
     * Analisis menggunakan Random Forest
     */
    private function analyzeWithRandomForest($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'random_forest');

            $accuracy = $this->modelEvaluationService->getCurrentAccuracy('random_forest');

            return [
                'algorithm' => 'Random Forest',
                'prediction' => $prediction,
                'confidence' => $accuracy / 100,
                'trees_count' => 3
            ];
        } catch (\Exception $e) {
            Log::warning('Random Forest analysis failed in dashboard: ' . $e->getMessage());
            return [
                'algorithm' => 'Random Forest',
                'prediction' => 'mentah',
                'confidence' => 0.75,
                'trees_count' => 3,
                'error' => 'Fallback prediction'
            ];
        }
    }

    /**
     * Hitung Ensemble Voting
     */
    private function calculateEnsembleVoting($dtPrediction, $knnPrediction, $rfPrediction)
    {
        $predictions = [$dtPrediction, $knnPrediction, $rfPrediction];
        $votes = array_count_values($predictions);
        arsort($votes);

        $finalPrediction = key($votes);
        $maxVotes = current($votes);
        $totalVotes = count($predictions);
        
        $confidence = $maxVotes / $totalVotes;
        
        $consensus = 'No Consensus';
        if ($maxVotes === $totalVotes) {
            $consensus = 'Unanimous';
        } elseif ($maxVotes >= 2) {
            $consensus = 'Strong Majority';
        }

        return [
            'algorithm' => 'Ensemble Voting',
            'prediction' => $finalPrediction,
            'confidence' => $confidence,
            'consensus' => $consensus,
            'votes' => $votes,
            'individual_predictions' => [
                'decision_tree' => $dtPrediction,
                'knn' => $knnPrediction,
                'random_forest' => $rfPrediction
            ]
        ];
    }

    /**
     * Generate rekomendasi berdasarkan tingkat kematangan
     */
    private function generateRecommendations($maturityLevel)
    {
        $recommendations = Recommendation::getRecommendationsByMaturityLevel($maturityLevel);
        
        if (empty($recommendations)) {
            // Fallback recommendations
            $fallbackRecommendations = [
                'mentah' => [
                    'storage' => ['Simpan di tempat sejuk dan kering', 'Hindari paparan sinar matahari langsung'],
                    'handling' => ['Tangani dengan hati-hati', 'Jangan dicuci sebelum disimpan'],
                    'use' => ['Tunggu hingga matang sebelum dikonsumsi', 'Dapat digunakan untuk masakan yang memerlukan tomat mentah'],
                    'timeframe' => ['Akan matang dalam 3-7 hari']
                ],
                'setengah_matang' => [
                    'storage' => ['Simpan di suhu ruang', 'Letakkan di tempat yang tidak terlalu lembab'],
                    'handling' => ['Periksa setiap hari', 'Pisahkan dari tomat yang sudah matang'],
                    'use' => ['Siap dikonsumsi dalam 1-3 hari', 'Cocok untuk salad atau masakan ringan'],
                    'timeframe' => ['Akan matang sempurna dalam 1-3 hari']
                ],
                'matang' => [
                    'storage' => ['Simpan di kulkas jika tidak langsung digunakan', 'Konsumsi dalam 3-5 hari'],
                    'handling' => ['Tangani dengan lembut', 'Periksa tanda-tanda pembusukan'],
                    'use' => ['Siap dikonsumsi', 'Cocok untuk semua jenis masakan'],
                    'timeframe' => ['Konsumsi segera untuk rasa terbaik']
                ],
                'busuk' => [
                    'storage' => ['Jangan disimpan bersama tomat lain', 'Segera buang'],
                    'handling' => ['Hindari kontak dengan tomat sehat', 'Gunakan sarung tangan saat membuang'],
                    'use' => ['Tidak layak konsumsi', 'Dapat digunakan untuk kompos'],
                    'timeframe' => ['Buang segera']
                ]
            ];
            
            return $fallbackRecommendations[$maturityLevel] ?? $fallbackRecommendations['mentah'];
        }
        
        return $recommendations;
    }

    /**
     * Fallback Decision Tree jika tidak ada aturan di database
     */
    private function fallbackDecisionTree($features)
    {
        $red = $features['red'];
        $green = $features['green'];
        $blue = $features['blue'];
        
        if ($red > 150 && $red > $green * 1.5) {
            return ['classification' => 'matang', 'confidence' => 0.8];
        } elseif ($green > $red && $green > 120) {
            return ['classification' => 'mentah', 'confidence' => 0.75];
        } elseif ($blue > 80 && $blue > $red) {
            return ['classification' => 'busuk', 'confidence' => 0.7];
        } else {
            return ['classification' => 'setengah_matang', 'confidence' => 0.65];
        }
    }

    /**
     * Evaluasi aturan Decision Tree
     */
    private function evaluateDecisionTreeRules($features, $rules)
    {
        $path = [];
        $rulesUsed = [];
        $currentNode = 'root';
        $maxIterations = 10;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $rule = $rules->where('node_type', $currentNode)->first();
            
            if (!$rule) {
                break;
            }

            $rulesUsed[] = $rule->rule_name;
            $path[] = $currentNode;

            if ($rule->evaluateCondition($features)) {
                if ($rule->true_result) {
                    return [
                        'classification' => $rule->true_result,
                        'confidence' => 0.85,
                        'path' => $path,
                        'rules_used' => $rulesUsed
                    ];
                }
                $currentNode = $rule->true_node;
            } else {
                if ($rule->false_result) {
                    return [
                        'classification' => $rule->false_result,
                        'confidence' => 0.8,
                        'path' => $path,
                        'rules_used' => $rulesUsed
                    ];
                }
                $currentNode = $rule->false_node;
            }

            $iteration++;
        }

        // Fallback jika tidak menemukan hasil
        return [
            'classification' => 'mentah',
            'confidence' => 0.6,
            'path' => $path,
            'rules_used' => $rulesUsed
        ];
    }

    /**
     * Update reading dengan hasil analisis
     */
    private function updateReadingWithAnalysis($readingId, $analysisResult)
    {
        try {
            $reading = TomatReading::find($readingId);
            if ($reading && isset($analysisResult['ensemble'])) {
                $reading->update([
                    'maturity_level' => $analysisResult['ensemble']['prediction'],
                    'confidence_score' => $analysisResult['ensemble']['confidence'] * 100,
                    'analysis_method' => 'ensemble_ai',
                    'analysis_details' => json_encode($analysisResult)
                ]);
                
                Log::info('Reading updated with AI analysis', [
                    'reading_id' => $readingId,
                    'prediction' => $analysisResult['ensemble']['prediction'],
                    'confidence' => $analysisResult['ensemble']['confidence']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update reading with analysis', [
                'reading_id' => $readingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dapatkan data untuk dashboard
     */
    private function getDashboardData()
    {
        return [
            'total_readings' => TomatReading::count(),
            'today_readings' => TomatReading::whereDate('created_at', today())->count(),
            'training_data_count' => TrainingData::active()->count(),
            'recent_readings' => TomatReading::latest()->take(10)->get(),
            'accuracy_data' => $this->modelEvaluationService->getCurrentAccuracies()
        ];
    }
}