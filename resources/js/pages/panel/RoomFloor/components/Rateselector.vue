<template>
    <div class="mb-6">
        <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 mb-4 flex items-center gap-2">
                <i class="pi pi-money-bill"></i>
                Seleccionar Tarifa
            </h3>

            <!-- Grid con las tarífas disponibles dinámicamente -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div 
                    v-for="rateType in availableRateTypes"
                    :key="rateType.id"
                    @click="handleSelectRate(rateType)"
                    :class="getRateCardClass(rateType)"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-surface-600 dark:text-surface-400">
                            {{ rateType.display_name || rateType.name }}
                        </span>
                        <i v-if="isSelected(rateType)" class="pi pi-check-circle text-primary-500"></i>
                    </div>

                    <!-- Mostrar rangos de precio disponibles -->
                    <div v-if="getRangesForRateType(rateType).length > 0" class="space-y-2">
                        <div 
                            v-for="range in getRangesForRateType(rateType)"
                            :key="range.id"
                            @click.stop="handleSelectPricingRange(rateType, range)"
                            :class="getPricingRangeClass(range)"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-surface-500 dark:text-surface-400">
                                    {{ range.formatted_time_range }}
                                </span>
                                <i v-if="isPricingRangeSelected(range)" class="pi pi-check-circle text-green-500 text-sm"></i>
                            </div>
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                {{ selectedCurrency?.symbol || 'S/' }} {{ formatPrice(range.price) }}
                            </p>
                        </div>
                    </div>

                    <!-- Mensaje si no hay rangos -->
                    <div v-else class="text-xs text-red-500 dark:text-red-400">
                        Sin precios configurados
                    </div>
                </div>
            </div>

            <!-- Alerta si no hay pricing ranges -->
            <div v-if="availablePricingRanges.length === 0" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="pi pi-times-circle text-red-600 dark:text-red-400"></i>
                    <span class="text-sm text-red-800 dark:text-red-300">
                        No hay precios configurados para esta habitación. Contacta al administrador.
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { RoomData, Currency, RateType, PricingRange } from '../interface/Useroomservicestore';

interface Props {
    roomData?: RoomData | null;
    selectedRate: RateType | null;
    selectedPricingRange?: PricingRange | null;
    selectedCurrency?: Currency | null;
}

interface Emits {
    (e: 'select-rate', rateType: RateType): void;
    (e: 'select-pricing-range', range: PricingRange): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// ==========================================
// COMPUTED PROPERTIES
// ==========================================

const availablePricingRanges = computed(() => {
    return props.roomData?.available_pricing_ranges || [];
});

// Obtener tipos de tarifa únicos de los pricing ranges
const availableRateTypes = computed(() => {
    const rateTypesMap = new Map<string | number, RateType>();
    
    availablePricingRanges.value.forEach(range => {
        if (!rateTypesMap.has(range.rate_type.id)) {
            rateTypesMap.set(range.rate_type.id, range.rate_type);
        }
    });
    
    return Array.from(rateTypesMap.values());
});

// ==========================================
// METHODS
// ==========================================

const getRangesForRateType = (rateType: RateType): PricingRange[] => {
    return availablePricingRanges.value.filter(range => 
        range.rate_type.id === rateType.id
    );
};

const handleSelectRate = (rateType: RateType) => {
    emit('select-rate', rateType);
};

const handleSelectPricingRange = (rateType: RateType, range: PricingRange) => {
    // Primero seleccionar el tipo de tarifa si no está seleccionado
    if (!isSelected(rateType)) {
        emit('select-rate', rateType);
    }
    // Luego seleccionar el rango específico
    emit('select-pricing-range', range);
};

const isSelected = (rateType: RateType): boolean => {
    return props.selectedRate?.id === rateType.id;
};

const isPricingRangeSelected = (range: PricingRange): boolean => {
    return props.selectedPricingRange?.id === range.id;
};

const formatPrice = (price: number): string => {
    return price.toFixed(2);
};

const getRateCardClass = (rateType: RateType) => {
    const baseClass = 'p-4 rounded-lg border-2 cursor-pointer transition-all';
    const selectedClass = 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-lg';
    const unselectedClass = 'border-surface-300 dark:border-surface-600 bg-white dark:bg-surface-800 hover:border-primary-300';
    
    return `${baseClass} ${isSelected(rateType) ? selectedClass : unselectedClass}`;
};

const getPricingRangeClass = (range: PricingRange) => {
    const baseClass = 'p-2 rounded border cursor-pointer transition-all';
    const selectedClass = 'border-green-500 bg-green-50 dark:bg-green-900/30';
    const unselectedClass = 'border-surface-200 dark:border-surface-600 hover:border-green-300';
    
    return `${baseClass} ${isPricingRangeSelected(range) ? selectedClass : unselectedClass}`;
};
</script>