<script setup lang="ts">
import cletureIconUrl from '../../../images/cleture-icon.svg';
import cletureLogoUrl from '../../../images/cleture-logo.svg';

withDefaults(
    defineProps<{
        compact?: boolean;
        inverse?: boolean;
    }>(),
    {
        compact: false,
        inverse: false,
    },
);
</script>

<template>
    <div
        class="brand-mark"
        :class="{ 'brand-mark-compact': compact, 'brand-mark-inverse': inverse }"
    >
        <img
            :src="compact ? cletureIconUrl : cletureLogoUrl"
            alt="Cleture"
            :width="compact ? 54 : 206"
            height="60"
        />
    </div>
</template>
