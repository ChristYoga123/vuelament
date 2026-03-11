<?php

namespace ChristYoga123\Vuelament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

            if (!$this->resolveUserAccess($user, $panel)) {
                Auth::guard($guard)->logout();
                return back()->withErrors([
                    'email' => 'You cannot access this panel.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended('/' . $panel->getPath());
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
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
        $guard = $panel->getAuthGuard();

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
            'password' => Hash::make($data['password']),  // ← Hash::make, bukan bcrypt()
        ]);

        // ── [FIX] Access control — sama seperti login ─────
        if (!$this->resolveUserAccess($user, $panel)) {
            // Akun terbuat tapi tidak boleh akses panel — logout & redirect ke login
            Auth::guard($guard)->logout();
            return redirect('/' . $panel->getPath() . '/login')
                ->withErrors(['email' => 'Your account was created but you do not have access to this panel. Please contact an administrator.']);
        }

        Auth::guard($guard)->login($user);
        $request->session()->regenerate();  // ← [FIX] Cegah session fixation

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

    // ── Helpers ──────────────────────────────────────────

    /**
     * Resolve apakah user boleh akses panel.
     *
     * Priority:
     * 1. hasPanelAccess()   — custom method di User model
     * 2. canAccessPanel()   — dari HasPanelAccess trait
     * 3. Fallback: izinkan hanya di env local (dengan warning log)
     *
     * [FIX] Digunakan di login() DAN register() agar konsisten.
     */
    protected function resolveUserAccess($user, $panel): bool
    {
        if (method_exists($user, 'hasPanelAccess')) {
            return $user->hasPanelAccess($panel);
        }

        if (method_exists($user, 'canAccessPanel')) {
            return $user->canAccessPanel($panel);
        }

        // Tidak ada method access control — hanya izinkan di local
        Log::warning(
            'Vuelament: User model does not implement hasPanelAccess() or canAccessPanel(). ' .
            'Add the HasPanelAccess trait for proper access control. ' .
            'Access is currently allowed only in the [local] environment.'
        );

        return App::environment('local');
    }
}
