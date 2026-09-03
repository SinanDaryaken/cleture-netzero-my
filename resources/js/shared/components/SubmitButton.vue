<script setup lang="ts">
import Button from 'primevue/button';

withDefaults(
    defineProps<{
        label: string;
        processingLabel: string;
        processing?: boolean;
        icon?: string;
    }>(),
    {
        processing: false,
        icon: undefined,
    },
);
</script>

<template>
    <Button
        class="submit-button"
        type="submit"
        :label="processing ? processingLabel : label"
        :loading="processing"
        :disabled="processing"
        :icon="icon"
    />
</template>
