<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication routes
Auth::routes([
    'login' => true,
    'register' => true,
    'logout' => true,
    'reset' => false,
    'confirm' => false,
    'verify' => false,
]);

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Account funding (rate limited)
    Route::get('fund-account', [HomeController::class, 'fundAccount'])
        ->middleware('throttle:6,1') // 6 requests per minute
        ->name('fund_account');

    // AJAX endpoints (rate limited)
    Route::post('transaction/source-converter', [TransactionController::class, 'sourceConverter'])
        ->middleware('throttle:60,1') // 60 requests per minute
        ->name('transaction.source_converter');
    Route::post('transaction/currency-balance', [TransactionController::class, 'currencyBalance'])
        ->middleware('throttle:30,1') // 30 requests per minute
        ->name('transaction.currency_balance');

    // Transaction resource routes
    Route::resource('transaction', TransactionController::class)
        ->middleware('throttle:30,1'); // 30 requests per minute
});

// Health check endpoint
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});
