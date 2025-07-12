<?php

use App\Http\Controllers\TomatReadingController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/tomat-readings')->group(function () {
    Route::get('/', [TomatReadingController::class, 'index']);
    Route::post('/', [TomatReadingController::class, 'store']);
    Route::get('/{id}', [TomatReadingController::class, 'show']);
    
    // Model Accuracy Evaluation Routes
    Route::post('/evaluate-accuracy', [TomatReadingController::class, 'evaluateModelAccuracy']);
    Route::get('/current-accuracy', [TomatReadingController::class, 'getCurrentAccuracy']);
    Route::get('/accuracy-history', [TomatReadingController::class, 'getAccuracyHistory']);

    // =====================================================================================
    // ENDPOINT UNTUK MENGELOLA DATA KLASIFIKASI
    // =====================================================================================
    
    // Endpoint untuk menyimpan data klasifikasi baru
    Route::post('/classifications', [TomatReadingController::class, 'storeClassification']);
    
    // Endpoint untuk mengambil daftar data klasifikasi
    Route::get('/classifications', [TomatReadingController::class, 'getClassifications']);
    
    // Endpoint untuk memverifikasi data klasifikasi
    Route::put('/classifications/{id}/verify', [TomatReadingController::class, 'verifyClassification']);
    
    // Endpoint untuk mendapatkan statistik akurasi klasifikasi
    Route::get('/classifications/accuracy-stats', [TomatReadingController::class, 'getClassificationAccuracyStats']);
});

// Additional routes for RGB analysis
Route::post('api/analyze-rgb', [TomatReadingController::class, 'analyze']);
