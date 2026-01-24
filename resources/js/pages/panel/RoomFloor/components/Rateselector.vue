<template>
    <div class="mb-6">
        <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 mb-4 flex items-center gap-2">
                <i class="pi pi-money-bill"></i>
                Seleccionar Tarifa
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div 
                    @click="$emit('select-rate', 'hour')"
                    :class="getRateCardClass('hour')"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Hora</span>
                        <i v-if="selectedRate === 'hour'" class="pi pi-check-circle text-primary-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ selectedCurrency?.symbol || 'S/' }} {{ roomData?.room_type?.base_price_per_hour }}
                    </p>
                </div>

                <div 
                    @click="$emit('select-rate', 'day')"
                    :class="getRateCardClass('day')"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Día</span>
                        <i v-if="selectedRate === 'day'" class="pi pi-check-circle text-primary-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ selectedCurrency?.symbol || 'S/' }} {{ roomData?.room_type?.base_price_per_day }}
                    </p>
                </div>

                <div 
                    @click="$emit('select-rate', 'night')"
                    :class="getRateCardClass('night')"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Noche</span>
                        <i v-if="selectedRate === 'night'" class="pi pi-check-circle text-primary-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ selectedCurrency?.symbol || 'S/' }} {{ roomData?.room_type?.base_price_per_night }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { RoomData, Currency, RateTypeKey } from '../interface/Useroomservicestore';

interface Props {
    roomData?: RoomData | null;
    selectedRate: RateTypeKey | null;
    selectedCurrency?: Currency | null;
}

interface Emits {
    (e: 'select-rate', rate: RateTypeKey): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const getRateCardClass = (rate: RateTypeKey) => {
    const baseClass = 'p-4 rounded-lg border-2 cursor-pointer transition-all';
    const selectedClass = 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-lg';
    const unselectedClass = 'border-surface-300 dark:border-surface-600 bg-white dark:bg-surface-800 hover:border-primary-300';
    
    return `${baseClass} ${props.selectedRate === rate ? selectedClass : unselectedClass}`;
};
</script>