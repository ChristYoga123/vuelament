<?php

namespace App\Vuelament\Http\Traits;

use App\Vuelament\Components\Form\BaseForm;
use App\Vuelament\Components\Layout\Grid;
use App\Vuelament\Components\Layout\Section;
use App\Vuelament\Components\Layout\Card;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ResourceController trait — the engine yang menghubungkan BaseResource ke Inertia + Eloquent
 *
 * Cara pakai:
 *   class UserController extends Controller {
 *       use ResourceController;
 *       protected static string $resource = UserResource::class;
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
        $pageSchema  = $resource::tableSchema();

        // Cari komponen Table di dalam schema
        $tableComponent = null;
        foreach ($pageSchema->getComponents() as $comp) {
            if ($comp instanceof \App\Vuelament\Components\Table\Table) {
                $tableComponent = $comp;
                break;
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
            $query = $resource::applyFilters($query, $filters);
        }

        // Apply sort
        $sortField = $request->input('sort', 'id');
        $sortDir   = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Paginate
        $perPage = $request->input('per_page', 10);
        $data    = $query->paginate($perPage)->withQueryString();

        $tableSchema = $pageSchema->toArray('index');
        $panel = app('vuelament.panel');

        return Inertia::render('Vuelament/Resource/Index', [
            'resource'    => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
                'icon'  => $resource::getIcon(),
            ],
            'tableSchema' => $tableSchema,
            'data'        => $data,
            'filters'     => $request->only(['search', 'filters', 'sort', 'direction', 'per_page']),
            'panel'       => $panel->toArray(),
            'auth'        => ['user' => $request->user()],
        ]);
    }

    // ── Create ───────────────────────────────────────────

    public function create()
    {
        $resource   = static::$resource;
        $formSchema = $resource::formSchema()->toArray('create');
        $panel = app('vuelament.panel');

        return Inertia::render('Vuelament/Resource/Create', [
            'resource'   => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema' => $formSchema,
            'panel'      => $panel->toArray(),
            'auth'       => ['user' => request()->user()],
        ]);
    }

    // ── Store ────────────────────────────────────────────

    public function store(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $panelId  = app('vuelament.panel')->getId();

        // Auto-extract validation rules dari form components
        $rules = $this->extractRulesFromSchema($resource::formSchema(), null, 'create');
        $data  = $rules ? $request->validate($rules) : $request->all();

        // Check if there's any state dehydrator
        $data = $this->mutateFormDataBeforeSave($data, $resource::formSchema(), 'create');

        // Hook: before save
        $data = $resource::beforeSave($data, 'create');

        // Create
        $record = $model::create($data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index")
            ->with('success', $resource::getLabel() . ' berhasil dibuat.');
    }

    // ── Edit ─────────────────────────────────────────────

    public function edit(string|int $id)
    {
        $resource   = static::$resource;
        $model      = $resource::getModel();
        $record     = $model::findOrFail($id);
        $formSchema = $resource::editSchema()->toArray('edit');
        $panel = app('vuelament.panel');

        return Inertia::render('Vuelament/Resource/Edit', [
            'resource'   => [
                'slug'  => $resource::getSlug(),
                'label' => $resource::getLabel(),
            ],
            'formSchema' => $formSchema,
            'record'     => $record,
            'panel'      => $panel->toArray(),
            'auth'       => ['user' => request()->user()],
        ]);
    }

    // ── Update ───────────────────────────────────────────

    public function update(Request $request, string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);
        $panelId  = app('vuelament.panel')->getId();

        // Auto-extract validation rules dari form components (pass record ID untuk unique ignore)
        $rules = $this->extractRulesFromSchema($resource::editSchema(), $id, 'edit');
        $data  = $rules ? $request->validate($rules) : $request->all();

        // Check if there's any state dehydrator
        $data = $this->mutateFormDataBeforeSave($data, $resource::editSchema(), 'edit');

        // Hook: before save
        $data = $resource::beforeSave($data, 'edit');

        // Update
        $record->update($data);

        return redirect()
            ->route("{$panelId}.{$resource::getSlug()}.index")
            ->with('success', $resource::getLabel() . ' berhasil diupdate.');
    }

    // ── Destroy ──────────────────────────────────────────

    public function destroy(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::findOrFail($id);
        $record->delete();

        return back()->with('success', $resource::getLabel() . ' berhasil dihapus.');
    }

    // ── Bulk Destroy ─────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);

        $model::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' data berhasil dihapus.');
    }

    // ── Bulk Restore (soft delete) ──────────────────────

    public function bulkRestore(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);

        $model::withTrashed()->whereIn('id', $ids)->restore();

        return back()->with('success', count($ids) . ' data berhasil direstore.');
    }

    // ── Bulk Force Delete ───────────────────────────────

    public function bulkForceDelete(Request $request)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $ids      = $request->input('ids', []);

        $model::withTrashed()->whereIn('id', $ids)->forceDelete();

        return back()->with('success', count($ids) . ' data berhasil dihapus permanen.');
    }

    // ── Restore (soft delete) ────────────────────────────

    public function restore(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::withTrashed()->findOrFail($id);
        $record->restore();

        return back()->with('success', $resource::getLabel() . ' berhasil direstore.');
    }

    // ── Force Delete ─────────────────────────────────────

    public function forceDelete(string|int $id)
    {
        $resource = static::$resource;
        $model    = $resource::getModel();
        $record   = $model::withTrashed()->findOrFail($id);
        $record->forceDelete();

        return back()->with('success', $resource::getLabel() . ' berhasil dihapus permanen.');
    }

    // ── Private helpers ──────────────────────────────────

    protected function applySearch($query, string $search, string $resource)
    {
        $tableSchema = $resource::tableSchema()->toArray();
        
        $tableComponent = collect($tableSchema['components'] ?? [])->firstWhere('type', 'table');
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
                if ($component instanceof \App\Vuelament\Components\Form\Repeater) {
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

    protected function mutateFormDataBeforeSave(array $data, \App\Vuelament\Core\PageSchema $schema, string $operation): array
    {
        $flatComponents = [];
        $this->flattenComponents($schema->getComponents(), $flatComponents);

        foreach ($flatComponents as $component) {
            if ($component instanceof BaseForm && $component->getName()) {
                $name = $component->getName();
                
                if (!array_key_exists($name, $data)) {
                    // check if saved but not present in data (maybe unchecked toggle). if missing completely, keep going.
                    continue;
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
