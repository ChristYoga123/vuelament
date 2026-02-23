<?php

namespace App\Vuelament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $panel = app('vuelament.panel');
        $guard = $panel->getAuthGuard();

        if (Auth::guard($guard)->check()) {
            return redirect('/' . $panel->getPath());
        }

        return $next($request);
    }
}
