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
});

// Additional routes for RGB analysis
Route::post('api/analyze-rgb', [TomatReadingController::class, 'analyze']);
