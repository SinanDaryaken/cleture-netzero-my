<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import AuthLayout from '../../../layouts/AuthLayout.vue';
import FormField from '../../../shared/components/FormField.vue';
import StatusMessage from '../../../shared/components/StatusMessage.vue';
import SubmitButton from '../../../shared/components/SubmitButton.vue';
import type { SharedPageProps } from '../types';

const page = usePage<SharedPageProps>();
const form = useForm({ email: '', password: '' });

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head :title="page.props.localization.translations.login.headTitle" />
    <AuthLayout
        :eyebrow="page.props.localization.translations.login.eyebrow"
        :title="page.props.localization.translations.login.title"
        :description="page.props.localization.translations.login.description"
    >
        <StatusMessage class="auth-message" :message="page.props.flash.status" />

        <form class="auth-form" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                :label="page.props.localization.translations.common.email"
                type="email"
                autocomplete="username"
                inputmode="email"
                :error="form.errors.email"
            />
            <div>
                <div class="field-heading">
                    <span>{{ page.props.localization.translations.common.password }}</span>
                    <Link href="/forgot-password">
                        {{ page.props.localization.translations.login.forgotPassword }}
                    </Link>
                </div>
                <FormField
                    id="password"
                    v-model="form.password"
                    label=""
                    type="password"
                    autocomplete="current-password"
                    :error="form.errors.password"
                />
            </div>
            <SubmitButton
                :processing="form.processing"
                :label="page.props.localization.translations.login.submit"
                :processing-label="page.props.localization.translations.login.processing"
                icon="pi pi-arrow-right"
            />
        </form>

        <p class="auth-switch">
            {{ page.props.localization.translations.login.noAccount }}
            <Link href="/register">
                {{ page.props.localization.translations.login.register }}
            </Link>
        </p>
    </AuthLayout>
</template>
