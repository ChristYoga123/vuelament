<?php

namespace ChristYoga123\Vuelament\Core\Pages;

/**
 * CreateRecord — base class untuk halaman create resource
 *
 * Extend class ini di Resource page class:
 *   class CreateProduct extends CreateRecord
 *   {
 *       protected static ?string $resource = ProductResource::class;
 *   }
 */
class CreateRecord
{
    protected static ?string $resource = null;
    protected static string $view = 'Vuelament/Resource/Create';

    /**
     * Header actions — tombol-tombol di atas halaman create
     */
    public static function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Form actions — tombol-tombol di bagian bawah form (submit area)
     * Default: Cancel + Create
     */
    public static function getFormActions(): array
    {
        return [];
    }

    public static function getResource(): ?string { return static::$resource; }
    public static function getView(): string { return static::$view; }
}
