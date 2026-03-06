<?php

namespace ChristYoga123\Vuelament\Core\Pages;

/**
 * EditRecord — base class untuk halaman edit resource
 *
 * Extend class ini di Resource page class:
 *   class EditProduct extends EditRecord
 *   {
 *       protected static ?string $resource = ProductResource::class;
 *
 *       public static function getHeaderActions(): array
 *       {
 *           return [
 *               DeleteAction::make(),
 *           ];
 *       }
 *   }
 */
class EditRecord
{
    protected static ?string $resource = null;
    protected static string $view = 'Vuelament/Resource/Edit';

    /**
     * Header actions — tombol-tombol di atas halaman edit
     * Contoh: DeleteAction
     */
    public static function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Form actions — tombol-tombol di bagian bawah form (submit area)
     * Default: Cancel + Save
     */
    public static function getFormActions(): array
    {
        return [];
    }

    public static function getResource(): ?string { return static::$resource; }
    public static function getView(): string { return static::$view; }
}
