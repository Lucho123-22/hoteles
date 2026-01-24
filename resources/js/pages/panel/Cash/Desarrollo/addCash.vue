<template>
  <div class="mb-4">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold">Gestión de Cajas Registradoras</h2>
      <Button label="Crear Múltiples Cajas" icon="pi pi-plus" @click="showDialog = true" severity="contrast" />
    </div>

    <!-- Dialog para crear múltiples cajas -->
    <Dialog v-model:visible="showDialog" modal header="Crear Múltiples Cajas" :style="{ width: '30rem' }">
      <div class="flex flex-col gap-4">
        <div>
          <label for="quantity" class="block text-sm font-medium mb-2">
            Cantidad de Cajas
          </label>
          <InputNumber id="quantity" v-model="quantity" :min="1" :max="20" showButtons class="w-full"
            :class="{ 'p-invalid': errors.quantity }" />
          <small v-if="errors.quantity" class="text-red-500">{{ errors.quantity }}</small>
        </div>

        <div class="border-l-4 p-3 rounded">
          <p class="text-sm">
            <i class="pi pi-info-circle mr-2"></i>
            Se crearán <strong>{{ quantity }}</strong> cajas registradoras con estado "cerrada"
          </p>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" @click="closeDialog" severity="secondary" text />
        <Button label="Crear" icon="pi pi-check" @click="createMultipleCashes" :loading="isCreating"
          severity="contrast" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const showDialog = ref(false);
const quantity = ref(1);
const isCreating = ref(false);
const errors = ref<{ quantity?: string }>({});

const emit = defineEmits(['refresh']);

const closeDialog = () => {
  showDialog.value = false;
  quantity.value = 1;
  errors.value = {};
};

const createMultipleCashes = async () => {
  errors.value = {};

  if (quantity.value < 1 || quantity.value > 20) {
    errors.value.quantity = 'La cantidad debe estar entre 1 y 20';
    return;
  }

  isCreating.value = true;

  try {
    const response = await axios.post(route('cash.cash-registers.multiple'), {
      quantity: quantity.value
    });

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: response.data.message,
        life: 3000
      });
      closeDialog();
      emit('refresh');
      router.reload({ only: ['cashRegisters'] });
    }
  } catch (error: any) {
    console.error('Error:', error);
    const errorMessage = error.response?.data?.message || 'Error al crear las cajas';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    });
  } finally {
    isCreating.value = false;
  }
};
</script>