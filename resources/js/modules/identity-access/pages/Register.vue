<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import AuthLayout from '../../../layouts/AuthLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="page.props.localization.translations.register.headTitle" />
    <AuthLayout
        :eyebrow="page.props.localization.translations.register.eyebrow"
        :title="page.props.localization.translations.register.title"
        :description="page.props.localization.translations.register.description"
    >
        <form class="auth-form" novalidate @submit.prevent="submit">
            <FormField
                id="name"
                v-model="form.name"
                :label="page.props.localization.translations.register.name"
                autocomplete="name"
                :error="form.errors.name"
            />
            <FormField
                id="email"
                v-model="form.email"
                :label="page.props.localization.translations.common.email"
                type="email"
                autocomplete="email"
                inputmode="email"
                :error="form.errors.email"
            />
            <div class="field-grid">
                <FormField
                    id="password"
                    v-model="form.password"
                    :label="page.props.localization.translations.common.password"
                    type="password"
                    autocomplete="new-password"
                    :error="form.errors.password"
                />
                <FormField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :label="page.props.localization.translations.register.passwordConfirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>
            <p class="form-note">
                {{ page.props.localization.translations.register.note }}
            </p>
            <SubmitButton
                :processing="form.processing"
                :label="page.props.localization.translations.register.submit"
                :processing-label="page.props.localization.translations.register.processing"
            />
        </form>

        <p class="auth-switch">
            {{ page.props.localization.translations.register.hasAccount }}
            <Link href="/login">{{ page.props.localization.translations.register.login }}</Link>
        </p>
    </AuthLayout>
</template>
