<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import Select from 'primevue/select';
import { watch } from 'vue';

import BrandMark from '../shared/components/BrandMark.vue';
import type { SharedPageProps } from '../modules/identity-access/types';

const page = usePage<SharedPageProps>();

watch(
    () => page.props.localization.locale,
    (locale) => {
        document.documentElement.lang = locale;
    },
    { immediate: true },
);

defineProps<{
    eyebrow?: string;
    title: string;
    description: string;
}>();

function selectLocale(locale: string | null): void {
    if (!locale || locale === page.props.localization.locale) {
        return;
    }

    router.post('/locale', { locale }, { preserveScroll: true });
}
</script>

<template>
    <main class="auth-shell">
        <div class="auth-pattern" aria-hidden="true"></div>
        <div class="auth-frame">
            <aside class="auth-brand-panel" aria-label="Cleture NetZero">
                <div>
                    <Link
                        class="auth-brand"
                        href="/"
                        :aria-label="page.props.localization.translations.authLayout.homeLabel"
                    >
                        <span class="brand-logo-surface">
                            <BrandMark />
                        </span>
                        <span class="auth-brand-product">NetZero</span>
                    </Link>

                    <div class="auth-brand-content">
                        <p class="auth-brand-kicker">
                            {{ page.props.localization.translations.authLayout.brandKicker }}
                        </p>
                        <p class="auth-brand-copy">
                            {{ page.props.localization.translations.authLayout.brandCopy }}
                        </p>
                    </div>
                </div>

                <div class="auth-trust-note">
                    <i class="pi pi-shield" aria-hidden="true"></i>
                    <span>{{ page.props.localization.translations.authLayout.trustNote }}</span>
                </div>
            </aside>

            <section class="auth-card" aria-labelledby="page-title">
                <div v-if="page.props.localization.languages.length > 1" class="auth-locale-picker">
                    <i class="pi pi-globe" aria-hidden="true"></i>
                    <Select
                        class="auth-locale-select"
                        :model-value="page.props.localization.locale"
                        :options="page.props.localization.languages"
                        option-label="nativeName"
                        option-value="code"
                        overlay-class="auth-locale-overlay"
                        dropdown-icon="pi pi-chevron-down"
                        :aria-label="page.props.localization.translations.authLayout.selectLanguage"
                        @update:model-value="selectLocale"
                    />
                </div>
                <header class="auth-header">
                    <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
                    <h1 id="page-title">{{ title }}</h1>
                    <p>{{ description }}</p>
                </header>
                <slot />
            </section>
        </div>
    </main>
</template>
