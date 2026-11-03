<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function authenticate(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'status'   => 'aktif',
        ];

        if (!Auth::attempt($credentials)) {

            return back()
                ->with('error', 'Username atau password salah.')
                ->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Simpan data login ke session
        session([
            'login'        => true,
            'id'           => $user->id,
            'officer_name' => $user->officer_name,
            'role'         => $user->role,
        ]);

        // Redirect berdasarkan role
        switch ($user->role) {

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

                Auth::logout();

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
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}