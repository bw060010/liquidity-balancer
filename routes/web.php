<?php

use App\Http\Controllers\CalculationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calculate')->name('home');

Route::get('/calculate', [CalculationController::class, 'showForm'])
    ->name('calculate.show');

Route::post('/calculate', [CalculationController::class, 'performCalculation'])
    ->middleware('throttle:30,1')
    ->name('calculate.store');
