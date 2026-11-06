<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalPinApproval;
use App\Services\CredentialLockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WithdrawalPinApprovalController extends Controller
{
    public function pending(Request $request)
    {
        $customer = $request->user();
        $now = now();

        WithdrawalPinApproval::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->where('expires_at', '<=', $now)
            ->update(['status' => 'expired']);

        $approvals = WithdrawalPinApproval::with('teller:id,officer_name')
            ->where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', $now)
            ->orderBy('created_at')
            ->get()
            ->map(fn (WithdrawalPinApproval $approval) => [
                'id' => $approval->id,
                'amount' => (float) $approval->amount,
                'teller_name' => $approval->teller?->officer_name,
                'created_at' => $approval->created_at,
                'expires_at' => $approval->expires_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    public function verify(Request $request, CredentialLockout $lockout, int $id)
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $customer = $request->user();
        $identifier = (string) $customer->id;
        $remaining = $lockout->remaining('customer-pin', $identifier);

        if ($remaining > 0) {
            return response()->json([
                'success' => false,
                'message' => $lockout->message('PIN', $remaining),
                'locked_for_seconds' => $remaining,
            ], 429);
        }

        $result = DB::transaction(function () use ($customer, $id, $validated, $identifier, $lockout) {
            $approval = WithdrawalPinApproval::whereKey($id)
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if (! $approval || $approval->status !== 'pending') {
                return ['status' => 404, 'message' => 'Permintaan PIN tidak ditemukan atau sudah diproses.'];
            }

            if ($approval->expires_at->isPast()) {
                $approval->update(['status' => 'expired']);

                return ['status' => 410, 'message' => 'Permintaan PIN sudah kedaluwarsa. Minta teller mengirim ulang.'];
            }

            $lockedCustomer = $customer->newQuery()
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedCustomer->status !== 'aktif' ||
                ! $lockedCustomer->pin ||
                ! Hash::check($validated['pin'], $lockedCustomer->pin)
            ) {
                $remaining = $lockout->failedAttempt('customer-pin', $identifier);

                return [
                    'status' => $remaining > 0 ? 429 : 422,
                    'message' => $remaining > 0
                        ? $lockout->message('PIN', $remaining)
                        : 'PIN salah. Periksa PIN dan coba lagi.',
                    'locked_for_seconds' => $remaining ?: null,
                ];
            }

            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            $lockout->clear('customer-pin', $identifier);

            return ['status' => 200, 'message' => 'PIN berhasil diverifikasi. Teller dapat melanjutkan transaksi.'];
        });

        return response()->json([
            'success' => $result['status'] === 200,
            'message' => $result['message'],
            ...array_filter([
                'locked_for_seconds' => $result['locked_for_seconds'] ?? null,
            ], fn ($value) => $value !== null),
        ], $result['status']);
    }

    public function decline(Request $request, int $id)
    {
        $result = DB::transaction(function () use ($request, $id) {
            $approval = WithdrawalPinApproval::whereKey($id)
                ->where('customer_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $approval || $approval->status !== 'pending') {
                return ['status' => 404, 'message' => 'Permintaan PIN tidak ditemukan atau sudah diproses.'];
            }

            if ($approval->expires_at->isPast()) {
                $approval->update(['status' => 'expired']);

                return ['status' => 410, 'message' => 'Permintaan PIN sudah kedaluwarsa.'];
            }

            $approval->update(['status' => 'rejected']);

            return ['status' => 200, 'message' => 'Permintaan penarikan berhasil ditolak.'];
        });

        return response()->json([
            'success' => $result['status'] === 200,
            'message' => $result['message'],
        ], $result['status']);
    }
}
