<?php

namespace ChristYoga123\Vuelament\Http\Controllers;

use ChristYoga123\Vuelament\Components\Table\Table;
use ChristYoga123\Vuelament\Core\PageSchema;
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

        // Resolve table schema and data (with search, sort, pagination, filter)
        $tableSchemaRaw = null;
        $tableData      = null;
        $tablePageSchema = $pageClass::table();

        if ($tablePageSchema) {
            $tableSchemaRaw = $tablePageSchema->toArray('index');

            // Find Table component and run full query
            foreach ($tablePageSchema->getComponents() as $comp) {
                if ($comp instanceof Table && $comp->getQueryClosure()) {
                    $query = call_user_func($comp->getQueryClosure());

                    if ($query instanceof \Illuminate\Database\Eloquent\Builder ||
                        $query instanceof \Illuminate\Database\Query\Builder) {

                        // Apply search
                        if ($search = $request->input('search')) {
                            $query = $this->applySearch($query, $search, $comp);
                        }

                        // [FIX] Sort — validate column against allowlist to prevent info disclosure
                        $sortField = $request->input('sort');
                        $sortDir   = $request->input('direction', 'desc');

                        if ($sortField) {
                            $allowedSortColumns = collect($comp->getColumns())
                                ->filter(fn($col) => $col->toArray()['sortable'] ?? false)
                                ->map(fn($col) => $col->getName())
                                ->toArray();

                            // Only apply sort if column is in allowlist
                            if (in_array($sortField, $allowedSortColumns, true)) {
                                // [FIX] Validate direction
                                $safeDir = in_array(strtolower($sortDir), ['asc', 'desc'], true)
                                    ? strtolower($sortDir)
                                    : 'desc';

                                $query->orderBy($sortField, $safeDir);
                            }
                        }

                        // Paginate atau get all
                        $tableArray = $comp->toArray();
                        if ($tableArray['paginated'] ?? true) {
                            // [FIX] Cap per_page 1–100 untuk cegah DoS (sama seperti ResourceController)
                            $perPage   = min(max((int) $request->input('per_page', $tableArray['perPage'] ?? 10), 1), 100);
                            $tableData = $query->paginate($perPage)->withQueryString();

                            $tableData->getCollection()->transform(function ($r) use ($comp) {
                                $vActions = [];
                                foreach ($comp->getActions() as $action) {
                                    $vActions[$action->getName()] = [
                                        'url'               => $action->evaluateUrl($r),
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
                                        'url'               => $action->evaluateUrl($r),
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
                'auth'  => ['user' => $this->safeAuthUser($request)],  // [FIX] only safe fields
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
            $bcArray = [];
            foreach ($customBreadcrumbs as $url => $label) {
                $bcArray[] = [
                    'url'   => is_numeric($url) ? null : $url,
                    'label' => $label,
                ];
            }
            $data['breadcrumbs'] = $bcArray;
        } else {
            $data['breadcrumbs'] = array_values(array_filter([
                ['label' => 'Dashboard', 'url' => '/' . $panel->getPath()],
                $resourceClass
                    ? ['label' => $resourceClass::getLabel(), 'url' => $resourceClass::getUrl('index')]
                    : null,
                ['label' => $pageClass::getTitle(), 'url' => null],
            ]));
        }

        return Inertia::render($pageClass::getView(), $data);
    }

    /**
     * [FIX] Return hanya field aman dari user model ke Inertia.
     * Cegah seluruh model (beserta field sensitif) terekspos ke frontend.
     */
    protected function safeAuthUser(Request $request): ?array
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if (method_exists($user, 'toInertiaArray')) {
            return $user->toInertiaArray();
        }

        return array_filter([
            'id'                => $user->getKey(),
            'name'              => $user->getAttribute('name'),
            'email'             => $user->getAttribute('email'),
            'avatar'            => $user->getAttribute('avatar'),
            'profile_photo_url' => $user->getAttribute('profile_photo_url'),
        ], fn($v) => $v !== null);
    }

    /**
     * Apply search to query based on columns that have searchable = true
     */
    protected function applySearch($query, string $search, Table $tableComponent)
    {
        $tableArray        = $tableComponent->toArray();
        $searchableColumns = collect($tableArray['columns'] ?? [])
            ->filter(fn($col) => ($col['searchable'] ?? false) === true)
            ->pluck('name')
            ->toArray();

        if (!empty($searchableColumns)) {
            $query->where(function ($q) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col, 'like', '%' . $search . '%');
                }
            });
        }

        return $query;
    }
}
