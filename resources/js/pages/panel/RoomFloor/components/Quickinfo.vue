<template>
    <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg border border-surface-200 dark:border-surface-700">
        <h4 class="font-semibold mb-3 text-surface-900 dark:text-surface-0">
            <i class="pi pi-info-circle mr-2"></i>Información Rápida
        </h4>
        <div class="space-y-2 text-sm">
            <InfoRow 
                label="Tipo"
                :value="roomData?.room_type?.name"
            />
            <InfoRow 
                label="Capacidad"
                :value="`${roomData?.room_type?.capacity} persona(s)`"
            />
            <InfoRow 
                label="Moneda"
                :value="`${selectedCurrency?.symbol} ${selectedCurrency?.code}`"
                value-class="text-green-600 dark:text-green-400"
            />
            <InfoRow 
                label="Tarifa"
                :value="rateLabel"
                value-class="text-green-600 dark:text-green-400"
            />
            <InfoRow 
                v-if="selectedPricingRange"
                label="Rango"
                :value="selectedPricingRange.formatted_time_range"
                value-class="text-primary-600 dark:text-primary-400"
            />
            <InfoRow 
                v-if="selectedPricingRange"
                label="Precio"
                :value="`${selectedCurrency?.symbol || 'S/'} ${selectedPricingRange.price.toFixed(2)}`"
                value-class="font-bold text-primary-600 dark:text-primary-400"
            />
            <InfoRow 
                label="Comprobante"
                :value="voucherType.toUpperCase()"
                value-class="text-primary-600 dark:text-primary-400"
            />
            <InfoRow 
                v-if="selectedClient"
                label="Cliente"
                :value="selectedClient?.name"
                value-class="text-blue-600 dark:text-blue-400"
                with-border
            />
            <InfoRow 
                v-if="currentBookingId"
                label="Booking ID"
                :value="currentBookingId"
                value-class="text-purple-600 dark:text-purple-400 text-xs"
                with-border
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InfoRow from './Inforow.vue';
import type { RoomData, Currency, Customer, RateType, PricingRange, VoucherType } from '../interface/Useroomservicestore';

interface Props {
    roomData?: RoomData | null;
    selectedCurrency?: Currency | null;
    selectedRate: RateType | null;
    selectedPricingRange?: PricingRange | null;
    voucherType: VoucherType;
    selectedClient?: Customer | null;
    currentBookingId: string | null;
}

const props = defineProps<Props>();

const rateLabel = computed(() => {
    if (!props.selectedRate) return 'No seleccionada';
    return props.selectedRate.display_name || props.selectedRate.name;
});
</script>