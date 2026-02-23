<?php

namespace App\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakePanelCommand extends Command
{
    protected $signature = 'vuelament:panel {name=Admin : Nama panel (contoh: Admin)}
                            {--id= : Panel ID / URL prefix (default: lowercase nama)}
                            {--force : Overwrite jika sudah ada}';

    protected $description = 'Generate Vuelament PanelProvider (contoh: AdminPanelProvider)';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $id   = $this->option('id') ?: Str::lower($name);
        $path = $id;

        $this->generatePanelProvider($name, $id, $path);

        $this->info("✅ PanelProvider [{$name}PanelProvider] berhasil dibuat!");
        $this->newLine();
        $this->line("Langkah selanjutnya:");
        $this->line("  1. Daftarkan di bootstrap/providers.php:");
        $this->line("     App\\Vuelament\\Providers\\{$name}PanelProvider::class,");
        $this->newLine();
        $this->line("  2. Hapus default VuelamentServiceProvider jika tidak dipakai.");
        $this->newLine();
        $this->line("  URL panel: /{$path}");
        $this->line("  Login:     /{$path}/login");
        $this->line("  Dashboard: /{$path}");

        return self::SUCCESS;
    }

    protected function generatePanelProvider(string $name, string $id, string $path): void
    {
        $outputPath = app_path("Vuelament/Providers/{$name}PanelProvider.php");

        if (file_exists($outputPath) && !$this->option('force')) {
            $this->error("[{$name}PanelProvider] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $stub = $this->getStub();
        $stub = str_replace(
            ['{{ name }}', '{{ id }}', '{{ path }}', '{{ brandName }}'],
            [$name, $id, $path, $name . ' Panel'],
            $stub
        );

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $stub);
        $this->info("  Created: app/Vuelament/Providers/{$name}PanelProvider.php");
    }

    protected function getStub(): string
    {
        $stubPath = app_path('Vuelament/Stubs/panel-provider.stub');
        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        return <<<'STUB'
<?php

namespace App\Vuelament\Providers;

use App\Vuelament\Core\Panel;
use App\Vuelament\Core\NavigationGroup;
use App\Vuelament\Core\NavigationItem;
use App\Vuelament\VuelamentServiceProvider;

class {{ name }}PanelProvider extends VuelamentServiceProvider
{
    public function panel(): Panel
    {
        return Panel::make()
            ->id('{{ id }}')
            ->path('{{ path }}')
            ->brandName('{{ brandName }}')
            ->login()
            // ->register()
            ->middleware(['web'])
            ->authMiddleware([\App\Vuelament\Http\Middleware\Authenticate::class])
            ->colors([
                'primary' => '#6366f1',
            ])
            ->discoverResources(app_path('Vuelament/Resources'), 'App\\Vuelament\\Resources')
            // ->discoverPages(app_path('Vuelament/Pages'), 'App\\Vuelament\\Pages')
            ->plugins([
                //
            ]);
    }
}
STUB;
    }
}
