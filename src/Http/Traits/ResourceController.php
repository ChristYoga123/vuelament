<?php

namespace ChristYoga123\Vuelament\Http\Traits;

use ChristYoga123\Vuelament\Components\Form\BaseForm;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ResourceController trait — connects BaseResource to Inertia + Eloquent
 *
 * [FIX] getResourceClass() menggantikan static::$resource langsung
 *       agar aman di Octane (tidak ada static property race condition).
 *
 * Usage:
 *   class UserController extends Controller {
 *       use ResourceController;
 *       protected static string $resource = UserResource::class;
 *   }
 */
trait ResourceController
{
    /**
     * [FIX] Gunakan method ini (bukan static::$resource langsung) di semua method.
     * ResourceRouteController meng-override ini dengan instance property.
     * Controller biasa tetap menggunakan static::$resource via late-static binding.
     */
    protected function getResourceClass(): string
    {
        return static::$resource;
    }

    /**
     * [FIX] Return hanya field aman dari user model ke Inertia.
     * Cegah seluruh model (beserta field sensitif) terekspos ke frontend.
     */
    protected function safeAuthUser(): ?array
    {
        $user = request()->user();
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

    // ── Index (list + table) ─────────────────────────────

    public function index(Request $request)
    {
        $resource = $this->getResourceClass();

        // [FIX] Authorization check
        if (!$resource::canViewAny()) {
            abort(403, 'Unauthorized action.');
        }

        $query          = $resource::getQuery();
        $tableComponent = $this->resolveTable($resource);

        // Custom query dari Table jika disediakan
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

        // [FIX] Sort — validate against allowed sortable columns to prevent info disclosure
        $allowedSortColumns = collect($tableComponent ? $tableComponent->getColumns() : [])
            ->filter(fn($col) => $col->toArray()['sortable'] ?? false)
            ->map(fn($col) => $col->getName())
            ->toArray();

        $sortField = in_array($request->input('sort'), $allowedSortColumns, true)
            ? $request->input('sort')
            : 'id';

        $sortDir = in_array(strtolower($request->input('direction', 'desc')), ['asc', 'desc'], true)
            ? $request->input('direction', 'desc')
            : 'desc';

        $query->orderBy($sortField, $sortDir);

        // [FIX] Paginate — cap per_page 1–100 untuk cegah DoS
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $data    = $query->paginate($perPage)->withQueryString();

        // Evaluate Table Actions & Columns per record
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
                        $params = ['record' => $record, 'state' => $val];
                        if (is_object($record)) {
                            $params[get_class($record)] = $record;
                        }
                        $val = app()->call($col->getGetStateUsing(), $params);
                        $record->{$col->getName()} = $val;
                    }

                    $formatted = $val;
                    if ($col->getFormatStateUsing()) {
                        $params = ['record' => $record, 'state' => $val];
                        if (is_object($record)) {
                            $params[get_class($record)] = $record;
                        }
                        $formatted = app()->call($col->getFormatStateUsing(), $params);
                    }

                    $vColumns[$col->getName()] = [
                        'formatted' => $formatted,
                        'color'     => $col->evaluateColor($record, $val),
                    ];
                }
                $record->setAttribute('_v_columns', $vColumns);

                return $record;
            });
        }

        if (method_exists($resource, 'table')) {
            $tableSchema = [
                'type'       => 'page',
                'title'      => $resource::getLabel(),
                'components' => [$tableComponent->toArray('index')],
            ];
        } else {
            $tableSchema = $resource::tableSchema()->toArray('index');
        }

        $panel = app('vuelament.panel');

        $pages     = $resource::getPages();
        $pageClass = $pages['index'] ?? null;
        $view      = 'Vuelament/Resource/Index';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        $formSchema = null;
        if ($view === 'Vuelament/Resource/Manage' ||
            ($pageClass && is_subclass_of($pageClass, \ChristYoga123\Vuelament\Core\Pages\ManageRecords::class))) {
            if (method_exists($resource, 'form')) {
                $form       = $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make());
                $formSchema = [
                    'type'       => 'page',
                    'title'      => 'Manage ' . $resource::getLabel(),
                    'components' => $form->toArray('manage'),
                ];
            } else {
                $formSchema = method_exists($resource, 'formSchema')
                    ? $resource::formSchema()->toArray('manage')
                    : null;
            }
        }

        return Inertia::render($view, [
            'resource'      => [
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
            'auth'          => ['user' => $this->safeAuthUser()],  // [FIX] only safe fields
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('index')),
        ]);
    }

    // ── Create ───────────────────────────────────────────

    public function create()
    {
        $resource = $this->getResourceClass();

        // [FIX] Authorization check
        if (!$resource::canCreate()) {
            abort(403, 'Unauthorized action.');
        }

        $formSchemaObj = $this->resolveFormSchema($resource, 'create');

        if (method_exists($resource, 'form')) {
            $formSchema = [
                'type'       => 'page',
                'title'      => 'Create ' . $resource::getLabel(),
                'components' => $formSchemaObj->toArray('create'),
            ];
        } else {
            $formSchema = $formSchemaObj->toArray('create');
        }

        $panel     = app('vuelament.panel');
        $pages     = $resource::getPages();
        $pageClass = $pages['create'] ?? null;
        $view      = 'Vuelament/Resource/Create';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        return Inertia::render($view, [
            'resource'      => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema'    => $formSchema,
            'headerActions' => $this->resolveHeaderActions($resource, 'create'),
            'panel'         => $panel->toArray(),
            'auth'          => ['user' => $this->safeAuthUser()],  // [FIX]
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('create')),
        ]);
    }

    // ── Store ────────────────────────────────────────────

    public function store(Request $request)
    {
        $resource = $this->getResourceClass();

        // [FIX] Authorization check
        if (!$resource::canCreate()) {
            abort(403, 'Unauthorized action.');
        }

        $model   = $resource::getModel();
        $panelId = app('vuelament.panel')->getId();

        $formSchemaObj = $this->resolveFormSchema($resource, 'create');

        $rules = $this->extractRulesFromSchema($formSchemaObj, null, 'create');
        $data  = $rules
            ? $request->validate($rules)
            : $request->only($this->extractFieldNames($formSchemaObj));

        $data   = $this->mutateFormDataBeforeSave($data, $formSchemaObj, 'create');
        $data   = $resource::mutateFormDataBeforeCreate($data);

        $record = $this->executeWithTransaction(function () use ($model, $data) {
            return $model::create($data);
        });

        $resource::afterCreate($record, $data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index", [], 303)
            ->with('success', $resource::getLabel() . ' created successfully.');
    }

    // ── Edit ─────────────────────────────────────────────

    public function edit(string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canView($record)) {
            abort(403, 'Unauthorized action.');
        }

        $formSchemaObj = $this->resolveFormSchema($resource, 'edit', $id);

        if (method_exists($resource, 'form')) {
            $formSchema = [
                'type'       => 'page',
                'title'      => 'Edit ' . $resource::getLabel(),
                'components' => $formSchemaObj->toArray('edit'),
            ];
        } else {
            $formSchema = $formSchemaObj->toArray('edit');
        }

        $panel     = app('vuelament.panel');
        $pages     = $resource::getPages();
        $pageClass = $pages['edit'] ?? null;
        $view      = 'Vuelament/Resource/Edit';
        if ($pageClass && method_exists($pageClass, 'getView')) {
            $view = $pageClass::getView() ?: $view;
        }

        return Inertia::render($view, [
            'resource'      => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema'    => $formSchema,
            'headerActions' => $this->resolveHeaderActions($resource, 'edit'),
            'record'        => $record,
            'panel'         => $panel->toArray(),
            'auth'          => ['user' => $this->safeAuthUser()],  // [FIX]
            'breadcrumbs'   => $this->formatBreadcrumbs($this->getBreadcrumbs('edit', $record)),
        ]);
    }

    // ── Update ───────────────────────────────────────────

    public function update(Request $request, string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canEdit($record)) {
            abort(403, 'Unauthorized action.');
        }

        $panelId       = app('vuelament.panel')->getId();
        $formSchemaObj = $this->resolveFormSchema($resource, 'edit', $id);

        $rules = $this->extractRulesFromSchema($formSchemaObj, $id, 'edit');
        $data  = $rules
            ? $request->validate($rules)
            : $request->only($this->extractFieldNames($formSchemaObj));

        $data = $this->mutateFormDataBeforeSave($data, $formSchemaObj, 'edit');
        $data = $resource::mutateFormDataBeforeSave($data);

        $this->executeWithTransaction(function () use ($record, $data) {
            $record->update($data);
        });

        $resource::afterSave($record, $data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index", [], 303)
            ->with('success', $resource::getLabel() . ' updated successfully.');
    }

    // ── Update Column (Single Field / Toggle) ────────────

    public function updateColumn(Request $request, string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canEdit($record)) {
            abort(403, 'Unauthorized action.');
        }

        $column = $request->input('column');
        $value  = $request->input('value');

        if (!$column) {
            abort(400, 'Column name is required.');
        }

        $tableComponent = $this->resolveTable($resource);

        // [FIX] Whitelist: hanya kolom bertipe 'toggle' yang boleh di-update
        $allowedColumns = collect($tableComponent?->getColumns() ?? [])
            ->filter(fn($col) => ($col->toArray()['type'] ?? '') === 'toggle'
                || ($col->toArray()['isToggle'] ?? false) === true)
            ->map(fn($col) => $col->getName())
            ->toArray();

        if (!in_array($column, $allowedColumns, true)) {
            abort(403, 'Column is not editable.');
        }

        $this->executeWithTransaction(function () use ($record, $column, $value) {
            $record->update([$column => $value]);
        });

        return back(303);
    }

    // ── Destroy ──────────────────────────────────────────

    public function destroy(string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canDelete($record)) {
            abort(403, 'Unauthorized action.');
        }

        $this->executeWithTransaction(function () use ($record) {
            $record->delete();
        });

        return back(303)->with('success', $resource::getLabel() . ' deleted successfully.');
    }

    // ── Bulk Destroy ─────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        // [FIX] Validasi IDs: harus array integer, maks 500 item
        $request->validate([
            'ids'   => 'required|array|max:500',
            'ids.*' => 'integer|min:1',
        ]);

        $resource = $this->getResourceClass();

        // [FIX] Authorization check
        if (!$resource::canDelete(null)) {
            abort(403, 'Unauthorized action.');
        }

        $model = $resource::getModel();
        $ids   = $request->input('ids', []);

        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::whereIn('id', $ids)->delete();
        });

        return back(303)->with('success', count($ids) . ' records deleted successfully.');
    }

    // ── Bulk Restore (soft delete) ──────────────────────

    public function bulkRestore(Request $request)
    {
        // [FIX] Validasi IDs
        $request->validate([
            'ids'   => 'required|array|max:500',
            'ids.*' => 'integer|min:1',
        ]);

        $resource = $this->getResourceClass();
        $model    = $resource::getModel();

        if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return back(303)->with('error', 'This resource does not support soft deletes.');
        }

        // [FIX] Authorization check
        if (!$resource::canRestore(null)) {
            abort(403, 'Unauthorized action.');
        }

        $ids = $request->input('ids', []);

        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::withTrashed()->whereIn('id', $ids)->restore();
        });

        return back(303)->with('success', count($ids) . ' records restored successfully.');
    }

    // ── Bulk Force Delete ───────────────────────────────

    public function bulkForceDelete(Request $request)
    {
        // [FIX] Validasi IDs
        $request->validate([
            'ids'   => 'required|array|max:500',
            'ids.*' => 'integer|min:1',
        ]);

        $resource = $this->getResourceClass();
        $model    = $resource::getModel();

        if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return back(303)->with('error', 'This resource does not support soft deletes.');
        }

        // [FIX] Authorization check
        if (!$resource::canForceDelete(null)) {
            abort(403, 'Unauthorized action.');
        }

        $ids = $request->input('ids', []);

        $this->executeWithTransaction(function () use ($model, $ids) {
            $model::withTrashed()->whereIn('id', $ids)->forceDelete();
        });

        return back(303)->with('success', count($ids) . ' records permanently deleted successfully.');
    }

    // ── Restore (soft delete) ────────────────────────────

    public function restore(string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();

        if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return back(303)->with('error', 'This resource does not support soft deletes.');
        }

        $record = $model::withTrashed()->findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canRestore($record)) {
            abort(403, 'Unauthorized action.');
        }

        $this->executeWithTransaction(function () use ($record) {
            $record->restore();
        });

        return back(303)->with('success', $resource::getLabel() . ' restored successfully.');
    }

    // ── Force Delete ─────────────────────────────────────

    public function forceDelete(string|int $id)
    {
        $resource = $this->getResourceClass();
        $model    = $resource::getModel();

        if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return back(303)->with('error', 'This resource does not support soft deletes.');
        }

        $record = $model::withTrashed()->findOrFail($id);

        // [FIX] Authorization check
        if (!$resource::canForceDelete($record)) {
            abort(403, 'Unauthorized action.');
        }

        $this->executeWithTransaction(function () use ($record) {
            $record->forceDelete();
        });

        return back(303)->with('success', $resource::getLabel() . ' permanently deleted successfully.');
    }

    // ── Execute Row Action ───────────────────────────────

    public function executeAction(Request $request, string|int $id)
    {
        $resource        = $this->getResourceClass();
        $model           = $resource::getModel();
        $usesSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
        $record          = $usesSoftDeletes ? $model::withTrashed()->findOrFail($id) : $model::findOrFail($id);

        $actionName = $request->input('action');
        $actionData = $request->input('data', []);

        $tableComponent = $this->resolveTable($resource);

        if (!$tableComponent) {
            return back(303)->with('error', 'Table not found in schema.');
        }

        $action = collect($tableComponent->getActions())
            ->first(fn($a) => $a->getName() === $actionName);

        if (!$action) {
            return back(303)->with('error', "Action [{$actionName}] not found.");
        }

        // Validate form schema if present
        if ($action instanceof \ChristYoga123\Vuelament\Components\Table\Actions\Action &&
            !empty($action->getFormComponents())) {
            $rules = [];
            $this->collectRulesFromComponents($action->getFormComponents(), $rules, null, 'create');
            if ($rules) {
                $mappedRules = [];
                foreach ($rules as $field => $fieldRules) {
                    $mappedRules["data.{$field}"] = $fieldRules;
                }
                $validated  = $request->validate(['data' => 'array'] + $mappedRules);
                $actionData = $validated['data'] ?? [];
            }
        }

        if (method_exists($action, 'execute')) {
            try {
                $this->executeWithTransaction(function () use ($action, $record, $actionData) {
                    $action->execute($record, $actionData);
                });
            } catch (\Throwable $e) {
                report($e);
                return back(303)->with('error', 'Action failed: ' . $e->getMessage());
            }

            $hasCustomNotification = !empty(session()->get('_vuelament_notifications', []));
            if (!$hasCustomNotification) {
                return back(303)->with('success', $action->toArray()['label'] . ' executed successfully.');
            }

            return back(303);
        }

        return back(303);
    }

    // ── Getters / Configurations ─────────────────────────

    public function getBreadcrumbs(string $operation, mixed $record = null): array
    {
        $resource = $this->getResourceClass();
        $panel    = app('vuelament.panel');
        $base     = ['/' . $panel->getPath() => 'Dashboard'];

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
                'url'   => is_numeric($url) || empty($url) ? null : $url,
                'label' => $label,
            ];
        }
        return array_values(array_filter($bcArray));
    }

    // ── Resolve page-level header actions ────────────────

    protected function resolveHeaderActions(string $resource, string $operation): array
    {
        $pages     = $resource::getPages();
        $pageClass = $pages[$operation] ?? null;

        if (!$pageClass || !is_string($pageClass) || !class_exists($pageClass)) {
            return [];
        }

        if (method_exists($pageClass, 'getHeaderActions')) {
            return array_map(fn($a) => $a->toArray(), $pageClass::getHeaderActions());
        }

        return [];
    }

    // ── Private helpers ──────────────────────────────────

    protected function resolveTable(string $resource): ?\ChristYoga123\Vuelament\Components\Table\Table
    {
        if (method_exists($resource, 'table')) {
            return $resource::table(\ChristYoga123\Vuelament\Components\Table\Table::make());
        }

        $schema = $resource::tableSchema();
        foreach ($schema->getComponents() as $comp) {
            if ($comp instanceof \ChristYoga123\Vuelament\Components\Table\Table) {
                return $comp;
            }
        }

        return null;
    }

    protected function resolveFormSchema(string $resource, string $operation = 'create', mixed $recordId = null): mixed
    {
        if (method_exists($resource, 'form')) {
            return $resource::form(\ChristYoga123\Vuelament\Components\Form\Form::make());
        }

        if ($operation === 'edit' && method_exists($resource, 'editSchema')) {
            return $resource::editSchema();
        }

        return $resource::formSchema();
    }

    protected function executeWithTransaction(\Closure $callback): mixed
    {
        if (app('vuelament.panel')->hasDatabaseTransactions()) {
            return \Illuminate\Support\Facades\DB::transaction($callback);
        }

        return $callback();
    }

    protected function applySearch($query, string $search, string $resource)
    {
        $table    = $this->resolveTable($resource);
        $tableArr = $table ? $table->toArray('index') : [];

        $searchableColumns = collect($tableArr['columns'] ?? [])
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

    /**
     * Extract validation rules from PageSchema -> form components.
     * [FIX] Menggunakan getResourceClass() bukan static::$resource
     */
    protected function extractRulesFromSchema($pageSchema, mixed $recordId = null, string $operation = 'create'): array
    {
        $resource  = $this->getResourceClass();
        $model     = new ($resource::getModel());
        $tableName = $model->getTable();

        $rules      = [];
        $components = $pageSchema->getComponents();
        $this->collectRulesFromComponents($components, $rules, $recordId, $operation, $tableName);

        return $rules;
    }

    protected function collectRulesFromComponents(
        array   $components,
        array   &$rules,
        mixed   $recordId  = null,
        string  $operation = 'create',
        ?string $tableName = null
    ): void {
        foreach ($components as $component) {
            if ($component instanceof BaseForm && $component->getName()) {
                $fieldRules = $component->getValidationRules($recordId, $operation, $tableName);
                if (!empty($fieldRules)) {
                    $rules[$component->getName()] = $fieldRules;
                }

                if ($component instanceof \ChristYoga123\Vuelament\Components\Form\Repeater) {
                    $nestedRules = $component->getNestedValidationRules($recordId, $operation, $tableName);
                    $rules       = array_merge($rules, $nestedRules);
                }
            }

            if (method_exists($component, 'getComponents') && !$component instanceof BaseForm) {
                $this->collectRulesFromComponents(
                    $component->getComponents(),
                    $rules,
                    $recordId,
                    $operation,
                    $tableName
                );
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
                    if ($component instanceof \ChristYoga123\Vuelament\Components\Form\Toggle) {
                        $data[$name] = false;
                    } else {
                        continue;
                    }
                }

                $state = $data[$name];

                $isDehydrated = $component->getIsDehydrated();
                if ($isDehydrated instanceof \Closure) {
                    $isDehydrated = app()->call($isDehydrated, ['state' => $state, 'operation' => $operation]);
                }
                if ($isDehydrated === false) {
                    unset($data[$name]);
                    continue;
                }

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

    protected function extractFieldNames(mixed $schema): array
    {
        $flat = [];
        $this->flattenComponents($schema->getComponents(), $flat);

        return collect($flat)
            ->map(fn($c) => $c->getName())
            ->filter()
            ->values()
            ->toArray();
    }
}
