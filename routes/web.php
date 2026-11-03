<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\AccountingJournalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

// ==========================
// AUTH
// ==========================

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ==========================
// AFTER LOGIN
// ==========================

Route::middleware('auth')->group(function () {

    // ==========================
    // DASHBOARD
    // ==========================

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/teller/dashboard', [DashboardController::class, 'teller'])
        ->middleware('role:teller')
        ->name('teller.dashboard');

    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisor'])
        ->middleware('role:supervisor')
        ->name('supervisor.dashboard');

    Route::get('/customer/dashboard', [DashboardController::class, 'customer'])
        ->middleware('role:customer')
        ->name('customer.dashboard');

    // ==========================
    // ADMIN
    // ==========================

    Route::middleware('role:admin')->group(function () {

        Route::resource('users', UserController::class);

        Route::resource('customers', CustomerController::class);

        Route::get('/accounting-journal', [AccountingJournalController::class, 'index'])
            ->name('accounting-journal.index');

        Route::post('/daily-reports/{id}/approve', [DailyReportController::class, 'approve'])
            ->name('daily-reports.approve');

        Route::post('/daily-reports/{id}/reject', [DailyReportController::class, 'reject'])
            ->name('daily-reports.reject');
    });

    // ==========================
    // TELLER
    // ==========================

    Route::middleware('role:teller')->group(function () {

        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'create', 'store']);
    });

    // ==========================
    // ADMIN & SUPERVISOR
    // (Hanya melihat Daily Report)
    // ==========================

    Route::middleware('role:admin,supervisor')->group(function () {

        Route::get('/daily-reports', [DailyReportController::class, 'index'])
            ->name('daily-reports.index');
    });

});