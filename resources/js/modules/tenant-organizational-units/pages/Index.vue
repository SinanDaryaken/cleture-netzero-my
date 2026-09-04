<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Select from 'primevue/select';
import { computed, ref, watch } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedPageProps } from '../../identity-access/types';

type OrganizationalUnit = {
    id: string;
    parentId: string | null;
    organizationUnitTypeId: string | null;
    name: string;
    markAsCompany: boolean;
    markAsFacility: boolean;
    sortOrder: number;
};

type OrganizationUnitType = {
    id: string;
    name: string;
    active: boolean;
};

type FlatOrganizationalUnit = OrganizationalUnit & {
    depth: number;
};

const props = defineProps<{
    units: OrganizationalUnit[];
    organizationUnitTypes: OrganizationUnitType[];
}>();

const page = usePage<SharedPageProps>();
const translations = computed(() => page.props.localization.translations.tenantOrganizationalUnits);
const selectedUnitId = ref<string | null>(props.units[0]?.id ?? null);
const editingUnit = ref<OrganizationalUnit | null>(null);
const dialogVisible = ref(false);
const viewMode = ref<'tree' | 'list'>('tree');
const classification = ref<'standard' | 'company' | 'facility'>('standard');
const form = useForm({
    name: '',
    parent_id: null as string | null,
    organization_unit_type_id: null as string | null,
    mark_as_company: false,
    mark_as_facility: false,
    sort_order: 0,
});

const unitsById = computed(() => new Map(props.units.map((unit) => [unit.id, unit])));
const flatUnits = computed<FlatOrganizationalUnit[]>(() => {
    const childrenByParent = new Map<string | null, OrganizationalUnit[]>();

    for (const unit of props.units) {
        const parentId =
            unit.parentId !== null && unitsById.value.has(unit.parentId) ? unit.parentId : null;
        const siblings = childrenByParent.get(parentId) ?? [];
        siblings.push(unit);
        childrenByParent.set(parentId, siblings);
    }

    const result: FlatOrganizationalUnit[] = [];
    const visited = new Set<string>();
    const append = (parentId: string | null, depth: number): void => {
        for (const unit of childrenByParent.get(parentId) ?? []) {
            if (visited.has(unit.id)) {
                continue;
            }

            visited.add(unit.id);
            result.push({ ...unit, depth });
            append(unit.id, depth + 1);
        }
    };

    append(null, 0);

    for (const unit of props.units) {
        if (!visited.has(unit.id)) {
            result.push({ ...unit, depth: 0 });
        }
    }

    return result;
});
const selectedUnit = computed(() =>
    selectedUnitId.value === null ? null : (unitsById.value.get(selectedUnitId.value) ?? null),
);
const organizationUnitTypesById = computed(
    () => new Map(props.organizationUnitTypes.map((type) => [type.id, type])),
);
const organizationUnitTypeOptions = computed(() =>
    props.organizationUnitTypes
        .filter(
            (type) =>
                type.active ||
                (editingUnit.value !== null &&
                    type.id === editingUnit.value.organizationUnitTypeId),
        )
        .map((type) => ({
            label: type.active ? type.name : `${type.name} (${translations.value.inactiveType})`,
            value: type.id,
        })),
);
const parentOptions = computed(() => [
    { label: translations.value.root, value: null },
    ...flatUnits.value
        .filter((unit) => isAllowedParent(unit.id))
        .map((unit) => ({
            label: `${'— '.repeat(unit.depth)}${unit.name}`,
            value: unit.id,
        })),
]);

watch(
    () => props.units,
    (units) => {
        if (
            selectedUnitId.value === null ||
            !units.some((unit) => unit.id === selectedUnitId.value)
        ) {
            selectedUnitId.value = units[0]?.id ?? null;
        }
    },
);

function isAllowedParent(candidateId: string): boolean {
    if (editingUnit.value === null) {
        return true;
    }

    if (candidateId === editingUnit.value.id) {
        return false;
    }

    let parentId = unitsById.value.get(candidateId)?.parentId ?? null;
    const visited = new Set<string>();

    while (parentId !== null && !visited.has(parentId)) {
        if (parentId === editingUnit.value.id) {
            return false;
        }

        visited.add(parentId);
        parentId = unitsById.value.get(parentId)?.parentId ?? null;
    }

    return true;
}

function unitType(unit: OrganizationalUnit): string {
    if (unit.markAsCompany) {
        return translations.value.company;
    }

    if (unit.markAsFacility) {
        return translations.value.facility;
    }

    return translations.value.standard;
}

function assignedType(unit: OrganizationalUnit): string {
    if (unit.organizationUnitTypeId === null) {
        return translations.value.noType;
    }

    return (
        organizationUnitTypesById.value.get(unit.organizationUnitTypeId)?.name ??
        translations.value.noType
    );
}

function unitIcon(unit: OrganizationalUnit): string {
    if (unit.markAsCompany) {
        return 'pi pi-building';
    }

    if (unit.markAsFacility) {
        return 'pi pi-warehouse';
    }

    return 'pi pi-folder';
}

function openCreateDialog(): void {
    editingUnit.value = null;
    classification.value = 'standard';
    form.reset();
    form.clearErrors();
    form.parent_id = selectedUnit.value?.id ?? null;
    form.organization_unit_type_id = null;
    form.sort_order = props.units.length;
    dialogVisible.value = true;
}

function openEditDialog(unit: OrganizationalUnit): void {
    editingUnit.value = unit;
    classification.value = unit.markAsCompany
        ? 'company'
        : unit.markAsFacility
          ? 'facility'
          : 'standard';
    form.clearErrors();
    form.name = unit.name;
    form.parent_id = unit.parentId;
    form.organization_unit_type_id = unit.organizationUnitTypeId;
    form.mark_as_company = unit.markAsCompany;
    form.mark_as_facility = unit.markAsFacility;
    form.sort_order = unit.sortOrder;
    dialogVisible.value = true;
}

function closeDialog(): void {
    if (!form.processing) {
        dialogVisible.value = false;
    }
}

function submit(): void {
    form.mark_as_company = classification.value === 'company';
    form.mark_as_facility = classification.value === 'facility';

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            form.reset();
        },
    };

    if (editingUnit.value !== null) {
        form.patch(`/tenant/organizational-units/${editingUnit.value.id}`, options);

        return;
    }

    form.post('/tenant/organizational-units', options);
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
                <div class="organizational-unit-heading-actions">
                    <div class="tenant-view-switch" :aria-label="translations.title">
                        <button
                            type="button"
                            :aria-pressed="viewMode === 'tree'"
                            @click="viewMode = 'tree'"
                        >
                            <i class="pi pi-sitemap" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            :aria-pressed="viewMode === 'list'"
                            @click="viewMode = 'list'"
                        >
                            <i class="pi pi-list" aria-hidden="true"></i>
                        </button>
                    </div>
                    <Button icon="pi pi-plus" :label="translations.add" @click="openCreateDialog" />
                </div>
            </header>

            <div v-if="units.length === 0" class="tenant-empty-state">
                <i class="pi pi-sitemap" aria-hidden="true"></i>
                <p>{{ translations.empty }}</p>
                <Button icon="pi pi-plus" :label="translations.add" @click="openCreateDialog" />
            </div>

            <div v-else-if="viewMode === 'tree'" class="organizational-unit-grid">
                <section class="tenant-data-panel organizational-unit-tree">
                    <header class="tenant-panel-heading">
                        <h2>{{ translations.treeTitle }}</h2>
                    </header>
                    <div class="organizational-unit-tree-items">
                        <button
                            v-for="unit in flatUnits"
                            :key="unit.id"
                            type="button"
                            :class="{ 'is-selected': selectedUnitId === unit.id }"
                            :style="{ '--unit-depth': unit.depth }"
                            @click="selectedUnitId = unit.id"
                        >
                            <i :class="unitIcon(unit)" aria-hidden="true"></i>
                            <span>{{ unit.name }}</span>
                            <small>{{ assignedType(unit) }} · {{ unitType(unit) }}</small>
                        </button>
                    </div>
                </section>

                <section class="tenant-data-panel organizational-unit-details">
                    <header class="tenant-panel-heading">
                        <h2>{{ translations.detailsTitle }}</h2>
                        <Button
                            v-if="selectedUnit"
                            icon="pi pi-pencil"
                            severity="secondary"
                            :label="translations.edit"
                            @click="openEditDialog(selectedUnit)"
                        />
                    </header>
                    <div v-if="selectedUnit" class="organizational-unit-detail-body">
                        <span class="organizational-unit-detail-icon">
                            <i :class="unitIcon(selectedUnit)" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3>{{ selectedUnit.name }}</h3>
                            <p>{{ assignedType(selectedUnit) }}</p>
                        </div>
                        <dl>
                            <div>
                                <dt>{{ translations.parent }}</dt>
                                <dd>
                                    {{
                                        selectedUnit.parentId
                                            ? unitsById.get(selectedUnit.parentId)?.name
                                            : translations.root
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt>{{ translations.structuralRole }}</dt>
                                <dd>{{ unitType(selectedUnit) }}</dd>
                            </div>
                            <div>
                                <dt>{{ translations.sortOrder }}</dt>
                                <dd>{{ selectedUnit.sortOrder }}</dd>
                            </div>
                        </dl>
                    </div>
                    <p v-else class="tenant-panel-placeholder">{{ translations.selectHint }}</p>
                </section>
            </div>

            <section v-else class="tenant-data-panel">
                <div class="tenant-table-scroll">
                    <table class="tenant-table">
                        <thead>
                            <tr>
                                <th>{{ translations.name }}</th>
                                <th>{{ translations.type }}</th>
                                <th>{{ translations.structuralRole }}</th>
                                <th>{{ translations.parent }}</th>
                                <th>{{ translations.sortOrder }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="unit in flatUnits" :key="unit.id">
                                <td>
                                    <div
                                        class="tenant-person-cell"
                                        :style="{ '--unit-depth': unit.depth }"
                                    >
                                        <span
                                            ><i :class="unitIcon(unit)" aria-hidden="true"></i
                                        ></span>
                                        <strong>{{ unit.name }}</strong>
                                    </div>
                                </td>
                                <td>{{ assignedType(unit) }}</td>
                                <td>{{ unitType(unit) }}</td>
                                <td>
                                    {{
                                        unit.parentId
                                            ? unitsById.get(unit.parentId)?.name
                                            : translations.root
                                    }}
                                </td>
                                <td>{{ unit.sortOrder }}</td>
                                <td class="tenant-table-action-cell">
                                    <Button
                                        icon="pi pi-pencil"
                                        severity="secondary"
                                        text
                                        rounded
                                        :aria-label="translations.editTitle"
                                        @click="openEditDialog(unit)"
                                    />
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
            :header="editingUnit ? translations.editTitle : translations.createTitle"
            class="tenant-form-dialog"
        >
            <form id="organizational-unit-form" class="tenant-dialog-form" @submit.prevent="submit">
                <label class="tenant-form-field">
                    <span>{{ translations.name }}</span>
                    <InputText v-model="form.name" :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" role="alert">{{ form.errors.name }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.parent }}</span>
                    <Select
                        v-model="form.parent_id"
                        :options="parentOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="Boolean(form.errors.parent_id)"
                    />
                    <small v-if="form.errors.parent_id" role="alert">{{
                        form.errors.parent_id
                    }}</small>
                </label>
                <label class="tenant-form-field">
                    <span>{{ translations.type }}</span>
                    <Select
                        v-model="form.organization_unit_type_id"
                        :options="organizationUnitTypeOptions"
                        option-label="label"
                        option-value="value"
                        show-clear
                        :placeholder="translations.noType"
                        :invalid="Boolean(form.errors.organization_unit_type_id)"
                    />
                    <small v-if="form.errors.organization_unit_type_id" role="alert">{{
                        form.errors.organization_unit_type_id
                    }}</small>
                </label>
                <fieldset class="tenant-radio-field">
                    <legend>{{ translations.structuralRole }}</legend>
                    <div class="tenant-radio-options">
                        <label>
                            <RadioButton
                                v-model="classification"
                                input-id="structural-role-standard"
                                value="standard"
                            />
                            <span>{{ translations.standard }}</span>
                        </label>
                        <label>
                            <RadioButton
                                v-model="classification"
                                input-id="structural-role-company"
                                value="company"
                            />
                            <span>{{ translations.company }}</span>
                        </label>
                        <label>
                            <RadioButton
                                v-model="classification"
                                input-id="structural-role-facility"
                                value="facility"
                            />
                            <span>{{ translations.facility }}</span>
                        </label>
                    </div>
                    <small v-if="form.errors.mark_as_company" role="alert">{{
                        form.errors.mark_as_company
                    }}</small>
                </fieldset>
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
                    form="organizational-unit-form"
                    icon="pi pi-check"
                    :label="
                        form.processing
                            ? translations.processing
                            : editingUnit
                              ? translations.updateSubmit
                              : translations.createSubmit
                    "
                    :loading="form.processing"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
