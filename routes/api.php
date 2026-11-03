<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\ForgotPasswordController;

Route::prefix('customer')->group(function () {

    // =========================
    // AUTH
    // =========================

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    Route::post('/forgot-password', [
        ForgotPasswordController::class,
        'sendOtpApi'
    ])->middleware('throttle:5,5');

    Route::post('/reset-password', [
        ForgotPasswordController::class,
        'resetPasswordApi'
    ])->middleware('throttle:5,5');

    // =========================
    // CUSTOMER AUTHENTICATED
    // =========================

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ]);

        // PROFILE
        Route::get('/profile', [
            CustomerController::class,
            'profile'
        ]);

        Route::put('/profile', [
            CustomerController::class,
            'updateProfile'
        ]);

        // BALANCE
        Route::get('/balance', [
            CustomerController::class,
            'balance'
        ]);

        // DASHBOARD
        Route::get('/dashboard', [
            CustomerController::class,
            'dashboard'
        ]);

        // BARCODE
        Route::get('/barcode', [
            CustomerController::class,
            'barcode'
        ]);

        // TRANSACTIONS
        Route::get('/transactions', [
            TransactionController::class,
            'index'
        ]);

        Route::get('/transactions/{id}', [
            TransactionController::class,
            'show'
        ]);
    });
});