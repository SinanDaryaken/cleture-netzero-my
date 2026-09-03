<script setup lang="ts">
defineProps<{
    message?: string | null;
}>();
</script>

<template>
    <div v-if="message" class="status-message" role="status">
        <i class="pi pi-check-circle" aria-hidden="true"></i>
        <span>{{ message }}</span>
    </div>
</template>
