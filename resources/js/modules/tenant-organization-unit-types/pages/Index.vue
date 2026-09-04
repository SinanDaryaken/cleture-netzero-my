<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedPageProps } from '../../identity-access/types';

type OrganizationUnitType = {
    id: string;
    name: string;
    active: boolean;
    sortOrder: number;
    unitsCount: number;
};

const props = defineProps<{
    organizationUnitTypes: OrganizationUnitType[];
}>();

const page = usePage<SharedPageProps>();
const confirm = useConfirm();
const translations = computed(
    () => page.props.localization.translations.tenantOrganizationUnitTypes,
);
const editingType = ref<OrganizationUnitType | null>(null);
const dialogVisible = ref(false);
const form = useForm({
    name: '',
    active: true,
    sort_order: 0,
});

function openCreateDialog(): void {
    editingType.value = null;
    form.reset();
    form.clearErrors();
    form.sort_order = props.organizationUnitTypes.length;
    dialogVisible.value = true;
}

function openEditDialog(type: OrganizationUnitType): void {
    editingType.value = type;
    form.clearErrors();
    form.name = type.name;
    form.active = type.active;
    form.sort_order = type.sortOrder;
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

    if (editingType.value !== null) {
        form.patch(`/tenant/organization-unit-types/${editingType.value.id}`, options);

        return;
    }

    form.post('/tenant/organization-unit-types', options);
}

function confirmDelete(type: OrganizationUnitType): void {
    if (type.unitsCount > 0) {
        return;
    }

    confirm.require({
        header: translations.value.deleteTitle,
        message: translations.value.deleteConfirmation.replace(':name', type.name),
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: translations.value.cancel,
        acceptLabel: translations.value.deleteSubmit,
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/tenant/organization-unit-types/${type.id}`, {
                preserveScroll: true,
            });
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
                <Button icon="pi pi-plus" :label="translations.add" @click="openCreateDialog" />
            </header>

            <section class="tenant-data-panel">
                <div class="tenant-table-scroll">
                    <table class="tenant-table organization-unit-types-table">
                        <thead>
                            <tr>
                                <th>{{ translations.sortOrder }}</th>
                                <th>{{ translations.status }}</th>
                                <th>{{ translations.listName }}</th>
                                <th>{{ translations.unitsCount }}</th>
                                <th class="tenant-table-action-heading">
                                    {{ translations.actions }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="type in organizationUnitTypes" :key="type.id">
                                <td>{{ type.sortOrder }}</td>
                                <td>
                                    <span
                                        class="tenant-status-badge"
                                        :class="{ 'tenant-status-badge-inactive': !type.active }"
                                    >
                                        {{
                                            type.active
                                                ? translations.activeLabel
                                                : translations.inactiveLabel
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <div class="tenant-person-cell">
                                        <span><i class="pi pi-tag" aria-hidden="true"></i></span>
                                        <strong>{{ type.name }}</strong>
                                    </div>
                                </td>
                                <td>{{ type.unitsCount }}</td>
                                <td class="tenant-table-action-cell">
                                    <div class="tenant-row-actions">
                                        <Button
                                            icon="pi pi-pencil"
                                            severity="secondary"
                                            text
                                            rounded
                                            :aria-label="
                                                translations.edit.replace(':name', type.name)
                                            "
                                            @click="openEditDialog(type)"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            severity="danger"
                                            text
                                            rounded
                                            :disabled="type.unitsCount > 0"
                                            :title="
                                                type.unitsCount > 0
                                                    ? translations.deleteDisabled
                                                    : translations.delete.replace(
                                                          ':name',
                                                          type.name,
                                                      )
                                            "
                                            :aria-label="
                                                translations.delete.replace(':name', type.name)
                                            "
                                            @click="confirmDelete(type)"
                                        />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="organizationUnitTypes.length === 0">
                                <td colspan="5" class="tenant-table-empty">
                                    <i class="pi pi-tags" aria-hidden="true"></i>
                                    {{ translations.empty }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :closable="!form.processing"
            :header="editingType ? translations.editTitle : translations.createTitle"
            class="tenant-form-dialog"
        >
            <form
                id="organization-unit-type-form"
                class="tenant-dialog-form"
                @submit.prevent="submit"
            >
                <label class="tenant-form-field">
                    <span>{{ translations.name }}</span>
                    <InputText v-model="form.name" :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" role="alert">{{ form.errors.name }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.sortOrder }}</span>
                    <InputNumber
                        v-model="form.sort_order"
                        :min="0"
                        :use-grouping="false"
                        :invalid="Boolean(form.errors.sort_order)"
                    />
                    <small v-if="form.errors.sort_order" role="alert">{{
                        form.errors.sort_order
                    }}</small>
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
                    form="organization-unit-type-form"
                    icon="pi pi-check"
                    :label="
                        form.processing
                            ? translations.processing
                            : editingType
                              ? translations.updateSubmit
                              : translations.createSubmit
                    "
                    :loading="form.processing"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
