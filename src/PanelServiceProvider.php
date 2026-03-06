<?php

namespace ChristYoga123\Vuelament;

use ChristYoga123\Vuelament\Core\Panel;
use ChristYoga123\Vuelament\Core\PageRegistration;
use ChristYoga123\Vuelament\Http\Controllers\AuthController;
use ChristYoga123\Vuelament\Http\Controllers\DashboardController;
use ChristYoga123\Vuelament\Http\Controllers\PageController;
use ChristYoga123\Vuelament\Http\Controllers\ResourceRouteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * PanelServiceProvider — base class for user panel providers.
 *
 * Extend class ini di PanelProvider yang di-generate (misal AdminPanelProvider).
 * Setiap panel (Admin, Sales, dsb.) punya PanelProvider masing-masing.
 */
abstract class PanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $panel = $this->panel();
        $panelId = $panel->getId();

        // ── Register ke Central Registry ─────────────
        // Semua panel di-register ke Vuelament singleton.
        // getCurrentPanel() di registry akan auto-detect panel yang aktif.
        $registry = $this->app->make('vuelament');
        $registry->registerPanel($panel);

        // ── Backward-compatible individual binding ───
        // Tetap bind 'vuelament.panel.{id}' untuk akses direct jika dibutuhkan.
        $this->app->singleton("vuelament.panel.{$panelId}", function () use ($panel) {
            return $panel;
        });
    }

    public function boot(): void
    {
        $panel = $this->panel();
        $panel->boot();

        // Auto-register routes
        $this->registerRoutes($panel);
    }

    /**
     * Override method ini di PanelProvider yang di-generate
     */
    abstract public function panel(): Panel;

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
     * Register CRUD routes untuk satu resource.
     *
     * Priority untuk controller:
     * 1. Per-model controller (jika ada) — untuk kustomisasi berat
     * 2. Generic ResourceRouteController — default, tidak perlu file tambahan
     */
    protected function registerResourceRoutes(string $resourceClass, string $panelId): void
    {
        $slug = $resourceClass::getSlug();

        // ── Resolve controller ──────────────────────────────
        // 1. Cek per-model controller colocated (App\Vuelament\Admin\User\UserController)
        $namespace = substr($resourceClass, 0, strrpos($resourceClass, '\\'));
        $baseName = class_basename($resourceClass);
        $controllerBaseName = str_replace('Resource', 'Controller', $baseName);
        $colocatedController = $namespace . '\\' . $controllerBaseName;

        // 2. Cek old convention (App\Http\Controllers\Vuelament\...)
        $oldControllerClass = str_replace(
            ['App\\Vuelament', '\\Resources', 'Resource'],
            ['App\\Http\\Controllers\\Vuelament', '', 'Controller'],
            $resourceClass
        );

        // 3. Fallback: Use generic ResourceRouteController
        $useGeneric = false;
        if (class_exists($colocatedController)) {
            $controllerClass = $colocatedController;
        } elseif (class_exists($oldControllerClass)) {
            $controllerClass = $oldControllerClass;
        } else {
            $useGeneric = true;
        }

        // ── Register CRUD routes ────────────────────────────

        if ($useGeneric) {
            // Generic: closure-based routes that bind resource per-request
            $this->registerGenericResourceRoutes($resourceClass, $slug, $panelId);
        } else {
            // Per-model controller
            Route::get($slug,               [$controllerClass, 'index'])->name("{$panelId}.{$slug}.index");
            Route::get("{$slug}/create",     [$controllerClass, 'create'])->name("{$panelId}.{$slug}.create");
            Route::post($slug,               [$controllerClass, 'store'])->name("{$panelId}.{$slug}.store");

            Route::delete("{$slug}/bulk-destroy", [$controllerClass, 'bulkDestroy'])->name("{$panelId}.{$slug}.bulk-destroy");

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

            if ($hasSoftDeletes) {
                Route::post("{$slug}/{id}/restore",   [$controllerClass, 'restore'])->name("{$panelId}.{$slug}.restore");
                Route::delete("{$slug}/{id}/force",   [$controllerClass, 'forceDelete'])->name("{$panelId}.{$slug}.force-delete");
            }
        }

        // ── Custom Resource Sub-pages ─────────────────────
        // Skip CRUD keys — those routes are already auto-registered above
        $crudKeys = ['index', 'create', 'edit'];

        foreach ($resourceClass::getPages() as $pageName => $pageRegistration) {
            if (in_array($pageName, $crudKeys)) {
                continue;
            }

            // Support backward compat: if value is class string
            if (is_string($pageRegistration) && class_exists($pageRegistration)) {
                $pageClass = $pageRegistration;
                $routePath = method_exists($pageClass, 'getRoutePath') ? $pageClass::getRoutePath() : $pageName;
            } elseif ($pageRegistration instanceof PageRegistration) {
                $pageClass = $pageRegistration->class;
                $routePath = $pageRegistration->route;
            } else {
                continue;
            }

            $fullPath = ltrim($routePath, '/');
            $fullPath = $fullPath ? "{$slug}/{$fullPath}" : "{$slug}/{$pageName}";

            Route::get($fullPath, function (\Illuminate\Http\Request $request) use ($pageClass, $resourceClass) {
                $routeParams = $request->route()->parameters();
                $recordId = $routeParams['record'] ?? (count($routeParams) > 0 ? reset($routeParams) : null);

                return app(PageController::class)->__invoke($request, $pageClass, $resourceClass, $recordId);
            })->name("{$panelId}.{$slug}.page.{$pageName}");
        }
    }

    /**
     * Register CRUD routes using generic ResourceRouteController.
     * Tidak perlu per-model controller — framework handles everything.
     */
    protected function registerGenericResourceRoutes(string $resourceClass, string $slug, string $panelId): void
    {
        $ctrl = ResourceRouteController::class;

        Route::get($slug, function (\Illuminate\Http\Request $r) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->index($r);
        })->name("{$panelId}.{$slug}.index");

        Route::get("{$slug}/create", function () use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->create();
        })->name("{$panelId}.{$slug}.create");

        Route::post($slug, function (\Illuminate\Http\Request $r) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->store($r);
        })->name("{$panelId}.{$slug}.store");

        // Bulk actions
        Route::delete("{$slug}/bulk-destroy", function (\Illuminate\Http\Request $r) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->bulkDestroy($r);
        })->name("{$panelId}.{$slug}.bulk-destroy");

        // SoftDelete bulk routes
        $modelClass = $resourceClass::getModel();
        $hasSoftDeletes = class_exists($modelClass)
            && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass));

        if ($hasSoftDeletes) {
            Route::post("{$slug}/bulk-restore", function (\Illuminate\Http\Request $r) use ($ctrl, $resourceClass) {
                return $ctrl::forResource($resourceClass)->bulkRestore($r);
            })->name("{$panelId}.{$slug}.bulk-restore");

            Route::delete("{$slug}/bulk-force-delete", function (\Illuminate\Http\Request $r) use ($ctrl, $resourceClass) {
                return $ctrl::forResource($resourceClass)->bulkForceDelete($r);
            })->name("{$panelId}.{$slug}.bulk-force-delete");
        }

        Route::get("{$slug}/{id}/edit", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->edit($id);
        })->name("{$panelId}.{$slug}.edit");

        Route::put("{$slug}/{id}", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->update($r, $id);
        })->name("{$panelId}.{$slug}.update");

        Route::patch("{$slug}/{id}/update-column", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->updateColumn($r, $id);
        })->name("{$panelId}.{$slug}.update-column");

        Route::post("{$slug}/{id}/action", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->executeAction($r, $id);
        })->name("{$panelId}.{$slug}.action");

        Route::delete("{$slug}/{id}", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
            return $ctrl::forResource($resourceClass)->destroy($id);
        })->name("{$panelId}.{$slug}.destroy");

        // SoftDelete single row routes
        if ($hasSoftDeletes) {
            Route::post("{$slug}/{id}/restore", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
                return $ctrl::forResource($resourceClass)->restore($id);
            })->name("{$panelId}.{$slug}.restore");

            Route::delete("{$slug}/{id}/force", function (\Illuminate\Http\Request $r, $id) use ($ctrl, $resourceClass) {
                return $ctrl::forResource($resourceClass)->forceDelete($id);
            })->name("{$panelId}.{$slug}.force-delete");
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
