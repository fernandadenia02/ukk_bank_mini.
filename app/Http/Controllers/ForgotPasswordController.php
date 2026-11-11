<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PasswordResetOtp;
use App\Models\StaffPasswordResetOtp;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $identity = trim($validated['identity']);
        $email = strtolower(trim($validated['email']));
        $message = 'Jika akun terdaftar, OTP berhasil dikirimkan. Silakan cek email dan folder spam.';

        if (!$this->issueOtpForIdentity($identity, $email)) {
            return back()
                ->withInput($request->only('identity', 'email'))
                ->withErrors([
                    'email' => 'Email gagal dikirim. Periksa konfigurasi SMTP atau coba lagi nanti.',
                ]);
        }

        return redirect()
            ->route('password.reset.form')
            ->with('status', $message)
            ->withInput([
                'identity' => $identity,
                'email' => $email,
            ]);
    }

    public function sendOtpApi(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        if (!$this->issueOtpForEmail($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email gagal dikirim. Periksa konfigurasi SMTP atau coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika akun terdaftar, OTP berhasil dikirimkan. Silakan cek email dan folder spam.',
        ]);
    }

    private function issueOtpForEmail(string $email): bool
    {
        $customer = Customer::where('email', $email)
            ->where('status', 'aktif')
            ->first();

        if ($customer) {
            return $this->sendOtpToAccount(
                $email,
                $customer->name,
                PasswordResetOtp::class,
                'customer_id',
                $customer->id,
            );
        }

        $user = User::where('email', $email)
            ->whereIn('role', ['admin', 'teller'])
            ->where('status', 'aktif')
            ->first();

        if (!$user) {
            return true;
        }

        return $this->sendOtpToAccount(
            $email,
            $user->officer_name,
            StaffPasswordResetOtp::class,
            'user_id',
            $user->id,
        );
    }

    private function issueOtpForIdentity(string $identity, string $email): bool
    {
        $account = $this->findAccountByIdentity($identity, $email);

        if (!$account) {
            return true;
        }

        return $this->sendOtpToAccount(
            $email,
            $account['name'],
            $account['otp_model'],
            $account['account_id_column'],
            $account['account_id'],
        );
    }

    private function findAccountByIdentity(string $identity, string $email): ?array
    {
        $customer = Customer::where('account_number', $identity)
            ->where('email', $email)
            ->where('status', 'aktif')
            ->first();

        if ($customer) {
            return [
                'name' => $customer->name,
                'user' => $customer,
                'otp_model' => PasswordResetOtp::class,
                'account_id_column' => 'customer_id',
                'account_id' => $customer->id,
                'allowed_roles' => ['customer'],
            ];
        }

        if (!ctype_digit($identity)) {
            return null;
        }

        $user = User::whereKey((int) $identity)
            ->where('email', $email)
            ->whereIn('role', ['admin', 'teller'])
            ->where('status', 'aktif')
            ->first();

        if (!$user) {
            return null;
        }

        return [
            'name' => $user->officer_name,
            'user' => $user,
            'otp_model' => StaffPasswordResetOtp::class,
            'account_id_column' => 'user_id',
            'account_id' => $user->id,
            'allowed_roles' => ['admin', 'teller'],
        ];
    }

    private function sendOtpToAccount(
        string $email,
        string $name,
        string $otpModel,
        string $accountIdColumn,
        int $accountId,
    ): bool {
        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($otpModel, $accountIdColumn, $accountId, $otp) {
            $otpModel::where($accountIdColumn, $accountId)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $otpModel::create([
                $accountIdColumn => $accountId,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
            ]);
        });

        try {
            Mail::raw(
                "Halo {$name},\n\n"
                . "Kode OTP untuk reset password Bank Mini kamu adalah: {$otp}\n\n"
                . "Kode berlaku selama 5 menit dan hanya dapat digunakan sekali.\n"
                . "Jangan bagikan kode ini kepada siapa pun.\n\n"
                . "Jika kamu tidak meminta reset password, abaikan email ini.",
                function ($mail) use ($email) {
                    $mail->to($email)
                        ->subject('Kode OTP Reset Password Bank Mini');
                }
            );
        } catch (Throwable $e) {
            $otpModel::where($accountIdColumn, $accountId)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            Log::error('Pengiriman email OTP gagal.', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function showResetForm()
    {
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'max:255', new StrongPassword, 'confirmed'],
        ]);

        $identity = trim($validated['identity']);
        $email = strtolower(trim($validated['email']));
        $account = $this->findAccountByIdentity($identity, $email);

        $error = $account
            ? $this->applyResolvedPasswordReset(
                $account,
                $validated['otp'],
                $validated['password'],
            )
            : 'OTP tidak valid atau sudah kedaluwarsa.';

        if ($error !== null) {
            return back()
                ->withErrors(['otp' => $error])
                ->withInput($request->only('identity', 'email'));
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diubah. Silakan login.');
    }

    private function applyResolvedPasswordReset(
        array $account,
        string $otp,
        string $password,
    ): ?string {
        $otpModel = $account['otp_model'];
        $accountIdColumn = $account['account_id_column'];
        $accountId = $account['account_id'];
        $user = $account['user'];

        return $this->applyOtpPasswordReset(
            $user,
            $otpModel,
            $accountIdColumn,
            $accountId,
            $account['allowed_roles'],
            $otp,
            $password,
        );
    }

    public function resetPasswordApi(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'max:255', new StrongPassword, 'confirmed'],
        ]);

        $error = $this->applyPasswordReset(
            strtolower(trim($validated['email'])),
            $validated['otp'],
            $validated['password'],
        );

        if ($error !== null) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login.',
        ]);
    }

    private function applyPasswordReset(
        string $email,
        string $otp,
        string $password,
    ): ?string {
        $customer = Customer::where('email', $email)
            ->where('status', 'aktif')
            ->first();

        if ($customer) {
            $user = $customer;
            $otpModel = PasswordResetOtp::class;
            $accountIdColumn = 'customer_id';
            $accountId = $customer->id;
            $allowedRoles = ['customer'];
        } else {
            $user = User::where('email', $email)
                ->whereIn('role', ['admin', 'teller'])
                ->where('status', 'aktif')
                ->first();

            if (!$user) {
                return 'OTP tidak valid atau sudah kedaluwarsa.';
            }

            $otpModel = StaffPasswordResetOtp::class;
            $accountIdColumn = 'user_id';
            $accountId = $user->id;
            $allowedRoles = ['admin', 'teller'];
        }

        return $this->applyOtpPasswordReset(
            $user,
            $otpModel,
            $accountIdColumn,
            $accountId,
            $allowedRoles,
            $otp,
            $password,
        );
    }

    private function applyOtpPasswordReset(
        User|Customer|null $user,
        string $otpModel,
        string $accountIdColumn,
        int $accountId,
        array $allowedRoles,
        string $otp,
        string $password,
    ): ?string {
        $otpRecord = $otpModel::where($accountIdColumn, $accountId)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (
            !$otpRecord ||
            $otpRecord->expires_at->isPast() ||
            $otpRecord->attempts >= 5
        ) {
            return 'OTP tidak valid atau sudah kedaluwarsa.';
        }

        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return 'Kode OTP yang kamu masukkan salah.';
        }

        $role = $user instanceof Customer ? 'customer' : $user?->role;
        if (!$user || !in_array($role, $allowedRoles, true) || $user->status !== 'aktif') {
            return 'Akun tidak dapat melakukan reset password.';
        }

        DB::transaction(function () use ($otpRecord, $user, $otpModel, $accountIdColumn, $accountId, $password) {
            $otpRecord->update(['used_at' => now()]);

            // Both account models hash assigned passwords through their hashed cast.
            $user->password = $password;
            $user->save();

            $otpModel::where($accountIdColumn, $accountId)
                ->whereNull('used_at')
                ->where('id', '!=', $otpRecord->id)
                ->update(['used_at' => now()]);
        });

        return null;
    }
}
