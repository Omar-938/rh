import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

// Composants globaux
import AppLayout from '@/Components/Layout/AppLayout.vue';
import InstallBanner from '@/Components/PWA/InstallBanner.vue';

const appName = import.meta.env.VITE_APP_NAME || 'SimpliRH';

createInertiaApp({
    title: (title) => title ? `${title} — ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp
            .use(plugin)
            .use(ZiggyVue, props.initialPage.props.ziggy)
            .component('Link', Link)
            .component('Head', Head)
            .component('AppLayout', AppLayout)
            .component('InstallBanner', InstallBanner)
            .mount(el);

        return vueApp;
    },
    progress: {
        color: '#2E86C1',
        showSpinner: false,
    },
});
