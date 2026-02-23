<?php

namespace App\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeResourceCommand extends Command
{
    protected $signature = 'vuelament:resource {name : Nama resource (contoh: User)}
                            {--model= : Nama model (default: sama dengan nama resource)}
                            {--panel=Admin : Nama panel tujuan (default: Admin)}
                            {--generate : Auto-generate fields dari database/migration}
                            {--force : Overwrite jika sudah ada}';

    protected $description = 'Generate Vuelament resource class beserta controller-nya';

    public function handle(): int
    {
        $name  = Str::studly($this->argument('name'));
        $model = $this->option('model') ?: $name;
        $slug  = Str::kebab(Str::plural($name));
        $panel = $this->option('panel') ? Str::studly($this->option('panel')) : '';
        $generate = $this->option('generate');

        $columns = [];
        $hasSoftDeletes = $this->modelHasSoftDeletes($model);

        if ($generate) {
            $columns = $this->introspectModel($model);
            if (empty($columns)) {
                $this->warn("  ⚠ Tabel tidak ditemukan, generate fields default.");
            } else {
                $this->info("  📋 Ditemukan " . count($columns) . " kolom dari tabel.");
            }
            if ($hasSoftDeletes) {
                $this->info("  🗑️  SoftDeletes terdeteksi — menambahkan restore/force-delete.");
            }
        }

        $this->generateResource($name, $model, $slug, $panel, $columns, $hasSoftDeletes);
        $this->generateController($name, $panel);

        $this->info("✅ Resource [{$name}Resource] dan [{$name}Controller] berhasil dibuat!");
        $this->newLine();
        $this->line("Routes otomatis ter-register dari Panel config.");
        $this->newLine();
        $this->line("Langkah selanjutnya:");
        $namespacePrefix = $panel ? "{$panel}\\" : "";
        $pathPrefix      = $panel ? "{$panel}/" : "";

        $this->line("  Opsi 1 — Daftarkan di PanelProvider:");
        $this->line("    ->resources([\\App\\Vuelament\\{$namespacePrefix}Resources\\{$name}Resource::class])");
        $this->newLine();
        $this->line("  Opsi 2 — Auto-discover:");
        $this->line("    ->discoverResources(app_path('Vuelament/{$pathPrefix}Resources'), 'App\\Vuelament\\{$namespacePrefix}Resources')");
        $this->newLine();
        $this->line("  URL: /{panel-path}/{$slug}");

        return self::SUCCESS;
    }

    // ── Introspect model table ────────────────────────────

    protected function modelHasSoftDeletes(string $model): bool
    {
        $modelClass = "App\\Models\\{$model}";
        if (!class_exists($modelClass)) {
            return false;
        }
        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass));
    }

    protected function introspectModel(string $model): array
    {
        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            return [];
        }

        $instance = new $modelClass;
        $table    = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumns($table);
        $result  = [];

        // Kolom yang di-skip
        $skip = ['id', 'password', 'remember_token', 'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($columns as $col) {
            $colName = $col['name'];
            if (in_array($colName, $skip)) {
                continue;
            }

            $result[] = [
                'name'     => $colName,
                'type'     => $col['type_name'] ?? $col['type'] ?? 'varchar',
                'nullable' => $col['nullable'] ?? false,
            ];
        }

        return $result;
    }

    // ── Map column type to form component ─────────────────

    protected function mapColumnToFormComponent(array $col): string
    {
        $name  = $col['name'];
        $type  = strtolower($col['type']);
        $required = !$col['nullable'] ? '->required()' : '';

        $label = Str::headline(str_replace('_id', '', $name));

        // Foreign key → select (placeholder)
        if (Str::endsWith($name, '_id')) {
            $relation = Str::camel(str_replace('_id', '', $name));
            $relModel = Str::studly(str_replace('_id', '', $name));
            return "                V::select('{$name}')->label('{$label}')->options(\\App\\Models\\{$relModel}::pluck('name', 'id')->toArray()){$required},";
        }

        // Map by type
        return match (true) {
            str_contains($type, 'text')                     => "                V::textarea('{$name}')->label('{$label}'){$required},",
            str_contains($type, 'bool'), $type === 'tinyint' => "                V::toggle('{$name}')->label('{$label}'),",
            str_contains($type, 'date'), str_contains($type, 'timestamp') => "                V::datePicker('{$name}')->label('{$label}'){$required},",
            str_contains($type, 'int'), str_contains($type, 'decimal'), str_contains($type, 'float'), str_contains($type, 'double') => "                V::textInput('{$name}')->label('{$label}')->type('number'){$required},",
            str_contains($type, 'json')                     => "                V::textarea('{$name}')->label('{$label}'){$required},",
            $name === 'email'                                => "                V::textInput('{$name}')->label('{$label}')->type('email'){$required},",
            default                                          => "                V::textInput('{$name}')->label('{$label}'){$required},",
        };
    }

    // ── Map column type to table column ───────────────────

    protected function mapColumnToTableColumn(array $col): string
    {
        $name  = $col['name'];
        $type  = strtolower($col['type']);
        $label = Str::headline(str_replace('_id', '', $name));

        return match (true) {
            str_contains($type, 'date'), str_contains($type, 'timestamp') => "                        Column::make('{$name}')->label('{$label}')->dateFormat('d/m/Y')->sortable(),",
            str_contains($type, 'bool'), $type === 'tinyint'              => "                        Column::make('{$name}')->label('{$label}')->badge(),",
            default                                                        => "                        Column::make('{$name}')->label('{$label}')->sortable()->searchable(),",
        };
    }

    // ── Map column to validation rule ────────────────────

    protected function mapColumnToRule(array $col): string
    {
        $name  = $col['name'];
        $type  = strtolower($col['type']);
        $rules = [];

        if (!$col['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        match (true) {
            $name === 'email' => $rules[] = 'email',
            str_contains($type, 'int') => $rules[] = 'integer',
            str_contains($type, 'decimal'), str_contains($type, 'float'), str_contains($type, 'double') => $rules[] = 'numeric',
            str_contains($type, 'bool'), $type === 'tinyint' => $rules[] = 'boolean',
            str_contains($type, 'date'), str_contains($type, 'timestamp') => $rules[] = 'date',
            str_contains($type, 'json') => $rules[] = 'array',
            default => $rules[] = 'string|max:255',
        };

        $ruleStr = implode('|', $rules);
        return "            '{$name}' => '{$ruleStr}',";
    }

    // ── Generate resource ─────────────────────────────────

    protected function generateResource(string $name, string $model, string $slug, string $panel, array $columns = [], bool $hasSoftDeletes = false): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Vuelament/{$pathPrefix}Resources/{$name}Resource.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Resource [{$name}Resource] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";

        if (!empty($columns)) {
            $content = $this->buildGeneratedResource($name, $model, $slug, $namespacePrefix, $columns, $hasSoftDeletes);
        } else {
            $stub = $this->getResourceStub();
            $content = str_replace(
                ['{{ namespace }}', '{{ name }}', '{{ model }}', '{{ modelFqn }}', '{{ slug }}', '{{ label }}'],
                ["App\\Vuelament{$namespacePrefix}\\Resources", $name, $model, "App\\Models\\{$model}", $slug, Str::headline($name)],
                $stub
            );
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("  Created: app/Vuelament/{$pathPrefix}Resources/{$name}Resource.php");
    }

    protected function buildGeneratedResource(string $name, string $model, string $slug, string $namespacePrefix, array $columns, bool $hasSoftDeletes = false): string
    {
        $namespace = "App\\Vuelament{$namespacePrefix}\\Resources";
        $modelFqn  = "App\\Models\\{$model}";
        $label     = Str::headline($name);

        // Build table columns (include id + columns + created_at)
        $tableColumns = "                        Column::make('id')->label('ID')->sortable(),\n";
        foreach ($columns as $col) {
            $tableColumns .= $this->mapColumnToTableColumn($col) . "\n";
        }
        $tableColumns .= "                        Column::make('created_at')->label('Dibuat')->dateFormat('d/m/Y')->sortable(),";

        // Build form components
        $formComponents = '';
        foreach ($columns as $i => $col) {
            $formComponents .= $this->mapColumnToFormComponent($col);
            if ($i < count($columns) - 1) {
                $formComponents .= "\n";
            }
        }

        // Build rules
        $rules = '';
        foreach ($columns as $i => $col) {
            $rules .= $this->mapColumnToRule($col);
            if ($i < count($columns) - 1) {
                $rules .= "\n";
            }
        }

        // Build imports
        $imports = "use App\\Vuelament\\Core\\BaseResource;
use App\\Vuelament\\Core\\PageSchema;
use App\\Vuelament\\Facades\\V;
use App\\Vuelament\\Components\\Table\\Table;
use App\\Vuelament\\Components\\Table\\Column;
use App\\Vuelament\\Components\\Table\\Actions\\EditAction;
use App\\Vuelament\\Components\\Table\\Actions\\DeleteAction;
use App\\Vuelament\\Components\\Actions\\ActionGroup;
use App\\Vuelament\\Components\\Actions\\CreateAction;
use App\\Vuelament\\Components\\Actions\\DeleteBulkAction;";

        if ($hasSoftDeletes) {
            $imports .= "
use App\\Vuelament\\Components\\Table\\Actions\\RestoreAction;
use App\\Vuelament\\Components\\Table\\Actions\\ForceDeleteAction;
use App\\Vuelament\\Components\\Table\\Filters\\SelectFilter;";
        }

        // Build table actions
        $tableActions = "                        EditAction::make(),
                        DeleteAction::make(),";
        if ($hasSoftDeletes) {
            $tableActions .= "
                        RestoreAction::make(),
                        ForceDeleteAction::make(),";
        }

        // Build filters
        $filters = '';
        if ($hasSoftDeletes) {
            $filters = "
                    ->filters([
                        SelectFilter::make('trashed')
                            ->label('Status')
                            ->options([
                                ''          => 'Tanpa Trashed',
                                'with'      => 'Dengan Trashed',
                                'only'      => 'Hanya Trashed',
                            ]),
                    ])";
        }

        // Build softDeletes property
        $softDeleteProp = $hasSoftDeletes ? "
    protected static bool \$softDeletes = true;" : '';

        return <<<PHP
<?php

namespace {$namespace};

{$imports}

class {$name}Resource extends BaseResource
{
    protected static string \$model = '{$modelFqn}';
    protected static string \$slug = '{$slug}';
    protected static string \$label = '{$label}';
    protected static string \$icon = 'circle';{$softDeleteProp}

    // ── Navigation ───────────────────────────────────────
    protected static int \$navigationSort = 0;
    // protected static ?string \$navigationGroup = 'Master Data';

    public static function tableSchema(): PageSchema
    {
        return PageSchema::make()
            ->title(static::\$label)
            ->components([
                Table::make()
                    ->columns([
{$tableColumns}
                    ])
                    ->actions([
{$tableActions}
                    ])
                    ->bulkActions([
                        ActionGroup::make('Aksi Massal')
                            ->icon('list')
                            ->actions([
                                DeleteBulkAction::make(),
                            ]),
                    ])
                    ->headerActions([
                        CreateAction::make(),
                    ]){$filters}
                    ->searchable()
                    ->paginated()
                    ->selectable(),
            ]);
    }

    public static function formSchema(): PageSchema
    {
        return PageSchema::make()
            ->title('Buat ' . static::\$label)
            ->components([
{$formComponents}
            ]);
    }

    public static function rules(string \$action, mixed \$id = null): array
    {
        return [
{$rules}
        ];
    }
}
PHP;
    }

    protected function generateController(string $name, string $panel): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Http/Controllers/Vuelament/{$pathPrefix}{$name}Controller.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Controller [{$name}Controller] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";

        $stub = $this->getControllerStub();
        $stub = str_replace(
            ['{{ namespace }}', '{{ resourceNamespace }}', '{{ name }}'],
            ["App\\Http\\Controllers\\Vuelament{$namespacePrefix}", "App\\Vuelament{$namespacePrefix}\\Resources", $name],
            $stub
        );

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);
        $this->info("  Created: app/Http/Controllers/Vuelament/{$pathPrefix}{$name}Controller.php");
    }

    protected function getResourceStub(): string
    {
        return file_get_contents(app_path('Vuelament/Stubs/resource.stub'));
    }

    protected function getControllerStub(): string
    {
        return file_get_contents(app_path('Vuelament/Stubs/controller.stub'));
    }
}
