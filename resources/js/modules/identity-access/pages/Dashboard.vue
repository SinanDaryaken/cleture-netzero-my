<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import BrandMark from '../../../shared/components/BrandMark.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
</script>

<template>
    <Head :title="page.props.localization.translations.dashboard.headTitle" />
    <div class="app-shell">
        <header class="app-header">
            <BrandMark />
            <div class="app-header-actions">
                <span class="test-badge"><i class="pi pi-wrench"></i> TEST</span>
                <span class="user-badge">
                    {{ page.props.auth.user?.name.slice(0, 2).toUpperCase() }}
                </span>
                <div class="user-copy">
                    <strong>{{ page.props.auth.user?.name }}</strong>
                    <span>{{ page.props.auth.user?.email }}</span>
                </div>
                <Link href="/logout" method="post" as="button" class="logout-button">
                    <i class="pi pi-sign-out"></i>
                    {{ page.props.localization.translations.dashboard.logout }}
                </Link>
            </div>
        </header>

        <main class="dashboard-shell">
            <section class="dashboard-hero">
                <span class="dashboard-icon"><i class="pi pi-th-large"></i></span>
                <div>
                    <p class="eyebrow">
                        {{ page.props.localization.translations.dashboard.eyebrow }}
                    </p>
                    <h1>{{ page.props.localization.translations.dashboard.title }}</h1>
                    <p>{{ page.props.localization.translations.dashboard.description }}</p>
                </div>
            </section>

            <section class="dashboard-grid">
                <article class="dashboard-card dashboard-card-primary">
                    <i class="pi pi-verified"></i>
                    <div>
                        <span>{{
                            page.props.localization.translations.dashboard.accountStatus
                        }}</span>
                        <strong>{{
                            page.props.localization.translations.dashboard.emailVerified
                        }}</strong>
                        <p>
                            {{ page.props.localization.translations.dashboard.verifiedDescription }}
                        </p>
                    </div>
                </article>
                <article class="dashboard-card">
                    <i class="pi pi-building"></i>
                    <div>
                        <span>{{ page.props.localization.translations.dashboard.nextStep }}</span>
                        <strong>
                            {{ page.props.localization.translations.dashboard.organizationSetup }}
                        </strong>
                        <p>
                            {{
                                page.props.localization.translations.dashboard
                                    .organizationDescription
                            }}
                        </p>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>
