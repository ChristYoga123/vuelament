<?php

namespace ChristYoga123\Vuelament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'vuelament:install
                            {--panel=Admin : Default panel name}
                            {--id= : Panel ID / URL prefix}
                            {--force : Overwrite existing files}';

    protected $description = 'Install Vuelament — publish assets, scaffold panel, install dependencies';

    public function handle(): int
    {
        $this->newLine();
        $this->info('🚀 Installing Vuelament...');
        $this->newLine();

        // ── Step 1: Publish config ──────────────────────────
        $this->task('Publishing config...', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'vuelament-config',
                '--force' => $this->option('force'),
            ]);
        });

        // ── Step 2: Publish Vue/JS assets ───────────────────
        $this->task('Publishing Vue/JS assets...', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'vuelament-views',
                '--force' => $this->option('force'),
            ]);
        });

        // ── Step 3: Generate AdminPanelProvider ─────────────
        $panelName = Str::studly($this->option('panel'));
        $panelId = $this->option('id') ?: Str::lower($panelName);

        $providerPath = app_path("Vuelament/Providers/{$panelName}PanelProvider.php");
        if (!file_exists($providerPath) || $this->option('force')) {
            $this->task("Generating {$panelName}PanelProvider...", function () use ($panelName, $panelId) {
                $this->callSilently('vuelament:panel', [
                    'name' => $panelName,
                    '--id' => $panelId,
                    '--force' => $this->option('force'),
                ]);
            });
        } else {
            $this->line("  ⏭ {$panelName}PanelProvider already exists (use --force to overwrite).");
        }

        // ── Step 4: Register PanelProvider in bootstrap/providers.php ──
        $this->task('Registering PanelProvider...', function () use ($panelName) {
            $this->registerProvider($panelName);
        });

        // ── Step 5: Scaffold app.js ─────────────────────────
        $appJsPath = resource_path('js/app.js');
        if (!file_exists($appJsPath) || !str_contains(file_get_contents($appJsPath), 'createInertiaApp')) {
            $this->task('Scaffolding app.js...', function () use ($appJsPath) {
                $this->scaffoldAppJs($appJsPath);
            });
        } else {
            $this->line('  ⏭ app.js already configured for Inertia.');
        }

        // ── Step 6: Scaffold vite.config.js ─────────────────
        $viteConfigPath = base_path('vite.config.js');
        if (!file_exists($viteConfigPath) || !str_contains(file_get_contents($viteConfigPath), '@vitejs/plugin-vue')) {
            $this->task('Scaffolding vite.config.js...', function () use ($viteConfigPath) {
                $this->scaffoldViteConfig($viteConfigPath);
            });
        } else {
            $this->line('  ⏭ vite.config.js already configured.');
        }

        // ── Summary ─────────────────────────────────────────
        $this->newLine();
        $this->info('✅ Vuelament installed successfully!');
        $this->newLine();

        $this->line('  Next steps:');
        $this->newLine();
        $this->line('  1. Install NPM dependencies:');
        $this->line('     <fg=yellow>npm install @inertiajs/vue3 @vitejs/plugin-vue @vueuse/core @vueup/vue-quill @vuepic/vue-datepicker lucide-vue-next vue-sonner reka-ui class-variance-authority clsx tailwind-merge tw-animate-css</>');
        $this->newLine();
        $this->line('  2. Set up Shadcn-Vue UI components:');
        $this->line('     <fg=yellow>npx -y shadcn-vue@latest init</>');
        $this->line('     <fg=yellow>npx -y shadcn-vue@latest add alert-dialog avatar breadcrumb button card checkbox dialog dropdown-menu input label pagination popover scroll-area select separator sheet sidebar skeleton switch table textarea tooltip</>');
        $this->newLine();
        $this->line('  3. Run migrations:');
        $this->line('     <fg=yellow>php artisan migrate</>');
        $this->newLine();
        $this->line('  4. Create admin user:');
        $this->line('     <fg=yellow>php artisan vuelament:user</>');
        $this->newLine();
        $this->line('  5. Start dev server:');
        $this->line('     <fg=yellow>npm run dev</>');
        $this->newLine();
        $this->line("  Panel URL: <fg=cyan>/{$panelId}</>");
        $this->line("  Login:     <fg=cyan>/{$panelId}/login</>");

        return self::SUCCESS;
    }

    /**
     * Register the PanelProvider in bootstrap/providers.php.
     */
    protected function registerProvider(string $panelName): void
    {
        $providersPath = base_path('bootstrap/providers.php');
        if (!file_exists($providersPath)) {
            return;
        }

        $content = file_get_contents($providersPath);
        $providerClass = "App\\Vuelament\\Providers\\{$panelName}PanelProvider::class";

        if (str_contains($content, $providerClass)) {
            return; // Already registered
        }

        // Insert before the closing bracket of the return array
        $content = preg_replace(
            '/(\]\s*;\s*$)/m',
            "    {$providerClass},\n$1",
            $content,
            1
        );

        file_put_contents($providersPath, $content);
    }

    /**
     * Scaffold resources/js/app.js with Inertia setup.
     */
    protected function scaffoldAppJs(string $path): void
    {
        $content = <<<'JS'
import './bootstrap';
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppWrapper from './AppWrapper.vue'
import '../css/app.css'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(AppWrapper, null, {
                default: () => h(App, props),
            }),
        })
            .use(plugin)
            .mount(el)
    },
    progress: {
        color: '#3b82f6',
    },
})
JS;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    /**
     * Scaffold vite.config.js with Vue and chunking.
     */
    protected function scaffoldViteConfig(string $path): void
    {
        $content = <<<'JS'
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vuePlugin from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vuePlugin(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        chunkSizeWarningLimit: 1200,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('@vueup/vue-quill')) return 'vendor-quill';
                        if (id.includes('@vuepic/vue-datepicker')) return 'vendor-datepicker';
                        if (id.includes('@inertiajs')) return 'vendor-inertia';
                        if (id.includes('vue') || id.includes('@vue')) return 'vendor-vue';
                        return 'vendor';
                    }
                }
            }
        }
    }
});
JS;

        file_put_contents($path, $content);
    }

    /**
     * Display a task with a loading indicator.
     */
    protected function task(string $description, callable $callback): void
    {
        $this->line("  {$description}");
        $callback();
        $this->line("    <fg=green>✓ Done.</>");
    }
}
