import 'primeicons/primeicons.css';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primeuix/themes/aura';
import { definePreset } from '@primeuix/themes';
import ConfirmationService from 'primevue/confirmationservice';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';

import cletureIconUrl from '../images/cleture-icon.svg';

const CletureTheme = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{emerald.50}',
            100: '{emerald.100}',
            200: '{emerald.200}',
            300: '{emerald.300}',
            400: '{emerald.400}',
            500: '{emerald.500}',
            600: '{emerald.600}',
            700: '{emerald.700}',
            800: '{emerald.800}',
            900: '{emerald.900}',
            950: '{emerald.950}',
        },
    },
});

const pages = import.meta.glob<{ default: DefineComponent }>('./modules/**/pages/*.vue');
const favicon =
    document.querySelector<HTMLLinkElement>('link[rel="icon"]') ?? document.createElement('link');

favicon.rel = 'icon';
favicon.type = 'image/svg+xml';
favicon.href = cletureIconUrl;

if (!favicon.isConnected) {
    document.head.append(favicon);
}

void createInertiaApp({
    progress: { color: '#047857' },
    resolve: async (name) => {
        const pagePath = `./modules/${name.replace(/\/([^/]+)$/, '/pages/$1')}.vue`;
        const page = pages[pagePath];

        if (!page) {
            throw new Error(`Bilinmeyen Inertia sayfası: ${name}`);
        }

        return (await page()).default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: CletureTheme,
                    options: {
                        darkModeSelector: 'none',
                    },
                },
            })
            .use(ConfirmationService)
            .use(ToastService)
            .mount(el);
    },
});
