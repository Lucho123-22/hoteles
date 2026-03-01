<template>
    <div 
        :class="[
            'p-8 rounded-xl border-2 mb-6',
            containerClass
        ]"
    >
        <div class="text-center">
            <i :class="clockIconClass"></i>

            <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">
                {{ isRunning ? 'Tiempo Restante' : 'Tiempo a Contratar' }}
            </p>

            <div :class="timeDisplayClass">
                {{ formattedTime }}
            </div>

            <!-- ===== MENSAJES DE ESTADO DINÁMICOS ===== -->

            <!-- Tiempo normal en curso -->
            <div v-if="isRunning && !isTimeUp" class="mt-2">
                <p class="text-xs text-surface-500 dark:text-surface-400">En curso</p>
            </div>

            <!-- Tiempo agotado pero dentro de tolerancia -->
            <div 
                v-else-if="isTimeUp && isWithinTolerance"
                class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-600 rounded-lg"
            >
                <p class="text-sm font-bold text-yellow-700 dark:text-yellow-300 flex items-center justify-center gap-2">
                    <i class="pi pi-clock"></i>
                    ⚠️ Tiempo contratado agotado
                </p>
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                    Tienes <strong>{{ toleranceMinutes }} min de tolerancia</strong> según las políticas del establecimiento
                </p>
                <p class="text-xs text-yellow-500 dark:text-yellow-500 mt-1">
                    Tiempo restante de tolerancia: <strong>{{ toleranceRemainingFormatted }}</strong>
                </p>
            </div>

            <!-- Tolerancia agotada — cobrando penalización -->
            <div 
                v-else-if="isTimeUp && !isWithinTolerance && penaltyActive"
                class="mt-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-600 rounded-lg"
            >
                <p class="text-sm font-bold text-red-700 dark:text-red-300 flex items-center justify-center gap-2">
                    <i class="pi pi-exclamation-triangle"></i>
                    🚨 Tolerancia agotada
                </p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                    Se cobra <strong>{{ currencySymbol }} {{ amountPerInterval }}</strong> 
                    cada <strong>{{ chargeIntervalMinutes }} min</strong> adicionales
                </p>
                <p class="text-xs text-red-500 dark:text-red-500 mt-1 font-semibold">
                    Penalización acumulada: {{ currencySymbol }} {{ penaltyAmount.toFixed(2) }}
                </p>
            </div>

            <!-- Tiempo agotado sin tolerancia ni penalización configurada -->
            <div 
                v-else-if="isTimeUp && !toleranceMinutes && !penaltyActive"
                class="mt-3 p-3 bg-orange-50 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-600 rounded-lg"
            >
                <p class="text-sm font-bold text-orange-700 dark:text-orange-300">
                    ⏰ Tiempo agotado
                </p>
                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                    Por favor finalice el servicio
                </p>
            </div>

            <!-- Sin actividad -->
            <div v-else-if="!isRunning" class="mt-2">
                <p class="text-xs text-surface-500 dark:text-surface-400">Sin actividad</p>
            </div>

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
    // ✅ Datos dinámicos de políticas
    toleranceMinutes?: number;
    penaltyActive?: boolean;
    chargeIntervalMinutes?: number;
    amountPerInterval?: number;
    penaltyAmount?: number;
    currencySymbol?: string;
}

const props = withDefaults(defineProps<Props>(), {
    toleranceMinutes: 0,
    penaltyActive: false,
    chargeIntervalMinutes: 15,
    amountPerInterval: 0,
    penaltyAmount: 0,
    currencySymbol: 'S/',
});

// ¿Se acabó el tiempo contratado?
const isTimeUp = computed(() => props.isRunning && props.remainingSeconds <= 0);
// Segundos excedidos desde que acabó el tiempo
const exceededSeconds = computed(() => 
    isTimeUp.value ? Math.abs(props.remainingSeconds) : 0
);

// Segundos de tolerancia total
const toleranceSeconds = computed(() => props.toleranceMinutes * 60);

// ¿Está dentro de la tolerancia?
const isWithinTolerance = computed(() => 
    isTimeUp.value && 
    props.toleranceMinutes > 0 && 
    exceededSeconds.value <= toleranceSeconds.value
);

// Segundos restantes de tolerancia
const toleranceRemainingSeconds = computed(() => {
    if (!isWithinTolerance.value) return 0;
    return toleranceSeconds.value - exceededSeconds.value;
});

// Tolerancia restante formateada mm:ss
const toleranceRemainingFormatted = computed(() => {
    const secs = toleranceRemainingSeconds.value;
    const mins = Math.floor(secs / 60);
    const s    = Math.floor(secs % 60);
    return `${String(mins).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const isLowTime = computed(() => 
    props.isRunning && props.remainingSeconds > 0 && props.remainingSeconds <= 300
);

// ===== CLASES DINÁMICAS =====

const containerClass = computed(() => {
    if (!props.isRunning) {
        return 'bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 border-primary-200 dark:border-primary-700';
    }
    if (isTimeUp.value && !isWithinTolerance.value && props.penaltyActive) {
        return 'bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-300 dark:border-red-600';
    }
    if (isTimeUp.value && isWithinTolerance.value) {
        return 'bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border-yellow-300 dark:border-yellow-600';
    }
    if (isLowTime.value) {
        return 'bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 border-orange-300 dark:border-orange-600';
    }
    return 'bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 border-primary-200 dark:border-primary-700';
});

const clockIconClass = computed(() => {
    const base = 'pi pi-clock text-4xl mb-4';
    if (isTimeUp.value && !isWithinTolerance.value && props.penaltyActive) {
        return `${base} text-red-600 dark:text-red-400 animate-pulse`;
    }
    if (isTimeUp.value && isWithinTolerance.value) {
        return `${base} text-yellow-600 dark:text-yellow-400 animate-pulse`;
    }
    if (isLowTime.value) {
        return `${base} text-orange-600 dark:text-orange-400 animate-pulse`;
    }
    return `${base} text-primary-600 dark:text-primary-400`;
});

const timeDisplayClass = computed(() => {
    const base = 'font-mono text-5xl font-bold mb-2';
    if (isTimeUp.value && !isWithinTolerance.value && props.penaltyActive) {
        return `${base} text-red-700 dark:text-red-300`;
    }
    if (isTimeUp.value && isWithinTolerance.value) {
        return `${base} text-yellow-700 dark:text-yellow-300`;
    }
    if (isLowTime.value) {
        return `${base} text-orange-700 dark:text-orange-300`;
    }
    return `${base} text-primary-700 dark:text-primary-300`;
});

const progressBarClass = computed(() => {
    const base = 'h-2 rounded-full transition-all duration-1000';
    if (isTimeUp.value && !isWithinTolerance.value && props.penaltyActive) {
        return `${base} bg-red-500`;
    }
    if (isTimeUp.value && isWithinTolerance.value) {
        return `${base} bg-yellow-500`;
    }
    if (isLowTime.value) {
        return `${base} bg-orange-500`;
    }
    return `${base} bg-primary-500`;
});
</script>