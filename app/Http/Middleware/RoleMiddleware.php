<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Memeriksa autentikasi dan hak akses berdasarkan role.
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        // Pastikan pengguna sudah login.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->status !== 'aktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Akun Anda nonaktif. Hubungi admin untuk bantuan.');
        }

        // Ambil role dari pengguna yang sedang login.
        $role = Auth::user() instanceof Customer
            ? 'customer'
            : Auth::user()->role;

        // Pastikan role pengguna sesuai dengan route.
        if (!in_array($role, $roles, true)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
