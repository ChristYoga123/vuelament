<?php

namespace App\Http\Controllers\Vuelament\Admin;

use App\Http\Controllers\Controller;
use App\Vuelament\Http\Traits\ResourceController;
use App\Vuelament\Admin\Resources\UserResource;

class UserController extends Controller
{
    use ResourceController;

    protected static string $resource = UserResource::class;
}
