<?php

namespace ChristYoga123\Vuelament;

use Illuminate\Support\ServiceProvider;

/**
 * VuelamentServiceProvider — auto-discovered by Laravel.
 *
 * Handles config merging, command registration, and asset publishing.
 * User panel providers (AdminPanelProvider, etc.) extend PanelServiceProvider instead.
 */
class VuelamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vuelament.php', 'vuelament');
    }

    public function boot(): void
    {
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
