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
