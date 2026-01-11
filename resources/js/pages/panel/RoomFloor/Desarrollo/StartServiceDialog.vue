<template>
    <Dialog 
        v-model:visible="isVisible" 
        modal 
        header="Iniciar Servicio y Procesar Pago"
        :style="{ width: '550px' }"
        @update:visible="$emit('update:visible', $event)"
    >
        <div class="space-y-4">
            <Message severity="success" :closable="false">
                ¿Confirma iniciar el servicio con los siguientes datos?
            </Message>

            <!-- Resumen del Servicio -->
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Cliente:</span>
                        <span class="font-semibold">{{ serviceData.clientName }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Habitación:</span>
                        <span class="font-semibold">{{ serviceData.roomNumber }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Tarifa:</span>
                        <span class="font-semibold">{{ serviceData.rateLabel }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Tiempo:</span>
                        <span class="font-semibold">{{ serviceData.timeAmount }} {{ serviceData.timeUnit }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Caja:</span>
                        <span class="font-semibold text-green-600">{{ serviceData.cashRegisterName }}</span>
                    </div>
                    <div v-if="serviceData.productsCount > 0" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Productos:</span>
                        <span class="font-semibold">{{ serviceData.productsCount }} item(s)</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-lg font-bold">Total a pagar:</span>
                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                            {{ serviceData.currencySymbol }} {{ serviceData.totalAmount }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Selector de Método de Pago -->
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
                <h4 class="font-semibold mb-3 text-surface-900 dark:text-surface-0">
                    <i class="pi pi-credit-card mr-2"></i>Método de Pago
                </h4>
                
                <div class="grid grid-cols-2 gap-3">
                    <div 
                        v-for="method in paymentMethods" 
                        :key="method.id"
                        @click="selectPaymentMethod(method)"
                        :class="[
                            'p-3 rounded-lg border-2 cursor-pointer transition-all',
                            selectedPaymentMethod?.id === method.id 
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-lg' 
                                : 'border-surface-300 dark:border-surface-600 bg-white dark:bg-surface-800 hover:border-primary-300'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm">{{ method.name }}</span>
                            <i v-if="selectedPaymentMethod?.id === method.id" class="pi pi-check-circle text-primary-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Campo Número de Operación -->
                <div v-if="selectedPaymentMethod?.requires_reference" class="mt-3">
                    <label class="block text-sm font-medium mb-2">
                        Número de Operación *
                    </label>
                    <InputText 
                        v-model="operationNumber" 
                        placeholder="Ingrese número de operación"
                        class="w-full"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                severity="secondary" 
                text 
                @click="handleCancel" 
            />
            <Button 
                label="Iniciar y Pagar" 
                icon="pi pi-check" 
                severity="success"
                @click="handleConfirm"
                :loading="loading"
                :disabled="!canConfirm"
            />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

interface ServiceData {
    clientName: string;
    roomNumber: string;
    rateLabel: string;
    timeAmount: number;
    timeUnit: string;
    cashRegisterName: string;
    productsCount: number;
    currencySymbol: string;
    totalAmount: string;
}

interface Props {
    visible: boolean;
    serviceData: ServiceData;
    paymentMethods: any[];
    loading?: boolean;
}

interface Emits {
    (e: 'update:visible', value: boolean): void;
    (e: 'confirm', data: { paymentMethod: any; operationNumber: string }): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false
});

const emit = defineEmits<Emits>();

const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const selectedPaymentMethod = ref<any>(null);
const operationNumber = ref<string>('');

const canConfirm = computed(() => {
    if (!selectedPaymentMethod.value) return false;
    if (selectedPaymentMethod.value.requires_reference && !operationNumber.value.trim()) {
        return false;
    }
    return true;
});

const selectPaymentMethod = (method: any) => {
    selectedPaymentMethod.value = method;
};

const handleConfirm = () => {
    emit('confirm', {
        paymentMethod: selectedPaymentMethod.value,
        operationNumber: operationNumber.value
    });
};

const handleCancel = () => {
    emit('cancel');
    emit('update:visible', false);
};

// Reset cuando se cierra el diálogo
watch(() => props.visible, (newVal) => {
    if (!newVal) {
        selectedPaymentMethod.value = null;
        operationNumber.value = '';
    } else {
        // Seleccionar método de pago en efectivo por defecto
        const cashMethod = props.paymentMethods.find(m => m.code === 'cash');
        if (cashMethod) {
            selectedPaymentMethod.value = cashMethod;
        }
    }
});
</script>