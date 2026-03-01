<template>
    <div class="mb-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
        <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
            Cantidad de Tiempo
        </label>

        <!-- Info del rango seleccionado -->
        <div v-if="selectedPricingRange" class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-700">
            <div class="text-xs text-blue-700 dark:text-blue-300">
                <i class="pi pi-info-circle mr-1"></i>
                {{ selectedPricingRange.formatted_time_range }} = {{ selectedPricingRange.duration_hours }}h por unidad
            </div>
        </div>

        <div class="flex gap-2">
            <InputNumber 
                :model-value="modelValue" 
                @update:model-value="$emit('update:modelValue', $event)"
                :min="1"
                :max="24"
                showButtons
                class="flex-1"
                :disabled="isTimerRunning || !selectedPricingRange"
            />
            <Button 
                :label="timeUnit" 
                severity="secondary"
                disabled
            />
        </div>

        <!-- Tiempo total -->
        <div v-if="selectedPricingRange && totalHours > 0" class="mt-2 text-xs text-center text-surface-600 dark:text-surface-400">
            <i class="pi pi-clock mr-1"></i>
            Total: <span class="font-bold">{{ totalHours }}h</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import type { RateType, PricingRange } from '../interface/Useroomservicestore';

interface Props {
    modelValue: number;
    selectedRate: RateType | null;
    selectedPricingRange?: PricingRange | null;
    isTimerRunning: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: number): void;
}

const props = defineProps<Props>();
defineEmits<Emits>();

const timeUnit = computed(() => {
    if (!props.selectedRate) return '';
    
    const units: Record<string, string> = {
        'HOURLY': 'Hora(s)',
        'DAILY': 'Día(s)',
        'NIGHTLY': 'Noche(s)'
    };
    
    return units[props.selectedRate.code] || 'Unidad(es)';
});

const totalHours = computed(() => {
    if (!props.selectedPricingRange) return 0;
    return props.selectedPricingRange.duration_hours * props.modelValue;
});
</script>