<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// require __DIR__ . '/api.php';

// Dashboard Route
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// API Routes untuk Dashboard
Route::prefix('api/dashboard')->group(function () {
    Route::get('/latest', [DashboardController::class, 'getLatestData']);
    Route::get('/readings', [DashboardController::class, 'getRecentReadings']);
    Route::get('/stats', [DashboardController::class, 'getSystemStats']);
    Route::post('/save-training', [DashboardController::class, 'saveAsTrainingData']);
    Route::post('/delete-reading', [DashboardController::class, 'deleteReading']);
});

Route::get('/test/sensor', function () {
    return view('test.sensor');
});
