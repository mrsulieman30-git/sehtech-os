import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { i18n } from './i18n/portal';

const appName = import.meta.env.VITE_APP_NAME || 'SEHTECH CompanyOS';

const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        app.use(plugin)
           .use(pinia)
           .use(i18n)
           .mount(el);
    },
    progress: {
        color: '#2563EB', // SEHTECH Active/Focus Blue
        showSpinner: false,
    },
});
