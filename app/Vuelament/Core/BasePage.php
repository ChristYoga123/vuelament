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

    // ── Getters ──────────────────────────────────────────

    public static function getSlug(): string           { return static::$slug; }
    public static function getTitle(): string          { return static::$title; }
    public static function getView(): string           { return static::$view; }
    public static function getIcon(): string           { return static::$icon; }
    public static function getNavigationSort(): int    { return static::$navigationSort; }
    public static function getNavigationGroup(): ?string { return static::$navigationGroup; }
    public static function getNavigationLabel(): string { return static::$navigationLabel ?? static::$title; }

    /**
     * Data yang di-pass ke Inertia render
     * Override di subclass untuk inject data
     */
    public static function getData(): array
    {
        return [];
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
        $panel = app('vuelament.panel');

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getIcon())
                ->url('/' . $panel->getPath() . '/' . static::getSlug())
                ->sort(static::getNavigationSort()),
        ];
    }
}
