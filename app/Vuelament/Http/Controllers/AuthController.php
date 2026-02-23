<?php

namespace App\Vuelament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController
{
    protected function panel()
    {
        return app('vuelament.panel');
    }

    // ── Login ────────────────────────────────────────────

    public function showLogin()
    {
        $panel = $this->panel();

        if (Auth::guard($panel->getAuthGuard())->check()) {
            return redirect('/' . $panel->getPath());
        }

        return Inertia::render('Vuelament/Auth/Login', [
            'canRegister' => $panel->hasRegister(),
            'panel'       => $panel->toArray(),
        ]);
    }

    public function login(Request $request)
    {
        $panel = $this->panel();

        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');
        $guard    = $panel->getAuthGuard();

        if (Auth::guard($guard)->attempt($credentials, $remember)) {
            $user = Auth::guard($guard)->user();

            // Cek akses panel
            if (method_exists($user, 'canAccessPanel') && !$user->canAccessPanel($panel)) {
                Auth::guard($guard)->logout();
                return back()->withErrors([
                    'email' => 'Anda tidak memiliki akses ke panel ini.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended('/' . $panel->getPath());
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // ── Register ─────────────────────────────────────────

    public function showRegister()
    {
        $panel = $this->panel();

        if (Auth::guard($panel->getAuthGuard())->check()) {
            return redirect('/' . $panel->getPath());
        }

        return Inertia::render('Vuelament/Auth/Register', [
            'panel' => $panel->toArray(),
        ]);
    }

    public function register(Request $request)
    {
        $panel = $this->panel();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userModel = $panel->getUserModel()
            ?: config('auth.providers.users.model', \App\Models\User::class);

        $user = $userModel::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        Auth::guard($panel->getAuthGuard())->login($user);

        return redirect('/' . $panel->getPath());
    }

    // ── Logout ───────────────────────────────────────────

    public function logout(Request $request)
    {
        $panel = $this->panel();

        Auth::guard($panel->getAuthGuard())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/' . $panel->getPath() . '/login');
    }
}
