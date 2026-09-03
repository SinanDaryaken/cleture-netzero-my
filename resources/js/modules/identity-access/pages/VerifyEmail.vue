<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthLayout from '../../../layouts/AuthLayout.vue';
import StatusMessage from '../../../shared/components/StatusMessage.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
const resendForm = useForm({});
const description = computed(() =>
    page.props.localization.translations.verifyEmail.description.replace(
        ':email',
        page.props.auth.user?.email ??
            page.props.localization.translations.verifyEmail.emailFallback,
    ),
);

function resend(): void {
    resendForm.post('/email/verification-notification');
}
</script>

<template>
    <Head :title="page.props.localization.translations.verifyEmail.headTitle" />
    <AuthLayout
        :eyebrow="page.props.localization.translations.verifyEmail.eyebrow"
        :title="page.props.localization.translations.verifyEmail.title"
        :description="description"
    >
        <StatusMessage class="auth-message" :message="page.props.flash.status" />

        <div class="verification-illustration" aria-hidden="true">
            <span><i class="pi pi-envelope"></i></span>
            <i class="pi pi-check-circle"></i>
        </div>

        <form @submit.prevent="resend">
            <SubmitButton
                :processing="resendForm.processing"
                :label="page.props.localization.translations.verifyEmail.submit"
                :processing-label="page.props.localization.translations.common.preparingRequest"
            />
        </form>

        <p class="form-note verification-note">
            {{ page.props.localization.translations.verifyEmail.note }}
        </p>
        <p class="auth-switch">
            {{ page.props.localization.translations.verifyEmail.otherAccount }}
            <Link href="/logout" method="post" as="button">
                {{ page.props.localization.translations.verifyEmail.logout }}
            </Link>
        </p>
    </AuthLayout>
</template>
