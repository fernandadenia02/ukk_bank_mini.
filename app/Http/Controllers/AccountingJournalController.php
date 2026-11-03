<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use Illuminate\Http\Request;

class AccountingJournalController extends Controller
{
    public function index(Request $request)
    {
        $dateToRules = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => $dateToRules,
        ]);

        $keyword = $filters['keyword'] ?? '';
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';

        $query = AccountingJournal::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('account', 'like', "%{$keyword}%");
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            });

        $journals = (clone $query)
            ->with('transaction.customer')
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $totalDebit = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');

        return view('accounting_journal.index', compact(
            'journals',
            'keyword',
            'dateFrom',
            'dateTo',
            'totalDebit',
            'totalCredit'
        ));
    }
}