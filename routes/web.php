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

    // Account funding
    Route::get('fund-account', [HomeController::class, 'fundAccount'])->name('fund_account');

    // Transaction routes
    Route::post('transaction/source-converter', [TransactionController::class, 'sourceConverter'])
        ->name('transaction.source_converter');
    Route::post('transaction/currency-balance', [TransactionController::class, 'currencyBalance'])
        ->name('transaction.currency_balance');
    Route::resource('transaction', TransactionController::class);
});

// Health check endpoint
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});
