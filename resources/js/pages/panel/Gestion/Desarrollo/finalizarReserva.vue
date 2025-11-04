<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        modal 
        :header="'Finalizar Reserva'" 
        :style="{ width: '35rem' }"
        :breakpoints="{ '960px': '75vw', '640px': '90vw' }"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <i class="pi pi-sign-out text-red-500 text-2xl"></i>
                <p class="text-sm text-red-700 dark:text-red-300">
                    ¿Está seguro de que desea finalizar esta reserva y realizar el checkout?
                </p>
            </div>

            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg">
                <div class="flex items-center gap-2 mb-3">
                    <i class="pi pi-home text-surface-600 dark:text-surface-400"></i>
                    <span class="font-semibold text-surface-700 dark:text-surface-300">
                        Habitación #{{ roomId}}
                    </span>
                </div>

                <div v-if="roomDetails" class="space-y-3 mt-4">
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-surface-900 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user text-primary-500"></i>
                            <span class="text-sm text-surface-600 dark:text-surface-400">Cliente:</span>
                        </div>
                        <span class="font-semibold text-surface-800 dark:text-surface-200">{{ roomDetails.customer || 'Sin cliente' }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-white dark:bg-surface-900 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-sign-in text-green-500"></i>
                            <span class="text-sm text-surface-600 dark:text-surface-400">Check-in:</span>
                        </div>
                        <span class="font-semibold text-surface-800 dark:text-surface-200">{{ roomDetails.check_in_formatted || 'N/A' }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-white dark:bg-surface-900 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-clock text-blue-500"></i>
                            <span class="text-sm text-surface-600 dark:text-surface-400">Tiempo total:</span>
                        </div>
                        <span class="font-semibold text-surface-800 dark:text-surface-200">{{ roomDetails.total_time || '0h 0m' }}</span>
                    </div>

                    <div v-if="roomDetails.has_extra_charges" class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-orange-500"></i>
                            <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Cargos extras pendientes:</span>
                        </div>
                        <span class="font-bold text-orange-700 dark:text-orange-300">S/ {{ roomDetails.extra_charges }}</span>
                    </div>
                </div>

                <div v-else class="flex justify-center py-4">
                    <i class="pi pi-spin pi-spinner text-3xl text-primary-500"></i>
                </div>
            </div>

            <div class="flex items-start gap-2 text-sm p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <i class="pi pi-info-circle text-yellow-600 mt-0.5"></i>
                <p class="text-yellow-700 dark:text-yellow-300">
                    Al finalizar la reserva, la habitación pasará a estado de <strong>limpieza</strong> y deberá ser limpiada antes de poder ser ocupada nuevamente.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button 
                    label="Cancelar" 
                    severity="secondary"
                    outlined
                    @click="closeDialog"
                    :disabled="loading"
                />
                <Button 
                    label="Finalizar Reserva" 
                    severity="danger"
                    icon="pi pi-sign-out"
                    @click="finalizarReserva"
                    :loading="loading"
                    :disabled="!roomDetails"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const toast = useToast();

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    roomId: {
        type: [Number, String],
        default: null
    },
    roomNumber: {
        type: [Number, String],
        default: null
    }
});

const emit = defineEmits(['update:visible', 'booking-finished']);

const loading = ref(false);
const roomDetails = ref(null);

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const closeDialog = () => {
    if (!loading.value) {
        dialogVisible.value = false;
        roomDetails.value = null;
    }
};

const showSuccessToast = (message) => {
    toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: message,
        life: 3000
    });
};

const showErrorToast = (message) => {
    toast.add({
        severity: 'error',
        summary: 'Error',
        detail: message,
        life: 3000
    });
};

const fetchRoomDetails = async () => {
    if (!props.roomId) return;

    try {
        const response = await axios.get(`/cuarto/${props.roomId}/detalles-checkout`);
        roomDetails.value = response.data.data;
    } catch (error) {
        console.error('Error al obtener detalles:', error);
        showErrorToast('Error al obtener los detalles de la habitación');
        closeDialog();
    }
};

const finalizarReserva = async () => {
    if (!props.roomId) {
        console.error('No se proporcionó un ID de habitación');
        return;
    }

    try {
        loading.value = true;
        
        const response = await axios.post(`/cuarto/${props.roomId}/checkout`);

        showSuccessToast(response.data.message || 'Reserva finalizada correctamente');
        emit('booking-finished');
        closeDialog();
        
    } catch (error) {
        console.error('Error al finalizar reserva:', error);
        const errorMessage = error.response?.data?.message || 'Error al finalizar la reserva';
        showErrorToast(errorMessage);
    } finally {
        loading.value = false;
    }
};

watch(() => props.visible, (newValue) => {
    if (newValue && props.roomId) {
        fetchRoomDetails();
    } else if (!newValue) {
        loading.value = false;
        roomDetails.value = null;
    }
});
</script>