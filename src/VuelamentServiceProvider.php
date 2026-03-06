<?php

namespace ChristYoga123\Vuelament;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

/**
 * VuelamentServiceProvider — auto-discovered by Laravel.
 *
 * Handles:
 * - Registry singleton ('vuelament') registration
 * - Dynamic 'vuelament.panel' binding (current panel resolution)
 * - Config merging, command registration, and asset publishing
 *
 * User panel providers (AdminPanelProvider, etc.) extend PanelServiceProvider instead.
 */
class VuelamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vuelament.php', 'vuelament');

        // ── Vuelament Registry (singleton) ─────────
        // Central registry yang menyimpan semua panel instances.
        // PanelServiceProvider::register() akan memanggil registerPanel() ke sini.
        $this->app->singleton('vuelament', function () {
            return new Vuelament();
        });

        $this->app->alias('vuelament', Vuelament::class);

        // ── Dynamic Panel Binding ──────────────────
        // 'vuelament.panel' selalu resolve ke "current panel" dari registry.
        // - HTTP context → detect dari URL path
        // - CLI context  → dari --panel option atau default panel
        $this->app->bind('vuelament.panel', function ($app) {
            return $app->make('vuelament')->getCurrentPanel();
        });
    }

    public function boot(): void
    {
        // ── Inertia Global Shares ───────────────────
        Inertia::share([
            'errors' => function () {
                return Session::get('errors')
                    ? Session::get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
        ]);

        // ── Config ──────────────────────────
        $this->publishes([
            __DIR__ . '/../config/vuelament.php' => config_path('vuelament.php'),
        ], 'vuelament-config');

        // ── Vue/JS Assets ───────────────────
        $this->publishes([
            __DIR__ . '/../resources/js/Layouts' => resource_path('js/Layouts'),
            __DIR__ . '/../resources/js/Pages/Vuelament' => resource_path('js/Pages/Vuelament'),
            __DIR__ . '/../resources/js/components/vuelament' => resource_path('js/components/vuelament'),
            __DIR__ . '/../resources/js/AppWrapper.vue' => resource_path('js/AppWrapper.vue'),
        ], 'vuelament-views');

        // ── Blade Views (Inertia root) ──────
        $this->publishes([
            __DIR__ . '/../resources/views/app.blade.php' => resource_path('views/app.blade.php'),
        ], 'vuelament-blade');

        // ── Stubs (optional, for customization) ─────
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/vuelament'),
        ], 'vuelament-stubs');

        // ── Artisan Commands ────────────────
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\MakeResourceCommand::class,
                Commands\MakeServiceCommand::class,
                Commands\MakePageCommand::class,
                Commands\MakePanelCommand::class,
                Commands\MakeUserCommand::class,
            ]);
        }
    }
}
