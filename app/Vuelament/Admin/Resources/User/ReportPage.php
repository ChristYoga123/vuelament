<?php

namespace App\Vuelament\Admin\Resources\User;

use App\Vuelament\Core\BasePage;

class ReportPage extends BasePage
{
    protected static string $slug = 'report';
    protected static string $title = 'Report Page';
    protected static string $view = 'Vuelament/Admin/Resources/User/ReportPage';
    protected static string $icon = 'file';
    protected static ?string $resource = UserResource::class;
    protected static int $navigationSort = 0;
    // protected static ?string $navigationGroup = null;

    /**
     * Data yang di-pass ke Vue component via Inertia
     */
    public static function getData(?\Illuminate\Database\Eloquent\Model $record = null): array
    {
        return [
            'reportStats' => [
                'total_logins' => rand(10, 100),
                'last_active' => now()->subDays(rand(1, 5))->format('Y-m-d H:i:s'),
                'status' => 'Active',
            ]
        ];
    }
}