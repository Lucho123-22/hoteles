<template>
    <div class="mb-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
        <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
            Cantidad de Tiempo
        </label>
        <div class="flex gap-2">
            <InputNumber 
                :model-value="modelValue" 
                @update:model-value="$emit('update:modelValue', $event)"
                :min="1"
                :max="24"
                showButtons
                class="flex-1"
                :disabled="isTimerRunning"
            />
            <Button 
                :label="timeUnit" 
                severity="secondary"
                disabled
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import type { RateTypeKey } from '../interface/Useroomservicestore';

interface Props {
    modelValue: number;
    selectedRate: RateTypeKey | null;
    isTimerRunning: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: number): void;
}

const props = defineProps<Props>();
defineEmits<Emits>();

const timeUnit = computed(() => {
    const units: Record<RateTypeKey, string> = {
        hour: 'Hora(s)',
        day: 'Día(s)',
        night: 'Noche(s)'
    };
    return props.selectedRate ? units[props.selectedRate] : '';
});
</script>