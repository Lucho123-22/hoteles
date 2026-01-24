<template>
    <div class="flex flex-col gap-1 text-sm md:text-right">
        <div class="flex items-center gap-2 text-surface-600 dark:text-surface-400">
            <i class="pi pi-user text-xs"></i>
            <span class="font-medium">{{ customer }}</span>
        </div>
        <div v-if="checkIn" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
            <i class="pi pi-sign-in"></i>
            <span>{{ formattedCheckIn }}</span>
        </div>
        <div v-if="checkOut" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
            <i class="pi pi-sign-out"></i>
            <span>{{ formattedCheckOut }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatCheckIn, formatCheckOut } from '../interface/Roommanagement';

const props = defineProps<{
    customer: string;
    checkIn?: string | null;
    checkOut?: string | null;
}>();

const formattedCheckIn = computed(() => formatCheckIn(props.checkIn || null));
const formattedCheckOut = computed(() => formatCheckOut(props.checkOut || null));
</script>