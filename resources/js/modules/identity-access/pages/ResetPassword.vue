<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import AuthLayout from '../../../layouts/AuthLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../types';

const props = defineProps<{ email: string; token: string }>();
const page = usePage<SharedPageProps>();
const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="page.props.localization.translations.resetPassword.headTitle" />
    <AuthLayout
        :eyebrow="page.props.localization.translations.common.accountRecovery"
        :title="page.props.localization.translations.resetPassword.title"
        :description="page.props.localization.translations.resetPassword.description"
    >
        <form class="auth-form" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                :label="page.props.localization.translations.common.email"
                type="email"
                autocomplete="email"
                inputmode="email"
                readonly
                :error="form.errors.email"
            />
            <FormField
                id="password"
                v-model="form.password"
                :label="page.props.localization.translations.resetPassword.newPassword"
                type="password"
                autocomplete="new-password"
                :hint="page.props.localization.translations.resetPassword.passwordHint"
                :error="form.errors.password"
            />
            <FormField
                id="password_confirmation"
                v-model="form.password_confirmation"
                :label="page.props.localization.translations.resetPassword.passwordConfirmation"
                type="password"
                autocomplete="new-password"
            />
            <SubmitButton
                :processing="form.processing"
                :label="page.props.localization.translations.resetPassword.submit"
                :processing-label="page.props.localization.translations.resetPassword.processing"
            />
        </form>

        <p class="auth-switch">
            <Link href="/login">
                {{ page.props.localization.translations.common.backToLogin }}
            </Link>
        </p>
    </AuthLayout>
</template>
