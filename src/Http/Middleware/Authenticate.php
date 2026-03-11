<?php

namespace ChristYoga123\Vuelament\Http\Middleware;

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

        // [FIX] Cek hasPanelAccess DULU, lalu canAccessPanel
        // Konsisten dengan AuthController::resolveUserAccess()
        $blocked = false;

        if (method_exists($user, 'hasPanelAccess')) {
            $blocked = !$user->hasPanelAccess($panel);
        } elseif (method_exists($user, 'canAccessPanel')) {
            $blocked = !$user->canAccessPanel($panel);
        }

        if ($blocked) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken(); // [FIX] tambah regenerateToken

            return redirect($panel->getLoginUrl())
                ->withErrors(['email' => 'You do not have access to this panel.']);
        }

        return $next($request);
    }
}
