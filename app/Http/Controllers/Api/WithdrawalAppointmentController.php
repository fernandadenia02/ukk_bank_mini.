<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WithdrawalAppointment;
use Illuminate\Http\Request;

class WithdrawalAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();
        if (! $customer instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini hanya tersedia untuk nasabah.',
            ], 403);
        }

        WithdrawalAppointment::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('consumed_at')
            ->whereDate('withdrawal_date', '<', today())
            ->update(['status' => 'expired']);

        $appointments = WithdrawalAppointment::where('customer_id', $customer->id)
            ->orderByDesc('withdrawal_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (WithdrawalAppointment $appointment) => [
                'id' => $appointment->id,
                'withdrawal_date' => $appointment->withdrawal_date->toDateString(),
                'amount' => (float) $appointment->amount,
                'status' => $appointment->status,
                'rejection_reason' => $appointment->rejection_reason,
                'created_at' => $appointment->created_at,
                'reviewed_at' => $appointment->reviewed_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }

    public function store(Request $request)
    {
        $customer = $request->user();
        if (! $customer instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini hanya tersedia untuk nasabah.',
            ], 403);
        }

        $validated = $request->validate([
            'withdrawal_date' => ['required', 'date_format:Y-m-d', 'after:today'],
            'amount' => ['required', 'numeric', 'gt:1000000', 'max:9999999999.99'],
        ], [
            'withdrawal_date.after' => 'Tanggal pengambilan harus minimal H-1 (paling cepat besok).',
            'amount.gt' => 'Nominal pengajuan harus lebih dari Rp 1.000.000.',
        ]);

        $appointment = WithdrawalAppointment::create([
            'customer_id' => $customer->id,
            'withdrawal_date' => $validated['withdrawal_date'],
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan janji pengambilan berhasil dikirim dan menunggu persetujuan teller.',
            'data' => [
                'id' => $appointment->id,
                'withdrawal_date' => $appointment->withdrawal_date->toDateString(),
                'amount' => (float) $appointment->amount,
                'status' => $appointment->status,
                'created_at' => $appointment->created_at,
            ],
        ], 201);
    }
}
