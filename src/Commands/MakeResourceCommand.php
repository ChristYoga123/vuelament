<?php

namespace ChristYoga123\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeResourceCommand extends Command
{
    protected $signature = 'vuelament:resource {name : Name resource (contoh: Product)}
                            {--model= : Name model (default: sama with nama resource)}
                            {--panel=Admin : Name panel tujuan (default: Admin)}
                            {--simple : Generate ManageXxx (single mode — create/edit in modal)}
                            {--generate : Auto-generate fields dari database/migration}
                            {--force : Overwrite jika sudah ada}';

    protected $description = 'Generate Vuelament resource module (panel-first, module-based)';

    public function handle(): int
    {
        $name      = Str::studly($this->argument('name'));
        $model     = $this->option('model') ?: $name;
        $slug      = Str::kebab(Str::plural($name));
        $panel     = $this->option('panel') ? Str::studly($this->option('panel')) : '';
        $isSimple  = $this->option('simple');
        $generate  = $this->option('generate');

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

        // ── Generate module structure ──────────────────────
        // app/Vuelament/{Panel}/{Model}/
        //    ├─ Resources/
        //    ├─ Pages/
        //    ├─ Services/
        //    ├─ Widgets/
        //    └─ {Model}Resource.php

        $this->generateResource($name, $model, $slug, $panel, $columns, $hasSoftDeletes, $isSimple);
        $this->generateService($name, $panel);
        $this->generateResourcePages($name, $panel, $isSimple);
        $this->createDirectories($name, $panel);

        $pathPrefix = $panel ? "{$panel}/" : "";
        $namespacePrefix = $panel ? "{$panel}\\" : "";

        $this->newLine();
        $this->info("✅ Module [{$name}] created successfully!");
        $this->newLine();

        $mode = $isSimple ? 'single (ManageXxx — modal create/edit)' : 'multi-page (List/Create/Edit)';
        $this->line("  Mode: {$mode}");
        $this->newLine();

        $this->line("  📁 Structure:");
        $this->line("     app/Vuelament/{$pathPrefix}{$name}/");
        $this->line("       ├─ Resources/");
        if ($isSimple) {
            $this->line("       │    └─ Manage{$name}s.php");
        } else {
            $this->line("       │    ├─ List{$name}s.php");
            $this->line("       │    ├─ Create{$name}.php");
            $this->line("       │    └─ Edit{$name}.php");
        }
        $this->line("       ├─ Pages/");
        $this->line("       ├─ Services/");
        $this->line("       │    └─ {$name}Service.php");
        $this->line("       ├─ Widgets/");
        $this->line("       └─ {$name}Resource.php");

        $this->newLine();
        $this->line("  Route: /{panel-path}/{$slug}");
        $this->newLine();
        $this->line("  Register in PanelProvider:");
        $this->line("    ->discoverResources(app_path('Vuelament/{$pathPrefix}'), 'App\\Vuelament\\{$namespacePrefix}')");

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

        if (Str::endsWith($name, '_id')) {
            $relation = Str::camel(str_replace('_id', '', $name));
            $relModel = Str::studly(str_replace('_id', '', $name));
            return "                Select::make('{$name}')->label('{$label}')->options(\\App\\Models\\{$relModel}::pluck('name', 'id')->toArray()){$required},";
        }

        return match (true) {
            str_contains($type, 'text')                     => "                Textarea::make('{$name}')->label('{$label}'){$required},",
            str_contains($type, 'bool'), $type === 'tinyint' => "                Toggle::make('{$name}')->label('{$label}'),",
            str_contains($type, 'date'), str_contains($type, 'timestamp') => "                DatePicker::make('{$name}')->label('{$label}'){$required},",
            str_contains($type, 'int'), str_contains($type, 'decimal'), str_contains($type, 'float'), str_contains($type, 'double') => "                TextInput::make('{$name}')->label('{$label}')->type('number'){$required},",
            str_contains($type, 'json')                     => "                Textarea::make('{$name}')->label('{$label}'){$required},",
            $name === 'email'                                => "                TextInput::make('{$name}')->label('{$label}')->type('email'){$required},",
            default                                          => "                TextInput::make('{$name}')->label('{$label}'){$required},",
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
            str_contains($type, 'bool'), $type === 'tinyint'              => "                        ToggleColumn::make('{$name}')->label('{$label}'),",
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
    // New: app/Vuelament/{Panel}/{Model}/{Model}Resource.php

    protected function generateResource(string $name, string $model, string $slug, string $panel, array $columns = [], bool $hasSoftDeletes = false, bool $isSimple = false): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Vuelament/{$pathPrefix}{$name}/{$name}Resource.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Resource [{$name}Resource] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";
        $plural = Str::plural($name);

        if (!empty($columns)) {
            $content = $this->buildGeneratedResource($name, $model, $slug, $namespacePrefix, $columns, $hasSoftDeletes, $isSimple);
        } else {
            $stub = $this->getResourceStub();
            $namespace = "App\\Vuelament{$namespacePrefix}\\{$name}";

            if ($isSimple) {
                // If simple, replace the getPages method block in stub
                $pagesBlock = <<<PHP
    public static function getPages(): array
    {
        return [
            'index' => Resources\Manage{$plural}::class,
        ];
    }
PHP;
                // find where getPages is in the stub and replace it
                $stub = preg_replace('/public static function getPages\(\): array\s*\{\s*return \[[^\]]*\];\s*\}/s', $pagesBlock, $stub);
            }

            $content = str_replace(
                ['{{ namespace }}', '{{ name }}', '{{ model }}', '{{ modelFqn }}', '{{ slug }}', '{{ label }}', '{{ plural }}'],
                [$namespace, $name, $model, "App\\Models\\{$model}", $slug, Str::headline($name), $plural],
                $stub
            );
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("  Created: app/Vuelament/{$pathPrefix}{$name}/{$name}Resource.php");
    }

    protected function buildGeneratedResource(string $name, string $model, string $slug, string $namespacePrefix, array $columns, bool $hasSoftDeletes = false, bool $isSimple = false): string
    {
        $namespace = "App\\Vuelament{$namespacePrefix}\\{$name}";
        $modelFqn  = "App\\Models\\{$model}";
        $label     = Str::headline($name);
        $plural    = Str::plural($name);

        // Build table columns
        $tableColumns = "                        Column::make('id')->label('ID')->sortable(),\n";
        foreach ($columns as $col) {
            $tableColumns .= $this->mapColumnToTableColumn($col) . "\n";
        }
        $tableColumns .= "                        Column::make('created_at')->label('Created')->dateFormat('d/m/Y')->sortable(),";

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
        $imports = "use ChristYoga123\\Vuelament\\Core\\BaseResource;
use ChristYoga123\\Vuelament\\Components\\Form\\Form;
use ChristYoga123\\Vuelament\\Components\\Form\\TextInput;
use ChristYoga123\\Vuelament\\Components\\Form\\Textarea;
use ChristYoga123\\Vuelament\\Components\\Form\\Select;
use ChristYoga123\\Vuelament\\Components\\Form\\Toggle;
use ChristYoga123\\Vuelament\\Components\\Form\\DatePicker;
use ChristYoga123\\Vuelament\\Components\\Table\\Table;
use ChristYoga123\\Vuelament\\Components\\Table\\Column;
use ChristYoga123\\Vuelament\\Components\\Table\\Columns\\ToggleColumn;
use ChristYoga123\\Vuelament\\Components\\Table\\Actions\\EditAction;
use ChristYoga123\\Vuelament\\Components\\Table\\Actions\\DeleteAction;
use ChristYoga123\\Vuelament\\Components\\Actions\\ActionGroup;
use ChristYoga123\\Vuelament\\Components\\Actions\\DeleteBulkAction;";

        if ($hasSoftDeletes) {
            $imports .= "
use ChristYoga123\\Vuelament\\Components\\Table\\Actions\\RestoreAction;
use ChristYoga123\\Vuelament\\Components\\Table\\Actions\\ForceDeleteAction;
use ChristYoga123\\Vuelament\\Components\\Table\\Filters\\SelectFilter;";
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
        } else {
            $filters = "
                    ->filters([])";
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

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
{\$tableColumns}
            ])
            ->actions([
{\$tableActions}
            ])
            ->bulkActions([
                ActionGroup::make('Aksi Massal')
                    ->icon('list')
                    ->actions([
                        DeleteBulkAction::make(),
                    ]),
            ]){\$filters}
            ->searchable()
            ->paginated()
            ->selectable();
    }

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
{\$formComponents}
            ]);
    }

    public static function getPages(): array
    {

        if ($isSimple) {
            $pagesStr = "        return [
            'index'  => Resources\\Manage{$plural}::class,
        ];";
        } else {
            $pagesStr = "        return [
            'index'  => Resources\\List{$plural}::class,
            'create' => Resources\\Create{$name}::class,
            'edit'   => Resources\\Edit{$name}::class,
        ];";
        }

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
                    ]){$filters}
                    ->searchable()
                    ->paginated()
                    ->selectable(),
            ]);
    }

    public static function formSchema(): PageSchema
    {
        return PageSchema::make()
            ->components([
{$formComponents}
            ]);
    }

    public static function getPages(): array
    {
{$pagesStr}
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

    // ── Generate Service ─────────────────────────────────
    // New: app/Vuelament/{Panel}/{Model}/Services/{Model}Service.php

    protected function generateService(string $name, string $panel): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Vuelament/{$pathPrefix}{$name}/Services/{$name}Service.php");

        if (file_exists($path) && !$this->option('force')) {
            return;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";
        $namespace = "App\\Vuelament{$namespacePrefix}\\{$name}\\Services";

        $content = <<<PHP
<?php

namespace {$namespace};

use App\\Models\\{$name};

class {$name}Service
{
    /**
     * Business logic dipanggil via Action.
     *
     * Contoh penggunaan di Resource:
     *   Action::make('publish')
     *       ->form([TextInput::make('title')])
     *       ->action([{$name}Service::class, 'publish'])
     *
     * Framework akan memanggil:
     *   app()->call([{$name}Service::class, 'publish'], [
     *       '{$this->lcfirst($name)}' => \$record,
     *       'data' => \$formData,
     *   ]);
     */
    // public function publish({$name} \${$this->lcfirst($name)}, array \$data): void
    // {
    //     \${$this->lcfirst($name)}->update([
    //         'title' => \$data['title'],
    //     ]);
    // }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("  Created: app/Vuelament/{$pathPrefix}{$name}/Services/{$name}Service.php");
    }

    // ── Generate Resource Page Classes ────────────────────
    // Non-simple: ListXxxs, CreateXxx, EditXxx
    // Simple:     ManageXxxs

    protected function generateResourcePages(string $name, string $panel, bool $isSimple): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $namespacePrefix = $panel ? "\\{$panel}" : "";
        $baseNamespace = "App\\Vuelament{$namespacePrefix}\\{$name}\\Resources";
        $resourceClass = "App\\Vuelament{$namespacePrefix}\\{$name}\\{$name}Resource";
        $dir = app_path("Vuelament/{$pathPrefix}{$name}/Resources");

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($isSimple) {
            // Single mode: ManageXxxs
            $plural = Str::plural($name);
            $className = "Manage{$plural}";
            $path = "{$dir}/{$className}.php";

            if (!file_exists($path) || $this->option('force')) {
                $content = <<<PHP
<?php

namespace {$baseNamespace};

use ChristYoga123\\Vuelament\\Core\\Pages\\ManageRecords;
use ChristYoga123\\Vuelament\\Components\\Actions\\CreateAction;

class {$className} extends ManageRecords
{
    protected static ?string \$resource = \\{$resourceClass}::class;

    public static function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
PHP;
                file_put_contents($path, $content);
                $this->info("  Created: app/Vuelament/{$pathPrefix}{$name}/Resources/{$className}.php");
            }
        } else {
            // Multi-page mode: ListXxxs, CreateXxx, EditXxx
            $plural = Str::plural($name);

            $pages = [
                "List{$plural}" => 'ListRecords',
                "Create{$name}" => 'CreateRecord',
                "Edit{$name}"   => 'EditRecord',
            ];

            foreach ($pages as $className => $baseClass) {
                $path = "{$dir}/{$className}.php";

                if (!file_exists($path) || $this->option('force')) {
                    $content = <<<PHP
<?php

namespace {$baseNamespace};

use ChristYoga123\\Vuelament\\Core\\Pages\\{$baseClass};

class {$className} extends {$baseClass}
{
    protected static ?string \$resource = \\{$resourceClass}::class;
}
PHP;
                    file_put_contents($path, $content);
                    $this->info("  Created: app/Vuelament/{$pathPrefix}{$name}/Resources/{$className}.php");
                }
            }
        }
    }

    // ── Create empty directories ─────────────────────────

    protected function createDirectories(string $name, string $panel): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $base = app_path("Vuelament/{$pathPrefix}{$name}");

        foreach (['Pages', 'Widgets'] as $subDir) {
            $dir = "{$base}/{$subDir}";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                file_put_contents("{$dir}/.gitkeep", '');
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────

    protected function lcfirst(string $str): string
    {
        return lcfirst($str);
    }

    protected function resolveStubPath(string $stub): string
    {
        $custom = base_path("stubs/vuelament/{$stub}");
        if (file_exists($custom)) {
            return $custom;
        }

        return __DIR__ . '/../../stubs/' . $stub;
    }

    protected function getResourceStub(): string
    {
        return file_get_contents($this->resolveStubPath('resource.stub'));
    }
}
