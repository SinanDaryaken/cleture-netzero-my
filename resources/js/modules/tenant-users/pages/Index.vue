<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedPageProps } from '../../identity-access/types';

type TenantUser = {
    id: string;
    name: string;
    email: string;
    active: boolean;
    createdAt: string | null;
};

type PaginatedUsers = {
    data: TenantUser[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string;
        active: string;
    };
}>();

const page = usePage<SharedPageProps>();
const confirm = useConfirm();
const translations = computed(() => page.props.localization.translations.tenantUsers);
const dialogVisible = ref(false);
const editingUser = ref<TenantUser | null>(null);
const search = ref(props.filters.search);
const activeFilter = ref(props.filters.active);
const statusOptions = computed(() => [
    { label: translations.value.allStatuses, value: '' },
    { label: translations.value.activeOnly, value: 'active' },
    { label: translations.value.inactiveOnly, value: 'inactive' },
]);
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    active: true,
});

const resultLabel = computed(() =>
    translations.value.results
        .replace(':total', String(props.users.total))
        .replace(':from', String(props.users.from ?? 0))
        .replace(':to', String(props.users.to ?? 0)),
);

function formatDate(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.DateTimeFormat(page.props.localization.locale, {
        dateStyle: 'medium',
    }).format(new Date(value));
}

function openCreateDialog(): void {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.active = true;
    dialogVisible.value = true;
}

function openEditDialog(user: TenantUser): void {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.active = user.active;
    dialogVisible.value = true;
}

function closeDialog(): void {
    if (!form.processing) {
        dialogVisible.value = false;
    }
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            form.reset();
        },
    };

    if (editingUser.value !== null) {
        form.patch(`/tenant/users/${editingUser.value.id}`, options);

        return;
    }

    form.post('/tenant/users', options);
}

function applyFilters(): void {
    router.get(
        '/tenant/users',
        {
            search: search.value || undefined,
            active: activeFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    activeFilter.value = '';
    applyFilters();
}

function confirmDelete(user: TenantUser): void {
    confirm.require({
        header: translations.value.deleteTitle,
        message: translations.value.deleteConfirmation.replace(':name', user.name),
        icon: 'pi pi-exclamation-triangle',
        blockScroll: true,
        defaultFocus: 'reject',
        rejectLabel: translations.value.cancel,
        acceptLabel: translations.value.deleteSubmit,
        rejectProps: {
            severity: 'secondary',
            text: true,
        },
        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-trash',
        },
        accept: () => {
            router.delete(`/tenant/users/${user.id}`, { preserveScroll: true });
        },
    });
}
</script>

<template>
    <Head :title="translations.headTitle" />
    <AppLayout>
        <main class="tenant-page-shell">
            <header class="tenant-page-heading">
                <div>
                    <p class="eyebrow">{{ translations.eyebrow }}</p>
                    <h1>{{ translations.title }}</h1>
                    <p>{{ translations.description }}</p>
                </div>
                <Button
                    icon="pi pi-user-plus"
                    :label="translations.add"
                    @click="openCreateDialog"
                />
            </header>

            <section class="tenant-data-panel" :aria-label="translations.title">
                <form class="tenant-filter-bar" @submit.prevent="applyFilters">
                    <label class="tenant-search-field">
                        <span class="sr-only">{{ translations.searchPlaceholder }}</span>
                        <i class="pi pi-search" aria-hidden="true"></i>
                        <InputText
                            v-model="search"
                            type="search"
                            :placeholder="translations.searchPlaceholder"
                        />
                    </label>
                    <Select
                        v-model="activeFilter"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :aria-label="translations.active"
                    />
                    <Button type="submit" severity="secondary" :label="translations.filter" />
                    <Button
                        v-if="search || activeFilter"
                        type="button"
                        severity="secondary"
                        text
                        :label="translations.clearFilters"
                        @click="clearFilters"
                    />
                </form>

                <div class="tenant-table-scroll">
                    <table class="tenant-table">
                        <thead>
                            <tr>
                                <th>{{ translations.name }}</th>
                                <th>{{ translations.email }}</th>
                                <th>{{ translations.active }}</th>
                                <th>{{ translations.createdAt }}</th>
                                <th class="tenant-table-action-heading">
                                    <span class="sr-only">{{ translations.actions }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id">
                                <td>
                                    <div class="tenant-person-cell">
                                        <span>{{ user.name.slice(0, 2).toUpperCase() }}</span>
                                        <strong>{{ user.name }}</strong>
                                    </div>
                                </td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span
                                        class="tenant-status-badge"
                                        :class="{ 'tenant-status-badge-inactive': !user.active }"
                                    >
                                        {{
                                            user.active
                                                ? translations.activeLabel
                                                : translations.inactiveLabel
                                        }}
                                    </span>
                                </td>
                                <td>{{ formatDate(user.createdAt) }}</td>
                                <td class="tenant-table-action-cell">
                                    <div class="tenant-row-actions">
                                        <Button
                                            icon="pi pi-pencil"
                                            severity="secondary"
                                            text
                                            rounded
                                            :aria-label="
                                                translations.edit.replace(':name', user.name)
                                            "
                                            @click="openEditDialog(user)"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            severity="danger"
                                            text
                                            rounded
                                            :aria-label="
                                                translations.delete.replace(':name', user.name)
                                            "
                                            @click="confirmDelete(user)"
                                        />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="tenant-table-empty">
                                    <i class="pi pi-users" aria-hidden="true"></i>
                                    {{ translations.empty }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer v-if="users.total > 0" class="tenant-pagination">
                    <span>{{ resultLabel }}</span>
                    <div>
                        <Link
                            v-if="users.prev_page_url"
                            :href="users.prev_page_url"
                            class="tenant-pagination-link"
                            preserve-state
                        >
                            <i class="pi pi-angle-left" aria-hidden="true"></i>
                            {{ translations.previous }}
                        </Link>
                        <span class="tenant-pagination-current">
                            {{ users.current_page }} / {{ users.last_page }}
                        </span>
                        <Link
                            v-if="users.next_page_url"
                            :href="users.next_page_url"
                            class="tenant-pagination-link"
                            preserve-state
                        >
                            {{ translations.next }}
                            <i class="pi pi-angle-right" aria-hidden="true"></i>
                        </Link>
                    </div>
                </footer>
            </section>
        </main>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :closable="!form.processing"
            :header="editingUser ? translations.editTitle : translations.createTitle"
            class="tenant-form-dialog"
        >
            <form id="tenant-user-form" class="tenant-dialog-form" @submit.prevent="submit">
                <label class="tenant-form-field">
                    <span>{{ translations.name }}</span>
                    <InputText
                        v-model="form.name"
                        autocomplete="name"
                        :invalid="Boolean(form.errors.name)"
                    />
                    <small v-if="form.errors.name" role="alert">{{ form.errors.name }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.email }}</span>
                    <InputText
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        :invalid="Boolean(form.errors.email)"
                    />
                    <small v-if="form.errors.email" role="alert">{{ form.errors.email }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.password }}</span>
                    <Password
                        v-model="form.password"
                        :feedback="false"
                        toggle-mask
                        autocomplete="new-password"
                        :invalid="Boolean(form.errors.password)"
                    />
                    <small class="tenant-form-hint">{{ translations.passwordHint }}</small>
                    <small v-if="form.errors.password" role="alert">{{
                        form.errors.password
                    }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.passwordConfirmation }}</span>
                    <Password
                        v-model="form.password_confirmation"
                        :feedback="false"
                        toggle-mask
                        autocomplete="new-password"
                    />
                </label>
                <label class="tenant-checkbox-field">
                    <Checkbox v-model="form.active" binary />
                    <span>{{ translations.activeLabel }}</span>
                </label>
            </form>
            <template #footer>
                <Button
                    severity="secondary"
                    text
                    :label="translations.cancel"
                    :disabled="form.processing"
                    @click="closeDialog"
                />
                <Button
                    type="submit"
                    form="tenant-user-form"
                    icon="pi pi-check"
                    :label="
                        form.processing
                            ? translations.processing
                            : editingUser
                              ? translations.updateSubmit
                              : translations.createSubmit
                    "
                    :loading="form.processing"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
