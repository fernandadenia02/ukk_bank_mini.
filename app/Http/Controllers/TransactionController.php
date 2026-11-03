<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DailyReport;
use App\Models\AccountingJournal;

class TransactionController extends Controller
{
    /**
     * Menampilkan daftar transaksi
     */
    public function index()
    {
        $transactions = Transaction::with(['customer', 'teller'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Menampilkan form transaksi
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('transactions.create', compact('customers'));
    }

    /**
     * Menyimpan transaksi
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'transaction_type' => 'required|in:setoran,penarikan',
            'amount'           => 'required|numeric|min:1000',
        ]);

        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()
                ->route('login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $customer = Customer::findOrFail($request->customer_id);

        $beginningBalance = $customer->balance;
// Penarikan
if ($request->transaction_type == 'penarikan') {

    // Saldo minimal yang harus tersisa
    $minimumBalance = 10000;

    // Hitung saldo setelah penarikan
    $endingBalance = $customer->balance - $request->amount;

    // Tidak boleh kurang dari saldo minimum
    if ($endingBalance < $minimumBalance) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Transaksi ditolak. Saldo minimal yang harus tersisa adalah Rp 10.000.'
            );

    }

} else {

    // Setoran
    $endingBalance = $customer->balance + $request->amount;

}
        // Update saldo customer
        $customer->update([
            'balance' => $endingBalance
        ]);

        // Simpan transaksi
       $transaction = Transaction::create([

    'customer_id'       => $customer->id,

    'teller_id'         => Auth::id(),

    'transaction_type'  => $request->transaction_type,

    'amount'            => $request->amount,

    'beginning_balance' => $beginningBalance,

    'ending_balance'    => $endingBalance,

    'date'              => now(),

]);

        // =========================================
// DAILY REPORT OTOMATIS
// =========================================

$report = DailyReport::firstOrCreate(

    [
        'teller_id' => Auth::id(),
        'date'      => now()->toDateString(),
    ],

    [
        'beginning_balance' => 0,
        'total_deposit'     => 0,
        'total_withdrawals' => 0,
        'ending_balance'    => 0,
        'physical_money'    => 0,
        'difference'        => 0,
        'status'            => 'pending',
    ]

);

if ($request->transaction_type == 'setoran') {

    $report->increment('total_deposit', $request->amount);

} else {

    $report->increment('total_withdrawals', $request->amount);

}

$report->ending_balance =
    $report->total_deposit - $report->total_withdrawals;

$report->physical_money = $report->ending_balance;

$report->difference =
    $report->physical_money - $report->ending_balance;

$report->save();

// ======================================
// ACCOUNTING JOURNAL OTOMATIS
// ======================================

if ($request->transaction_type == 'setoran') {

    AccountingJournal::create([
        'transaction_id' => $transaction->id,
        'account'        => 'Kas',
        'debit'          => $request->amount,
        'credit'         => 0,
        'date'           => now(),
    ]);

    AccountingJournal::create([
        'transaction_id' => $transaction->id,
        'account'        => 'Tabungan Nasabah',
        'debit'          => 0,
        'credit'         => $request->amount,
        'date'           => now(),
    ]);

} else {

    AccountingJournal::create([
        'transaction_id' => $transaction->id,
        'account'        => 'Tabungan Nasabah',
        'debit'          => $request->amount,
        'credit'         => 0,
        'date'           => now(),
    ]);

    AccountingJournal::create([
        'transaction_id' => $transaction->id,
        'account'        => 'Kas',
        'debit'          => 0,
        'credit'         => $request->amount,
        'date'           => now(),
    ]);

}

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction successful.');
    }
}