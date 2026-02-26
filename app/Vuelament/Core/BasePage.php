<?php

namespace App\Vuelament\Core;

/**
 * BasePage — base class untuk custom page di Vuelament
 *
 * Extend class ini untuk membuat custom page.
 * Mirip Filament Page — punya slug, title, navigation, dan Vue component.
 *
 * Contoh:
 *   class Settings extends BasePage
 *   {
 *       protected static string $slug = 'settings';
 *       protected static string $title = 'Settings';
 *       protected static string $view = 'Vuelament/Pages/Settings';
 *       protected static string $icon = 'settings';
 *       protected static int $navigationSort = 99;
 *   }
 */
abstract class BasePage
{
    protected static string $slug = '';
    protected static string $title = '';
    protected static string $view = '';       // Inertia component path
    protected static string $icon = 'file';
    protected static int $navigationSort = 0;
    protected static ?string $navigationGroup = null;
    protected static ?string $navigationLabel = null;
    protected static ?string $navigationBadge = null;
    protected static ?string $description = null;
    protected static ?string $routePath = null;
    protected static ?string $resource = null;

    // ── Getters ──────────────────────────────────────────

    public static function getRoutePath(): string      { return static::$routePath ?? static::$slug; }
    public static function getResource(): ?string      { return static::$resource; }

    public static function getSlug(): string           { return static::$slug; }
    public static function getTitle(): string          { return static::$title; }
    public static function getView(): string           { return static::$view; }
    public static function getIcon(): string           { return static::$icon; }
    public static function getNavigationSort(): int    { return static::$navigationSort; }
    public static function getNavigationGroup(): ?string { return static::$navigationGroup; }
    public static function getNavigationLabel(): string { return static::$navigationLabel ?? static::$title; }
    public static function getDescription(): ?string   { return static::$description; }

    public static function getBreadcrumbs(): array     { return []; }

    /**
     * Daftarkan routing file (terutama kalau dipanggil di Resource::getPages())
     */
    public static function route(string $route): PageRegistration
    {
        return new PageRegistration(static::class, $route);
    }

    /**
     * Dapatkan URL page berdasarkan panel aktif dan parameternya
     */
    public static function getUrl(array $parameters = []): string
    {
        $panel = app('vuelament.panel')->getId();
        // Coba temukan rute dengan nama page ini sebagai custom global page
        $routeNames = ["{$panel}." . static::getSlug(), "{$panel}.page." . static::getSlug()];
        
        foreach ($routeNames as $routeName) {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                return route($routeName, $parameters);
            }
        }
        
        // Fallback default (Manual Resource parameter)
        if (isset($parameters['resource'])) {
            $resource = $parameters['resource'];
            unset($parameters['resource']);
            // Mengambil short name dari namespace class sebagai fallback pageName
            $pageName = class_basename(static::class); 
            $routeName = "{$panel}.{$resource}.page.{$pageName}";
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                return route($routeName, $parameters);
            }
        }
        
        // Auto-resolve Resource context
        if (static::$resource && class_exists(static::$resource)) {
            $resourceClass = static::$resource;
            $resourceSlug = $resourceClass::getSlug();
            
            // Find page name from resource's getPages() array
            $pages = $resourceClass::getPages();
            $pageKey = null;
            
            foreach ($pages as $key => $pageDef) {
                if ($pageDef === static::class) {
                    $pageKey = $key;
                    break;
                }
                if ($pageDef instanceof \App\Vuelament\Core\PageRegistration && $pageDef->class === static::class) {
                    $pageKey = $key;
                    break;
                }
            }
            
            if ($pageKey) {
                $routeName = "{$panel}.{$resourceSlug}.page.{$pageKey}";
                if (\Illuminate\Support\Facades\Route::has($routeName)) {
                    return route($routeName, $parameters);
                }
            }
        }

        // Fallback default
        return url('/' . $panel . '/' . ltrim(static::getRoutePath(), '/'));
    }

    /**
     * Data yang di-pass ke Inertia render
     * Override di subclass untuk inject data
     */
    public static function getData(?\Illuminate\Database\Eloquent\Model $record = null): array
    {
        return [];
    }

    /**
     * Mengembalikan schema Table, mirp seperti Filament's table()
     */
    public static function table(): ?PageSchema
    {
        return null;
    }

    /**
     * Mengembalikan schema Form, mirip seperti Filament's form()
     */
    public static function form(): ?PageSchema
    {
        return null;
    }

    /**
     * Return NavigationItem[] — bisa di-spread ke navigation()
     *
     * Contoh:
     *   ->navigation([
     *       NavigationGroup::make('Pengaturan')->items([
     *           ...SettingsPage::getNavigationItems(),
     *       ]),
     *   ])
     */
    public static function getNavigationItems(): array
    {
        // Save slug relatif saja — Panel akan prepend path di buildCustomNavigation()
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getIcon())
                ->url(static::getSlug())
                ->sort(static::getNavigationSort()),
        ];
    }
}
