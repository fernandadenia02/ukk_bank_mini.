<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Menampilkan transaksi customer yang sedang login
     */
    public function index()
    {
        $customer = Auth::user();

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        $transactions = Transaction::where(
            'customer_id',
            $customer->id
        )
        ->with('teller')
        ->orderBy('date', 'desc')
        ->get();

        return response()->json([
            'message' => 'Data transaksi berhasil diambil.',
            'data' => $transactions
        ]);
    }
}