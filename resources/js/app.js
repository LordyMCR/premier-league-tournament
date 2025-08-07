import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({
                methods: {
                    route(name, params) {
                        if (typeof window.route === 'function') {
                            return window.route(name, params);
                        }
                        // Fallback for when Ziggy is not available
                        console.warn(`Route '${name}' called but Ziggy not available`);
                        return '#';
                    }
                }
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
