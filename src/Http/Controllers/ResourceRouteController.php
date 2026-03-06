<?php

namespace ChristYoga123\Vuelament\Http\Controllers;

use ChristYoga123\Vuelament\Http\Traits\ResourceController;

/**
 * ResourceRouteController — generic controller yang menangani semua resource.
 *
 * Menggantikan per-model controller (UserController, ProductController, dll.)
 * Resource class di-bind via `forResource()` sebelum method dipanggil.
 *
 * Cara kerja:
 *   1. VuelamentServiceProvider register routes dengan closure
 *   2. Closure memanggil ResourceRouteController::forResource($resourceClass)
 *   3. Controller menjalankan method CRUD (index, create, store, edit, update, destroy)
 *
 * Jika per-model controller masih dibutuhkan untuk kustomisasi berat,
 * cukup buat {Model}Controller yang `use ResourceController` seperti sebelumnya.
 */
class ResourceRouteController
{
    use ResourceController;

    protected static string $resource = '';

    /**
     * Bind resource class ke controller instance.
     * PHP bersifat single-request per process — static property aman di sini.
     */
    public static function forResource(string $resourceClass): static
    {
        static::$resource = $resourceClass;
        return new static();
    }
}
