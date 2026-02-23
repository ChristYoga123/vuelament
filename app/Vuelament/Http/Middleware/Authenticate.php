<?php

namespace App\Vuelament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $panel = app('vuelament.panel');
        $guard = $panel->getAuthGuard();

        if (!Auth::guard($guard)->check()) {
            return redirect()->guest($panel->getLoginUrl());
        }

        $user = Auth::guard($guard)->user();

        // Cek canAccessPanel jika method ada di model User
        if (method_exists($user, 'canAccessPanel') && !$user->canAccessPanel($panel)) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();

            return redirect($panel->getLoginUrl())
                ->withErrors(['email' => 'Anda tidak memiliki akses ke panel ini.']);
        }

        return $next($request);
    }
}
