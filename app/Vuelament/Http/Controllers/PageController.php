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
     * Route-nya auto-registered dari panel->pages atau panel->resources sub-pages
     */
    public function __invoke(Request $request, string $pageClass, ?string $resourceClass = null, mixed $recordId = null)
    {
        $panel = app('vuelament.panel');

        $record = null;
        if ($resourceClass && $recordId) {
            $modelClass = $resourceClass::getModel();
            if (class_exists($modelClass)) {
                $record = $modelClass::findOrFail($recordId);
            }
        }

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
                            
                            $tableData->getCollection()->transform(function ($r) use ($comp) {
                                $vActions = [];
                                foreach ($comp->getActions() as $action) {
                                    $vActions[$action->getName()] = [
                                        'url' => $action->evaluateUrl($r),
                                        'shouldOpenInNewTab' => $action->toArray()['shouldOpenInNewTab'] ?? false,
                                    ];
                                }
                                $r->setAttribute('_v_actions', $vActions);
                                return $r;
                            });
                        } else {
                            $tableData = $query->get();
                            $tableData->transform(function ($r) use ($comp) {
                                $vActions = [];
                                foreach ($comp->getActions() as $action) {
                                    $vActions[$action->getName()] = [
                                        'url' => $action->evaluateUrl($r),
                                        'shouldOpenInNewTab' => $action->toArray()['shouldOpenInNewTab'] ?? false,
                                    ];
                                }
                                $r->setAttribute('_v_actions', $vActions);
                                return $r;
                            });
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
            $pageClass::getData($record),
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
                'record'      => $record,
                'resource'    => [
                    'slug'  => $resourceClass ? $resourceClass::getSlug() : $pageClass::getSlug(),
                    'label' => $resourceClass ? $resourceClass::getLabel() : $pageClass::getTitle(),
                ],
                'breadcrumbs' => [],
            ]
        );

        $customBreadcrumbs = $pageClass::getBreadcrumbs();
        if (!empty($customBreadcrumbs)) {
            // Evaluasi [url => label]
            $bcArray = [];
            foreach ($customBreadcrumbs as $url => $label) {
                $bcArray[] = [
                    'url' => is_numeric($url) ? null : $url,
                    'label' => $label,
                ];
            }
            $data['breadcrumbs'] = $bcArray;
        } else {
            // Default Vuelament
            $data['breadcrumbs'] = [
                ['label' => 'Dashboard', 'url' => '/' . $panel->getPath()],
                $resourceClass ? ['label' => $resourceClass::getLabel(), 'url' => $resourceClass::getUrl('index')] : null,
                ['label' => $pageClass::getTitle(), 'url' => null],
            ];
            $data['breadcrumbs'] = array_values(array_filter($data['breadcrumbs']));
        }

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
