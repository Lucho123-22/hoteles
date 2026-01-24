<template>
    <div :class="rowClass">
        <span class="text-surface-600 dark:text-surface-400">{{ label }}:</span>
        <span :class="valueClassComputed">{{ value || 'N/A' }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    label: string;
    value?: string | number | null;
    valueClass?: string;
    withBorder?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    valueClass: 'font-semibold text-surface-900 dark:text-surface-0',
    withBorder: false
});

const rowClass = computed(() => {
    const baseClass = 'flex justify-between';
    return props.withBorder ? `${baseClass} pt-2 border-t` : baseClass;
});

const valueClassComputed = computed(() => {
    return props.valueClass || 'font-semibold text-surface-900 dark:text-surface-0';
});
</script>