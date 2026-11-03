<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!session()->has('role')) {
            return redirect()->route('login');
        }

        if (!in_array(session('role'), $roles)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}