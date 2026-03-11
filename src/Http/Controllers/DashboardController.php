<?php

namespace ChristYoga123\Vuelament\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function index(Request $request)
    {
        $panel = app('vuelament.panel');

        return Inertia::render('Vuelament/Dashboard', [
            'panel' => $panel->toArray(),
            'auth'  => [
                'user' => $this->safeAuthUser($request),  // [FIX] hanya field aman
            ],
        ]);
    }

    /**
     * [FIX] Return hanya field aman dari user model ke Inertia.
     * Cegah seluruh model (beserta field sensitif) terekspos ke frontend.
     *
     * Developer bisa menambahkan method toInertiaArray() di User model
     * untuk mengontrol field apa saja yang dikirim ke frontend.
     */
    protected function safeAuthUser(Request $request): ?array
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if (method_exists($user, 'toInertiaArray')) {
            return $user->toInertiaArray();
        }

        return array_filter([
            'id'                => $user->getKey(),
            'name'              => $user->getAttribute('name'),
            'email'             => $user->getAttribute('email'),
            'avatar'            => $user->getAttribute('avatar'),
            'profile_photo_url' => $user->getAttribute('profile_photo_url'),
        ], fn($v) => $v !== null);
    }
}
