<?php

namespace App\Vuelament;

use App\Vuelament\Core\Panel;
use App\Vuelament\Http\Controllers\AuthController;
use App\Vuelament\Http\Controllers\DashboardController;
use App\Vuelament\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * VuelamentServiceProvider — base class
 *
 * Extend class ini di PanelProvider yang di-generate (misal AdminPanelProvider).
 * Atau bisa langsung dipakai sebagai default.
 */
class VuelamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Panel sebagai singleton
        $this->app->singleton('vuelament.panel', function () {
            return $this->panel();
        });
    }

    public function boot(): void
    {
        $panel = $this->app->make('vuelament.panel');
        $panel->boot();

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Vuelament\Commands\MakeResourceCommand::class,
                \App\Vuelament\Commands\MakePanelCommand::class,
                \App\Vuelament\Commands\MakePageCommand::class,
                \App\Vuelament\Commands\MakeUserCommand::class,
            ]);
        }

        // Auto-register routes
        $this->registerRoutes($panel);
    }

    /**
     * Override method ini di PanelProvider yang di-generate
     */
    public function panel(): Panel
    {
        return Panel::make()
            ->id('admin')
            ->path('admin')
            ->brandName('Vuelament')
            ->login();
    }

    /**
     * Auto-register semua routes berdasarkan panel config
     *
     * Routes yang di-generate:
     * - Guest routes:   login, register
     * - Auth routes:    logout, dashboard, resource CRUD, custom pages
     */
    protected function registerRoutes(Panel $panel): void
    {
        $path    = $panel->getPath();
        $panelId = $panel->getId();

        Route::prefix($path)->middleware($panel->getMiddleware())->group(function () use ($panel, $panelId) {

            // ── Guest routes (login, register) ───────────
            Route::middleware($panel->getGuestMiddleware())->group(function () use ($panel, $panelId) {

                if ($panel->hasLogin()) {
                    Route::get('login',  [AuthController::class, 'showLogin'])->name("{$panelId}.login");
                    Route::post('login', [AuthController::class, 'login']);
                }

                if ($panel->hasRegister()) {
                    Route::get('register',  [AuthController::class, 'showRegister'])->name("{$panelId}.register");
                    Route::post('register', [AuthController::class, 'register']);
                }
            });

            // ── Authenticated routes ─────────────────────
            Route::middleware($panel->getAuthMiddleware())->group(function () use ($panel, $panelId) {

                // Logout
                Route::post('logout', [AuthController::class, 'logout'])->name("{$panelId}.logout");

                // Dashboard
                Route::get('/', [DashboardController::class, 'index'])->name("{$panelId}.dashboard");

                // ── Auto-register resource CRUD routes ───
                foreach ($panel->getResources() as $resourceClass) {
                    $this->registerResourceRoutes($resourceClass, $panelId);
                }

                // ── Auto-register custom page routes ─────
                foreach ($panel->getPages() as $pageClass) {
                    $this->registerPageRoute($pageClass, $panelId);
                }
            });
        });
    }

    /**
     * Register CRUD routes untuk satu resource
     */
    protected function registerResourceRoutes(string $resourceClass, string $panelId): void
    {
        $slug = $resourceClass::getSlug();

        // New Convention: Colocated controller (App\Vuelament\{Panel}\Resources\{Name}\{Name}Controller)
        $colocatedController = substr_replace($resourceClass, 'Controller', strrpos($resourceClass, 'Resource'));
        
        // Old Convention: App\Vuelament\{Panel}\Resources\{Name}Resource -> App\Http\Controllers\Vuelament\{Panel}\{Name}Controller
        $oldControllerClass = str_replace(
            ['App\\Vuelament', '\\Resources', 'Resource'],
            ['App\\Http\\Controllers\\Vuelament', '', 'Controller'],
            $resourceClass
        );

        $controllerClass = class_exists($colocatedController) ? $colocatedController : $oldControllerClass;

        if (!class_exists($controllerClass)) {
            return;
        }

        Route::get($slug,               [$controllerClass, 'index'])->name("{$panelId}.{$slug}.index");
        Route::get("{$slug}/create",     [$controllerClass, 'create'])->name("{$panelId}.{$slug}.create");
        Route::post($slug,               [$controllerClass, 'store'])->name("{$panelId}.{$slug}.store");

        // Bulk actions — harus sebelum {id} wildcard agar tidak ter-match oleh {id}
        Route::delete("{$slug}/bulk-destroy",       [$controllerClass, 'bulkDestroy'])->name("{$panelId}.{$slug}.bulk-destroy");

        // SoftDelete bulk routes
        $modelClass = $resourceClass::getModel();
        $hasSoftDeletes = class_exists($modelClass)
            && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass));

        if ($hasSoftDeletes) {
            Route::post("{$slug}/bulk-restore",         [$controllerClass, 'bulkRestore'])->name("{$panelId}.{$slug}.bulk-restore");
            Route::delete("{$slug}/bulk-force-delete",  [$controllerClass, 'bulkForceDelete'])->name("{$panelId}.{$slug}.bulk-force-delete");
        }

        Route::get("{$slug}/{id}/edit",   [$controllerClass, 'edit'])->name("{$panelId}.{$slug}.edit");
        Route::put("{$slug}/{id}",        [$controllerClass, 'update'])->name("{$panelId}.{$slug}.update");
        Route::patch("{$slug}/{id}/update-column", [$controllerClass, 'updateColumn'])->name("{$panelId}.{$slug}.update-column");
        Route::post("{$slug}/{id}/action", [$controllerClass, 'executeAction'])->name("{$panelId}.{$slug}.action");
        Route::delete("{$slug}/{id}",     [$controllerClass, 'destroy'])->name("{$panelId}.{$slug}.destroy");

        // SoftDelete single row routes
        if ($hasSoftDeletes) {
            Route::post("{$slug}/{id}/restore",   [$controllerClass, 'restore'])->name("{$panelId}.{$slug}.restore");
            Route::delete("{$slug}/{id}/force",   [$controllerClass, 'forceDelete'])->name("{$panelId}.{$slug}.force-delete");
        }

        // Custom Resource Sub-pages
        foreach ($resourceClass::getPages() as $pageName => $pageRegistration) {
            // Support backward compat: if value is class string
            if (is_string($pageRegistration) && class_exists($pageRegistration)) {
                $pageClass = $pageRegistration;
                $routePath = method_exists($pageClass, 'getRoutePath') ? $pageClass::getRoutePath() : $pageName;
            } elseif ($pageRegistration instanceof \App\Vuelament\Core\PageRegistration) {
                $pageClass = $pageRegistration->class;
                $routePath = $pageRegistration->route;
            } else {
                continue;
            }

            // Clean up Route Name using $pageName or fallback
            // Full Path allows `{record}/report` syntax
            $fullPath = ltrim($routePath, '/');
            $fullPath = $fullPath ? "{$slug}/{$fullPath}" : "{$slug}/{$pageName}";

            Route::get($fullPath, function (\Illuminate\Http\Request $request) use ($pageClass, $resourceClass) {
                // Ambil semua parameter URL
                $routeParams = $request->route()->parameters();
                // Utamakan param bernama {record}, jika tidak ada ambil parameter apa saja yang pertama muncul
                $recordId = $routeParams['record'] ?? (count($routeParams) > 0 ? reset($routeParams) : null);

                return app(\App\Vuelament\Http\Controllers\PageController::class)->__invoke($request, $pageClass, $resourceClass, $recordId);
            })->name("{$panelId}.{$slug}.page.{$pageName}");
        }
    }

    /**
     * Register route untuk custom page
     */
    protected function registerPageRoute(string $pageClass, string $panelId): void
    {
        $slug = $pageClass::getSlug();

        Route::get($slug, function (\Illuminate\Http\Request $request) use ($pageClass) {
            return app(PageController::class)->__invoke($request, $pageClass);
        })->name("{$panelId}.page.{$slug}");
    }
}
