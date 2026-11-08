<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalAppointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TellerWithdrawalAppointmentController extends Controller
{
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

    public function reject(int $id)
    {
        $result = DB::transaction(function () use ($id) {
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
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Janji pengambilan ditolak.');
    }
}
