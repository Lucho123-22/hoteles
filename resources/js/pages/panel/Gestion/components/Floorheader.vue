<template>
    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-200 dark:border-primary-800">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300">
                    {{ floor.name }}
                </h3>
                <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                    {{ floor.available_rooms }}/{{ floor.total_rooms }} habitaciones disponibles
                </p>
            </div>
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                Piso {{ floor.floor_number }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Floor } from '../interface/Roommanagement';

defineProps<{
    floor: Floor;
}>();
</script>