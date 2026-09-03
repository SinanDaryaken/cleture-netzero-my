<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import AuthLayout from '../../../layouts/AuthLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import StatusMessage from '../../../shared/components/StatusMessage.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
const form = useForm({ email: '' });

function submit(): void {
    form.post('/forgot-password');
}
</script>

<template>
    <Head :title="page.props.localization.translations.forgotPassword.headTitle" />
    <AuthLayout
        :eyebrow="page.props.localization.translations.common.accountRecovery"
        :title="page.props.localization.translations.forgotPassword.title"
        :description="page.props.localization.translations.forgotPassword.description"
    >
        <StatusMessage class="auth-message" :message="page.props.flash.status" />

        <form class="auth-form" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                :label="page.props.localization.translations.common.email"
                type="email"
                autocomplete="email"
                inputmode="email"
                :error="form.errors.email"
            />
            <SubmitButton
                :processing="form.processing"
                :label="page.props.localization.translations.forgotPassword.submit"
                :processing-label="page.props.localization.translations.common.preparingRequest"
            />
        </form>

        <p class="auth-switch">
            {{ page.props.localization.translations.forgotPassword.rememberedPassword }}
            <Link href="/login">
                {{ page.props.localization.translations.common.backToLogin }}
            </Link>
        </p>
    </AuthLayout>
</template>
