<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\AccountingJournalController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\CashAdvanceRequestController;
use App\Http\Controllers\TellerWithdrawalAppointmentController;

// API DOCUMENTATION
Route::get('/api-docs', function () {
    return view('api-docs');
})->name('api.docs');

// HALAMAN AWAL
Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        $route = match (Auth::guard('web')->user()->role) {
            'admin' => 'admin.dashboard',
            'teller' => 'teller.dashboard',
            'supervisor' => 'supervisor.dashboard',
            default => null,
        };

        if ($route) {
            return redirect()->route($route);
        }

        Auth::guard('web')->logout();
    }

    if (Auth::guard('customer')->check()) {
        return redirect()->route('customer.dashboard');
    }

    return redirect()->route('login');
});

// LOGIN DAN RESET PASSWORD
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'authenticate'])
        ->name('login.authenticate');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])
        ->name('password.request');

    Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])
        ->middleware('throttle:5,5')
        ->name('password.otp.send');

    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset.form');

    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,5')
        ->name('password.update');
});

// LOGOUT
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth:web,customer')
    ->name('logout');

// ROUTE SETELAH LOGIN
Route::middleware('auth')->group(function () {

    // DASHBOARD
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/teller/dashboard', [DashboardController::class, 'teller'])
        ->middleware('role:teller')
        ->name('teller.dashboard');

    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisor'])
        ->middleware('role:supervisor')
        ->name('supervisor.dashboard');

    // KHUSUS ADMIN
    Route::middleware('role:admin')->group(function () {

        Route::post('/cash-advance-requests/{id}/approve', [CashAdvanceRequestController::class, 'approve'])
            ->name('cash-advance-requests.approve');
        Route::post('/cash-advance-requests/{id}/reject', [CashAdvanceRequestController::class, 'reject'])
            ->name('cash-advance-requests.reject');

        // Kelola pengguna dan nasabah
        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])
            ->name('users.status');
        Route::resource('customers', CustomerController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/customers/{id}/status', [CustomerController::class, 'updateStatus'])
            ->name('customers.status');
        Route::get('/members', [MemberController::class, 'index'])
            ->name('members.index');
        Route::get('/members/create', [MemberController::class, 'create'])
            ->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])
            ->name('members.store');
        Route::get('/members/import/template', [MemberController::class, 'downloadImportTemplate'])
            ->name('members.import.template');
        Route::post('/members/import', [MemberController::class, 'import'])
            ->name('members.import');
        Route::get('/members/promotion', [MemberController::class, 'promotion'])
            ->name('members.promotion');
        Route::post('/members/promotion', [MemberController::class, 'promote'])
            ->name('members.promote');

        // Jurnal akuntansi
        Route::get('/accounting-journal', [AccountingJournalController::class, 'index'])
            ->name('accounting-journal.index');
        Route::get('/accounting-journal/pdf', [AccountingJournalController::class, 'pdf'])
            ->name('accounting-journal.pdf');

        // Setujui dan tolak Daily Report
        Route::post('/daily-reports/{id}/approve', [DailyReportController::class, 'approve'])
            ->name('daily-reports.approve');

        Route::post('/daily-reports/{id}/reject', [DailyReportController::class, 'reject'])
            ->name('daily-reports.reject');

        // LAPORAN BULANAN
        Route::get('/monthly-reports', [MonthlyReportController::class, 'index'])
            ->name('monthly-reports.index');

        // CETAK / UNDUH PDF LAPORAN BULANAN
        Route::get('/monthly-reports/pdf', [MonthlyReportController::class, 'pdf'])
            ->name('monthly-reports.pdf');
    });

    // DAILY REPORT ADMIN DAN SUPERVISOR
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])
            ->name('daily-reports.index');

        Route::get('/daily-reports/{id}', [DailyReportController::class, 'show'])
            ->name('daily-reports.show');
    });

    // KHUSUS TELLER
    Route::middleware('role:teller')->group(function () {
        Route::post('/teller/withdrawal-appointments/{id}/approve', [TellerWithdrawalAppointmentController::class, 'approve'])
            ->whereNumber('id')
            ->name('teller.withdrawal-appointments.approve');
        Route::post('/teller/withdrawal-appointments/{id}/reject', [TellerWithdrawalAppointmentController::class, 'reject'])
            ->whereNumber('id')
            ->name('teller.withdrawal-appointments.reject');

        Route::post('/transactions/withdrawal-pin-requests', [TransactionController::class, 'createWithdrawalPinApproval'])
            ->name('transactions.pin-approval.create');
        Route::get('/transactions/withdrawal-pin-requests/{approvalId}', [TransactionController::class, 'withdrawalPinApprovalStatus'])
            ->whereNumber('approvalId')
            ->name('transactions.pin-approval.status');

        Route::post('/teller/bank-status', [DashboardController::class, 'updateBankStatus'])
            ->name('teller.bank-status.update');

        Route::post('/my-daily-reports/opening-cash', [CashAdvanceRequestController::class, 'store'])
            ->name('teller.daily-reports.opening-cash');
        Route::post('/my-daily-reports/close', [DailyReportController::class, 'closeCash'])
            ->name('teller.daily-reports.close');
        Route::get('/teller/members', [MemberController::class, 'tellerIndex'])
            ->name('teller.members.index');
        Route::get('/teller/members/{id}/open-account', [MemberController::class, 'openingForm'])
            ->name('teller.members.open.form');
        Route::post('/teller/members/{id}/open-account', [MemberController::class, 'openAccount'])
            ->name('teller.members.open');

        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'create', 'store']);
        Route::post('/transactions/pin-reset/send-otp', [TransactionController::class, 'sendCustomerPinResetOtp'])
            ->middleware('throttle:3,1')
            ->name('transactions.pin-reset.send');
        Route::post('/transactions/pin-reset', [TransactionController::class, 'resetCustomerPin'])
            ->middleware('throttle:10,1')
            ->name('transactions.pin-reset.update');

        Route::get('/transactions/{id}/receipt', [TransactionController::class, 'receipt'])
            ->name('transactions.receipt');

        Route::get('/my-daily-reports/{id}', [DailyReportController::class, 'show'])
            ->name('teller.daily-reports.show');
    });
});

Route::middleware(['auth:customer', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [DashboardController::class, 'customer'])
        ->name('customer.dashboard');
});