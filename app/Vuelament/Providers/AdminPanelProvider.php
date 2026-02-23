<?php

namespace App\Vuelament\Providers;

use App\Vuelament\Core\NavigationGroup;
use App\Vuelament\Core\Panel;
use App\Vuelament\Admin\Resources\UserResource;
use App\Vuelament\VuelamentServiceProvider;

class AdminPanelProvider extends VuelamentServiceProvider
{
    public function panel(): Panel
    {
        return Panel::make()
            ->id('admin')
            ->path('admin')
            ->brandName('Admin Panel')
            ->login()
            ->middleware(['web'])
            ->authMiddleware([\App\Vuelament\Http\Middleware\Authenticate::class])
            ->colors([
                'primary' => '#6366f1',
            ])
            ->discoverResources(app_path('Vuelament/Admin/Resources'), 'App\\Vuelament\\Admin\\Resources')
            ->discoverPages(app_path('Vuelament/Admin/Pages'), 'App\\Vuelament\\Admin\\Pages')
            ->discoverWidgets(app_path('Vuelament/Admin/Widgets'), 'App\\Vuelament\\Admin\\Widgets')
            ->plugins([
                //
            ])
            ->navigation([
                NavigationGroup::make('Master')
                    ->items([
                        ...UserResource::getNavigationItems(),
                    ])
            ]);
    }
}
