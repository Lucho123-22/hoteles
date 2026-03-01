<template>
    <div class="grid grid-cols-12 gap-6">
        <!-- Información Principal -->
        <div class="col-span-12 lg:col-span-8">
            <!-- Encabezado de la Habitación -->
            <RoomHeader 
                :room-data="store.roomData"
                :selected-currency="store.selectedCurrency"
            />

            <!-- Selector de Tarifa -->
            <RateSelector 
                :room-data="store.roomData"
                :selected-rate="store.selectedRate"
                :selected-pricing-range="store.selectedPricingRange"
                :selected-currency="store.selectedCurrency"
                :disabled="store.isTimerRunning"
                @select-rate="store.selectRate"
                @select-pricing-range="store.selectPricingRange"
            />

            <!-- Componente: Registro de Cliente -->
            <CustomerRegistration 
                v-model="store.selectedClient"
                :disabled="store.isTimerRunning"
                class="mb-6"
                @customer-saved="store.setCustomer"
            />

            <ProductSales 
                v-model="store.products"
                :currency-symbol="store.selectedCurrency?.symbol || 'S/'"
                :disabled="false"
                :booking-id="store.currentBookingId"
                :is-service-active="store.isTimerRunning"
                class="mb-6"
            />

            <!-- Componente: Resumen / Boleta -->
            <BillingSummary 
                :room-number="store.roomData?.room_number"
                :room-price="store.currentRoomPrice"
                :room-subtotal="store.isTimerRunning ? store.roomSubtotal : 0"
                :selected-rate="store.selectedRate"
                :selected-pricing-range="store.selectedPricingRange"
                :time-amount="store.timeAmount"
                :products="store.products"
                :currency-symbol="store.selectedCurrency?.symbol || 'S/'"
                :currency-code="store.selectedCurrency?.code || 'PEN'"
                :penalty-amount="store.penaltyAmount"
                :penalty-minutes="store.penaltyMinutes"
                v-model="store.voucherType"
            />
        </div>

        <!-- Panel Lateral - Cronómetro y Acción -->
        <div class="col-span-12 lg:col-span-4">
            <div class="sticky top-6">
                <!-- Estado Actual -->
                <RoomStatus :room-data="store.roomData" />

                <!-- Cronómetro REGRESIVO -->
                <Timer
                    :is-running="store.isTimerRunning"
                    :formatted-time="store.formattedTime"
                    :remaining-seconds="store.remainingSeconds"
                    :progress-percentage="store.progressPercentage"
                    :tolerance-minutes="store.toleranceMinutes"
                    :penalty-active="store.subBranchPolicies?.penalty_settings?.penalty_active ?? store.subBranchPolicies?.penalty?.penalty_active ?? false"
                    :charge-interval-minutes="store.subBranchPolicies?.penalty_settings?.charge_interval_minutes ?? store.subBranchPolicies?.penalty?.charge_interval_minutes ?? 15"
                    :amount-per-interval="store.subBranchPolicies?.penalty_settings?.amount_per_interval ?? store.subBranchPolicies?.penalty?.amount_per_interval ?? 0"
                    :penalty-amount="store.penaltyAmount"
                    :currency-symbol="store.selectedCurrency?.symbol || 'S/'"
                />

                <!-- Control de Tiempo -->
                <TimeControl
                    v-model="store.timeAmount"
                    :selected-rate="store.selectedRate"
                    :selected-pricing-range="store.selectedPricingRange"
                    :is-timer-running="store.isTimerRunning"
                    @update:model-value="store.updateTimeAmount"
                />

                <!-- Botones de Acción -->
                <ActionButtons
                    :is-timer-running="store.isTimerRunning"
                    :can-start="store.canStartService"
                    @start="store.confirmStartService"
                    @finish="store.confirmFinishService"
                />

                <!-- Información Rápida -->
                <QuickInfo
                    :room-data="store.roomData"
                    :selected-currency="store.selectedCurrency"
                    :selected-rate="store.selectedRate"
                    :selected-pricing-range="store.selectedPricingRange"
                    :voucher-type="store.voucherType"
                    :selected-client="store.selectedClient"
                    :current-booking-id="store.currentBookingId"
                />
            </div>
        </div>
    </div>

    <!-- Diálogos Modulares -->
    <StartServiceDialog 
        v-model:visible="store.showStartDialog"
        :service-data="startServiceData"
        :payment-methods="store.paymentMethods"
        :loading="store.processingPayment"
        @confirm="handleStartService"
        @cancel="store.showStartDialog = false"
    />

    <FinishServiceDialog 
        v-model:visible="store.showFinishDialog"
        :service-data="finishServiceData"
        :time-data="timeFinishData"
        :payment-methods="store.paymentMethods"
        :loading="store.processingFinish"
        @confirm="handleFinishService"
        @cancel="store.showFinishDialog = false"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoomServiceStore } from '../interface/Useroomservicestore';
import type { StartServicePayload, FinishServicePayload, RoomData } from '../interface/Useroomservicestore';

// Importar componentes modulares
import RoomHeader from '../components/Roomheader.vue';
import RateSelector from '../components/Rateselector.vue';
import RoomStatus from '../components/Roomstatus.vue';
import Timer from '../components/Timer.vue';
import TimeControl from '../components/Timecontrol.vue';
import ActionButtons from '../components/Actionbuttons.vue';
import QuickInfo from '../components/Quickinfo.vue';
import CustomerRegistration from './CustomerRegistration.vue';
import ProductSales from './ProductSales.vue';
import BillingSummary from './BillingSummary.vue';
import StartServiceDialog from './StartServiceDialog.vue';
import FinishServiceDialog from './FinishServiceDialog.vue';

// ==========================================
// PROPS
// ==========================================
interface Props {
    roomData?: RoomData;
}

const props = defineProps<Props>();

// ==========================================
// STORE
// ==========================================
const store = useRoomServiceStore();

// ==========================================
// COMPUTED PROPERTIES FOR DIALOGS
// ==========================================
const startServiceData = computed(() => ({
    clientName: store.selectedClient?.name || '',
    roomNumber: store.roomData?.room_number || '',
    rateLabel: store.getRateLabel(store.selectedRate),
    timeAmount: store.timeAmount,
    timeUnit: store.getTimeUnit(store.selectedRate),
    cashRegisterName: store.userCashRegister?.name || '',
    productsCount: store.products.length,
    currencySymbol: store.selectedCurrency?.symbol || 'S/',
    totalAmount: store.totalAmount
}));

// ✅ Calcular consumos pending en tiempo real
const pendingProductsAmount = computed(() => {
    return store.products
        .filter(p => p.status === 'pending')
        .reduce((sum, p) => {
            const qty = parseFloat(String(p.quantity || p.cantidad || 0));
            const price = parseFloat(String(p.precio_venta || p.price || 0));
            return sum + (qty * price);
        }, 0);
});

const finishServiceData = computed(() => ({
    clientName: store.selectedClient?.name || '',
    roomNumber: store.roomData?.room_number || '',
    currencySymbol: store.selectedCurrency?.symbol || 'S/',
    roomSubtotal: store.roomSubtotal || 0,                    // ✅ del store
    pendingProductsAmount: parseFloat(pendingProductsAmount.value.toFixed(2)),
    penaltyAmount: store.penaltyAmount || 0,
    penaltyMinutes: store.penaltyMinutes || 0,
    alreadyPaidAmount: store.roomSubtotal || 0,               // ✅ mismo valor
    pendingAmount: parseFloat((pendingProductsAmount.value + (store.penaltyAmount || 0)).toFixed(2))
}));

const timeFinishData = computed(() => ({
    contractedTime: `${store.timeAmount} ${store.getTimeUnit(store.selectedRate)}`,
    extraTime: store.extraTimeFormatted,
    hasExtraTime: store.hasExtraTime
}));

// ==========================================
// EVENT HANDLERS
// ==========================================
const handleStartService = async (data: StartServicePayload) => {
    try {
        await store.startService(data);
    } catch (error) {
        console.error('Error starting service:', error);
    }
};

const handleFinishService = async (data: FinishServicePayload) => {
    try {
        await store.finishService(data);
    } catch (error) {
        console.error('Error finishing service:', error);
    }
};

// ==========================================
// LIFECYCLE HOOKS
// ==========================================
onMounted(async () => {
    // CRÍTICO: Limpiar el store ANTES de inicializar con nuevos datos
    store.cleanup();
    
    if (props.roomData) {
        await store.initialize(props.roomData);
    }
});

// ==========================================
// WATCH: Detectar cambio de habitación
// ==========================================
watch(() => props.roomData?.room_number, async (newRoomNumber, oldRoomNumber) => {
    // Si cambió el número de habitación, reiniciar todo
    if (newRoomNumber && newRoomNumber !== oldRoomNumber) {
        console.log(`Cambio de habitación detectado: ${oldRoomNumber} → ${newRoomNumber}`);
        
        // Limpiar estado anterior
        store.cleanup();
        
        // Reinicializar con los nuevos datos
        if (props.roomData) {
            await store.initialize(props.roomData);
        }
    }
}, { immediate: false });

onUnmounted(() => {
    // Limpiar al desmontar el componente
    store.cleanup();
});
</script>