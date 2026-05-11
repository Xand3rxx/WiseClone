<?php

declare(strict_types=1);

use App\Http\Controllers\AdminRateController;
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
    'verify' => true,
]);

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Admin rate management
    Route::get('admin/rates', [AdminRateController::class, 'index'])->name('admin.rates.index');
    Route::patch('admin/rates/{charge}', [AdminRateController::class, 'update'])->name('admin.rates.update');

    // Account funding (rate limited)
    Route::post('fund-account', [HomeController::class, 'fundAccount'])
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
