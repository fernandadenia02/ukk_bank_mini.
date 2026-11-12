<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\DailyReport;
use App\Models\CashAdvanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * ==========================================
     * DASHBOARD ADMIN
     * ==========================================
     */
    public function admin(Request $request)
    {
        // ==========================
        // STATISTIK USER
        // ==========================

        $totalUsers = User::count();

        $totalCustomers = Customer::count();

        $totalTellers = User::where('role', 'teller')->count();

        $totalSupervisors = User::where('role', 'supervisor')->count();

        $balanceClasses = Customer::query()
            ->whereNotNull('class')
            ->where('class', '<>', '')
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        $selectedBalanceClass = $request->query('class');

        if ($selectedBalanceClass !== null) {
            $request->validate([
                'class' => ['required', 'string', 'max:20', Rule::in($balanceClasses->all())],
            ]);
        }

        $customerBalanceQuery = Customer::query();

        if ($selectedBalanceClass !== null) {
            $customerBalanceQuery->where('class', $selectedBalanceClass);
        }

        $totalCustomerBalance = $customerBalanceQuery->sum('balance');


        // ==========================
        // STATISTIK TRANSAKSI
        // ==========================

        $totalTransactions = Transaction::count();

        $todayTransactions = Transaction::whereDate(
            'date',
            today()
        )->count();


        // ==========================
        // SETORAN HARI INI
        // ==========================

        $totalDeposit = Transaction::where(
            'transaction_type',
            'setoran'
        )
        ->whereDate('date', today())
        ->sum('amount');


        // ==========================
        // PENARIKAN HARI INI
        // ==========================

        $totalWithdrawal = Transaction::where(
            'transaction_type',
            'penarikan'
        )
        ->whereDate('date', today())
        ->sum('amount');


        // ==========================
        // DAILY REPORT
        // ==========================

        $pendingReports = DailyReport::where(
            'status',
            'pending'
        )->count();

        $approvedReports = DailyReport::where(
            'status',
            'approved'
        )->count();

        $rejectedReports = DailyReport::where(
            'status',
            'rejected'
        )->count();

        $pendingCashAdvanceRequests = CashAdvanceRequest::with(['teller', 'report'])
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get();


        // ==========================
        // TRANSAKSI TERBARU
        // ==========================

        $latestTransactions = Transaction::with([
            'customer',
            'teller'
        ])
        ->orderBy('date', 'desc')
        ->take(5)
        ->get();


        // ==========================
        // CUSTOMER TERBARU
        // ==========================

        $latestCustomers = Customer::orderBy(
            'id',
            'desc'
        )
        ->take(5)
        ->get();


        return view(
            'admin.dashboard',
            compact(
                'totalUsers',
                'totalCustomers',
                'balanceClasses',
                'selectedBalanceClass',
                'totalCustomerBalance',
                'totalTellers',
                'totalSupervisors',
                'totalTransactions',
                'todayTransactions',
                'totalDeposit',
                'totalWithdrawal',
                'pendingReports',
                'approvedReports',
                'rejectedReports',
                'pendingCashAdvanceRequests',
                'latestTransactions',
                'latestCustomers'
            )
        );
    }

/**
 * ==========================================
 * DASHBOARD TELLER
 * ==========================================
 */
public function teller(Request $request)
{
    $bankIsOpen = (bool) DB::table('bank_statuses')
        ->where('id', 1)
        ->value('is_open');

    // ==========================
    // TRANSAKSI HARI INI
    // ==========================

    $todayTransactions = Transaction::where(
        'teller_id',
        Auth::id()
    )
    ->whereDate('date', today())
    ->count();


    // ==========================
    // TOTAL SETORAN HARI INI
    // ==========================

    $totalDeposit = Transaction::where(
        'teller_id',
        Auth::id()
    )
    ->where(
        'transaction_type',
        'setoran'
    )
    ->whereDate('date', today())
    ->sum('amount');


    // ==========================
    // TOTAL PENARIKAN HARI INI
    // ==========================

    $totalWithdrawal = Transaction::where(
        'teller_id',
        Auth::id()
    )
    ->where(
        'transaction_type',
        'penarikan'
    )
    ->whereDate('date', today())
    ->sum('amount');


    // ==========================
    // DAILY REPORT
    // ==========================

    $dailyReport = DailyReport::firstOrCreate(
        [
            'teller_id' => Auth::id(),
            'date' => today()->toDateString(),
        ],
        [
            'beginning_balance' => 0,
            'total_deposit' => 0,
            'total_withdrawals' => 0,
            'ending_balance' => 0,
            'physical_money' => 0,
            'difference' => 0,
            'status' => 'pending',
        ]
    );

    $rejectedReports = DailyReport::where('teller_id', Auth::id())
        ->where('status', 'rejected')
        ->orderByDesc('date')
        ->get();

    $cashAdvanceRequests = $dailyReport->cashAdvanceRequests()
        ->with('reviewer')
        ->orderBy('requested_at')
        ->get();

    // ==========================
    // SEARCH NASABAH
    // ==========================

    $search = $request->input('search');

    $customers = collect();
    $inactiveCustomers = collect();

    if (!empty($search)) {

        $customers = Customer::where(function ($query) use ($search) {

            $query->where(
                'name',
                'like',
                '%' . $search . '%'
            )
            ->orWhere(
                'account_number',
                'like',
                '%' . $search . '%'
            );

        })
        ->orderBy('name')
        ->get();
    }

    $inactiveCustomers = Customer::where('status', 'nonaktif')
        ->orderBy('name')
        ->paginate(10, ['*'], 'inactive_page')
        ->withQueryString();


    // ==========================
    // DASHBOARD TELLER
    // ==========================

    return view(
        'teller.dashboard',
        compact(
            'todayTransactions',
            'totalDeposit',
            'totalWithdrawal',
            'dailyReport',
            'cashAdvanceRequests',
            'rejectedReports',
            'customers',
            'inactiveCustomers',
            'search',
            'bankIsOpen'
        )
    );
}

public function updateBankStatus(Request $request)
{
    $validated = $request->validate([
        'is_open' => ['required', 'boolean'],
    ]);

    DB::table('bank_statuses')
        ->where('id', 1)
        ->update(['is_open' => $validated['is_open']]);

    return back()->with(
        'success',
        $validated['is_open']
            ? 'Status bank berhasil diubah menjadi buka.'
            : 'Bank sedang istirahat dan ditutup sementara.'
    );
}

    /**
     * ==========================================
     * DASHBOARD SUPERVISOR
     * ==========================================
     */
    public function supervisor()
    {
        // ==========================
        // TOTAL REPORT
        // ==========================

        $totalReports = DailyReport::count();


        // ==========================
        // REPORT HARI INI
        // ==========================

        $todayReports = DailyReport::whereDate(
            'date',
            today()
        )->count();


        // ==========================
        // REPORT PENDING
        // ==========================

        $pendingReports = DailyReport::where(
            'status',
            'pending'
        )->count();


        // ==========================
        // REPORT APPROVED
        // ==========================

        $approvedReports = DailyReport::where(
            'status',
            'approved'
        )->count();


        // ==========================
        // REPORT REJECTED
        // ==========================

        $rejectedReports = DailyReport::where(
            'status',
            'rejected'
        )->count();


        return view(
            'supervisor.dashboard',
            compact(
                'totalReports',
                'todayReports',
                'pendingReports',
                'approvedReports',
                'rejectedReports'
            )
        );
    }


    /**
     * ==========================================
     * DASHBOARD CUSTOMER
     * ==========================================
     */
    public function customer()
    {
        $bankIsOpen = (bool) DB::table('bank_statuses')
            ->where('id', 1)
            ->value('is_open');

        // ==========================
        // DATA CUSTOMER LOGIN
        // ==========================

        $customer = Auth::guard('customer')->user();


        if (!$customer) {

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Customer data not found.'
                );

        }


        // ==========================
        // SEMUA TRANSAKSI CUSTOMER
        // ==========================

        $transactions = Transaction::where(
            'customer_id',
            $customer->id
        )
        ->orderBy(
            'date',
            'desc'
        )
        ->get();


        // ==========================
        // TOTAL TRANSAKSI
        // ==========================

        $totalTransactions =
            $transactions->count();


        // ==========================
        // TOTAL SETORAN
        // ==========================

        $totalDeposit =
            $transactions
                ->where(
                    'transaction_type',
                    'setoran'
                )
                ->sum('amount');


        // ==========================
        // TOTAL PENARIKAN
        // ==========================

        $totalWithdrawal =
            $transactions
                ->where(
                    'transaction_type',
                    'penarikan'
                )
                ->sum('amount');


        // ==========================
        // 5 TRANSAKSI TERBARU
        // ==========================

        $latestTransactions =
            $transactions->take(5);


        // ==========================
        // TAMPILKAN DASHBOARD
        // ==========================

        return view(
            'customer.dashboard',
            compact(
                'customer',
                'transactions',
                'latestTransactions',
                'totalTransactions',
                'totalDeposit',
                'totalWithdrawal',
                'bankIsOpen'
            )
        );
    }
}