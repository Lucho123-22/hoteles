<template>
    <div 
        class="bg-surface-100 dark:bg-surface-700 px-4 py-2 rounded-lg transition-all duration-300"
        :class="timerClasses"
    >
        <div class="flex items-center gap-2" :class="centered ? 'justify-center' : ''">
            <i class="pi pi-clock text-lg" :class="iconClasses"></i>
            <span class="font-mono text-lg font-semibold" :class="textClasses">
                {{ remainingTime }}
            </span>
        </div>
        <div 
            v-if="statusMessage" 
            class="text-xs font-semibold mt-1"
            :class="[messageClasses, centered ? 'text-center' : '']"
        >
            {{ statusMessage }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoomTimer } from '../interface/Roommanagement';

// ============================================
// PROPS - IMPORTANTE: Ahora usa roomId
// ============================================
const props = defineProps<{
    roomId: string;  // ← CAMBIO IMPORTANTE: Ya no usa check_in/check_out
    centered?: boolean;
}>();

// ============================================
// COMPOSABLE
// ============================================
const timer = useRoomTimer();

// ============================================
// COMPUTED - Usa roomId en lugar de check_in/check_out
// ============================================
const remainingTime = computed(() => timer.getRemainingTime(props.roomId));
const isNear = computed(() => timer.isNear(props.roomId));
const isExpired = computed(() => timer.isExpired(props.roomId));
const isSuspicious = computed(() => timer.isSuspicious(props.roomId));

const timerClasses = computed(() => ({
    'animate-pulse bg-orange-100 dark:bg-orange-900/30 border-2 border-orange-400': isNear.value,
    'animate-bounce bg-red-100 dark:bg-red-900/30 border-2 border-red-500': isExpired.value,
    'bg-yellow-100 dark:bg-yellow-900/30 border-2 border-yellow-400': isSuspicious.value
}));

const iconClasses = computed(() => ({
    'text-orange-600': isNear.value && !isExpired.value,
    'text-red-600': isExpired.value,
    'text-yellow-600': isSuspicious.value,
    'text-primary-600': !isNear.value && !isExpired.value && !isSuspicious.value
}));

const textClasses = computed(() => ({
    'text-orange-600 dark:text-orange-400': isNear.value && !isExpired.value,
    'text-red-600 dark:text-red-400': isExpired.value,
    'text-yellow-600 dark:text-yellow-400': isSuspicious.value,
    'text-primary-700 dark:text-primary-300': !isNear.value && !isExpired.value && !isSuspicious.value
}));

const messageClasses = computed(() => ({
    'text-red-600 dark:text-red-400': isExpired.value,
    'text-orange-600 dark:text-orange-400': isNear.value && !isExpired.value,
    'text-yellow-600 dark:text-yellow-400': isSuspicious.value,
    'text-surface-500 dark:text-surface-400': !isNear.value && !isExpired.value && !isSuspicious.value
}));

const statusMessage = computed(() => {
    if (isExpired.value) return '¡TIEMPO VENCIDO!';
    if (isNear.value) return '¡Próximo a vencer!';
    if (isSuspicious.value) return '⚠️ Datos sospechosos';
    return 'Tiempo restante';
});
</script>