<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class MonthlyReportController extends Controller
{
    private function getReportData(Request $request): array
    {
        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year'  => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $month = (int) ($filters['month'] ?? now()->month);
        $year  = (int) ($filters['year'] ?? now()->year);

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = Transaction::query()
            ->whereBetween('date', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);

        $summary = (clone $query)
            ->selectRaw("
                COUNT(*) as total_transactions,
                COALESCE(SUM(
                    CASE WHEN transaction_type = 'setoran'
                    THEN amount ELSE 0 END
                ), 0) as total_deposit,
                COALESCE(SUM(
                    CASE WHEN transaction_type = 'penarikan'
                    THEN amount ELSE 0 END
                ), 0) as total_withdrawals
            ")
            ->first();

        $transactions = (clone $query)
            ->with(['customer', 'teller'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $reportStatuses = DailyReport::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return compact(
            'month',
            'year',
            'startDate',
            'endDate',
            'summary',
            'transactions',
            'reportStatuses'
        );
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);

        return view('monthly_reports.index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->getReportData($request);

        $pdf = Pdf::loadView('monthly_reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download(
            'laporan-bulanan-' .
            $data['year'] . '-' .
            str_pad($data['month'], 2, '0', STR_PAD_LEFT) .
            '.pdf'
        );
    }
}