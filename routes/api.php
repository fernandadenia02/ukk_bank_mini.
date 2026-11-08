<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerPinRecoveryController;
use App\Http\Controllers\BankStatusController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WithdrawalAppointmentController;
use App\Http\Controllers\Api\WithdrawalPinApprovalController;
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

        Route::post('/forgot-pin', [CustomerPinRecoveryController::class, 'sendOtp'])
            ->middleware('throttle:3,1');
        Route::post('/reset-pin', [CustomerPinRecoveryController::class, 'resetPin'])
            ->middleware('throttle:10,1');

        Route::get('/bank-status', [BankStatusController::class, 'show']);
        Route::get('/withdrawal-appointments', [WithdrawalAppointmentController::class, 'index']);
        Route::post('/withdrawal-appointments', [WithdrawalAppointmentController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/withdrawal-pin-requests/pending', [WithdrawalPinApprovalController::class, 'pending']);
        Route::post('/withdrawal-pin-requests/{id}/verify', [WithdrawalPinApprovalController::class, 'verify'])
            ->whereNumber('id')
            ->middleware('throttle:30,1');
        Route::post('/withdrawal-pin-requests/{id}/decline', [WithdrawalPinApprovalController::class, 'decline'])
            ->whereNumber('id');

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