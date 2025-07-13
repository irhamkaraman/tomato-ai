<?php

namespace App\Http\Controllers;

use App\Models\TomatReading;
use App\Models\TrainingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard utama
     */
    public function index()
    {
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
            'averageConfidence'
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
}