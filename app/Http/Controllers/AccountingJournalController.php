<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use Illuminate\Http\Request;

class AccountingJournalController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $journals = AccountingJournal::with('transaction.customer')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('account', 'like', "%{$keyword}%");
            })
            ->orderBy('date', 'desc')
            ->paginate(20);

        $totalDebit = AccountingJournal::sum('debit');
        $totalCredit = AccountingJournal::sum('credit');

        return view('accounting_journal.index', compact(
            'journals',
            'keyword',
            'totalDebit',
            'totalCredit'
        ));
    }
}