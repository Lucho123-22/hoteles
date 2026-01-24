<template>
    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 p-8 rounded-xl border-2 border-primary-200 dark:border-primary-700 mb-6">
        <div class="text-center">
            <i :class="clockIconClass"></i>
            <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">
                {{ isRunning ? 'Tiempo Restante' : 'Tiempo a Contratar' }}
            </p>
            <div :class="timeDisplayClass">
                {{ formattedTime }}
            </div>
            <p class="text-xs text-surface-500 dark:text-surface-400">
                {{ statusMessage }}
            </p>
            
            <!-- Barra de progreso -->
            <div v-if="isRunning" class="mt-4">
                <div class="w-full bg-surface-300 dark:bg-surface-600 rounded-full h-2">
                    <div 
                        :class="progressBarClass"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>
                <p class="text-xs mt-2 text-surface-600 dark:text-surface-400">
                    {{ progressPercentage > 0 ? progressPercentage.toFixed(1) : 0 }}% del tiempo restante
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    isRunning: boolean;
    formattedTime: string;
    remainingSeconds: number;
    progressPercentage: number;
}

const props = defineProps<Props>();

const isLowTime = computed(() => props.isRunning && props.remainingSeconds <= 300);
const isTimeUp = computed(() => props.remainingSeconds <= 0);

const clockIconClass = computed(() => {
    const baseClass = 'pi pi-clock text-4xl mb-4';
    return isLowTime.value 
        ? `${baseClass} text-red-600 dark:text-red-400 animate-pulse` 
        : `${baseClass} text-primary-600 dark:text-primary-400`;
});

const timeDisplayClass = computed(() => {
    const baseClass = 'font-mono text-5xl font-bold mb-2';
    return isLowTime.value
        ? `${baseClass} text-red-700 dark:text-red-300`
        : `${baseClass} text-primary-700 dark:text-primary-300`;
});

const progressBarClass = computed(() => {
    const baseClass = 'h-2 rounded-full transition-all duration-1000';
    return isLowTime.value ? `${baseClass} bg-red-500` : `${baseClass} bg-primary-500`;
});

const statusMessage = computed(() => {
    if (!props.isRunning) return 'Sin actividad';
    if (isTimeUp.value) return '¡Tiempo agotado! Se cobrará tiempo extra.';
    return 'En curso';
});
</script>