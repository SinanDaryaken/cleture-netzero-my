<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
</script>

<template>
    <Head :title="page.props.localization.translations.dashboard.headTitle" />
    <AppLayout>
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
                <Link href="/organization" class="dashboard-card dashboard-card-link">
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
                        <span class="dashboard-card-action">
                            {{ page.props.localization.translations.dashboard.manageOrganization }}
                            <i class="pi pi-arrow-right" aria-hidden="true"></i>
                        </span>
                    </div>
                </Link>
            </section>
        </main>
    </AppLayout>
</template>
