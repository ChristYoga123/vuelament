<?php

namespace ChristYoga123\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    protected $signature = 'vuelament:service {name : Name service (contoh: Order)}
                            {--panel=Admin : Name panel tujuan (default: Admin)}
                            {--resource= : Name Resource module (opsional, contoh: User)}
                            {--force : Overwrite jika sudah ada}';

    protected $description = 'Generate Vuelament service class for action business logic';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $panel = $this->option('panel') ? Str::studly($this->option('panel')) : '';
        $resource = $this->option('resource') ? Str::studly($this->option('resource')) : $name;

        // Jika nama sudah mengandung "Service", hapus suffix-nya untuk model name
        $modelName = Str::replaceLast('Service', '', $name);
        $serviceName = Str::endsWith($name, 'Service') ? $name : $name . 'Service';

        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Vuelament/{$pathPrefix}{$resource}/Services/{$serviceName}.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("[{$serviceName}] sudah ada! Gunakan --force untuk overwrite.");
            return self::FAILURE;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";
        $namespace = "App\\Vuelament{$namespacePrefix}\\{$resource}\\Services";
        $modelFqn = "App\\Models\\{$modelName}";
        $lcModel = lcfirst($modelName);

        $content = <<<PHP
<?php

namespace {$namespace};

use {$modelFqn};

class {$serviceName}
{
    /**
     * Business logic dipanggil via Action.
     *
     * Penggunaan di Resource:
     *   Action::make('example')
     *       ->action([{$serviceName}::class, 'example'])
     *
     * Framework resolve instance via container:
     *   \$instance = app({$serviceName}::class);
     *   app()->call([\$instance, 'example'], [
     *       '{$lcModel}' => \$record,
     *       'data'       => \$formData,
     *   ]);
     */
    public function example({$modelName} \${$lcModel}, array \$data = []): void
    {
        // TODO: implementasi business logic
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);

        $this->newLine();
        $this->info("✅ Service [{$serviceName}] created successfully!");
        $this->newLine();
        $this->line("  📄 app/Vuelament/{$pathPrefix}{$resource}/Services/{$serviceName}.php");
        $this->newLine();
        $this->line("  Penggunaan di Resource:");
        $this->line("    Action::make('example')");
        $this->line("        ->action([{$serviceName}::class, 'example'])");
        $this->newLine();

        return self::SUCCESS;
    }
}
