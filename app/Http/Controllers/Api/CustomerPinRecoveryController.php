<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPinResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CustomerPinRecoveryController extends Controller
{
    public function sendOtp(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();
        if (! $customer->email) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum terdaftar pada akun nasabah.',
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);

        CustomerPinResetOtp::where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        CustomerPinResetOtp::create([
            'customer_id' => $customer->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        try {
            Mail::raw(
                "Halo {$customer->name},\n\n"
                . "Kode OTP untuk membuat PIN baru Bank Mini kamu adalah: {$otp}\n\n"
                . "Kode berlaku selama 5 menit dan hanya dapat digunakan sekali.\n"
                . "Jangan bagikan kode ini kepada siapa pun.\n\n"
                . "Jika kamu tidak meminta reset PIN, abaikan email ini.",
                function ($mail) use ($customer) {
                    $mail->to($customer->email)
                        ->subject('Kode OTP Reset PIN Bank Mini');
                }
            );
        } catch (Throwable) {
            CustomerPinResetOtp::where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            Log::error('Pengiriman email OTP reset PIN nasabah gagal.', [
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email OTP gagal dikirim. Silakan coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika akun memiliki email terdaftar, kode OTP akan dikirim. Periksa Inbox dan Spam.',
        ]);
    }

    public function resetPin(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:6'],
            'pin_confirmation' => ['required', 'digits:6', 'same:pin'],
        ]);

        /** @var Customer $customer */
        $customer = $request->user();

        $result = DB::transaction(function () use ($customer, $validated) {
            $otp = CustomerPinResetOtp::where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return [
                    'status' => 404,
                    'message' => 'Permintaan reset PIN tidak ditemukan. Minta OTP baru.',
                ];
            }

            if ($otp->expires_at->isPast()) {
                $otp->update(['used_at' => now()]);

                return [
                    'status' => 410,
                    'message' => 'OTP sudah kedaluwarsa. Minta OTP baru.',
                ];
            }

            if ($otp->attempts >= 5) {
                return [
                    'status' => 429,
                    'message' => 'Terlalu banyak percobaan OTP. Minta OTP baru.',
                    'locked_for_seconds' => now()->diffInSeconds($otp->expires_at),
                ];
            }

            if (! Hash::check($validated['otp'], $otp->otp_hash)) {
                $otp->increment('attempts');

                if ($otp->attempts >= 5) {
                    return [
                        'status' => 429,
                        'message' => 'Terlalu banyak percobaan OTP. Minta OTP baru.',
                        'locked_for_seconds' => now()->diffInSeconds($otp->expires_at),
                    ];
                }

                return [
                    'status' => 422,
                    'message' => 'Kode OTP salah. Periksa email dan coba lagi.',
                ];
            }

            $customer->newQuery()
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->update(['pin' => Hash::make($validated['pin'])]);

            $otp->update(['used_at' => now()]);
            CustomerPinResetOtp::where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->where('id', '!=', $otp->id)
                ->update(['used_at' => now()]);

            return [
                'status' => 200,
                'message' => 'PIN berhasil diperbarui.',
            ];
        });

        return response()->json([
            'success' => $result['status'] === 200,
            'message' => $result['message'],
            ...array_filter([
                'locked_for_seconds' => $result['locked_for_seconds'] ?? null,
            ], fn ($value) => $value !== null),
        ], $result['status']);
    }
}
