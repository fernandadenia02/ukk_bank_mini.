<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $query = $this->filteredQuery($keyword, $dateFrom, $dateTo);

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

    public function pdf(Request $request)
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'custom'])],
            'date' => ['required_if:period,daily,weekly,monthly,yearly', 'nullable', 'date_format:Y-m-d'],
            'date_from' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'required_if:period,custom',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ]);

        $period = $filters['period'];
        if ($period === 'custom') {
            $start = CarbonImmutable::parse($filters['date_from'])->startOfDay();
            $end = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        } else {
            $date = CarbonImmutable::parse($filters['date'])->startOfDay();
            [$start, $end] = match ($period) {
                'daily' => [$date, $date->endOfDay()],
                'weekly' => [
                    $date->startOfWeek(Carbon::MONDAY),
                    $date->endOfWeek(Carbon::SUNDAY),
                ],
                'monthly' => [$date->startOfMonth(), $date->endOfMonth()],
                'yearly' => [$date->startOfYear(), $date->endOfYear()],
            };
        }

        $keyword = $filters['keyword'] ?? '';
        $query = $this->filteredQuery(
            $keyword,
            $start->toDateString(),
            $end->toDateString()
        );
        $journals = (clone $query)
            ->with('transaction.customer')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $data = [
            'journals' => $journals,
            'keyword' => $keyword,
            'periodLabel' => match ($period) {
                'daily' => 'Harian',
                'weekly' => 'Mingguan',
                'monthly' => 'Bulanan',
                'yearly' => 'Tahunan',
                'custom' => 'Rentang Tanggal',
            },
            'startDate' => $start,
            'endDate' => $end,
            'totalDebit' => (clone $query)->sum('debit'),
            'totalCredit' => (clone $query)->sum('credit'),
        ];

        return Pdf::loadView('accounting_journal.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download(
                'jurnal-akuntansi-' .
                $start->format('Y-m-d') . '-' .
                $end->format('Y-m-d') .
                '.pdf'
            );
    }

    private function filteredQuery(string $keyword, ?string $dateFrom, ?string $dateTo): Builder
    {
        return AccountingJournal::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('account', 'like', "%{$keyword}%");
            })
            ->when($dateFrom !== null, function ($query) use ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($dateTo !== null, function ($query) use ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            });
    }
}