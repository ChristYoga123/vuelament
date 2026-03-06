<?php

namespace ChristYoga123\Vuelament\Core\Pages;

/**
 * ManageRecords — base class untuk halaman manage resource (single mode)
 *
 * Digunakan ketika create & edit dilakukan di dalam modal pada halaman list.
 * Menggantikan ListRecords + CreateRecord + EditRecord.
 *
 * Extend class ini di Resource page class:
 *   class ManageProducts extends ManageRecords
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
class ManageRecords
{
    protected static ?string $resource = null;
    protected static string $view = 'Vuelament/Resource/Manage';

    /**
     * Header actions — tombol-tombol di atas halaman manage
     * Contoh: CreateAction (akan membuka modal), ExportAction
     */
    public static function getHeaderActions(): array
    {
        return [];
    }

    public static function getResource(): ?string { return static::$resource; }
    public static function getView(): string { return static::$view; }
}
