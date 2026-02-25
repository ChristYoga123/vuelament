<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/admin');
Route::get('/', function () {
    return Inertia::render('Home');
});
