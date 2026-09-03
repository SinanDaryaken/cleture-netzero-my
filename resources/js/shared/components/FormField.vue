<script setup lang="ts">
const model = defineModel<string>({ required: true });

withDefaults(
    defineProps<{
        id: string;
        label: string;
        type?: string;
        autocomplete?: string;
        inputmode?: 'email' | 'text';
        maxlength?: number;
        error?: string;
        hint?: string;
        readonly?: boolean;
    }>(),
    {
        type: 'text',
        autocomplete: undefined,
        inputmode: undefined,
        maxlength: 255,
        error: undefined,
        hint: undefined,
        readonly: false,
    },
);
</script>

<template>
    <div class="field">
        <label v-if="label" :for="id">{{ label }}</label>
        <input
            :id="id"
            v-model="model"
            class="field-control"
            :class="{ 'field-control-invalid': error }"
            :type="type"
            :autocomplete="autocomplete"
            :inputmode="inputmode"
            :maxlength="maxlength"
            :readonly="readonly"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error || hint ? id + '-description' : undefined"
        />
        <p v-if="error" :id="id + '-description'" class="field-error">
            <i class="pi pi-exclamation-circle" aria-hidden="true"></i>
            {{ error }}
        </p>
        <p v-else-if="hint" :id="id + '-description'" class="field-hint">{{ hint }}</p>
    </div>
</template>
