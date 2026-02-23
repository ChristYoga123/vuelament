<?php

namespace App\Vuelament\Core;

abstract class BaseResource
{
    protected static string $model = '';
    protected static string $slug = '';
    protected static string $label = '';
    protected static string $icon = 'circle';

    // ── Navigation ───────────────────────────────────────

    protected static int $navigationSort = 0;
    protected static ?string $navigationGroup = null;
    protected static ?string $navigationIcon = null;  // null = pakai $icon
    protected static ?string $navigationLabel = null;  // null = pakai $label
    protected static ?string $navigationBadge = null;
    protected static ?string $navigationBadgeColor = null;

    // ── Schema (override di resource class) ──────────────

    public static function tableSchema(): PageSchema
    {
        return PageSchema::make()->title(static::$label);
    }

    public static function formSchema(): PageSchema
    {
        return PageSchema::make()->title('Create ' . static::$label);
    }

    public static function editSchema(): PageSchema
    {
        return static::formSchema()->title('Edit ' . static::$label);
    }

    /**
     * Dashboard widgets (override di resource class)
     */
    public static function widgets(): array
    {
        return [];
    }

    public static function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::getModel()::query();
    }

    public static function applyFilters($query, array $filters): mixed
    {
        return $query;
    }

    public static function beforeSave(array $data, string $action): array
    {
        return $data;
    }

    // ── Navigation Items ─────────────────────────────────
    //
    // Return NavigationItem[] — bisa di-spread di navigation([]) panel
    //
    // Contoh di Panel:
    //   ->navigation([
    //       NavigationGroup::make('Master Data')->items([
    //           ...UserResource::getNavigationItems(),
    //           ...RoleResource::getNavigationItems(),
    //       ]),
    //   ])

    public static function getNavigationItems(): array
    {
        // Simpan slug relatif saja — Panel akan prepend path di buildCustomNavigation()
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->url(static::getSlug())
                ->sort(static::getNavigationSort())
                ->badge(static::$navigationBadge, static::$navigationBadgeColor ?? 'primary'),
        ];
    }

    // ── Getters ──────────────────────────────────────────

    public static function getModel(): string  { return static::$model; }
    public static function getSlug(): string   { return static::$slug; }
    public static function getLabel(): string  { return static::$label; }
    public static function getIcon(): string   { return static::$icon; }

    public static function getNavigationSort(): int       { return static::$navigationSort; }
    public static function getNavigationGroup(): ?string  { return static::$navigationGroup; }
    public static function getNavigationIcon(): string    { return static::$navigationIcon ?? static::$icon; }
    public static function getNavigationLabel(): string   { return static::$navigationLabel ?? static::$label; }
}