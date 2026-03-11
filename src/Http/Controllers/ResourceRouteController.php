<?php

namespace ChristYoga123\Vuelament\Http\Controllers;

use ChristYoga123\Vuelament\Http\Traits\ResourceController;

/**
 * ResourceRouteController — generic controller yang menangani semua resource.
 *
 * [FIX] Race condition pada Octane (Swoole/RoadRunner):
 * Static property $resource TIDAK aman di persistent PHP processes karena
 * concurrent requests bisa saling overwrite nilainya.
 *
 * Solusi: simpan resource class sebagai INSTANCE property, lalu override
 * getResourceClass() dari trait agar semua method dalam trait menggunakan
 * instance property (bukan static::$resource).
 *
 * Cara kerja:
 *   1. PanelServiceProvider register closure-based routes
 *   2. Closure memanggil ResourceRouteController::forResource($resourceClass)
 *   3. forResource() membuat instance BARU dan set $resourceInstance pada instance itu
 *   4. Setiap request mendapat instance sendiri — tidak ada shared state
 */
class ResourceRouteController
{
    use ResourceController;

    /**
     * Resource class yang di-bind per-instance.
     * [FIX] Instance property menggantikan static property.
     */
    protected string $resourceInstance = '';

    /**
     * Static property ini TIDAK dipakai oleh ResourceRouteController.
     * Tetap ada agar trait ResourceController tidak error jika diakses
     * via static::$resource dari luar context ini.
     */
    protected static string $resource = '';

    /**
     * Buat instance baru dan bind resource class ke instance tersebut.
     * Setiap pemanggilan menghasilkan instance independen — aman untuk Octane.
     */
    public static function forResource(string $resourceClass): static
    {
        $instance                   = new static();
        $instance->resourceInstance = $resourceClass;

        return $instance;
    }

    /**
     * [FIX] Override getResourceClass() dari trait.
     * Kembalikan instance property (bukan static::$resource).
     */
    protected function getResourceClass(): string
    {
        return $this->resourceInstance;
    }
}
