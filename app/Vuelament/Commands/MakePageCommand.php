<?php

namespace App\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakePageCommand extends Command
{
    protected $signature = 'vuelament:page {name : Nama page (contoh: Settings)}
                            {--panel=Admin : Nama panel tujuan (default: Admin)}
                            {--force : Overwrite jika sudah ada}';

    protected $description = 'Generate Vuelament custom page (PHP class + Vue component)';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $slug = Str::kebab($name);
        $panel = $this->option('panel') ? Str::studly($this->option('panel')) : '';

        $this->generatePageClass($name, $slug, $panel);
        $this->generateVueComponent($name, $panel);

        $pathPrefix = $panel ? "{$panel}/" : "";
        $namespacePrefix = $panel ? "\\{$panel}" : "";

        $this->info("✅ Page [{$name}] berhasil dibuat!");
        $this->newLine();
        $this->line("Files created:");
        $this->line("  - app/Vuelament/{$pathPrefix}Pages/{$name}.php");
        $this->line("  - resources/js/Pages/Vuelament/Pages/{$pathPrefix}{$name}.vue");
        $this->newLine();
        $this->line("Langkah selanjutnya:");
        $this->line("  1. Daftarkan di PanelProvider:");
        $this->line("     ->pages([\\App\\Vuelament{$namespacePrefix}\\Pages\\{$name}::class])");
        $this->newLine();
        $this->line("  Atau discover otomatis:");
        $this->line("     ->discoverPages(app_path('Vuelament/{$pathPrefix}Pages'), 'App\\Vuelament{$namespacePrefix}\\Pages')");
        $this->newLine();
        $this->line("  URL: /{panel-path}/{$slug}");

        return self::SUCCESS;
    }

    protected function generatePageClass(string $name, string $slug, string $panel): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = app_path("Vuelament/{$pathPrefix}Pages/{$name}.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("[{$name}] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $namespacePrefix = $panel ? "\\{$panel}" : "";
        $viewPath = $panel ? "{$panel}/{$name}" : $name;

        $stub = $this->getPageStub();
        $stub = str_replace(
            ['{{ namespace }}', '{{ name }}', '{{ slug }}', '{{ title }}', '{{ view }}'],
            ["App\\Vuelament{$namespacePrefix}\\Pages", $name, $slug, Str::headline($name), "Vuelament/Pages/{$viewPath}"],
            $stub
        );

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);
        $this->info("  Created: app/Vuelament/{$pathPrefix}Pages/{$name}.php");
    }

    protected function generateVueComponent(string $name, string $panel): void
    {
        $pathPrefix = $panel ? "{$panel}/" : "";
        $path = resource_path("js/Pages/Vuelament/Pages/{$pathPrefix}{$name}.vue");

        if (file_exists($path) && !$this->option('force')) {
            $this->error("[{$name}.vue] sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $viewPath = $panel ? "{$panel}/{$name}" : $name;

        $stub = $this->getVueStub();
        $stub = str_replace(
            ['{{ name }}', '{{ title }}', '{{ viewPath }}'],
            [$name, Str::headline($name), $viewPath],
            $stub
        );

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);
        $this->info("  Created: resources/js/Pages/Vuelament/Pages/{$pathPrefix}{$name}.vue");
    }

    protected function getPageStub(): string
    {
        $stubPath = app_path('Vuelament/Stubs/page.stub');
        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Vuelament\Core\BasePage;

class {{ name }} extends BasePage
{
    protected static string $slug = '{{ slug }}';
    protected static string $title = '{{ title }}';
    protected static string $view = '{{ view }}';
    protected static string $icon = 'file';
    protected static int $navigationSort = 0;
    // protected static ?string $navigationGroup = null;

    /**
     * Data yang di-pass ke Vue component via Inertia
     */
    public static function getData(): array
    {
        return [
            //
        ];
    }
}
STUB;
    }

    protected function getVueStub(): string
    {
        $stubPath = app_path('Vuelament/Stubs/page-vue.stub');
        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        return <<<'STUB'
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

const props = defineProps({
  page: { type: Object, default: () => ({}) },
})
</script>

<template>
  <DashboardLayout :title="page.title || '{{ title }}'">
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">{{ page.title || '{{ title }}' }}</h1>
      <p class="text-muted-foreground mt-1">Halaman custom {{ title }}.</p>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>{{ title }}</CardTitle>
        <CardDescription>Edit konten halaman ini sesuai kebutuhan Anda.</CardDescription>
      </CardHeader>
      <CardContent>
        <p class="text-sm text-muted-foreground">
          Halaman ini di-generate oleh <code class="rounded bg-muted px-1 py-0.5">php artisan vuelament:page {{ name }}</code>.
          Anda bisa mengedit file Vue ini di <code class="rounded bg-muted px-1 py-0.5">resources/js/Pages/Vuelament/Pages/{{ viewPath }}.vue</code>
          dan class PHP-nya.
        </p>
      </CardContent>
    </Card>
  </DashboardLayout>
</template>
STUB;
    }
}
