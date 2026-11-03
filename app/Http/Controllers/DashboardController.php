<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\DailyReport;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard Admin
     */
    public function admin()
    {
        $totalUsers = User::count();

        $totalCustomers = Customer::count();

        $totalTransactions = Transaction::count();

        $todayTransactions = Transaction::whereDate('date', today())->count();

        $totalDeposit = Transaction::where('transaction_type', 'setoran')
            ->whereDate('date', today())
            ->sum('amount');

        $totalWithdrawal = Transaction::where('transaction_type', 'penarikan')
            ->whereDate('date', today())
            ->sum('amount');

        $pendingReports = DailyReport::where('status', 'pending')->count();

        $approvedReports = DailyReport::where('status', 'approved')->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalCustomers',
            'totalTransactions',
            'todayTransactions',
            'totalDeposit',
            'totalWithdrawal',
            'pendingReports',
            'approvedReports'
        ));
    }

    /**
     * Dashboard Teller
     */
    public function teller()
    {
        $todayTransactions = Transaction::where('teller_id', Auth::id())
            ->whereDate('date', today())
            ->count();

        $totalDeposit = Transaction::where('teller_id', Auth::id())
            ->where('transaction_type', 'setoran')
            ->whereDate('date', today())
            ->sum('amount');

        $totalWithdrawal = Transaction::where('teller_id', Auth::id())
            ->where('transaction_type', 'penarikan')
            ->whereDate('date', today())
            ->sum('amount');

        $dailyReport = DailyReport::where('teller_id', Auth::id())
            ->whereDate('date', today())
            ->first();

        return view('teller.dashboard', compact(
            'todayTransactions',
            'totalDeposit',
            'totalWithdrawal',
            'dailyReport'
        ));
    }

    /**
     * Dashboard Supervisor
     */
    public function supervisor()
{
    $totalReports = DailyReport::count();

    $todayReports = DailyReport::whereDate('date', today())->count();

    $pendingReports = DailyReport::where('status', 'pending')->count();

    $approvedReports = DailyReport::where('status', 'approved')->count();

    $rejectedReports = DailyReport::where('status', 'rejected')->count();

    return view('supervisor.dashboard', compact(
        'totalReports',
        'todayReports',
        'pendingReports',
        'approvedReports',
        'rejectedReports'
    ));
}

    /**
     * Dashboard Customer
     */
    public function customer()
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer) {

            return redirect()
                ->route('login')
                ->with('error', 'Customer data not found.');

        }

        $transactions = Transaction::where('customer_id', $customer->id)
            ->orderBy('date', 'desc')
            ->get();

        return view('customer.dashboard', compact(
            'customer',
            'transactions'
        ));
    }
}