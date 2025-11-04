<template>
    <Dialog v-model:visible="visible" modal header="Confirmar Eliminación" :style="{ width: '25rem' }">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-orange-500" style="font-size: 1.5rem"></i>
                <span class="font-bold">Confirmar Eliminación</span>
            </div>
        </template>

        <div class="flex flex-col gap-4">
            <p class="text-surface-600 dark:text-surface-400 mb-0">
                ¿Está seguro que desea eliminar este detalle del movimiento?
            </p>

            <div v-if="detail" class="p-3 surface-100 dark:surface-700 rounded-lg">
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between">
                        <span class="font-semibold">Producto:</span>
                        <span>{{ detail.producto?.nombre || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold">Tipo:</span>
                        <span>{{ detail.tipo }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold">Cantidad:</span>
                        <span>{{ detail.cantidades }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold">Precio Total:</span>
                        <span class="font-bold text-green-600">S/ {{ detail.precio_total }}</span>
                    </div>
                </div>
            </div>

            <Message severity="warn" :closable="false">
                Esta acción no se puede deshacer. El stock se ajustará automáticamente.
            </Message>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancelar" severity="secondary" @click="closeDialog" :disabled="loading" outlined />
                <Button label="Eliminar" severity="danger" @click="deleteDetail" :loading="loading"
                    icon="pi pi-trash" />
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const toast = useToast();

const props = defineProps<{
    visible: boolean;
    detail: any;
}>();

const emit = defineEmits(['update:visible', 'deleted']);

const visible = ref(false);
const loading = ref(false);

watch(() => props.visible, (newVal) => {
    visible.value = newVal;
});

watch(visible, (newVal) => {
    if (!newVal) {
        emit('update:visible', false);
    }
});

const closeDialog = () => {
    visible.value = false;
};

const deleteDetail = async () => {
    if (!props.detail?.id) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el detalle a eliminar',
            life: 3000
        });
        return;
    }

    loading.value = true;

    try {
        await axios.delete(`/movement-detail/${props.detail.id}`);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Detalle eliminado correctamente',
            life: 3000
        });

        emit('deleted');
        closeDialog();
    } catch (error: any) {
        console.error('Error al eliminar:', error);

        const errorMessage = error.response?.data?.message || 'No se pudo eliminar el detalle del movimiento';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};
</script>