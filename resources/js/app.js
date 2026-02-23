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