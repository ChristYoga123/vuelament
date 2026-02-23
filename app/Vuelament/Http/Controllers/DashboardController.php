<?php

namespace App\Vuelament\Http\Controllers;

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
                'user' => $request->user(),
            ],
        ]);
    }
}
