<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import StatusMessage from '../../../shared/components/StatusMessage.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../../identity-access/types';

type Organization = {
    name: string;
    taxNumber: string;
};

const props = defineProps<{
    organization: Organization | null;
}>();
const page = usePage<SharedPageProps>();
const hasOrganization = computed(() => props.organization !== null);
const form = useForm({
    name: props.organization?.name ?? '',
    tax_number: props.organization?.taxNumber ?? '',
});

function submit(): void {
    if (hasOrganization.value) {
        form.patch('/organization', { preserveScroll: true });

        return;
    }

    form.post('/organization', { preserveScroll: true });
}
</script>

<template>
    <Head :title="page.props.localization.translations.organization.headTitle" />
    <AppLayout>
        <main class="organization-shell">
            <Link href="/dashboard" class="organization-back-link">
                <i class="pi pi-arrow-left" aria-hidden="true"></i>
                {{ page.props.localization.translations.organization.backToDashboard }}
            </Link>

            <section class="organization-hero">
                <span class="dashboard-icon"><i class="pi pi-building"></i></span>
                <div>
                    <p class="eyebrow">
                        {{ page.props.localization.translations.organization.eyebrow }}
                    </p>
                    <h1>{{ page.props.localization.translations.organization.title }}</h1>
                    <p>{{ page.props.localization.translations.organization.description }}</p>
                </div>
            </section>

            <section class="organization-card">
                <div class="organization-card-heading">
                    <div>
                        <span>{{
                            hasOrganization
                                ? page.props.localization.translations.organization.existingLabel
                                : page.props.localization.translations.organization.newLabel
                        }}</span>
                        <h2>{{ page.props.localization.translations.organization.formTitle }}</h2>
                    </div>
                    <i class="pi pi-shield" aria-hidden="true"></i>
                </div>

                <StatusMessage :message="page.props.flash.status" />

                <form class="organization-form" @submit.prevent="submit">
                    <FormField
                        id="organization-name"
                        v-model="form.name"
                        :label="page.props.localization.translations.organization.name"
                        autocomplete="organization"
                        :error="form.errors.name"
                    />
                    <FormField
                        id="tax-number"
                        v-model="form.tax_number"
                        :label="page.props.localization.translations.organization.taxNumber"
                        :maxlength="32"
                        :hint="page.props.localization.translations.organization.taxNumberHint"
                        :error="form.errors.tax_number"
                    />
                    <SubmitButton
                        icon="pi pi-save"
                        :label="
                            hasOrganization
                                ? page.props.localization.translations.organization.updateSubmit
                                : page.props.localization.translations.organization.createSubmit
                        "
                        :processing-label="
                            hasOrganization
                                ? page.props.localization.translations.organization.updating
                                : page.props.localization.translations.organization.creating
                        "
                        :processing="form.processing"
                    />
                </form>
            </section>
        </main>
    </AppLayout>
</template>
