<?php

namespace App\Vuelament\Http\Controllers;

use App\Vuelament\Components\Table\Table;
use App\Vuelament\Core\PageSchema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController
{
    /**
     * Handle custom page rendering
     * Route-nya auto-registered dari panel->pages
     */
    public function __invoke(Request $request, string $pageClass)
    {
        $panel = app('vuelament.panel');

        // Resolve table schema dan data (dengan search, sort, pagination, filter)
        $tableSchemaRaw = null;
        $tableData      = null;
        $tablePageSchema = $pageClass::table();

        if ($tablePageSchema) {
            $tableSchemaRaw = $tablePageSchema->toArray('index');

            // Cari Table component dan jalankan query penuh
            foreach ($tablePageSchema->getComponents() as $comp) {
                if ($comp instanceof Table && $comp->getQueryClosure()) {
                    $query = call_user_func($comp->getQueryClosure());

                    if ($query instanceof \Illuminate\Database\Eloquent\Builder ||
                        $query instanceof \Illuminate\Database\Query\Builder) {

                        // Apply search
                        if ($search = $request->input('search')) {
                            $query = $this->applySearch($query, $search, $comp);
                        }

                        // Apply sort
                        $sortField = $request->input('sort');
                        $sortDir   = $request->input('direction', 'desc');
                        if ($sortField) {
                            $query->orderBy($sortField, $sortDir);
                        }

                        // Paginate atau get all
                        $tableArray = $comp->toArray();
                        if ($tableArray['paginated'] ?? true) {
                            $perPage   = $request->input('per_page', $tableArray['perPage'] ?? 10);
                            $tableData = $query->paginate($perPage)->withQueryString();
                        } else {
                            $tableData = $query->get();
                        }
                    } elseif ($query instanceof \Illuminate\Support\Collection || is_array($query)) {
                        $tableData = $query;
                    }
                    break;
                }
            }
        }

        // Resolve form schema
        $formSchemaRaw = $pageClass::form()?->toArray('create');

        $data = array_merge(
            $pageClass::getData(),
            [
                'panel' => $panel->toArray(),
                'auth'  => ['user' => $request->user()],
                'page'  => [
                    'title'       => $pageClass::getTitle(),
                    'slug'        => $pageClass::getSlug(),
                    'icon'        => $pageClass::getIcon(),
                    'description' => $pageClass::getDescription(),
                ],
                'tableSchema' => $tableSchemaRaw,
                'data'        => $tableData,
                'filters'     => $request->only(['search', 'sort', 'direction', 'per_page']),
                'formSchema'  => $formSchemaRaw,
                'resource'    => [
                    'slug'  => $pageClass::getSlug(),
                    'label' => $pageClass::getTitle(),
                ],
            ]
        );

        return Inertia::render($pageClass::getView(), $data);
    }

    /**
     * Apply search ke query berdasarkan kolom yang memiliki searchable = true
     */
    protected function applySearch($query, string $search, Table $tableComponent)
    {
        $tableArray = $tableComponent->toArray();
        $searchableColumns = collect($tableArray['columns'] ?? [])
            ->filter(fn($col) => ($col['searchable'] ?? false) === true)
            ->pluck('name')
            ->toArray();

        if (!empty($searchableColumns)) {
            $query->where(function ($q) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }
}
