<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { onMounted, ref, watch } from 'vue';

import type { SharedPageProps } from '../modules/identity-access/types';
import BrandMark from '../shared/components/BrandMark.vue';

const navigationPreferencesVersion = 1;
const page = usePage<SharedPageProps>();
const sidebarCollapsed = ref(false);

watch(
    () => page.props.localization.locale,
    (locale) => {
        document.documentElement.lang = locale;
    },
    { immediate: true },
);

function isCurrentRoute(href: string): boolean {
    return page.url === href || page.url.startsWith(`${href}/`);
}

function persistNavigationPreferences(): void {
    try {
        window.localStorage.setItem(
            'cleture-my-navigation',
            JSON.stringify({
                version: navigationPreferencesVersion,
                sidebarCollapsed: sidebarCollapsed.value,
            }),
        );
    } catch {
        // Browser storage is an optional presentation enhancement.
    }
}

function toggleSidebar(): void {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    persistNavigationPreferences();
}

function selectLocale(locale: string | null): void {
    if (!locale || locale === page.props.localization.locale) {
        return;
    }

    router.post('/locale', { locale }, { preserveScroll: true });
}

onMounted(() => {
    try {
        const saved = window.localStorage.getItem('cleture-my-navigation');

        if (!saved) {
            return;
        }

        const preferences = JSON.parse(saved) as {
            version?: number;
            sidebarCollapsed?: boolean;
        };

        if (preferences.version === navigationPreferencesVersion) {
            sidebarCollapsed.value = Boolean(preferences.sidebarCollapsed);
        }
    } catch {
        sidebarCollapsed.value = false;
    }
});
</script>

<template>
    <div class="app-shell" :class="{ 'app-shell-collapsed': sidebarCollapsed }">
        <aside class="app-sidebar">
            <div class="sidebar-brand-row">
                <Link
                    href="/dashboard"
                    class="app-sidebar-brand"
                    :aria-label="page.props.localization.translations.appLayout.dashboardLabel"
                >
                    <span class="brand-logo-surface">
                        <BrandMark :compact="sidebarCollapsed" />
                    </span>
                    <span class="app-sidebar-product">NetZero</span>
                </Link>
                <Button
                    class="sidebar-toggle"
                    icon="pi pi-angle-double-left"
                    severity="secondary"
                    text
                    rounded
                    :aria-label="page.props.localization.translations.appLayout.collapseSidebar"
                    :title="page.props.localization.translations.appLayout.collapseSidebar"
                    @click="toggleSidebar"
                />
            </div>

            <nav
                class="app-navigation"
                :aria-label="page.props.localization.translations.appLayout.navigationLabel"
            >
                <Link
                    href="/dashboard"
                    class="app-navigation-link"
                    :class="{ 'app-navigation-link-active': isCurrentRoute('/dashboard') }"
                    :aria-current="isCurrentRoute('/dashboard') ? 'page' : undefined"
                    :title="
                        sidebarCollapsed
                            ? page.props.localization.translations.appLayout.overview
                            : undefined
                    "
                >
                    <i class="pi pi-th-large" aria-hidden="true"></i>
                    <span class="navigation-text">
                        {{ page.props.localization.translations.appLayout.overview }}
                    </span>
                </Link>
                <Link
                    href="/organization"
                    class="app-navigation-link"
                    :class="{ 'app-navigation-link-active': isCurrentRoute('/organization') }"
                    :aria-current="isCurrentRoute('/organization') ? 'page' : undefined"
                    :title="
                        sidebarCollapsed
                            ? page.props.localization.translations.appLayout.organization
                            : undefined
                    "
                >
                    <i class="pi pi-building" aria-hidden="true"></i>
                    <span class="navigation-text">
                        {{ page.props.localization.translations.appLayout.organization }}
                    </span>
                </Link>
            </nav>

            <div class="sidebar-account">
                <span
                    class="sidebar-user-badge"
                    :title="sidebarCollapsed ? page.props.auth.user?.name : undefined"
                >
                    {{ page.props.auth.user?.name.slice(0, 2).toUpperCase() }}
                </span>
                <div class="sidebar-user-copy">
                    <strong>{{ page.props.auth.user?.name }}</strong>
                    <span>{{ page.props.auth.user?.email }}</span>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="sidebar-logout-button"
                    :aria-label="page.props.localization.translations.appLayout.logout"
                    :title="page.props.localization.translations.appLayout.logout"
                >
                    <i class="pi pi-sign-out" aria-hidden="true"></i>
                </Link>
            </div>
        </aside>

        <div class="app-frame">
            <header class="app-header">
                <Button
                    v-if="sidebarCollapsed"
                    class="app-sidebar-expand"
                    icon="pi pi-angle-double-right"
                    severity="secondary"
                    text
                    rounded
                    :aria-label="page.props.localization.translations.appLayout.expandSidebar"
                    :title="page.props.localization.translations.appLayout.expandSidebar"
                    @click="toggleSidebar"
                />
                <div class="app-header-actions">
                    <span class="test-badge"><i class="pi pi-wrench"></i> TEST</span>
                    <div
                        v-if="page.props.localization.languages.length > 1"
                        class="app-locale-picker"
                    >
                        <i class="pi pi-globe" aria-hidden="true"></i>
                        <Select
                            class="auth-locale-select app-locale-select"
                            :model-value="page.props.localization.locale"
                            :options="page.props.localization.languages"
                            option-label="nativeName"
                            option-value="code"
                            overlay-class="auth-locale-overlay"
                            dropdown-icon="pi pi-chevron-down"
                            :aria-label="
                                page.props.localization.translations.authLayout.selectLanguage
                            "
                            @update:model-value="selectLocale"
                        />
                    </div>
                </div>
            </header>

            <slot />
        </div>
    </div>
</template>
