<?php

namespace ChristYoga123\Vuelament\Http\Traits;

use ChristYoga123\Vuelament\Components\Form\BaseForm;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ResourceController trait — the engine yang menghubungkan BaseResource ke Inertia + Eloquent
 *
 * Cara pakai:
 *   class UserController extends Controller {
 *       use ResourceController;
 *       protected static string $resource = UserResource::class;
 *       
 *       // (Opsional) override breadcrumb default jika dibutuhkan:
 *       // public function getBreadcrumbs(string $operation, mixed $record = null): array { ... }
 *   }
 */
trait ResourceController
{
    /**
     * Override di controller untuk menentukan resource class
     */
    // protected static string $resource = SomeResource::class;

    // ── Index (list + table) ─────────────────────────────

    public function index(Request $request)
    {
        $resource = static::$resource;
        $model       = $resource::getModel();
        $query       = $resource::getQuery();
        if (method_exists($resource, 'table')) {
            $tableComponent = $resource::table(\ChristYoga123\Vuelament\Components\Table\Table::make());
        } else {
            $pageSchema  = $resource::tableSchema();
            $tableComponent = null;
            foreach ($pageSchema->getComponents() as $comp) {
                if ($comp instanceof \ChristYoga123\Vuelament\Components\Table\Table) {
                    $tableComponent = $comp;
                    break;
                }
            }
        }

        // Jalankan custom query dari Table jika disediakan
        if ($tableComponent && $tableComponent->getQueryClosure()) {
            $customQuery = call_user_func($tableComponent->getQueryClosure(), $query);
            if ($customQuery instanceof \Illuminate\Database\Eloquent\Builder ||
                $customQuery instanceof \Illuminate\Database\Query\Builder ||
                $customQuery instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                $query = $customQuery;
            }
        }

        // Apply search
        if ($search = $request->input('search')) {
            $query = $this->applySearch($query, $search, $resource);
        }

        // Apply filters
        if ($filters = $request->input('filters', [])) {
            if ($tableComponent) {
                foreach ($tableComponent->getFilters() as $filterComp) {
                    $fArray = $filterComp->toArray();
                    if (!empty($fArray['isTrashed']) && isset($filters[$fArray['name']])) {
                        $val = $filters[$fArray['name']];
                        if ($val === 'with') {
                            $query->withTrashed();
                        } elseif ($val === 'only') {
                            $query->onlyTrashed();
                        }
                    }
                }
            }

            $query = $resource::applyFilters($query, $filters);
        }

        // Apply sort
        $sortField = $request->input('sort', 'id');
        $sortDir   = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Paginate
        $perPage = $request->input('per_page', 10);
        $data    = $query->paginate($perPage)->withQueryString();

        // Evaluate Table Actions per record
        if ($tableComponent) {
            $tableColumns = $tableComponent->getColumns();
            $data->getCollection()->transform(function ($record) use ($tableComponent, $tableColumns) {
                $vActions = [];
                foreach ($tableComponent->getActions() as $action) {
                    $vActions[$action->getName()] = [
                        'url' => $action->evaluateUrl($record),
                    ];
                }
                $record->setAttribute('_v_actions', $vActions);
                
                $vColumns = [];
                foreach ($tableColumns as $col) {
                    $val = $record->{$col->getName()};
                    if ($col->getGetStateUsing()) {
                        $params = [
                            'record' => $record,
                            'state' => $val,
                        ];
                        if (is_object($record)) {
                            $params[get_class($record)] = $record;
                        }
                        $val = app()->call($col->getGetStateUsing(), $params);
                        // Set evaluated state back to literal record to match component bindings
                        $record->{$col->getName()} = $val;
                    }
                    
                    $formatted = $val;
                    if ($col->getFormatStateUsing()) {
                        $params = [
                            'record' => $record,
                            'state' => $val,
                        ];
                        if (is_object($record)) {
                            $params[get_class($record)] = $record;
                        }
                        $formatted = app()->call($col->getFormatStateUsing(), $params);
                    }
                    
                    $vColumns[$col->getName()] = [
                        'formatted' => $formatted,
                        'color' => $col->evaluateColor($record, $val),
                    ];
                }
                $record->setAttribute('_v_columns', $vColumns);
                
                return $record;
            });
        }

        if (method_exists($resource, 'table')) {
            // Kita sudah peroleh $tableComponent di atas
            $tableSchema = [
                'type' => 'page',
                'title' => $resource::getLabel(),
                'components' => [$tableComponent->toArray('index')],
            ];
        } else {
            // Backward compatibility
            $tableSchema = $resource::tableSchema()->toArray('index');
        }
        $panel = app('vuelament.panel');

        $pages = $resource::getPages();
        $pageClass = $pages['index'] ?? null;
        $view = 'Vuelament/Resource/Index';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        $formSchema = null;
        if ($view === 'Vuelament/Resource/Manage' || ($pageClass && is_subclass_of($pageClass, \ChristYoga123\Vuelament\Core\Pages\ManageRecords::class))) {
            if (method_exists($resource, 'form')) {
                $form = $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make());
                $formSchema = [
                    'type' => 'page',
                    'title' => 'Manage ' . $resource::getLabel(),
                    'components' => $form->toArray('manage'),
                ];
            } else {
                $formSchema = method_exists($resource, 'formSchema') ? $resource::formSchema()->toArray('manage') : null;
            }
        }

        return Inertia::render($view, [
            'resource'    => [
                'slug'        => $resource::getSlug(),
                'label'       => $resource::getLabel(),
                'description' => $resource::getDescription(),
                'icon'        => $resource::getIcon(),
            ],
            'tableSchema'   => $tableSchema,
            'formSchema'    => $formSchema,
            'headerActions' => $this->resolveHeaderActions($resource, 'index'),
            'data'          => $data,
            'filters'       => $request->only(['search', 'filters', 'sort', 'direction', 'per_page']),
            'panel'         => $panel->toArray(),
            'auth'          => ['user' => $request->user()],
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('index')),
        ]);
    }

    // ── Create ───────────────────────────────────────────

    public function create()
    {
        $resource   = static::$resource;
        
        if (method_exists($resource, 'form')) {
            $form = $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make());
            $formSchema = [
                'type' => 'page',
                'title' => 'Create ' . $resource::getLabel(),
                'components' => $form->toArray('create'),
            ];
        } else {
            // Backward compatibility
            $formSchema = $resource::formSchema()->toArray('create');
        }
        $panel = app('vuelament.panel');

        $pages = $resource::getPages();
        $pageClass = $pages['create'] ?? null;
        $view = 'Vuelament/Resource/Create';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        return Inertia::render($view, [
            'resource'   => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema'    => $formSchema,
            'headerActions' => $this->resolveHeaderActions($resource, 'create'),
            'panel'         => $panel->toArray(),
            'auth'          => ['user' => request()->user()],
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('create')),
        ]);
    }

    // ── Getters / Configurations ─────────────────────────

    /**
     * Helper untuk menghasilkan susunan breadcrumb kustom layaknya Filament.
     * Mengembalikan struktur array yang berisi URL sebagai key dan Label sebagai value.
     */
    public function getBreadcrumbs(string $operation, mixed $record = null): array
    {
        $resource = static::$resource;
        $panel = app('vuelament.panel');
        $base = [
            '/' . $panel->getPath() => 'Dashboard',
        ];

        if ($operation === 'index') {
            $base[null] = $resource::getLabel();
        } else {
            $base[$resource::getUrl('index')] = $resource::getLabel();
            if ($operation === 'create') {
                $base[null] = 'Create';
            } elseif ($operation === 'edit') {
                $base[null] = 'Edit';
            }
        }

        return $base;
    }

    protected function formatBreadcrumbs(array $breadcrumbs): array
    {
        $bcArray = [];
        foreach ($breadcrumbs as $url => $label) {
            $bcArray[] = [
                'url' => is_numeric($url) || empty($url) ? null : $url,
                'label' => $label,
            ];
        }
        return array_values(array_filter($bcArray));
    }

    // ── Store ────────────────────────────────────────────

    public function store(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $panelId  = app('vuelament.panel')->getId();

        $formSchemaObj = method_exists($resource, 'form') 
            ? $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make()) 
            : $resource::formSchema();

        // Auto-extract validation rules dari form components
        $rules = $this->extractRulesFromSchema($formSchemaObj, null, 'create');
        $data  = $rules ? $request->validate($rules) : $request->all();

        // Check if there's any state dehydrator
        $data = $this->mutateFormDataBeforeSave($data, $formSchemaObj, 'create');

        // Hook: before create
        $data = $resource::mutateFormDataBeforeCreate($data);

        $record = $this->executeWithTransaction(function () use ($model, $data) {
            return $model::create($data);
        });

        // Hook: after create
        $resource::afterCreate($record, $data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index", [], 303)
            ->with('success', $resource::getLabel() . ' created successfully.');
    }

    // ── Edit ─────────────────────────────────────────────

    public function edit(string|int $id)
    {
        $resource   = static::$resource;
        $model      = $resource::getModel();
        $record     = $model::findOrFail($id);
        if (method_exists($resource, 'form')) {
            $form = $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make());
            $formSchema = [
                'type' => 'page',
                'title' => 'Edit ' . $resource::getLabel(),
                'components' => $form->toArray('edit'),
            ];
        } else {
            // Backward compatibility
            $formSchema = method_exists($resource, 'editSchema') ? $resource::editSchema()->toArray('edit') : $resource::formSchema()->toArray('edit');
        }
        $panel = app('vuelament.panel');

        $pages = $resource::getPages();
        $pageClass = $pages['edit'] ?? null;
        $view = 'Vuelament/Resource/Edit';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        return Inertia::render($view, [
            'resource'   => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema'    => $formSchema,
            'headerActions' => $this->resolveHeaderActions($resource, 'edit'),
            'record'        => $record,
            'panel'         => $panel->toArray(),
            'auth'          => ['user' => request()->user()],
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('edit', $record)),
        ]);
    }

    // ── Update ───────────────────────────────────────────

    public function update(Request $request, string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);
        $panelId  = app('vuelament.panel')->getId();

        $formSchemaObj = method_exists($resource, 'form') 
            ? $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make()) 
            : (method_exists($resource, 'editSchema') ? $resource::editSchema() : $resource::formSchema());

        // Auto-extract validation rules dari form components (pass record ID untuk unique ignore)
        $rules = $this->extractRulesFromSchema($formSchemaObj, $id, 'edit');
        $data  = $rules ? $request->validate($rules) : $request->all();

        // Check if there's any state dehydrator
        $data = $this->mutateFormDataBeforeSave($data, $formSchemaObj, 'edit');

        // Hook: before save
        $data = $resource::mutateFormDataBeforeSave($data);

        $this->executeWithTransaction(function () use ($record, $data) {
            $record->update($data);
        });

        // Hook: after save
        $resource::afterSave($record, $data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index", [], 303)
            ->with('success', $resource::getLabel() . ' updated successfully.');
    }

    // ── Update Column (Single Field / Toggle) ────────────

    public function updateColumn(Request $request, string|int $id)
    {
        $resource = static::$resource;
        $model = $resource::getModel();
        $record = $model::findOrFail($id);

        $column = $request->input('column');
        $value = $request->input('value');

        if (!$column) {
            abort(400, 'Name kolom diperlukan.');
        }

        $record->update([$column => $value]);

        return back(303);
    }

    // ── Destroy ──────────────────────────────────────────

    public function destroy(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);
        
        $this->executeWithTransaction(function () use ($record) {
            $record->delete();
        });

        return back(303)->with('success', $resource::getLabel() . ' deleted successfully.');
    }

    // ── Bulk Destroy ─────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);
        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::whereIn('id', $ids)->delete();
        });

        return back(303)->with('success', count($ids) . ' records deleted successfully.');
    }

    // ── Bulk Restore (soft delete) ──────────────────────

    public function bulkRestore(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);
        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::withTrashed()->whereIn('id', $ids)->restore();
        });

        return back(303)->with('success', count($ids) . ' records restored successfully.');
    }

    // ── Bulk Force Delete ───────────────────────────────

    public function bulkForceDelete(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);
        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::withTrashed()->whereIn('id', $ids)->forceDelete();
        });

        return back(303)->with('success', count($ids) . ' records permanently deleted successfully.');
    }

    // ── Restore (soft delete) ────────────────────────────

    public function restore(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::withTrashed()->findOrFail($id);
        
        $this->executeWithTransaction(function () use ($record) {
            $record->restore();
        });

        return back(303)->with('success', $resource::getLabel() . ' restored successfully.');
    }

    // ── Force Delete ─────────────────────────────────────

    public function forceDelete(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::withTrashed()->findOrFail($id);
        
        $this->executeWithTransaction(function () use ($record) {
            $record->forceDelete();
        });

        return back(303)->with('success', $resource::getLabel() . ' permanently deleted successfully.');
    }

    // ── Execute Row Action ───────────────────────────────

    public function executeAction(Request $request, string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::withTrashed()->findOrFail($id);
        
        $actionName = $request->input('action');
        $actionData = $request->input('data', []);

        // Find the action from Table component
        if (method_exists($resource, 'table')) {
            $tableComponent = $resource::table(\ChristYoga123\Vuelament\Components\Table\Table::make());
        } else {
            $tableSchema = $resource::tableSchema();
            $tableComponent = collect($tableSchema->getComponents())
                ->first(fn($c) => $c instanceof \ChristYoga123\Vuelament\Components\Table\Table);
        }

        if (!$tableComponent) {
            return back(303)->with('error', 'Table not found in schema.');
        }

        $action = collect($tableComponent->getActions())
            ->first(fn($a) => $a->getName() === $actionName);

        if (!$action) {
            return back(303)->with('error', "Action [{$actionName}] not found.");
        }

        // Validate form schema if present
        if ($action instanceof \ChristYoga123\Vuelament\Components\Table\Actions\Action && !empty($action->getFormComponents())) {
            $rules = [];
            $this->collectRulesFromComponents($action->getFormComponents(), $rules, null, 'create');
            if ($rules) {
                // Apply data.* to match the payload shape 'data.field'
                $mappedRules = [];
                foreach ($rules as $field => $fieldRules) {
                    $mappedRules["data.{$field}"] = $fieldRules;
                }
                $validated = $request->validate(['data' => 'array'] + $mappedRules);
                $actionData = $validated['data'] ?? [];
            }
        }

        if (method_exists($action, 'execute')) {
            $this->executeWithTransaction(function () use ($action, $record, $actionData) {
                $action->execute($record, $actionData);
            });
            return back(303)->with('success', $action->toArray()['label'] . ' executed successfully.');
        }

        return back(303);
    }

    // ── Resolve page-level header actions ────────────────

    /**
     * Resolve header actions dari page class Resource.
     * Membaca getPages() Resource, mencari page class untuk operation tertentu,
     * dan memanggil getHeaderActions() jika tersedia.
     *
     * Contoh: ListUsers::getHeaderActions() → [CreateAction::make()]
     */
    protected function resolveHeaderActions(string $resource, string $operation): array
    {
        $pages = $resource::getPages();
        $pageClass = $pages[$operation] ?? null;

        // Pastikan itu class string (bukan PageRegistration untuk custom pages)
        if (!$pageClass || !is_string($pageClass) || !class_exists($pageClass)) {
            return [];
        }

        if (method_exists($pageClass, 'getHeaderActions')) {
            return array_map(fn($a) => $a->toArray(), $pageClass::getHeaderActions());
        }

        return [];
    }

    // ── Private helpers ──────────────────────────────────

    protected function executeWithTransaction(\Closure $callback)
    {
        if (app('vuelament.panel')->hasDatabaseTransactions()) {
            return \Illuminate\Support\Facades\DB::transaction($callback);
        }

        return $callback();
    }

    protected function applySearch($query, string $search, string $resource)
    {
        if (method_exists($resource, 'table')) {
            $tableComponent = $resource::table(\ChristYoga123\Vuelament\Components\Table\Table::make())->toArray('index');
        } else {
            $tableSchema = $resource::tableSchema()->toArray();
            $tableComponent = collect($tableSchema['components'] ?? [])->firstWhere('type', 'table');
        }
        
        $searchableColumns = collect($tableComponent['columns'] ?? [])
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

    /**
     * Extract validation rules otomatis dari PageSchema -> form components
     * Mendukung layout nesting (Section, Grid, Card)
     *
     * @param mixed $recordId ID record saat edit (untuk unique ignore)
     *
     * Contoh hasil:
     *   ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email,5']]
     */
    protected function extractRulesFromSchema($pageSchema, mixed $recordId = null, string $operation = 'create'): array
    {
        $resource = static::$resource;
        $model = new ($resource::getModel());
        $tableName = $model->getTable();
        
        $rules = [];
        $components = $pageSchema->getComponents();
        $this->collectRulesFromComponents($components, $rules, $recordId, $operation, $tableName);
        return $rules;
    }

    protected function collectRulesFromComponents(array $components, array &$rules, mixed $recordId = null, string $operation = 'create', ?string $tableName = null): void
    {
        foreach ($components as $component) {
            // Jika ini form field -> extract rules
            if ($component instanceof BaseForm && $component->getName()) {
                $fieldRules = $component->getValidationRules($recordId, $operation, $tableName);
                if (!empty($fieldRules)) {
                    $rules[$component->getName()] = $fieldRules;
                }

                // Jika Repeater -> tambahkan juga nested rules (items.*.field)
                if ($component instanceof \ChristYoga123\Vuelament\Components\Form\Repeater) {
                    $nestedRules = $component->getNestedValidationRules($recordId, $operation, $tableName);
                    $rules = array_merge($rules, $nestedRules);
                }
            }

            // Jika layout (Grid, Section, Card) -> rekursi ke children
            if (method_exists($component, 'getComponents') && !$component instanceof BaseForm) {
                $this->collectRulesFromComponents($component->getComponents(), $rules, $recordId, $operation, $tableName);
            }
        }
    }

    protected function mutateFormDataBeforeSave(array $data, mixed $schema, string $operation): array
    {
        $flatComponents = [];
        $this->flattenComponents($schema->getComponents(), $flatComponents);

        foreach ($flatComponents as $component) {
            if ($component instanceof BaseForm && $component->getName()) {
                $name = $component->getName();
                
                if (!array_key_exists($name, $data)) {
                    // Check if saved but not present in data (e.g. unchecked toggle)
                    if ($component instanceof \ChristYoga123\Vuelament\Components\Form\Toggle) {
                        $data[$name] = false;
                    } else {
                        continue;
                    }
                }
                
                $state = $data[$name];

                // Check dehydrated / saved (skip saving if false)
                $isDehydrated = $component->getIsDehydrated();
                if ($isDehydrated instanceof \Closure) {
                    $isDehydrated = app()->call($isDehydrated, ['state' => $state, 'operation' => $operation]);
                }
                if ($isDehydrated === false) {
                    unset($data[$name]);
                    continue;
                }

                // Check dehydrateStateUsing
                $dehydrator = $component->getDehydrateStateUsing();
                if ($dehydrator instanceof \Closure) {
                    $data[$name] = app()->call($dehydrator, ['state' => $state, 'operation' => $operation]);
                }
            }
        }

        return $data;
    }

    protected function flattenComponents(array $components, array &$flat): void
    {
        foreach ($components as $component) {
            if ($component instanceof BaseForm) {
                $flat[] = $component;
            }
            if (method_exists($component, 'getComponents') && !$component instanceof BaseForm) {
                $this->flattenComponents($component->getComponents(), $flat);
            }
        }
    }
}
