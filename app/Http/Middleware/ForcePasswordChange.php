<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && session('must_change_password') === true) {
            $allowedRoutes = ['profile.show', 'profile.update', 'profile.password', 'logout'];
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if (!in_array($currentRoute, $allowedRoutes) && !$request->is('profile*') && !$request->is('logout')) {
                return redirect()->route('profile.show')->with('warning', 'Demi keamanan, Anda wajib mengubah password default (NIK) sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
