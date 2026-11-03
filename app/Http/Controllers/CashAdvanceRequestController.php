<?php

namespace App\Http\Controllers;

use App\Models\CashAdvanceRequest;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashAdvanceRequestController extends Controller
{
    public function store(Request $request)
    {
        $amountInput = $request->input('amount');
        if (is_string($amountInput)) {
            $request->merge([
                'amount' => preg_replace('/[.,\s]/', '', $amountInput),
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000', 'max:9999999999.99'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();

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

            if ($report->closed_at || $report->status !== 'pending') {
                return 'Permintaan modal hanya dapat diajukan selama kas harian masih dibuka.';
            }

            if ((float) $report->ending_balance > 0) {
                return 'Permintaan modal tambahan hanya dapat diajukan saat kas tersedia sudah habis.';
            }

            $hasPendingRequest = CashAdvanceRequest::where('daily_report_id', $report->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingRequest) {
                return 'Permintaan modal masih menunggu persetujuan admin.';
            }

            CashAdvanceRequest::create([
                'daily_report_id' => $report->id,
                'teller_id' => Auth::id(),
                'amount' => $validated['amount'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            return null;
        });

        if ($result !== null) {
            return redirect()->route('teller.dashboard')->with('error', $result);
        }

        return redirect()
            ->route('teller.dashboard')
            ->with('success', 'Permintaan modal kas berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function approve($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menyetujui permintaan modal kas.');
        }

        $result = DB::transaction(function () use ($id) {
            $cashRequest = CashAdvanceRequest::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cashRequest->status !== 'pending') {
                return 'Permintaan modal ini sudah diproses.';
            }

            $report = DailyReport::whereKey($cashRequest->daily_report_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($report->closed_at || $report->status !== 'pending') {
                return 'Kas harian sudah ditutup atau laporan sudah diproses.';
            }

            $amount = (float) $cashRequest->amount;
            $report->beginning_balance = (float) $report->beginning_balance + $amount;
            $report->ending_balance = (float) $report->ending_balance + $amount;
            $report->physical_money = (float) $report->physical_money + $amount;
            $report->difference = (float) $report->physical_money - (float) $report->ending_balance;
            $report->save();

            $cashRequest->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Permintaan modal kas disetujui dan saldo kas teller telah ditambahkan.');
    }

    public function reject(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat menolak permintaan modal kas.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 2000 karakter.',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $cashRequest = CashAdvanceRequest::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cashRequest->status !== 'pending') {
                return 'Permintaan modal ini sudah diproses.';
            }

            $cashRequest->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => trim($validated['rejection_reason']),
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Permintaan modal kas ditolak. Alasan penolakan telah disimpan.');
    }
}
