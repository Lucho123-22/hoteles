<template>
    <Dialog 
        v-model:visible="isVisible" 
        modal 
        header="Finalizar Servicio"
        :style="{ width: '550px' }"
        @update:visible="$emit('update:visible', $event)"
    >
        <div class="space-y-4">
            <Message severity="info" :closable="false">
                ¿Desea finalizar el servicio?
            </Message>

            <!-- Resumen de Tiempo -->
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-2 border-yellow-200 dark:border-yellow-700">
                <h4 class="font-semibold mb-2 text-yellow-800 dark:text-yellow-300">
                    <i class="pi pi-clock mr-2"></i>Resumen de Tiempo
                </h4>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Tiempo contratado:</span>
                        <span class="font-semibold">{{ timeData.contractedTime }}</span>
                    </div>
                    <div 
                        v-if="timeData.hasExtraTime" 
                        class="flex justify-between text-red-600 dark:text-red-400"
                    >
                        <span>Tiempo extra:</span>
                        <span class="font-semibold">{{ timeData.extraTime }}</span>
                    </div>
                </div>
            </div>

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
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-lg font-bold">Saldo pendiente:</span>
                        <span class="text-lg font-bold text-orange-600 dark:text-orange-400">
                            {{ serviceData.currencySymbol }} {{ serviceData.pendingAmount }}
                        </span>
                    </div>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-2">
                        * El tiempo extra (si existe) se calculará y cobrará automáticamente
                    </p>
                </div>
            </div>

            <!-- Método de Pago para saldo pendiente -->
            <div 
                v-if="serviceData.pendingAmount > 0" 
                class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700"
            >
                <h4 class="font-semibold mb-3 text-surface-900 dark:text-surface-0">
                    <i class="pi pi-credit-card mr-2"></i>Método de Pago (Saldo Pendiente)
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

            <!-- Notas Opcionales -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Notas (Opcional)
                </label>
                <Textarea 
                    v-model="notes" 
                    rows="3"
                    placeholder="Observaciones al finalizar el servicio"
                    class="w-full"
                />
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                severity="secondary" 
                @click="handleCancel" 
            />
            <Button 
                label="Finalizar Servicio" 
                icon="pi pi-check" 
                severity="danger"
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
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';

interface ServiceData {
    clientName: string;
    roomNumber: string;
    currencySymbol: string;
    pendingAmount: number;
}

interface TimeData {
    contractedTime: string;
    extraTime: string;
    hasExtraTime: boolean;
}

interface Props {
    visible: boolean;
    serviceData: ServiceData;
    timeData: TimeData;
    paymentMethods: any[];
    loading?: boolean;
}

interface Emits {
    (e: 'update:visible', value: boolean): void;
    (e: 'confirm', data: { paymentMethod: any | null; operationNumber: string; notes: string }): void;
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
const notes = ref<string>('');

const canConfirm = computed(() => {
    // Si hay saldo pendiente, se requiere método de pago
    if (props.serviceData.pendingAmount > 0) {
        if (!selectedPaymentMethod.value) return false;
        if (selectedPaymentMethod.value.requires_reference && !operationNumber.value.trim()) {
            return false;
        }
    }
    return true;
});

const selectPaymentMethod = (method: any) => {
    selectedPaymentMethod.value = method;
};

const handleConfirm = () => {
    emit('confirm', {
        paymentMethod: selectedPaymentMethod.value,
        operationNumber: operationNumber.value,
        notes: notes.value
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
        notes.value = '';
    }
});
</script>