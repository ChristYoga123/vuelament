<?php

namespace App\Vuelament\Admin\Resources\User;

use App\Vuelament\Http\Traits\ResourceController;

class UserController
{
    use ResourceController;

    protected static string $resource = UserResource::class;
}
