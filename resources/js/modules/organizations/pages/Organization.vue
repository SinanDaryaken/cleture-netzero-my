<script setup lang="ts">
import { Head, Link, useForm, usePage, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../../identity-access/types';

type Organization = {
    name: string;
    taxNumber: string;
    netZeroRequested: boolean;
    tenant: {
        provisioningStatus: 'pending' | 'provisioning' | 'ready' | 'failed';
        active: boolean;
        available: boolean;
    } | null;
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
const provisioningForm = useForm({});
const provisioningStatus = computed(() => props.organization?.tenant?.provisioningStatus ?? null);
const provisioningInProgress = computed(
    () => provisioningStatus.value === 'pending' || provisioningStatus.value === 'provisioning',
);
const { start: startProvisioningPoll, stop: stopProvisioningPoll } = usePoll(
    2_000,
    {
        only: ['organization'],
        showProgress: false,
    },
    {
        autoStart: provisioningInProgress.value,
        mode: 'rest',
    },
);

watch(provisioningInProgress, (inProgress) => {
    if (inProgress) {
        startProvisioningPoll();

        return;
    }

    stopProvisioningPoll();
});

function submit(): void {
    if (hasOrganization.value) {
        form.patch('/organization', { preserveScroll: true });

        return;
    }

    form.post('/organization', { preserveScroll: true });
}

function requestNetZero(): void {
    provisioningForm.post('/organization/netzero-provisioning', { preserveScroll: true });
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

                <section v-if="hasOrganization" class="netzero-provisioning">
                    <div>
                        <p class="netzero-provisioning-title">
                            {{ page.props.localization.translations.organization.netZeroTitle }}
                        </p>
                        <p class="netzero-provisioning-description">
                            {{
                                page.props.localization.translations.organization.netZeroDescription
                            }}
                        </p>
                    </div>

                    <div
                        v-if="provisioningStatus"
                        class="netzero-provisioning-status"
                        role="status"
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        <span>{{
                            page.props.localization.translations.organization.netZeroStatusLabel
                        }}</span>
                        <strong>
                            {{
                                page.props.localization.translations.organization.netZeroStatuses[
                                    provisioningStatus
                                ]
                            }}
                        </strong>
                    </div>

                    <form v-else @submit.prevent="requestNetZero">
                        <SubmitButton
                            icon="pi pi-sparkles"
                            :label="
                                page.props.localization.translations.organization
                                    .netZeroRequestSubmit
                            "
                            :processing-label="
                                page.props.localization.translations.organization.netZeroRequesting
                            "
                            :processing="provisioningForm.processing"
                        />
                    </form>
                </section>
            </section>
        </main>
    </AppLayout>
</template>
