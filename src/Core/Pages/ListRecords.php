<?php

namespace ChristYoga123\Vuelament\Core\Pages;

/**
 * ListRecords — base class untuk halaman list/index resource
 *
 * Extend class ini di Resource page class:
 *   class ListProducts extends ListRecords
 *   {
 *       protected static ?string $resource = ProductResource::class;
 *
 *       public static function getHeaderActions(): array
 *       {
 *           return [
 *               CreateAction::make(),
 *           ];
 *       }
 *   }
 */
class ListRecords
{
    protected static ?string $resource = null;
    protected static string $view = 'Vuelament/Resource/Index';

    /**
     * Header actions — tombol-tombol di atas halaman list
     * Contoh: CreateAction, ExportAction, ImportAction
     */
    public static function getHeaderActions(): array
    {
        return [];
    }

    public static function getResource(): ?string { return static::$resource; }
    public static function getView(): string { return static::$view; }
}
