<template>
    <div class="p-6 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-300 dark:border-slate-600">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                <i class="pi pi-file-edit"></i>
                Resumen de Cuenta
            </h3>
            <div class="flex gap-2">
                <Button 
                    v-for="type in voucherTypes" 
                    :key="type.value"
                    :label="voucherType === type.value ? `✓ ${type.label}` : type.label" 
                    :severity="voucherType === type.value ? 'success' : 'secondary'"
                    size="small"
                    @click="voucherType = type.value"
                    :outlined="voucherType !== type.value"
                />
            </div>
        </div>

        <!-- Indicador comprobante y moneda -->
        <div class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="pi pi-receipt text-primary-600 dark:text-primary-400"></i>
                    <span class="font-semibold text-primary-700 dark:text-primary-300">
                        Comprobante: {{ voucherType.toUpperCase() }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="pi pi-dollar text-green-600 dark:text-green-400"></i>
                    <span class="font-semibold text-green-700 dark:text-green-300">
                        {{ currencySymbol }} {{ currencyCode }}
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <!-- Habitación -->
            <div class="flex justify-between items-center pb-2 border-b border-surface-300 dark:border-surface-600">
                <div>
                    <p class="font-medium text-surface-900 dark:text-surface-0">
                        Habitación {{ roomNumber || 'N/A' }}
                    </p>
                    <p class="text-sm text-surface-600 dark:text-surface-400">
                        {{ rateLabel }} - {{ timeAmount }} {{ timeUnit }}
                    </p>
                </div>
                <span class="font-semibold text-lg text-surface-900 dark:text-surface-0">
                    {{ currencySymbol }} {{ roomTotalDisplay.toFixed(2) }}
                </span>
            </div>

            <!-- Productos -->
            <div v-if="products.length > 0" class="pb-2 border-b border-surface-300 dark:border-surface-600">
                <p class="font-medium text-surface-900 dark:text-surface-0 mb-2">Productos</p>
                <div v-for="product in products" :key="product.id" class="flex justify-between text-sm mb-1">
                    <span class="text-surface-600 dark:text-surface-400">
                        {{ product.name || product.nombre }} x{{ getProductQuantity(product).toFixed(2) }}
                        <span 
                            v-if="product.status === 'pending'"
                            class="ml-1 text-xs text-orange-500 dark:text-orange-400 font-medium"
                        >(pendiente)</span>
                        <span 
                            v-else-if="product.status === 'paid'"
                            class="ml-1 text-xs text-green-500 dark:text-green-400 font-medium"
                        >(pagado)</span>
                    </span>
                    <span class="text-surface-900 dark:text-surface-0">
                        {{ currencySymbol }} {{ getProductTotal(product).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Penalización -->
            <div 
                v-if="safePenalty > 0"
                class="flex justify-between items-center pb-2 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"
            >
                <div>
                    <p class="font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
                        <i class="pi pi-exclamation-triangle text-sm"></i>
                        Penalización por tiempo extra
                    </p>
                    <p class="text-xs text-red-500 dark:text-red-400">
                        {{ penaltyMinutes }} minutos adicionales
                    </p>
                </div>
                <span class="font-semibold text-lg text-red-600 dark:text-red-400">
                    + {{ currencySymbol }} {{ safePenalty.toFixed(2) }}
                </span>
            </div>

            <!-- Subtotal -->
            <div class="flex justify-between items-center text-lg">
                <span class="font-medium text-surface-700 dark:text-surface-300">Subtotal:</span>
                <span class="font-semibold text-surface-900 dark:text-surface-0">
                    {{ currencySymbol }} {{ subtotal.toFixed(2) }}
                </span>
            </div>

            <!-- Total -->
            <div 
                class="flex justify-between items-center pt-3 border-t-2"
                :class="safePenalty > 0 
                    ? 'border-red-400 dark:border-red-600' 
                    : 'border-surface-400 dark:border-surface-500'"
            >
                <span class="text-2xl font-bold text-surface-900 dark:text-surface-0">TOTAL:</span>
                <span 
                    class="text-3xl font-bold"
                    :class="safePenalty > 0 
                        ? 'text-red-600 dark:text-red-400' 
                        : 'text-primary-600 dark:text-primary-400'"
                >
                    {{ currencySymbol }} {{ total.toFixed(2) }}
                </span>
            </div>

            <!-- Aviso tiempo extra -->
            <div 
                v-if="safePenalty > 0"
                class="text-xs text-center text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"
            >
                ⚠️ El cliente se pasó del tiempo contratado. Se está cobrando penalización en tiempo real.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';

interface Product {
    id: number | string;
    name?: string;
    nombre?: string;
    price?: number | string;
    precio_venta?: number | string;
    quantity?: number | string;
    cantidad?: number | string;
    status?: string;
}

interface Props {
    roomNumber?: string | number;
    roomPrice?: number | string;
    roomSubtotal?: number;        // ✅ NUEVO: viene del booking activo (fuente de verdad)
    selectedRate?: any;
    selectedPricingRange?: any;
    timeAmount?: number | string;
    products?: Product[];
    currencySymbol?: string;
    currencyCode?: string;
    modelValue?: 'boleta' | 'ticket' | 'factura';
    penaltyAmount?: number;
    penaltyMinutes?: number;
}

const props = withDefaults(defineProps<Props>(), {
    roomNumber: '',
    roomPrice: 0,
    roomSubtotal: 0,             // ✅
    selectedRate: null,
    selectedPricingRange: null,
    timeAmount: 1,
    products: () => [],
    currencySymbol: 'S/',
    currencyCode: 'PEN',
    modelValue: 'boleta',
    penaltyAmount: 0,
    penaltyMinutes: 0,
});

const emit = defineEmits<{
    'update:modelValue': [type: 'boleta' | 'ticket' | 'factura'];
}>();

const voucherTypes = [
    { value: 'boleta', label: 'Boleta' },
    { value: 'ticket', label: 'Ticket' },
    { value: 'factura', label: 'Factura' }
];

const voucherType = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value as 'boleta' | 'ticket' | 'factura')
});

const rateLabel = computed(() => {
    if (props.selectedPricingRange?.rate_type?.display_name) {
        return props.selectedPricingRange.rate_type.display_name;
    }
    if (props.selectedPricingRange?.rate_type?.name) {
        return props.selectedPricingRange.rate_type.name;
    }
    if (props.selectedRate?.display_name) {
        return props.selectedRate.display_name;
    }
    if (props.selectedRate?.name) {
        return props.selectedRate.name;
    }
    return 'Sin tarifa';
});

const timeUnit = computed(() => {
    const code = props.selectedRate?.code || props.selectedPricingRange?.rate_type?.code || '';
    const units: Record<string, string> = {
        'HOURLY':   'Hora(s)',
        'DAILY':    'Día(s)',
        'NIGHTLY':  'Noche(s)',
        'MINUTOS':  'Bloque(s)',
    };
    return units[code] || 'Unidad(es)';
});

const safeNumber = (value: any, defaultValue: number = 0): number => {
    const parsed = parseFloat(value);
    return isNaN(parsed) ? defaultValue : parsed;
};

const getProductQuantity = (product: Product): number => {
    return safeNumber(product.quantity || product.cantidad);
};

const getProductTotal = (product: Product): number => {
    const quantity = safeNumber(product.quantity || product.cantidad);
    const price = safeNumber(product.price || product.precio_venta);
    return quantity * price;
};

// ✅ Si viene roomSubtotal del booking activo, usarlo como fuente de verdad
// Si no (antes de iniciar servicio), calcularlo normalmente
const roomTotalDisplay = computed(() => {
    const subtotal = safeNumber(props.roomSubtotal);
    if (subtotal > 0) {
        return subtotal;
    }
    const price = safeNumber(props.roomPrice);
    const amount = safeNumber(props.timeAmount, 1);
    return price * amount;
});

const productsTotal = computed(() => {
    return props.products.reduce((sum, p) => {
        const quantity = safeNumber(p.quantity || p.cantidad);
        const price = safeNumber(p.price || p.precio_venta);
        return sum + (quantity * price);
    }, 0);
});

const safePenalty = computed(() => safeNumber(props.penaltyAmount));

const subtotal = computed(() => {
    return roomTotalDisplay.value + productsTotal.value;
});

const total = computed(() => {
    return subtotal.value + safePenalty.value;
});
</script>