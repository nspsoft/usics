import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { registerSW } from 'virtual:pwa-register';

const appName = import.meta.env.VITE_APP_NAME || 'ERP Manufacturing';

// Initialize PWA Service Worker
if ('serviceWorker' in navigator) {
    registerSW({
        onOfflineReady() {},
    });
}

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('pwa_debug') === '1') {
        navigator.serviceWorker.getRegistration().then((reg) => {
            const el = document.createElement('div');
            el.style.position = 'fixed';
            el.style.right = '12px';
            el.style.bottom = '12px';
            el.style.zIndex = '2147483647';
            el.style.maxWidth = '360px';
            el.style.padding = '10px 12px';
            el.style.borderRadius = '10px';
            el.style.border = '1px solid rgba(148, 163, 184, 0.25)';
            el.style.background = 'rgba(2, 6, 23, 0.9)';
            el.style.color = 'rgba(226, 232, 240, 0.95)';
            el.style.fontSize = '12px';
            el.style.lineHeight = '1.35';
            el.style.fontFamily =
                'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
            el.style.pointerEvents = 'none';

            const controller = navigator.serviceWorker.controller;
            const swUrl = controller?.scriptURL ?? '(none)';
            const hasWaiting = Boolean(reg?.waiting);
            const hasInstalling = Boolean(reg?.installing);
            const hasActive = Boolean(reg?.active);

            el.textContent = [
                `pwa_debug=1`,
                `controller=${controller ? 'yes' : 'no'}`,
                `sw=${swUrl}`,
                `reg: active=${hasActive ? 'yes' : 'no'} installing=${hasInstalling ? 'yes' : 'no'} waiting=${hasWaiting ? 'yes' : 'no'}`,
            ].join('\n');

            document.body.appendChild(el);
        });
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
        showSpinner: true,
    },
});
