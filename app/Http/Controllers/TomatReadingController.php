<?php

/**
 * =====================================================================================
 * SISTEM PAKAR KEMATANGAN TOMAT BERBASIS ARTIFICIAL INTELLIGENCE
 * =====================================================================================
 *
 * Controller ini mengimplementasikan sistem pakar untuk mendeteksi tingkat kematangan
 * tomat menggunakan kombinasi beberapa algoritma Machine Learning:
 *
 * 1. DECISION TREE (Pohon Keputusan)
 *    - Algoritma utama yang menggunakan aturan berbasis kondisi
 *    - Menggunakan nilai RGB dan rasio warna sebagai fitur
 *    - Aturan dapat dikonfigurasi secara dinamis melalui database
 *    - Fallback ke aturan hardcode jika database kosong
 *
 * 2. K-NEAREST NEIGHBORS (KNN)
 *    - Algoritma klasifikasi berbasis kedekatan data
 *    - Menggunakan Euclidean distance untuk menghitung jarak
 *    - K=3 (mengambil 3 tetangga terdekat)
 *    - Data training dapat dikelola melalui database
 *
 * 3. RANDOM FOREST
 *    - Ensemble method yang mensimulasikan multiple decision trees
 *    - Menggunakan voting majority untuk prediksi final
 *    - Implementasi sederhana dengan 3 "pohon" virtual
 *
 * 4. ENSEMBLE VOTING
 *    - Menggabungkan hasil dari ketiga algoritma
 *    - Menggunakan majority voting untuk prediksi final
 *    - Memberikan confidence score berdasarkan konsensus
 *
 * FITUR UTAMA:
 * - Analisis warna RGB untuk deteksi kematangan
 * - Sistem rekomendasi dinamis berbasis database
 * - Confidence scoring untuk setiap prediksi
 * - API endpoints untuk integrasi dengan IoT devices
 * - Interface admin untuk konfigurasi aturan dan data training
 *
 * KLASIFIKASI KEMATANGAN:
 * - 'mentah': Tomat hijau, belum siap panen
 * - 'setengah_matang': Tomat mulai berubah warna, siap transportasi
 * - 'matang': Tomat merah, siap konsumsi
 * - 'busuk': Tomat rusak, tidak layak konsumsi
 *
 * =====================================================================================
 */

namespace App\Http\Controllers;

use App\Models\TomatReading;
use App\Models\TrainingData;
use App\Models\DecisionTreeRule;
use App\Models\Recommendation;
use App\Models\Classification;
use App\Services\ModelEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TomatReadingController extends Controller
{
    /**
     * Model Evaluation Service untuk menghitung akurasi real-time
     */
    protected $modelEvaluationService;

    /**
     * Constructor untuk dependency injection
     */
    public function __construct(ModelEvaluationService $modelEvaluationService)
    {
        $this->modelEvaluationService = $modelEvaluationService;
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MENAMPILKAN SEMUA DATA PEMBACAAN TOMAT
     * =====================================================================================
     *
     * Method ini mengembalikan semua data pembacaan sensor tomat yang tersimpan
     * dalam database, diurutkan berdasarkan waktu terbaru.
     *
     * FUNGSI UTAMA:
     * - Mengambil semua record TomatReading dari database
     * - Mengurutkan berdasarkan created_at (terbaru di atas)
     * - Mengembalikan response JSON dengan format standar
     *
     * KEGUNAAN:
     * - Dashboard monitoring untuk melihat riwayat pembacaan
     * - Analisis trend kematangan tomat dari waktu ke waktu
     * - Data untuk training dan validasi algoritma AI
     *
     * @return \Illuminate\Http\Response JSON response dengan semua data pembacaan
     */
    public function index()
    {
        $readings = TomatReading::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $readings
        ]);
    }

    public function show($id)
    {
        $reading = TomatReading::find($id);

        if (!$reading) {
            return response()->json([
                'success' => false,
                'message' => 'Tomat reading not found'
            ], 404);
        }

        // Generate recommendations and analysis
        $recommendations = $this->generateRecommendations($reading->maturity_level);
        $mlAnalysis = $this->generateDecisionTreeAnalysis([
            'red' => $reading->red_value,
            'green' => $reading->green_value,
            'blue' => $reading->blue_value,
        ]);

        return response()->json([
            'success' => true,
            'data' => $reading,
            'recommendations' => $recommendations,
            'analysis' => $mlAnalysis
        ]);
    }

    public function store(Request $request)
    {
        try {
            // Log incoming request untuk debugging
            Log::info('ESP32 data received', [
                'device_id' => $request->device_id,
                'data' => $request->all()
            ]);
            
            $validator = Validator::make($request->all(), [
                'device_id' => 'required|string',
                'red_value' => 'required|integer|min:0|max:255',
                'green_value' => 'required|integer|min:0|max:255',
                'blue_value' => 'required|integer|min:0|max:255',
                'clear_value' => 'required|integer|min:0',
                'temperature' => 'required|numeric',
                'humidity' => 'required|numeric',
                'raw_sensor_data' => 'nullable|array'
            ]);

        // Auto-create device if not exists
        $device = \App\Models\Device::firstOrCreate(
            ['device_id' => $request->device_id],
            [
                'name' => 'Auto-registered ' . $request->device_id,
                'location' => 'Unknown'
            ]
        );

        if ($validator->fails()) {
            Log::warning('ESP32 data validation failed', [
                'device_id' => $request->device_id,
                'errors' => $validator->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine tomato maturity based on RGB values
        $maturityLevel = $this->determineTomatoMaturity($request->red_value, $request->green_value, $request->blue_value);

        // Calculate confidence score
        $confidenceScore = $this->calculateConfidenceScore($request->red_value, $request->green_value, $request->blue_value, $maturityLevel);

        // Determine status
        $status = $this->determineStatus($maturityLevel);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($maturityLevel);

        // Generate decision tree analysis
        $mlAnalysis = $this->generateDecisionTreeAnalysis([
            'red' => $request->red_value,
            'green' => $request->green_value,
            'blue' => $request->blue_value,
        ]);

        // Create the reading
        try {
            $reading = TomatReading::create([
                'device_id' => $request->device_id,
                'red_value' => $request->red_value,
                'green_value' => $request->green_value,
                'blue_value' => $request->blue_value,
                'clear_value' => $request->clear_value,
                'temperature' => $request->temperature,
                'humidity' => $request->humidity,
                'maturity_level' => $maturityLevel,
                'confidence_score' => $confidenceScore,
                'status' => $status,
                'recommendations' => $recommendations,
                'ml_analysis' => $mlAnalysis,
                'raw_sensor_data' => $request->raw_sensor_data ?? []
            ]);
            
            Log::info('TomatReading created successfully', [
                'reading_id' => $reading->id,
                'device_id' => $request->device_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create TomatReading', [
                'error' => $e->getMessage(),
                'device_id' => $request->device_id,
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save sensor data',
                'error' => 'Database error occurred'
            ], 500);
        }

        // Trigger real-time accuracy evaluation setelah data baru ditambahkan
        try {
            $this->modelEvaluationService->evaluateAllAlgorithms();
            Log::info('Model accuracy evaluation completed after new reading', [
                'reading_id' => $reading->id,
                'maturity_level' => $maturityLevel
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to evaluate model accuracy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reading_id' => $reading->id
            ]);
            // Jangan throw exception, biarkan response tetap sukses
            // karena data sudah berhasil disimpan
        }

        Log::info('ESP32 data processed successfully', [
            'reading_id' => $reading->id,
            'device_id' => $request->device_id,
            'maturity_level' => $maturityLevel,
            'confidence_score' => $confidenceScore
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Reading created successfully',
            'data' => $reading,
            'recommendations' => $recommendations,
            'analysis' => $mlAnalysis
        ], 201);
        
        } catch (\Exception $e) {
            Log::error('Critical error in store method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => 'Data processing failed'
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MENERIMA DATA SENSOR DARI ESP32 MENGGUNAKAN GET METHOD
     * =====================================================================================
     *
     * Method ini menerima data sensor dari ESP32 melalui parameter URL (GET method)
     * dan memproses data dengan algoritma AI yang sama seperti method store.
     *
     * PARAMETER URL YANG DITERIMA:
     * - device_id: ID perangkat ESP32
     * - red_value: Nilai merah RGB (0-255)
     * - green_value: Nilai hijau RGB (0-255)
     * - blue_value: Nilai biru RGB (0-255)
     * - clear_value: Nilai clear sensor warna
     * - temperature: Suhu dalam Celsius
     * - humidity: Kelembaban dalam persen
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function receiveSensorData(Request $request)
    {
        try {
            // Log incoming request untuk debugging
            Log::info('ESP32 GET data received', [
                'device_id' => $request->query('device_id'),
                'data' => $request->query()
            ]);
            
            $validator = Validator::make($request->query(), [
                'device_id' => 'required|string',
                'red_value' => 'required|integer|min:0|max:255',
                'green_value' => 'required|integer|min:0|max:255',
                'blue_value' => 'required|integer|min:0|max:255',
                'clear_value' => 'required|integer|min:0',
                'temperature' => 'required|numeric',
                'humidity' => 'required|numeric'
            ]);

            // Auto-create device if not exists
            $device = \App\Models\Device::firstOrCreate(
                ['device_id' => $request->query('device_id')],
                [
                    'name' => 'Auto-registered ' . $request->query('device_id'),
                    'location' => 'Unknown'
                ]
            );

            if ($validator->fails()) {
                Log::warning('ESP32 GET data validation failed', [
                    'device_id' => $request->query('device_id'),
                    'errors' => $validator->errors(),
                    'request_data' => $request->query()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get values from query parameters
            $redValue = (int) $request->query('red_value');
            $greenValue = (int) $request->query('green_value');
            $blueValue = (int) $request->query('blue_value');
            $clearValue = (int) $request->query('clear_value');
            $temperature = (float) $request->query('temperature');
            $humidity = (float) $request->query('humidity');
            $deviceId = $request->query('device_id');

            // Determine tomato maturity based on RGB values
            $maturityLevel = $this->determineTomatoMaturity($redValue, $greenValue, $blueValue);

            // Calculate confidence score
            $confidenceScore = $this->calculateConfidenceScore($redValue, $greenValue, $blueValue, $maturityLevel);

            // Determine status
            $status = $this->determineStatus($maturityLevel);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($maturityLevel);

            // Generate decision tree analysis
            $mlAnalysis = $this->generateDecisionTreeAnalysis([
                'red' => $redValue,
                'green' => $greenValue,
                'blue' => $blueValue,
            ]);

            // Create the reading
            try {
                $reading = TomatReading::create([
                    'device_id' => $deviceId,
                    'red_value' => $redValue,
                    'green_value' => $greenValue,
                    'blue_value' => $blueValue,
                    'clear_value' => $clearValue,
                    'temperature' => $temperature,
                    'humidity' => $humidity,
                    'maturity_level' => $maturityLevel,
                    'confidence_score' => $confidenceScore,
                    'status' => $status,
                    'recommendations' => $recommendations,
                    'ml_analysis' => $mlAnalysis,
                    'raw_sensor_data' => []
                ]);
                
                Log::info('TomatReading created successfully via GET', [
                    'reading_id' => $reading->id,
                    'device_id' => $deviceId
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to create TomatReading via GET', [
                    'error' => $e->getMessage(),
                    'device_id' => $deviceId,
                    'data' => $request->query()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save sensor data',
                    'error' => 'Database error occurred'
                ], 500);
            }

            // Trigger real-time accuracy evaluation setelah data baru ditambahkan
            try {
                $this->modelEvaluationService->evaluateAllAlgorithms();
                Log::info('Model accuracy evaluation completed after new GET reading', [
                    'reading_id' => $reading->id,
                    'maturity_level' => $maturityLevel
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to evaluate model accuracy via GET', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'reading_id' => $reading->id
                ]);
                // Jangan throw exception, biarkan response tetap sukses
                // karena data sudah berhasil disimpan
            }

            Log::info('ESP32 GET data processed successfully', [
                'reading_id' => $reading->id,
                'device_id' => $deviceId,
                'maturity_level' => $maturityLevel,
                'confidence_score' => $confidenceScore
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reading created successfully via GET',
                'data' => $reading,
                'recommendations' => $recommendations,
                'analysis' => $mlAnalysis
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Critical error in receiveSensorData method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->query()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => 'Data processing failed'
            ], 500);
        }
    }

    /**
     * Get readings for a specific device
     */
    public function getByDevice($deviceId)
    {
        $readings = TomatReading::where('device_id', $deviceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $readings->count(),
            'data' => $readings
        ]);
    }

    /**
     * =====================================================================================
     * ENDPOINT ANALISIS RGB TANPA PENYIMPANAN DATABASE
     * =====================================================================================
     *
     * Method ini khusus untuk analisis RGB dari dashboard widget tanpa menyimpan
     * data ke database. Digunakan untuk testing dan preview hasil AI.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'red' => 'required|integer|min:0|max:255',
            'green' => 'required|integer|min:0|max:255',
            'blue' => 'required|integer|min:0|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine tomato maturity based on RGB values
        $maturityLevel = $this->determineTomatoMaturity($request->red, $request->green, $request->blue);

        // Calculate confidence score
        $confidenceScore = $this->calculateConfidenceScore($request->red, $request->green, $request->blue, $maturityLevel);

        // Determine status
        $status = $this->determineStatus($maturityLevel);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($maturityLevel);

        // Generate comprehensive ML analysis using ModelEvaluationService
        $mlAnalysis = [
            'decision_tree' => $this->generateDecisionTreeAnalysis([
                'red' => $request->red,
                'green' => $request->green,
                'blue' => $request->blue,
            ]),
            'knn_analysis' => $this->generateKNNAnalysisFromService($request->red, $request->green, $request->blue),
            'random_forest' => $this->generateRandomForestAnalysisFromService($request->red, $request->green, $request->blue),
            'ensemble_result' => $this->calculateEnsembleVotingFromService($request->red, $request->green, $request->blue)
        ];

        return response()->json([
            'success' => true,
            'message' => 'Analysis completed successfully',
            'data' => [
                'rgb_input' => [
                    'red' => $request->red,
                    'green' => $request->green,
                    'blue' => $request->blue
                ],
                'maturity_level' => $maturityLevel,
                'confidence_score' => $confidenceScore,
                'status' => $status,
                'color_preview' => sprintf('#%02x%02x%02x', $request->red, $request->green, $request->blue),
                'recommendations' => $recommendations,
                'ml_analysis' => $mlAnalysis
            ]
        ]);
    }

    /**
     * =====================================================================================
     * ALGORITMA INTI DECISION TREE UNTUK KLASIFIKASI KEMATANGAN TOMAT
     * =====================================================================================
     *
     * Method ini mengimplementasikan algoritma Decision Tree sederhana untuk
     * menentukan tingkat kematangan tomat berdasarkan nilai RGB dari sensor warna.
     *
     * PRINSIP ALGORITMA:
     * 1. RASIO MERAH/HIJAU: Indikator utama kematangan
     *    - Tomat mentah: dominasi hijau (rasio < 1.1)
     *    - Tomat setengah matang: transisi warna (rasio 1.1-1.7)
     *    - Tomat matang: dominasi merah (rasio > 1.7)
     *
     * 2. DETEKSI PEMBUSUKAN: Kombinasi warna abnormal
     *    - Nilai biru dan hijau tinggi dengan merah rendah
     *    - Indikasi perubahan warna akibat pembusukan
     *
     * 3. THRESHOLD BERBASIS PENELITIAN:
     *    - Nilai ambang batas berdasarkan studi empiris
     *    - Dapat disesuaikan berdasarkan jenis tomat dan kondisi lingkungan
     *
     * INPUT: Nilai RGB (0-255) dari sensor TCS3200/TCS34725
     * OUTPUT: String klasifikasi ('mentah', 'setengah_matang', 'matang', 'busuk')
     *
     * @param int $red Nilai merah (0-255)
     * @param int $green Nilai hijau (0-255)
     * @param int $blue Nilai biru (0-255)
     * @return string Klasifikasi kematangan tomat
     */
    private function determineTomatoMaturity($red, $green, $blue)
    {
        // FITUR UTAMA: Rasio merah/hijau sebagai indikator kematangan
        // Penelitian menunjukkan rasio ini paling akurat untuk deteksi ripeness
        $redToGreenRatio = $green > 0 ? $red / $green : 0;

        // NODE 1: Deteksi pembusukan berdasarkan pola warna abnormal
        // Kombinasi biru-hijau tinggi + merah rendah = indikasi busuk
        if ($blue > 80 && $green > 80 && $red < 100) {
            return 'busuk';
        }

        // NODE 2: Klasifikasi matang - dominasi merah dengan rasio tinggi
        // Threshold 1.7 dan red > 150 berdasarkan analisis data empiris
        if ($redToGreenRatio > 1.7 && $red > 150) {
            return 'matang';
        }
        // NODE 3: Klasifikasi setengah matang - transisi warna
        // Fase perubahan dari hijau ke merah
        elseif ($redToGreenRatio > 1.1 && $red > 100) {
            return 'setengah_matang';
        }
        // NODE 4: Default - klasifikasi mentah
        // Dominasi hijau atau rasio rendah
        else {
            return 'mentah';
        }
    }

    /**
     * =====================================================================================
     * ALGORITMA CONFIDENCE SCORING BERBASIS FUZZY LOGIC
     * =====================================================================================
     *
     * Method ini menghitung tingkat kepercayaan (confidence score) dari prediksi
     * kematangan tomat menggunakan prinsip fuzzy logic dan analisis statistik.
     *
     * METODOLOGI CONFIDENCE SCORING:
     *
     * 1. BASE CONFIDENCE (Kepercayaan Dasar):
     *    - Dihitung berdasarkan seberapa "jelas" karakteristik setiap kelas
     *    - Tomat matang: confidence tinggi jika merah dominan + rasio tinggi
     *    - Tomat mentah: confidence tinggi jika hijau dominan
     *    - Setengah matang: confidence lebih rendah (zona transisi)
     *    - Busuk: confidence tinggi jika pola warna abnormal jelas
     *
     * 2. INTENSITY FACTOR (Faktor Intensitas):
     *    - Warna yang lebih intens = confidence lebih tinggi
     *    - Berdasarkan rata-rata nilai RGB
     *
     * 3. CONFIDENCE CAPPING:
     *    - Maksimal 95% untuk menghindari overconfidence
     *    - Menyisakan ruang untuk uncertainty
     *
     * APLIKASI DALAM SISTEM PAKAR:
     * - Confidence > 80%: Prediksi sangat reliable
     * - Confidence 60-80%: Prediksi cukup reliable
     * - Confidence < 60%: Perlu verifikasi manual
     *
     * @param int $red Nilai merah (0-255)
     * @param int $green Nilai hijau (0-255)
     * @param int $blue Nilai biru (0-255)
     * @param string $maturityLevel Hasil klasifikasi kematangan
     * @return float Confidence score (0.0 - 0.95)
     */
    private function calculateConfidenceScore($red, $green, $blue, $maturityLevel)
    {
        // FITUR UTAMA: Rasio merah/hijau untuk analisis confidence
        $redToGreenRatio = $green > 0 ? $red / $green : 0;

        // TAHAP 1: Hitung base confidence berdasarkan karakteristik kelas
        $baseConfidence = 0;

        switch ($maturityLevel) {
            case 'matang':
                // KELAS MATANG: Confidence tinggi jika indikator kematangan kuat
                if ($red > 170 && $redToGreenRatio > 2.0) {
                    $baseConfidence = 0.9;  // Sangat yakin - merah dominan sekali
                } elseif ($red > 150 && $redToGreenRatio > 1.7) {
                    $baseConfidence = 0.8;  // Yakin - sesuai threshold standar
                } else {
                    $baseConfidence = 0.7;  // Cukup yakin - borderline case
                }
                break;

            case 'setengah_matang':
                // KELAS SETENGAH MATANG: Confidence lebih rendah (zona transisi)
                if ($redToGreenRatio > 1.3 && $red > 120) {
                    $baseConfidence = 0.75; // Cukup yakin - indikator transisi jelas
                } else {
                    $baseConfidence = 0.65; // Kurang yakin - ambiguous zone
                }
                break;

            case 'mentah':
                // KELAS MENTAH: Confidence tinggi jika hijau dominan jelas
                if ($green > $red && $green > 100) {
                    $baseConfidence = 0.85; // Sangat yakin - hijau dominan kuat
                } else {
                    $baseConfidence = 0.7;  // Cukup yakin - kurang jelas
                }
                break;

            case 'busuk':
                // KELAS BUSUK: Confidence tinggi jika pola abnormal jelas
                if ($blue > 100 && $green > 100 && $red < 80) {
                    $baseConfidence = 0.85; // Sangat yakin - pola busuk jelas
                } else {
                    $baseConfidence = 0.7;  // Cukup yakin - indikasi lemah
                }
                break;

            default:
                $baseConfidence = 0.6; // Default untuk kasus tidak dikenal
        }

        // TAHAP 2: Adjustment berdasarkan intensitas warna
        // Warna yang lebih intens umumnya lebih reliable untuk deteksi
        $colorIntensity = ($red + $green + $blue) / 3;
        $intensityFactor = $colorIntensity > 100 ? 0.1 : 0;

        // TAHAP 3: Final confidence dengan capping untuk menghindari overconfidence
        return min(0.95, $baseConfidence + $intensityFactor);
    }

    /**
     * Determine status based on maturity level
     */
    private function determineStatus($maturityLevel)
    {
        switch ($maturityLevel) {
            case 'mentah':
                return 'Belum siap panen';
            case 'setengah_matang':
                return 'Siap panen untuk transportasi jauh';
            case 'matang':
                return 'Siap konsumsi';
            case 'busuk':
                return 'Tidak layak konsumsi';
            default:
                return 'Status tidak diketahui';
        }
    }

    /**
     * Generate recommendations based on maturity level
     */
    /**
     * =====================================================================================
     * SISTEM REKOMENDASI DINAMIS BERBASIS DATABASE
     * =====================================================================================
     *
     * Method ini mengimplementasikan sistem rekomendasi yang dapat dikonfigurasi
     * secara dinamis melalui database. Sistem ini menggantikan pendekatan hardcode
     * dengan pendekatan yang lebih fleksibel dan mudah dikelola.
     *
     * ARSITEKTUR SISTEM REKOMENDASI:
     * 1. Database-First Approach: Prioritas utama pada data dari database
     * 2. Fallback Mechanism: Sistem cadangan jika database kosong
     * 3. Dynamic Content Management: Admin dapat mengubah rekomendasi via Filament
     * 4. Categorized Recommendations: Rekomendasi dikelompokkan berdasarkan kategori
     *
     * KATEGORI REKOMENDASI:
     * - storage: Panduan penyimpanan optimal
     * - handling: Cara penanganan yang tepat
     * - use: Saran penggunaan dan konsumsi
     * - timeframe: Estimasi waktu dan durasi
     *
     * TINGKAT KEMATANGAN YANG DIDUKUNG:
     * - mentah: Tomat belum matang (hijau)
     * - setengah_matang: Tomat dalam proses pematangan
     * - matang: Tomat siap konsumsi
     * - busuk: Tomat tidak layak konsumsi
     *
     * KEUNGGULAN SISTEM:
     * - Fleksibilitas: Rekomendasi dapat diubah tanpa coding
     * - Skalabilitas: Mudah menambah kategori atau tingkat kematangan baru
     * - Konsistensi: Format output yang seragam
     * - Reliability: Sistem fallback mencegah error
     *
     * INTEGRASI DENGAN AI:
     * - Hasil klasifikasi AI (Decision Tree, KNN, Random Forest) menjadi input
     * - Rekomendasi disesuaikan dengan tingkat confidence
     * - Mendukung multiple maturity levels dalam satu analisis
     *
     * @param string $maturityLevel Tingkat kematangan hasil klasifikasi AI
     * @return array Rekomendasi terstruktur berdasarkan kategori
     */
    private function generateRecommendations($maturityLevel)
    {
        // TAHAP 1: Pengambilan rekomendasi dari database
        // Menggunakan model Recommendation dengan method khusus untuk filtering
        $recommendations = Recommendation::getRecommendationsByMaturityLevel($maturityLevel);

        // TAHAP 2: Validasi dan fallback mechanism
        // Jika database kosong atau tidak ada data, gunakan sistem fallback
        if (empty($recommendations)) {
            return $this->generateFallbackRecommendations($maturityLevel);
        }

        // TAHAP 3: Format standardization
        // Konversi format database ke format yang kompatibel dengan frontend
        $formattedRecommendations = [];
        foreach ($recommendations as $category => $contents) {
            // Ambil rekomendasi pertama dari setiap kategori (prioritas tertinggi)
            $formattedRecommendations[$category] = is_array($contents) && !empty($contents) ? $contents[0] : '';
        }

        return $formattedRecommendations;
    }

    /**
     * =====================================================================================
     * SISTEM FALLBACK REKOMENDASI BERBASIS KNOWLEDGE BASE
     * =====================================================================================
     *
     * Method ini menyediakan sistem cadangan (fallback) untuk rekomendasi ketika
     * database kosong atau tidak tersedia. Sistem ini menggunakan knowledge base
     * yang telah dikurasi oleh ahli pertanian dan hortikultura.
     *
     * PRINSIP FALLBACK SYSTEM:
     * 1. Reliability: Memastikan sistem selalu memberikan output
     * 2. Expert Knowledge: Berdasarkan pengetahuan ahli pertanian
     * 3. Comprehensive Coverage: Mencakup semua tingkat kematangan
     * 4. Practical Guidelines: Rekomendasi yang dapat diterapkan
     *
     * KNOWLEDGE BASE SUMBER:
     * - Standar industri pertanian
     * - Penelitian pascapanen tomat
     * - Best practices penyimpanan dan penanganan
     * - Panduan keamanan pangan
     *
     * KATEGORI REKOMENDASI FALLBACK:
     * - storage: Kondisi penyimpanan optimal (suhu, kelembaban, cahaya)
     * - handling: Teknik penanganan untuk meminimalkan kerusakan
     * - use: Panduan penggunaan berdasarkan kematangan
     * - timeframe: Estimasi waktu berdasarkan kondisi penyimpanan
     *
     * TINGKAT KEMATANGAN & KARAKTERISTIK:
     * - mentah: Hijau, keras, perlu waktu pematangan 5-7 hari
     * - setengah_matang: Mulai berubah warna, ideal untuk transportasi
     * - matang: Merah penuh, siap konsumsi, tahan 2-3 hari
     * - busuk: Tidak layak konsumsi, risiko kontaminasi
     *
     * INTEGRASI DENGAN SISTEM UTAMA:
     * - Dipanggil otomatis jika database kosong
     * - Format output konsisten dengan sistem database
     * - Mendukung semua fitur yang sama dengan sistem utama
     *
     * @param string $maturityLevel Tingkat kematangan hasil klasifikasi AI
     * @return array Rekomendasi fallback berdasarkan expert knowledge
     */
    private function generateFallbackRecommendations($maturityLevel)
    {
        $recommendations = [];

        switch ($maturityLevel) {
            case 'mentah':
                $recommendations = [
                    'storage' => 'Simpan di suhu ruangan (20-25°C) dengan sedikit sinar matahari untuk mempercepat pematangan',
                    'handling' => 'Tangani dengan hati-hati, hindari benturan karena mudah memar',
                    'use' => 'Belum disarankan untuk konsumsi langsung, tunggu hingga matang',
                    'timeframe' => 'Perkiraan 5-7 hari untuk mencapai kematangan optimal'
                ];
                break;

            case 'setengah_matang':
                $recommendations = [
                    'storage' => 'Simpan pada suhu 15-20°C untuk memperlambat pematangan',
                    'handling' => 'Dapat ditangani dengan normal, tahan terhadap transportasi',
                    'use' => 'Ideal untuk dikirim ke pasar, akan matang dalam beberapa hari',
                    'timeframe' => 'Perkiraan 2-4 hari untuk mencapai kematangan optimal'
                ];
                break;

            case 'matang':
                $recommendations = [
                    'storage' => 'Simpan di lemari es (7-10°C) untuk memperpanjang kesegaran',
                    'handling' => 'Tangani dengan hati-hati, sudah lebih lunak dan mudah rusak',
                    'use' => 'Siap untuk konsumsi langsung, ideal untuk salad segar atau saji langsung',
                    'timeframe' => 'Optimal untuk dikonsumsi dalam 2-3 hari'
                ];
                break;

            case 'busuk':
                $recommendations = [
                    'storage' => 'Tidak disarankan untuk disimpan',
                    'handling' => 'Pisahkan dari produk lain untuk mencegah kontaminasi',
                    'use' => 'Tidak layak konsumsi, sebaiknya dibuang',
                    'timeframe' => 'Segera dibuang untuk menghindari kontaminasi pada produk lain'
                ];
                break;

            default:
                $recommendations = [
                    'storage' => 'Simpan sesuai kondisi tomat',
                    'handling' => 'Tangani dengan hati-hati',
                    'use' => 'Periksa kualitas sebelum konsumsi',
                    'timeframe' => 'Lakukan penilaian visual untuk menentukan masa konsumsi'
                ];
        }

        return $recommendations;
    }

    /**
     * Generate decision tree analysis based on RGB values using database rules
     */
    /**
     * =====================================================================================
     * ALGORITMA DECISION TREE DENGAN DYNAMIC RULE MANAGEMENT
     * =====================================================================================
     *
     * Method ini mengimplementasikan Decision Tree yang dapat dikonfigurasi secara
     * dinamis melalui database. Decision Tree adalah algoritma supervised learning
     * yang membuat keputusan berdasarkan serangkaian aturan if-then.
     *
     * KONSEP DECISION TREE:
     * 1. ROOT NODE: Titik awal pengambilan keputusan
     * 2. INTERNAL NODES: Tes kondisi pada fitur (RGB values, ratios)
     * 3. BRANCHES: Hasil dari tes kondisi (true/false)
     * 4. LEAF NODES: Keputusan final (kelas kematangan)
     *
     * KEUNGGULAN DECISION TREE:
     * - Mudah diinterpretasi dan dijelaskan (white-box model)
     * - Tidak memerlukan preprocessing data
     * - Dapat menangani data numerik dan kategorikal
     * - Memberikan feature importance secara natural
     * - Cepat dalam prediksi
     *
     * FITUR DYNAMIC RULE MANAGEMENT:
     * - Aturan dapat dikonfigurasi melalui admin interface
     * - Mendukung berbagai operator (>, <, >=, <=, ==, !=)
     * - Prioritas aturan berdasarkan urutan (order)
     * - Fallback ke aturan hardcode jika database kosong
     *
     * FITUR YANG DIHITUNG:
     * - RGB values: Nilai warna dasar dari sensor
     * - Red/Green ratio: Indikator utama kematangan
     * - Red/Blue ratio: Indikator sekunder
     * - Green/Blue ratio: Indikator pembusukan
     *
     * STRUKTUR ATURAN DATABASE:
     * - condition_field: Fitur yang diuji (red, green, blue, ratio_rg, dll)
     * - condition_operator: Operator perbandingan
     * - condition_value: Nilai threshold
     * - maturity_class: Kelas hasil jika kondisi terpenuhi
     *
     * @param array $colorData Data RGB input ['red' => int, 'green' => int, 'blue' => int]
     * @return array Hasil analisis Decision Tree dengan ensemble voting
     */
    private function generateDecisionTreeAnalysis($colorData)
    {
        // TAHAP 1: Ekstraksi fitur dari data RGB
        $red = $colorData['red'];
        $green = $colorData['green'];
        $blue = $colorData['blue'];

        // TAHAP 2: Hitung fitur turunan (derived features)
        // Rasio-rasio ini adalah indikator kunci untuk klasifikasi kematangan
        $redToGreen = $green > 0 ? $red / $green : 0;     // Indikator utama kematangan
        $redToBlue = $blue > 0 ? $red / $blue : 0;        // Indikator sekunder
        $greenToBlue = $blue > 0 ? $green / $blue : 0;    // Indikator pembusukan

        // TAHAP 3: Inisialisasi struktur analisis
        $analysis = [
            'methods' => [
                'decision_tree' => [
                    'name' => 'Dynamic Decision Tree Analysis',
                    'attributes' => [
                        'red' => $red,
                        'green' => $green,
                        'blue' => $blue,
                        'red_to_green_ratio' => round($redToGreen, 3),
                        'red_to_blue_ratio' => round($redToBlue, 3),
                        'green_to_blue_ratio' => round($greenToBlue, 3)
                    ],
                    'decision_path' => [],
                    'rules_used' => []
                ],
                // Jalankan algoritma lain secara paralel untuk ensemble
                'knn' => $this->generateKNNAnalysis($colorData),
                'random_forest' => $this->generateRandomForestAnalysis($colorData)
            ]
        ];

        // TAHAP 4: Ambil aturan Decision Tree dari database
        // Aturan diurutkan berdasarkan prioritas (node_order)
        $rules = DecisionTreeRule::active()->byNodeType('condition')->ordered()->get();

        if ($rules->isEmpty()) {
            // FALLBACK: Gunakan aturan hardcoded jika database kosong
            return $this->generateFallbackDecisionTreeAnalysis($colorData);
        }

        // TAHAP 5: Evaluasi Decision Tree menggunakan aturan dinamis dari database
        $result = $this->evaluateDecisionTree($colorData, $rules);

        // TAHAP 6: Masukkan hasil evaluasi ke struktur analisis
        $analysis['methods']['decision_tree']['decision_path'] = $result['path'];
        $analysis['methods']['decision_tree']['rules_used'] = $result['rules_used'];
        $analysis['methods']['decision_tree']['final_classification'] = $result['classification'];
        $analysis['methods']['decision_tree']['confidence_note'] = 'Keputusan diambil berdasarkan ' . count($result['rules_used']) . ' aturan dinamis dari database';

        // TAHAP 7: Implementasi Ensemble Voting
        // Gabungkan hasil dari Decision Tree, KNN, dan Random Forest
        $analysis['ensemble_result'] = $this->calculateEnsembleVoting(
            $result['classification'],
            $analysis['methods']['knn']['prediction'],
            $analysis['methods']['random_forest']['prediction']
        );

        return $analysis;
    }

    /**
     * =====================================================================================
     * EVALUASI DECISION TREE MENGGUNAKAN ATURAN DATABASE DINAMIS
     * =====================================================================================
     *
     * Method ini mengevaluasi Decision Tree dengan menggunakan aturan yang tersimpan
     * dalam database. Algoritma ini mengimplementasikan tree traversal dengan
     * mengikuti path dari root node hingga leaf node.
     *
     * ALGORITMA TREE TRAVERSAL:
     * 1. Mulai dari root node (node_order = 1)
     * 2. Evaluasi kondisi pada current node
     * 3. Jika kondisi TRUE: lanjut ke next_node_true
     * 4. Jika kondisi FALSE: lanjut ke next_node_false
     * 5. Ulangi hingga mencapai leaf node atau max iterations
     *
     * STRUKTUR NODE DATABASE:
     * - node_type: 'condition' (internal node) atau 'leaf' (terminal node)
     * - condition_field: Fitur yang dievaluasi (red, green, blue, ratio_rg, dll)
     * - condition_operator: Operator (>, <, >=, <=, ==, !=)
     * - condition_value: Threshold value
     * - next_node_true: Node tujuan jika kondisi TRUE
     * - next_node_false: Node tujuan jika kondisi FALSE
     * - maturity_class: Kelas hasil (untuk leaf nodes)
     *
     * FITUR KEAMANAN:
     * - Max iterations untuk mencegah infinite loops
     * - Validasi node existence
     * - Error handling untuk aturan yang tidak valid
     *
     * DECISION PATH TRACKING:
     * - Merekam setiap langkah evaluasi
     * - Menyimpan aturan yang digunakan
     * - Memberikan transparansi proses pengambilan keputusan
     *
     * @param array $colorData Data RGB dan fitur turunan
     * @param Collection $rules Koleksi aturan Decision Tree dari database
     * @return array Hasil evaluasi dengan path, rules used, dan classification
     */
    private function evaluateDecisionTree($colorData, $rules)
    {
        // TAHAP 1: Inisialisasi variabel tracking
        $path = [];                    // Jejak langkah-langkah evaluasi
        $rulesUsed = [];              // Aturan yang digunakan dalam evaluasi
        $currentNodeOrder = 1;        // Mulai dari root node
        $maxIterations = 10;          // Batas maksimal iterasi (safety net)
        $iteration = 0;               // Counter iterasi saat ini

        while ($iteration < $maxIterations) {
            $iteration++;

            // Cari rule dengan node_order yang sesuai
            $currentRule = $rules->where('node_order', $currentNodeOrder)->first();

            if (!$currentRule) {
                // Jika tidak ada rule, gunakan klasifikasi default
                $path[] = "Node {$currentNodeOrder}: Tidak ditemukan aturan, menggunakan klasifikasi default";
                return [
                    'path' => $path,
                    'rules_used' => $rulesUsed,
                    'classification' => 'mentah' // default
                ];
            }

            $rulesUsed[] = $currentRule->rule_name;

            // Evaluasi kondisi
            $conditionResult = $currentRule->evaluateCondition($colorData);
            $conditionText = $conditionResult ? 'true' : 'false';

            $path[] = "Node {$currentNodeOrder} ({$currentRule->rule_name}): {$currentRule->condition_field} {$currentRule->condition_operator} {$currentRule->condition_value} = {$conditionText}";

            // Tentukan aksi selanjutnya
            if ($conditionResult) {
                if ($currentRule->true_action === 'classify') {
                    $path[] = "Hasil: Klasifikasi sebagai {$currentRule->true_result}";
                    return [
                        'path' => $path,
                        'rules_used' => $rulesUsed,
                        'classification' => $currentRule->true_result
                    ];
                } else {
                    $currentNodeOrder = (int) $currentRule->true_result;
                }
            } else {
                if ($currentRule->false_action === 'classify') {
                    $path[] = "Hasil: Klasifikasi sebagai {$currentRule->false_result}";
                    return [
                        'path' => $path,
                        'rules_used' => $rulesUsed,
                        'classification' => $currentRule->false_result
                    ];
                } else {
                    $currentNodeOrder = (int) $currentRule->false_result;
                }
            }
        }

        // Jika mencapai batas iterasi
        $path[] = "Peringatan: Mencapai batas maksimal iterasi, menggunakan klasifikasi default";
        return [
            'path' => $path,
            'rules_used' => $rulesUsed,
            'classification' => 'mentah'
        ];
    }

    /**
     * Fallback decision tree analysis with hardcoded rules
     */
    private function generateFallbackDecisionTreeAnalysis($colorData)
    {
        $red = $colorData['red'];
        $green = $colorData['green'];
        $blue = $colorData['blue'];
        $redToGreen = $green > 0 ? $red / $green : 0;

        $analysis = [
            'methods' => [
                'decision_tree' => [
                    'name' => 'Fallback Decision Tree Analysis',
                    'attributes' => [
                        'red' => $red,
                        'green' => $green,
                        'blue' => $blue,
                        'red_to_green_ratio' => $redToGreen
                    ],
                    'decision_path' => []
                ],
                'knn' => $this->generateKNNAnalysis($colorData),
                'random_forest' => $this->generateRandomForestAnalysis($colorData)
            ]
        ];

        // Fallback Decision Tree Analysis
        if ($blue > 80 && $green > 80 && $red < 100) {
            $analysis['methods']['decision_tree']['decision_path'] = [
                'node_1' => 'Evaluasi nilai blue > 80 (true)',
                'node_2' => 'Evaluasi nilai green > 80 (true)',
                'node_3' => 'Evaluasi nilai red < 100 (true)',
                'conclusion' => 'Klasifikasi sebagai tomat busuk dengan indikasi perubahan warna abnormal'
            ];
            $classification = 'busuk';
        } elseif ($redToGreen > 1.7 && $red > 150) {
            $analysis['methods']['decision_tree']['decision_path'] = [
                'node_1' => 'Evaluasi nilai blue > 80 (false)',
                'node_2' => 'Evaluasi rasio red/green > 1.7 (true)',
                'node_3' => 'Evaluasi nilai red > 150 (true)',
                'conclusion' => 'Klasifikasi sebagai tomat matang dengan dominasi warna merah'
            ];
            $classification = 'matang';
        } elseif ($redToGreen > 1.1 && $red > 100) {
            $analysis['methods']['decision_tree']['decision_path'] = [
                'node_1' => 'Evaluasi nilai blue > 80 (false)',
                'node_2' => 'Evaluasi rasio red/green > 1.7 (false)',
                'node_3' => 'Evaluasi rasio red/green > 1.1 (true)',
                'node_4' => 'Evaluasi nilai red > 100 (true)',
                'conclusion' => 'Klasifikasi sebagai tomat setengah matang dengan perubahan warna dari hijau ke merah'
            ];
            $classification = 'setengah_matang';
        } else {
            $analysis['methods']['decision_tree']['decision_path'] = [
                'node_1' => 'Evaluasi nilai blue > 80 (false)',
                'node_2' => 'Evaluasi rasio red/green > 1.7 (false)',
                'node_3' => 'Evaluasi rasio red/green > 1.1 (false)',
                'conclusion' => 'Klasifikasi sebagai tomat mentah dengan dominasi warna hijau'
            ];
            $classification = 'mentah';
        }

        $analysis['methods']['decision_tree']['confidence_note'] = 'Menggunakan aturan fallback karena tidak ada aturan dinamis di database';

        // Calculate ensemble voting
        $analysis['ensemble_result'] = $this->calculateEnsembleVoting(
            $classification,
            $analysis['methods']['knn']['prediction'],
            $analysis['methods']['random_forest']['prediction']
        );

        return $analysis;
    }

    /**
     * =====================================================================================
     * ALGORITMA K-NEAREST NEIGHBORS (KNN) UNTUK KLASIFIKASI KEMATANGAN TOMAT
     * =====================================================================================
     *
     * KNN adalah algoritma machine learning supervised yang mengklasifikasikan data
     * berdasarkan kedekatan (similarity) dengan data training yang sudah ada.
     *
     * PRINSIP KERJA KNN:
     * 1. LAZY LEARNING: Tidak membangun model eksplisit, menyimpan semua data training
     * 2. INSTANCE-BASED: Prediksi berdasarkan contoh (instance) yang mirip
     * 3. NON-PARAMETRIC: Tidak membuat asumsi tentang distribusi data
     * 4. DISTANCE-BASED: Menggunakan jarak Euclidean untuk mengukur similarity
     *
     * ALGORITMA KNN STEP-BY-STEP:
     * 1. Hitung jarak dari data input ke semua data training
     * 2. Urutkan berdasarkan jarak (terdekat ke terjauh)
     * 3. Ambil K tetangga terdekat (K=3 dalam implementasi ini)
     * 4. Lakukan voting berdasarkan kelas mayoritas dari K tetangga
     * 5. Prediksi = kelas dengan suara terbanyak
     *
     * RUMUS JARAK EUCLIDEAN:
     * distance = √[(r₁-r₂)² + (g₁-g₂)² + (b₁-b₂)²]
     *
     * KEUNGGULAN KNN:
     * - Sederhana dan mudah diimplementasikan
     * - Efektif untuk data dengan pola non-linear
     * - Tidak perlu training phase (lazy learning)
     * - Robust terhadap data noisy jika K dipilih dengan tepat
     *
     * PARAMETER PENTING:
     * - K=3: Jumlah tetangga terdekat yang dipertimbangkan
     * - Euclidean Distance: Metrik jarak dalam ruang RGB 3D
     * - Majority Voting: Metode penentuan kelas final
     *
     * @param array $colorData Data RGB input ['red' => int, 'green' => int, 'blue' => int]
     * @return array Hasil analisis KNN dengan prediksi dan confidence score
     */
    private function generateKNNAnalysis($colorData)
    {
        // TAHAP 1: Ambil data training dari database
        // Data training adalah contoh-contoh RGB yang sudah dilabeli
        $trainingDataFromDB = TrainingData::active()->get();

        // TAHAP 2: Konversi ke format yang dibutuhkan algoritma KNN
        $trainingData = $trainingDataFromDB->map(function($data) {
            return [
                'red' => $data->red_value,
                'green' => $data->green_value,
                'blue' => $data->blue_value,
                'class' => $data->maturity_class
            ];
        })->toArray();

        // TAHAP 2.5: Tambahkan data klasifikasi yang sudah terverifikasi sebagai data training tambahan
        $verifiedClassifications = Classification::where('is_verified', true)->get();
        $classificationData = $verifiedClassifications->map(function($data) {
            return [
                'red' => $data->red_value,
                'green' => $data->green_value,
                'blue' => $data->blue_value,
                'class' => $data->actual_status
            ];
        })->toArray();

        // Gabungkan data training dan data klasifikasi terverifikasi
        $trainingData = array_merge($trainingData, $classificationData);

        // TAHAP 3: Fallback ke data default jika tidak ada data training di database
        if (empty($trainingData)) {
            // Data ini berdasarkan penelitian empiris karakteristik warna tomat
            $trainingData = [
                // KELAS MATANG: Dominasi merah, nilai hijau rendah
                ['red' => 180, 'green' => 80, 'blue' => 70, 'class' => 'matang'],
                ['red' => 160, 'green' => 90, 'blue' => 75, 'class' => 'matang'],

                // KELAS SETENGAH MATANG: Transisi warna, rasio R/G seimbang
                ['red' => 140, 'green' => 120, 'blue' => 80, 'class' => 'setengah_matang'],
                ['red' => 120, 'green' => 130, 'blue' => 85, 'class' => 'setengah_matang'],

                // KELAS MENTAH: Dominasi hijau, nilai merah rendah
                ['red' => 90, 'green' => 150, 'blue' => 70, 'class' => 'mentah'],
                ['red' => 80, 'green' => 140, 'blue' => 75, 'class' => 'mentah'],

                // KELAS BUSUK: Pola warna abnormal, biru-hijau tinggi
                ['red' => 70, 'green' => 90, 'blue' => 100, 'class' => 'busuk'],
                ['red' => 60, 'green' => 85, 'blue' => 95, 'class' => 'busuk']
            ];
        }

        // TAHAP 4: Set parameter K (jumlah tetangga terdekat)
        // K=3 dipilih untuk menghindari tie dan memberikan voting yang stabil
        $k = 3;
        $distances = [];

        // TAHAP 5: Hitung jarak Euclidean untuk setiap data training
        // Jarak dalam ruang 3D (R, G, B) menggunakan teorema Pythagoras
        foreach ($trainingData as $data) {
            // RUMUS EUCLIDEAN DISTANCE dalam ruang RGB 3D
            $distance = sqrt(
                pow($data['red'] - $colorData['red'], 2) +     // Komponen Red
                pow($data['green'] - $colorData['green'], 2) + // Komponen Green
                pow($data['blue'] - $colorData['blue'], 2)     // Komponen Blue
            );
            $distances[] = [
                'distance' => $distance,
                'class' => $data['class'],
                'rgb' => [$data['red'], $data['green'], $data['blue']] // Untuk debugging
            ];
        }

        // TAHAP 6: Urutkan berdasarkan jarak (ascending - terdekat dulu)
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // TAHAP 7: Ambil K tetangga terdekat
        $kNearest = array_slice($distances, 0, $k);

        // TAHAP 8: Implementasi Majority Voting dari K tetangga terdekat
        $votes = array_count_values(array_column($kNearest, 'class'));
        arsort($votes); // Urutkan berdasarkan jumlah suara (descending)
        $prediction = key($votes);
        $maxVotes = max($votes);

        // TAHAP 9: Hitung confidence berdasarkan konsensus voting
        // Confidence = (suara terbanyak / total K) * 100%
        $confidencePercentage = ($maxVotes / $k) * 100;

        // TAHAP 10: Return hasil lengkap untuk analisis dan debugging
        return [
            'name' => 'K-Nearest Neighbor Analysis',
            'k_value' => $k,
            'nearest_neighbors' => $kNearest,
            'prediction' => $prediction,
            'confidence_score' => $confidencePercentage . '%',
            'voting_details' => $votes, // Detail hasil voting
            'total_training_data' => count($trainingData), // Ukuran dataset
            'algorithm_type' => 'Supervised Learning - Instance Based'
        ];
    }

    private function generateRandomForestAnalysis($colorData)
    {
        // Ambil data training dari database untuk analisis statistik
        $trainingDataFromDB = TrainingData::active()->get();

        // Hitung threshold dinamis berdasarkan data training
        $thresholds = $this->calculateDynamicThresholds($trainingDataFromDB);

        // Simulasi multiple decision trees dengan threshold dinamis
        $trees = [
            // Tree 1: Fokus pada rasio merah/hijau dengan threshold dinamis
            function($red, $green, $blue) use ($thresholds) {
                $ratio = $green > 0 ? $red / $green : 0;
                if ($ratio > $thresholds['red_green_ratio_high']) return 'matang';
                if ($ratio > $thresholds['red_green_ratio_medium']) return 'setengah_matang';
                if ($blue > $thresholds['blue_high']) return 'busuk';
                return 'mentah';
            },
            // Tree 2: Fokus pada nilai absolut dengan threshold dinamis
            function($red, $green, $blue) use ($thresholds) {
                if ($red > $thresholds['red_high']) return 'matang';
                if ($red > $thresholds['red_medium']) return 'setengah_matang';
                if ($blue > $thresholds['blue_medium']) return 'busuk';
                return 'mentah';
            },
            // Tree 3: Fokus pada perbandingan warna dengan threshold dinamis
            function($red, $green, $blue) use ($thresholds) {
                if ($red > $green && $red > $blue && $red > $thresholds['red_dominance']) return 'matang';
                if ($red > $green && $red > $thresholds['red_moderate']) return 'setengah_matang';
                if ($blue > $red && $blue > $green) return 'busuk';
                return 'mentah';
            }
        ];

        $votes = [];
        foreach ($trees as $tree) {
            $prediction = $tree($colorData['red'], $colorData['green'], $colorData['blue']);
            $votes[$prediction] = ($votes[$prediction] ?? 0) + 1;
        }

        arsort($votes);
        $finalPrediction = key($votes);
        $confidenceScore = (max($votes) / count($trees)) * 100;

        return [
            'name' => 'Random Forest Analysis',
            'number_of_trees' => count($trees),
            'votes' => $votes,
            'prediction' => $finalPrediction,
            'confidence_score' => $confidenceScore . '%'
        ];
    }

    /**
     * Hitung threshold dinamis berdasarkan data training
     */
    private function calculateDynamicThresholds($trainingData)
    {
        if ($trainingData->isEmpty()) {
            // Fallback ke threshold default jika tidak ada data training
            return [
                'red_high' => 160,
                'red_medium' => 120,
                'red_moderate' => 100,
                'red_dominance' => 150,
                'blue_high' => 90,
                'blue_medium' => 85,
                'red_green_ratio_high' => 1.8,
                'red_green_ratio_medium' => 1.2
            ];
        }

        // Kelompokkan data berdasarkan kelas kematangan
        $matangData = $trainingData->where('maturity_class', 'matang');
        $setengahMatangData = $trainingData->where('maturity_class', 'setengah_matang');
        $mentahData = $trainingData->where('maturity_class', 'mentah');
        $busukData = $trainingData->where('maturity_class', 'busuk');

        // Hitung statistik untuk setiap kelas
        $redHighThreshold = $matangData->isNotEmpty() ? $matangData->min('red_value') : 160;
        $redMediumThreshold = $setengahMatangData->isNotEmpty() ? $setengahMatangData->min('red_value') : 120;
        $redModerateThreshold = $setengahMatangData->isNotEmpty() ? $setengahMatangData->avg('red_value') : 100;
        $redDominanceThreshold = $matangData->isNotEmpty() ? $matangData->avg('red_value') : 150;

        $blueHighThreshold = $busukData->isNotEmpty() ? $busukData->min('blue_value') : 90;
        $blueMediumThreshold = $busukData->isNotEmpty() ? $busukData->avg('blue_value') : 85;

        // Hitung rasio red/green untuk threshold dinamis
        $redGreenRatioHigh = 1.8;
        $redGreenRatioMedium = 1.2;

        if ($matangData->isNotEmpty()) {
            $avgRatioMatang = $matangData->map(function($item) {
                return $item->green_value > 0 ? $item->red_value / $item->green_value : 0;
            })->avg();
            $redGreenRatioHigh = max(1.5, $avgRatioMatang * 0.9);
        }

        if ($setengahMatangData->isNotEmpty()) {
            $avgRatioSetengahMatang = $setengahMatangData->map(function($item) {
                return $item->green_value > 0 ? $item->red_value / $item->green_value : 0;
            })->avg();
            $redGreenRatioMedium = max(1.0, $avgRatioSetengahMatang * 0.9);
        }

        return [
            'red_high' => $redHighThreshold,
            'red_medium' => $redMediumThreshold,
            'red_moderate' => $redModerateThreshold,
            'red_dominance' => $redDominanceThreshold,
            'blue_high' => $blueHighThreshold,
            'blue_medium' => $blueMediumThreshold,
            'red_green_ratio_high' => $redGreenRatioHigh,
            'red_green_ratio_medium' => $redGreenRatioMedium
        ];
    }

    /**
     * =====================================================================================
     * ALGORITMA RANDOM FOREST UNTUK KLASIFIKASI KEMATANGAN TOMAT
     * =====================================================================================
     *
     * Random Forest adalah algoritma ensemble learning yang menggabungkan multiple
     * decision trees untuk menghasilkan prediksi yang lebih akurat dan robust.
     *
     * KONSEP RANDOM FOREST:
     * 1. ENSEMBLE OF TREES: Menggabungkan banyak decision trees
     * 2. BOOTSTRAP AGGREGATING (BAGGING): Setiap tree dilatih dengan subset data berbeda
     * 3. RANDOM FEATURE SELECTION: Setiap split menggunakan subset fitur acak
     * 4. MAJORITY VOTING: Prediksi final berdasarkan voting dari semua trees
     *
     * KEUNGGULAN RANDOM FOREST:
     * - Mengurangi overfitting dibanding single decision tree
     * - Robust terhadap noise dan outliers
     * - Memberikan feature importance
     * - Tidak perlu feature scaling
     * - Handle missing values dengan baik
     *
     * IMPLEMENTASI DALAM SISTEM:
     * Karena keterbatasan data training, implementasi ini mensimulasikan
     * Random Forest dengan 3 decision trees yang memiliki threshold berbeda.
     * Setiap tree merepresentasikan "sudut pandang" yang berbeda dalam
     * mengklasifikasikan kematangan tomat.
     *
     * VARIASI THRESHOLD:
     * - Tree 1 (Standard): Threshold balanced untuk kondisi normal
     * - Tree 2 (Conservative): Threshold ketat untuk akurasi tinggi
     * - Tree 3 (Liberal): Threshold longgar untuk sensitivitas tinggi
     *
     * PARAMETER FOREST:
     * - Jumlah Trees: 3 (optimal untuk sistem embedded)
     * - Voting Method: Simple Majority
     * - Confidence Cap: 80% (lebih konservatif dari single tree)
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return array Hasil prediksi Random Forest dengan detail setiap tree
     */
    /**
     * =====================================================================================
     * RANDOM FOREST PREDICTION USING MODEL EVALUATION SERVICE
     * =====================================================================================
     *
     * Method ini menggunakan ModelEvaluationService untuk prediksi Random Forest yang sudah diperbaiki.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return array|string Hasil prediksi Random Forest
     */
    private function randomForestPrediction($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'random_forest');
            
            // Return format yang konsisten dengan implementasi legacy
            return [
                'prediction' => $prediction ?? 'mentah',
                'confidence' => 0.8, // Default confidence untuk kompatibilitas
                'algorithm_type' => 'Ensemble Learning - Multiple Decision Trees',
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            Log::error('Random Forest prediction failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            
            // Fallback ke implementasi legacy jika service gagal
            return $this->randomForestPredictionLegacy($red, $green, $blue);
        }
    }

    /**
     * =====================================================================================
     * RANDOM FOREST PREDICTION LEGACY IMPLEMENTATION
     * =====================================================================================
     *
     * Method ini adalah implementasi legacy Random Forest untuk fallback.
     */
    private function randomForestPredictionLegacy($red, $green, $blue)
    {
        // TAHAP 1: Inisialisasi forest dengan multiple decision trees
        // Setiap tree memiliki parameter threshold yang berbeda untuk diversitas
        $trees = [];

        // TREE 1: Standard Decision Tree dengan threshold balanced
        // Dioptimalkan untuk kondisi pencahayaan normal dan tomat standar
        $trees[] = $this->simulateDecisionTree($red, $green, $blue, [
            'red_threshold' => 150,    // Threshold merah standar
            'green_threshold' => 100,  // Threshold hijau standar
            'ratio_threshold' => 1.5,  // Rasio R/G standar
            'tree_id' => 'standard'
        ]);

        // TREE 2: Conservative Decision Tree dengan threshold ketat
        // Lebih selektif, mengurangi false positive untuk kelas "matang"
        $trees[] = $this->simulateDecisionTree($red, $green, $blue, [
            'red_threshold' => 160,    // Threshold merah lebih tinggi
            'green_threshold' => 90,   // Threshold hijau lebih rendah
            'ratio_threshold' => 1.7,  // Rasio R/G lebih ketat
            'tree_id' => 'conservative'
        ]);

        // TREE 3: Liberal Decision Tree dengan threshold longgar
        // Lebih sensitif, mengurangi false negative untuk deteksi dini
        $trees[] = $this->simulateDecisionTree($red, $green, $blue, [
            'red_threshold' => 140,    // Threshold merah lebih rendah
            'green_threshold' => 110,  // Threshold hijau lebih tinggi
            'ratio_threshold' => 1.3,  // Rasio R/G lebih longgar
            'tree_id' => 'liberal'
        ]);

        // TAHAP 2: Kumpulkan prediksi dari semua trees
        $predictions = array_column($trees, 'prediction');

        // TAHAP 3: Implementasi Majority Voting (Bootstrap Aggregating)
        $votes = array_count_values($predictions);
        arsort($votes); // Urutkan berdasarkan jumlah suara (descending)

        $finalPrediction = array_key_first($votes);
        $maxVotes = $votes[$finalPrediction];

        // TAHAP 4: Hitung confidence berdasarkan konsensus forest
        // Random Forest umumnya lebih konservatif dalam confidence scoring
        // Maksimal 80% untuk menghindari overconfidence
        $confidence = ($maxVotes / count($trees)) * 0.8;

        // TAHAP 5: Analisis diversitas dan agreement antar trees
        $uniquePredictions = count(array_unique($predictions));
        $agreementLevel = $this->calculateTreeAgreement($votes, count($trees));

        // TAHAP 6: Return hasil lengkap untuk analisis dan debugging
        return [
            'prediction' => $finalPrediction,
            'confidence' => $confidence,
            'trees' => $trees, // Detail prediksi setiap tree
            'votes' => $votes, // Hasil voting
            'forest_stats' => [
                'total_trees' => count($trees),
                'unique_predictions' => $uniquePredictions,
                'agreement_level' => $agreementLevel,
                'diversity_score' => ($uniquePredictions / count($trees)) * 100
            ],
            'status' => 'legacy_fallback'
        ];
    }

    /**
     * =====================================================================================
     * SIMULASI INDIVIDUAL DECISION TREE DALAM RANDOM FOREST
     * =====================================================================================
     *
     * Method ini mensimulasikan satu decision tree dengan parameter threshold
     * yang dapat dikustomisasi. Setiap tree dalam forest memiliki "kepribadian"
     * yang berbeda melalui variasi threshold.
     *
     * KONSEP TREE DIVERSITY:
     * Dalam Random Forest yang sesungguhnya, diversitas dicapai melalui:
     * 1. Bootstrap sampling (subset data berbeda)
     * 2. Random feature selection
     * 3. Random threshold selection
     *
     * IMPLEMENTASI SIMPLIFIED:
     * Karena keterbatasan, diversitas dicapai melalui variasi threshold manual
     * yang merepresentasikan "bias" berbeda dalam pengambilan keputusan.
     *
     * STRUKTUR DECISION TREE:
     *
     * Root Node: Deteksi Pembusukan
     * ├── IF (blue > 80 AND green > 80 AND red < 100) → BUSUK
     * └── ELSE:
     *     ├── IF (ratio > threshold AND red > red_threshold) → MATANG
     *     └── ELSE:
     *         ├── IF (ratio > 1.1 AND red > 100) → SETENGAH_MATANG
     *         └── ELSE → MENTAH
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @param array $thresholds Parameter threshold untuk tree ini
     * @return array Hasil prediksi tree individual dengan metadata
     */
    private function simulateDecisionTree($red, $green, $blue, $thresholds)
    {
        // FITUR UTAMA: Hitung rasio merah/hijau sebagai indikator kematangan
        $redToGreenRatio = $green > 0 ? $red / $green : 0;

        // NODE 1: Deteksi pembusukan (prioritas tertinggi)
        // Pola warna abnormal: biru-hijau tinggi + merah rendah
        if ($blue > 80 && $green > 80 && $red < 100) {
            $prediction = 'busuk';
            $reasoning = 'Detected spoilage pattern: high blue-green, low red';
        }
        // NODE 2: Deteksi kematangan dengan threshold yang dapat disesuaikan
        // Menggunakan parameter threshold dari forest untuk diversitas
        elseif ($redToGreenRatio > $thresholds['ratio_threshold'] && $red > $thresholds['red_threshold']) {
            $prediction = 'matang';
            $reasoning = 'High red dominance with ratio > ' . $thresholds['ratio_threshold'];
        }
        // NODE 3: Deteksi setengah matang (zona transisi)
        // Threshold tetap untuk konsistensi antar trees
        elseif ($redToGreenRatio > 1.1 && $red > 100) {
            $prediction = 'setengah_matang';
            $reasoning = 'Transition phase: moderate red-green ratio';
        }
        // NODE 4: Default ke mentah
        // Dominasi hijau atau rasio rendah
        else {
            $prediction = 'mentah';
            $reasoning = 'Green dominance or low red-green ratio';
        }

        // Return hasil dengan metadata lengkap untuk analisis
        return [
            'prediction' => $prediction,
            'thresholds_used' => $thresholds,
            'calculated_ratio' => round($redToGreenRatio, 3),
            'reasoning' => $reasoning,
            'tree_type' => $thresholds['tree_id'] ?? 'unknown',
            'input_rgb' => [$red, $green, $blue]
        ];
    }

    /**
     * =====================================================================================
     * ANALISIS TINGKAT KESEPAKATAN ANTAR TREES DALAM RANDOM FOREST
     * =====================================================================================
     *
     * Method ini menganalisis seberapa besar tingkat kesepakatan (agreement)
     * antar decision trees dalam Random Forest. Tingkat agreement yang tinggi
     * menunjukkan prediksi yang lebih reliable.
     *
     * INTERPRETASI AGREEMENT LEVEL:
     * - Unanimous (100%): Semua trees setuju - prediksi sangat reliable
     * - Strong Majority (67-99%): Mayoritas kuat - prediksi reliable
     * - Simple Majority (34-66%): Mayoritas sederhana - prediksi cukup reliable
     * - No Consensus (0-33%): Tidak ada kesepakatan - prediksi tidak reliable
     *
     * @param array $votes Hasil voting dari semua trees
     * @param int $totalTrees Total jumlah trees dalam forest
     * @return string Deskripsi tingkat kesepakatan
     */
    private function calculateTreeAgreement($votes, $totalTrees)
    {
        $maxVotes = max($votes);
        $agreementPercentage = ($maxVotes / $totalTrees) * 100;

        if ($agreementPercentage == 100) {
            return 'Unanimous Agreement (100%)';
        } elseif ($agreementPercentage >= 67) {
            return 'Strong Majority (' . round($agreementPercentage) . '%)';
        } elseif ($agreementPercentage >= 34) {
            return 'Simple Majority (' . round($agreementPercentage) . '%)';
        } else {
            return 'No Clear Consensus (' . round($agreementPercentage) . '%)';
        }
    }

    /**
     * =====================================================================================
     * ALGORITMA ENSEMBLE LEARNING - INTI SISTEM ARTIFICIAL INTELLIGENCE
     * =====================================================================================
     *
     * Method ini adalah jantung dari sistem AI yang menggabungkan tiga algoritma
     * machine learning untuk menghasilkan prediksi yang lebih akurat dan robust.
     *
     * KONSEP ENSEMBLE LEARNING:
     * Ensemble learning adalah teknik yang menggabungkan beberapa model pembelajaran
     * untuk menghasilkan prediksi yang lebih baik daripada model individual.
     * Prinsip: "Wisdom of Crowds" - keputusan kolektif lebih akurat.
     *
     * ALGORITMA YANG DIGUNAKAN:
     *
     * 1. DECISION TREE (Pohon Keputusan)
     *    - Algoritma rule-based dengan struktur if-then
     *    - Mudah diinterpretasi dan dijelaskan
     *    - Baik untuk data dengan pola yang jelas
     *
     * 2. K-NEAREST NEIGHBORS (KNN)
     *    - Algoritma lazy learning berbasis similarity
     *    - Menggunakan data historis untuk prediksi
     *    - Efektif untuk data dengan distribusi non-linear
     *
     * 3. RANDOM FOREST (Hutan Acak)
     *    - Ensemble dari multiple decision trees
     *    - Mengurangi overfitting dan bias
     *    - Robust terhadap noise dan outliers
     *
     * METODE VOTING:
     * - MAJORITY VOTING: Prediksi dengan suara terbanyak menang
     * - CONFIDENCE SCORING: Berdasarkan tingkat konsensus
     * - WEIGHTED ENSEMBLE: Setiap algoritma memiliki bobot yang sama
     *
     * KEUNGGULAN ENSEMBLE:
     * - Akurasi lebih tinggi dari model individual
     * - Mengurangi variance dan bias
     * - Lebih robust terhadap data noise
     * - Memberikan confidence score yang reliable
     *
     * @param string $decisionTreeResult Hasil prediksi dari Decision Tree
     * @param string $knnResult Hasil prediksi dari KNN
     * @param string $randomForestResult Hasil prediksi dari Random Forest
     * @return array Hasil ensemble dengan prediksi final dan confidence
     */
    private function calculateEnsembleVoting($decisionTreeResult, $knnResult, $randomForestResult)
    {
        // TAHAP 1: Ekstrak kelas dari hasil decision tree
        // Decision tree mengembalikan string deskriptif, perlu ekstraksi kelas
        preg_match('/Klasifikasi sebagai tomat (\w+)/', $decisionTreeResult, $matches);
        $decisionTreeClass = $matches[1] ?? 'unknown';

        // TAHAP 2: Implementasi Majority Voting
        // Setiap algoritma mendapat satu suara dengan bobot yang sama
        $votes = [
            $decisionTreeClass => 1,
            $knnResult => 1,
            $randomForestResult => 1
        ];

        // TAHAP 3: Hitung voting dan tentukan pemenang
        arsort($votes); // Urutkan berdasarkan jumlah suara (descending)
        $finalPrediction = key($votes);
        $maxVotes = max($votes);

        // TAHAP 4: Hitung confidence berdasarkan tingkat konsensus
        // Semakin banyak algoritma yang setuju, semakin tinggi confidence
        // Formula: (jumlah suara terbanyak / total algoritma) * 100%
        $confidenceScore = ($maxVotes / 3) * 100;

        // TAHAP 5: Return hasil lengkap untuk analisis dan debugging
        return [
            'final_prediction' => $finalPrediction,
            'confidence_score' => $confidenceScore . '%',
            'individual_predictions' => [
                'decision_tree' => $decisionTreeClass,
                'knn' => $knnResult,
                'random_forest' => $randomForestResult
            ],
            'voting_details' => $votes, // Untuk transparansi proses voting
            'consensus_level' => $this->getConsensusLevel($confidenceScore)
        ];
    }

    /**
     * Menentukan tingkat konsensus berdasarkan confidence score
     */
    private function getConsensusLevel($confidenceScore)
    {
        if ($confidenceScore >= 100) {
            return 'Unanimous (Semua algoritma setuju)';
        } elseif ($confidenceScore >= 66.67) {
            return 'Strong Majority (2 dari 3 algoritma setuju)';
        } else {
            return 'No Consensus (Tidak ada kesepakatan mayoritas)';
        }
    }

    /**
     * =====================================================================================
     * GENERATE KNN ANALYSIS USING MODEL EVALUATION SERVICE
     * =====================================================================================
     *
     * Method ini menggunakan ModelEvaluationService untuk analisis KNN yang sudah diperbaiki.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return array Hasil analisis KNN dengan detail lengkap
     */
    private function generateKNNAnalysisFromService($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'knn');
            
            $accuracy = $this->modelEvaluationService->getCurrentAccuracy('knn');
            
            return [
                'name' => 'K-Nearest Neighbor Analysis',
                'prediction' => $prediction ?? 'mentah',
                'accuracy' => $accuracy . '%',
                'algorithm_type' => 'Supervised Learning - Instance Based',
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            Log::error('KNN analysis failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            
            return [
                'name' => 'K-Nearest Neighbor Analysis',
                'prediction' => 'mentah',
                'accuracy' => '0%',
                'algorithm_type' => 'Supervised Learning - Instance Based',
                'status' => 'error',
                'error' => 'KNN prediction service unavailable'
            ];
        }
    }

    /**
     * =====================================================================================
     * IMPLEMENTASI ALGORITMA K-NEAREST NEIGHBORS (KNN) - LEGACY
     * =====================================================================================
     *
     * Method ini adalah implementasi legacy KNN yang masih digunakan untuk fallback.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return string Hasil prediksi kelas kematangan
     */
    private function knnPrediction($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'knn');
            
            return $prediction ?? 'mentah';
        } catch (\Exception $e) {
            Log::error('KNN prediction failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            return 'mentah';
        }
    }

    /**
     * =====================================================================================
     * GENERATE RANDOM FOREST ANALYSIS USING MODEL EVALUATION SERVICE
     * =====================================================================================
     *
     * Method ini menggunakan ModelEvaluationService untuk analisis Random Forest yang sudah diperbaiki.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return array Hasil analisis Random Forest dengan detail lengkap
     */
    private function generateRandomForestAnalysisFromService($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'random_forest');
            
            $accuracy = $this->modelEvaluationService->getCurrentAccuracy('random_forest');
            
            return [
                'name' => 'Random Forest Analysis',
                'prediction' => $prediction ?? 'mentah',
                'accuracy' => $accuracy . '%',
                'algorithm_type' => 'Ensemble Learning - Multiple Decision Trees',
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            Log::error('Random Forest analysis failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            
            return [
                'name' => 'Random Forest Analysis',
                'prediction' => 'mentah',
                'accuracy' => '0%',
                'algorithm_type' => 'Ensemble Learning - Multiple Decision Trees',
                'status' => 'error',
                'error' => 'Random Forest prediction service unavailable'
            ];
        }
    }

    /**
     * =====================================================================================
     * CALCULATE ENSEMBLE VOTING USING MODEL EVALUATION SERVICE
     * =====================================================================================
     *
     * Method ini menggunakan ModelEvaluationService untuk analisis Ensemble yang sudah diperbaiki.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return array Hasil analisis Ensemble dengan detail lengkap
     */
    private function calculateEnsembleVotingFromService($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'ensemble');
            
            $accuracy = $this->modelEvaluationService->getCurrentAccuracy('ensemble');
            
            // Dapatkan prediksi individual untuk transparansi
            $dtPrediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'decision_tree');
            
            $knnPrediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'knn');
            
            $rfPrediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'random_forest');
            
            return [
                'name' => 'Ensemble Method Analysis',
                'final_prediction' => $prediction ?? 'mentah',
                'accuracy' => $accuracy . '%',
                'individual_predictions' => [
                    'decision_tree' => $dtPrediction ?? 'mentah',
                    'knn' => $knnPrediction ?? 'mentah',
                    'random_forest' => $rfPrediction ?? 'mentah'
                ],
                'algorithm_type' => 'Meta-Learning - Ensemble of Multiple Algorithms',
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            Log::error('Ensemble analysis failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            
            return [
                'name' => 'Ensemble Method Analysis',
                'final_prediction' => 'mentah',
                'accuracy' => '0%',
                'individual_predictions' => [
                    'decision_tree' => 'mentah',
                    'knn' => 'mentah',
                    'random_forest' => 'mentah'
                ],
                'algorithm_type' => 'Meta-Learning - Ensemble of Multiple Algorithms',
                'status' => 'error',
                'error' => 'Ensemble prediction service unavailable'
            ];
        }
    }

    /**
     * =====================================================================================
     * IMPLEMENTASI ALGORITMA ENSEMBLE LEARNING - LEGACY
     * =====================================================================================
     *
     * Method ini adalah implementasi legacy Ensemble yang masih digunakan untuk fallback.
     *
     * @param int $red Nilai merah input (0-255)
     * @param int $green Nilai hijau input (0-255)
     * @param int $blue Nilai biru input (0-255)
     * @return string Hasil prediksi ensemble
     */
    private function ensemblePrediction($red, $green, $blue)
    {
        try {
            $prediction = $this->modelEvaluationService->makeSinglePrediction([
                'red_value' => $red,
                'green_value' => $green,
                'blue_value' => $blue
            ], 'ensemble');
            
            return $prediction ?? 'mentah';
        } catch (\Exception $e) {
            Log::error('Ensemble prediction failed', [
                'error' => $e->getMessage(),
                'input' => ['red' => $red, 'green' => $green, 'blue' => $blue]
            ]);
            return 'mentah';
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT EVALUASI AKURASI MODEL MANUAL
     * =====================================================================================
     *
     * Method ini memungkinkan evaluasi manual akurasi semua algoritma AI.
     * Berguna untuk trigger evaluasi dari dashboard atau API call.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluateModelAccuracy()
    {
        try {
            $results = $this->modelEvaluationService->evaluateAllAlgorithms();

            return response()->json([
                'success' => true,
                'message' => 'Model accuracy evaluation completed successfully',
                'data' => [
                    'evaluation_results' => $results,
                    'timestamp' => now()->toISOString(),
                    'training_data_count' => TrainingData::count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Manual model accuracy evaluation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to evaluate model accuracy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT MENDAPATKAN AKURASI TERKINI SEMUA ALGORITMA
     * =====================================================================================
     *
     * Method ini mengembalikan akurasi terkini untuk semua algoritma AI
     * tanpa melakukan evaluasi ulang.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentAccuracy()
    {
        try {
            $accuracies = $this->modelEvaluationService->getCurrentAccuracies();

            return response()->json([
                'success' => true,
                'data' => [
                    'accuracies' => $accuracies,
                    'last_updated' => $this->modelEvaluationService->getLastEvaluationTime(),
                    'training_data_count' => TrainingData::count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get current accuracy', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get current accuracy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT RIWAYAT AKURASI MODEL
     * =====================================================================================
     *
     * Method ini mengembalikan riwayat akurasi untuk algoritma tertentu
     * atau semua algoritma dalam periode waktu tertentu.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccuracyHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'algorithm' => 'nullable|string|in:decision_tree,knn,random_forest,ensemble',
            'days' => 'nullable|integer|min:1|max:365',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $algorithm = $request->get('algorithm');
            $days = $request->get('days', 30);
            $limit = $request->get('limit', 50);

            $history = $this->modelEvaluationService->getAccuracyHistory(
                $algorithm,
                $days,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'history' => $history,
                    'algorithm' => $algorithm,
                    'period_days' => $days,
                    'total_records' => count($history)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get accuracy history', [
                'error' => $e->getMessage(),
                'algorithm' => $request->get('algorithm')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get accuracy history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MENYIMPAN DATA KLASIFIKASI
     * =====================================================================================
     *
     * Method ini menerima data klasifikasi dari ESP32 atau sumber lain untuk
     * disimpan ke database dan digunakan dalam peningkatan akurasi AI.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeClassification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string',
            'red_value' => 'required|integer|min:0|max:255',
            'green_value' => 'required|integer|min:0|max:255',
            'blue_value' => 'required|integer|min:0|max:255',
            'clear_value' => 'nullable|integer|min:0',
            'actual_status' => 'required|string|in:mentah,setengah_matang,matang,busuk,sangat_matang',
            'predicted_status' => 'nullable|string|in:mentah,setengah_matang,matang,busuk,sangat_matang',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Jika predicted_status tidak diberikan, gunakan AI untuk prediksi
            $predictedStatus = $request->predicted_status;
            if (!$predictedStatus) {
                $predictedStatus = $this->determineTomatoMaturity(
                    $request->red_value,
                    $request->green_value,
                    $request->blue_value
                );
            }

            $classification = Classification::create([
                'device_id' => $request->device_id,
                'red_value' => $request->red_value,
                'green_value' => $request->green_value,
                'blue_value' => $request->blue_value,
                'clear_value' => $request->clear_value,
                'actual_status' => $request->actual_status,
                'predicted_status' => $predictedStatus,
                'notes' => $request->notes,
                'is_verified' => false // Default belum terverifikasi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Classification data stored successfully',
                'data' => $classification
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to store classification data', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store classification data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MENGAMBIL DATA KLASIFIKASI
     * =====================================================================================
     *
     * Method ini mengembalikan daftar data klasifikasi dengan filter opsional.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassifications(Request $request)
    {
        try {
            $query = Classification::query();

            // Filter berdasarkan status verifikasi
            if ($request->has('is_verified')) {
                $query->where('is_verified', $request->boolean('is_verified'));
            }

            // Filter berdasarkan actual_status
            if ($request->has('actual_status')) {
                $query->where('actual_status', $request->actual_status);
            }

            // Filter berdasarkan predicted_status
            if ($request->has('predicted_status')) {
                $query->where('predicted_status', $request->predicted_status);
            }

            // Filter berdasarkan classification_result
            if ($request->has('classification_result')) {
                $query->where('classification_result', $request->boolean('classification_result'));
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $classifications = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $classifications
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get classifications', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get classifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MEMVERIFIKASI DATA KLASIFIKASI
     * =====================================================================================
     *
     * Method ini memverifikasi data klasifikasi yang sudah ada.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyClassification(Request $request, $id)
    {
        try {
            $classification = Classification::findOrFail($id);

            $classification->update([
                'is_verified' => true,
                'verified_at' => now()
            ]);

            // Trigger evaluasi ulang akurasi model setelah verifikasi
            $this->modelEvaluationService->evaluateAllAlgorithms();

            return response()->json([
                'success' => true,
                'message' => 'Classification verified successfully',
                'data' => $classification->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify classification', [
                'error' => $e->getMessage(),
                'classification_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify classification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================================================
     * ENDPOINT UNTUK MENDAPATKAN STATISTIK AKURASI KLASIFIKASI
     * =====================================================================================
     *
     * Method ini mengembalikan statistik akurasi berdasarkan data klasifikasi.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassificationAccuracyStats()
    {
        try {
            $totalVerified = Classification::where('is_verified', true)->count();
            $correctPredictions = Classification::where('is_verified', true)
                ->where('classification_result', true)
                ->count();
            $incorrectPredictions = Classification::where('is_verified', true)
                ->where('classification_result', false)
                ->count();
            $unverified = Classification::where('is_verified', false)->count();

            $accuracyPercentage = $totalVerified > 0 ? ($correctPredictions / $totalVerified) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_verified' => $totalVerified,
                    'correct_predictions' => $correctPredictions,
                    'incorrect_predictions' => $incorrectPredictions,
                    'unverified' => $unverified,
                    'accuracy_percentage' => round($accuracyPercentage, 2)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get classification accuracy stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get classification accuracy stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
