<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/api.php';

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test/sensor', function () {
    return view('test.sensor');
});
