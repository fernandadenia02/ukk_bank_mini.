<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\CashAdvanceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyReportController extends Controller
{
    public function closeCash(Request $request)
    {
        $physicalMoneyInput = $request->input('physical_money');
        if (is_string($physicalMoneyInput)) {
            $request->merge([
                'physical_money' => preg_replace('/[.,\s]/', '', $physicalMoneyInput),
            ]);
        }

        $validated = $request->validate([
            'physical_money' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            \App\Models\User::whereKey(Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            $report = DailyReport::where('teller_id', Auth::id())
                ->whereDate('date', today())
                ->lockForUpdate()
                ->first();

            if (!$report) {
                $report = DailyReport::create([
                    'teller_id' => Auth::id(),
                    'date' => today()->toDateString(),
                    'beginning_balance' => 0,
                    'total_deposit' => 0,
                    'total_withdrawals' => 0,
                    'ending_balance' => 0,
                    'physical_money' => 0,
                    'difference' => 0,
                    'status' => 'pending',
                ]);
            }

            if ($report->closed_at) {
                return 'Kas harian sudah ditutup.';
            }

            if (!in_array($report->status, ['pending', 'rejected'], true)) {
                return 'Laporan kas ini sudah diproses dan tidak dapat ditutup kembali.';
            }

            $physicalMoney = (float) $validated['physical_money'];
            $report->update([
                'physical_money' => $physicalMoney,
                'difference' => $physicalMoney - (float) $report->ending_balance,
                'closed_at' => now(),
                'status' => 'pending',
                'supervisor_id' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ]);

            return null;
        });

        if ($result !== null) {
            return redirect()
                ->route('teller.dashboard')
                ->with('error', $result);
        }

        return redirect()
            ->route('teller.dashboard')
            ->with('success', 'Kas harian berhasil ditutup. Transaksi hari ini dikunci dan laporan dapat diperiksa admin.');
    }

    /**
     * Menampilkan Daily Report.
     * Admin dan Supervisor dapat melihat.
     */
    public function index()
    {
        $reports = DailyReport::with('teller')
            ->orderBy('date', 'desc')
            ->get();

        return view('daily_reports.index', compact('reports'));
    }

    /**
     * Menampilkan detail Daily Report.
     * Admin dan Supervisor dapat melihat semua laporan.
     * Teller hanya dapat melihat laporan miliknya sendiri.
     */
    public function show($id)
    {
        $report = DailyReport::with('teller')
            ->findOrFail($id);

        $user = Auth::user();

        // Teller hanya boleh melihat laporan miliknya sendiri.
        if (
            $user->role === 'teller' &&
            (int) $report->teller_id !== (int) $user->id
        ) {
            abort(403, 'Kamu hanya dapat melihat laporan milikmu sendiri.');
        }

        // Hanya Admin, Supervisor, dan Teller yang dapat melihat detail.
        if (!in_array($user->role, ['admin', 'supervisor', 'teller'])) {
            abort(403, 'Kamu tidak memiliki akses untuk melihat laporan ini.');
        }

        $transactions = Transaction::with('customer')
            ->where('teller_id', $report->teller_id)
            ->whereDate('date', $report->date)
            ->orderBy('date', 'asc')
            ->get();

        $cashAdvanceRequests = CashAdvanceRequest::where('daily_report_id', $report->id)
            ->with('reviewer')
            ->orderBy('requested_at')
            ->get();

        return view(
            'daily_reports.show',
            compact('report', 'transactions', 'cashAdvanceRequests')
        );
    }

    /**
     * Admin menyetujui Daily Report.
     */
    public function approve($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menyetujui Daily Report.');
        }

        $result = DB::transaction(function () use ($id) {
            $report = DailyReport::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!$report->closed_at) {
                return 'Teller harus menutup kas harian sebelum laporan dapat disetujui.';
            }

            if ($report->status !== 'pending') {
                return 'Daily Report ini sudah diproses.';
            }

            $report->update([
                'status' => 'approved',
                'supervisor_id' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with(
                'error',
                $result
            );
        }

        return back()->with(
            'success',
            'Daily Report berhasil disetujui.'
        );
    }

    /**
     * Admin menolak Daily Report dengan alasan.
     */
    public function reject(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menolak Daily Report.');
        }

        $report = DailyReport::findOrFail($id);
        if (!$report->closed_at) {
            return back()->with(
                'error',
                'Teller harus menutup kas harian sebelum admin dapat mengirim catatan atau menolak laporan.'
            );
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 2000 karakter.',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $report = DailyReport::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!$report->closed_at) {
                return 'Teller harus menutup kas harian sebelum admin dapat mengirim catatan atau menolak laporan.';
            }

            if ($report->status !== 'pending') {
                return 'Daily Report ini sudah diproses.';
            }

            $report->update([
                'status' => 'rejected',
                'supervisor_id' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => trim($validated['rejection_reason']),
                'closed_at' => $report->date->isToday() ? null : $report->closed_at,
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with('error', $result);
        }

        return back()->with(
            'success',
            'Daily Report berhasil ditolak. Alasan penolakan telah disimpan.'
        );
    }
}
