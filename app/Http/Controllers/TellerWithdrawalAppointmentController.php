<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TellerWithdrawalAppointmentController extends Controller
{
    public function index()
    {
        WithdrawalAppointment::where('status', 'pending')
            ->whereDate('withdrawal_date', '<', today())
            ->update(['status' => 'expired']);

        $withdrawalAppointments = WithdrawalAppointment::with([
            'customer:id,name,account_number',
            'reviewer:id,officer_name',
        ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('withdrawal_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('teller.withdrawal-appointments.index', compact('withdrawalAppointments'));
    }

    public function approve(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $appointment = WithdrawalAppointment::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->status !== 'pending') {
                return 'Pengajuan janji ini sudah diproses.';
            }

            if ($appointment->withdrawal_date->isPast() || $appointment->withdrawal_date->isToday()) {
                $appointment->update(['status' => 'expired']);

                return 'Tanggal pengambilan sudah lewat. Pengajuan ditandai kedaluwarsa.';
            }

            $appointment->update([
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

        return back()->with('success', 'Janji pengambilan disetujui.');
    }

    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Catatan penolakan wajib diisi.',
            'rejection_reason.min' => 'Catatan penolakan minimal 5 karakter.',
            'rejection_reason.max' => 'Catatan penolakan maksimal 2000 karakter.',
        ]);
        $rejectionReason = trim($validated['rejection_reason']);

        if (mb_strlen($rejectionReason) < 5) {
            return back()
                ->withErrors(['rejection_reason' => 'Catatan penolakan minimal 5 karakter.'])
                ->withInput();
        }

        $result = DB::transaction(function () use ($id, $rejectionReason) {
            $appointment = WithdrawalAppointment::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->status !== 'pending') {
                return 'Pengajuan janji ini sudah diproses.';
            }

            $appointment->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Janji pengambilan ditolak. Catatan penolakan telah disimpan.');
    }
}
