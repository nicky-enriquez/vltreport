<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessExcelController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('v1')->group(function () {
    Route::get('/processexcel', [ProcessExcelController::class, 'process'])->name('process.excel');    
});

