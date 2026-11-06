<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Services\CredentialLockout;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman login
     */
    public function index()
    {
        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function authenticate(Request $request, CredentialLockout $lockout)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = mb_strtolower(trim((string) $request->input('username')));
        $remaining = $lockout->remaining('login', $username);

        if ($remaining > 0) {
            return back()
                ->with('error', $lockout->message('kata sandi', $remaining))
                ->withInput($request->only('username'));
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'status'   => 'aktif',
        ];

        Auth::guard('web')->logout();
        Auth::guard('customer')->logout();

        $guard = 'web';
        if (!Auth::guard('web')->attempt($credentials)) {
            $customer = Customer::where('username', $request->username)->first();

            if (
                !$customer ||
                $customer->status !== 'aktif' ||
                !$customer->password ||
                !Hash::check($request->password, $customer->password)
            ) {
                $remaining = $lockout->failedAttempt('login', $username);

                if ($remaining > 0) {
                    return back()
                        ->with('error', $lockout->message('kata sandi', $remaining))
                        ->withInput($request->only('username'));
                }

                return back()
                    ->with('error', 'Username atau password salah.')
                    ->withInput($request->only('username'));
            }

            Auth::guard('customer')->login($customer);
            $guard = 'customer';
        }

        $lockout->clear('login', $username);
        $request->session()->regenerate();

        $user = Auth::guard($guard)->user();

        // Simpan data login ke session
        session([
            'login'        => true,
            'id'           => $user->getAuthIdentifier(),
            'officer_name' => $user instanceof Customer ? $user->name : $user->officer_name,
            'role'         => $user instanceof Customer ? 'customer' : $user->role,
        ]);

        // Redirect berdasarkan role
        $role = $user instanceof Customer ? 'customer' : $user->role;
        switch ($role) {

            case 'admin':

                return redirect()
                    ->route('admin.dashboard');

            case 'teller':

                return redirect()
                    ->route('teller.dashboard');

            case 'supervisor':

                return redirect()
                    ->route('supervisor.dashboard');

            case 'nasabah':

                return redirect()
                    ->route('customer.dashboard');

            case 'customer':

                return redirect()
                    ->route('customer.dashboard');

            default:

                Auth::guard('web')->logout();
                Auth::guard('customer')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', 'Role tidak dikenali.');
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $sessionExpired = $request->boolean('idle_timeout');

        Auth::guard('web')->logout();
        Auth::guard('customer')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $response = redirect()->route('login');

        if ($sessionExpired) {
            $response->with('error', 'Sesi berakhir karena tidak ada aktivitas selama 5 menit. Silakan login kembali.');
        }

        return $response;
    }
}