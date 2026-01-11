<template>
    <div class="p-5 bg-surface-50 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                <i class="pi pi-user"></i>
                Cliente
            </h3>
            <Button 
                label="Registrar Cliente" 
                icon="pi pi-user-plus" 
                severity="info"
                size="small"
                @click="showDialog = true"
                :disabled="disabled || !!selectedClient"
            />
        </div>
        
        <!-- Cliente seleccionado -->
        <div v-if="selectedClient" class="p-4 bg-white dark:bg-surface-700 rounded-lg border border-surface-300 dark:border-surface-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                        <i class="pi pi-user text-primary-600 dark:text-primary-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedClient.name }}</p>
                        <p class="text-sm text-surface-600 dark:text-surface-400">{{ selectedClient.document_number }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">ID: {{ selectedClient.id }}</p>
                    </div>
                </div>
                <Button 
                    icon="pi pi-times" 
                    severity="danger"
                    text
                    rounded
                    @click="removeClient"
                    :disabled="disabled"
                />
            </div>
        </div>

        <!-- Sin cliente -->
        <div v-else class="text-center py-6 text-surface-500 dark:text-surface-400">
            <i class="pi pi-user-plus text-4xl mb-2"></i>
            <p class="text-sm">No hay cliente registrado</p>
        </div>

        <!-- Dialog Registrar Cliente -->
        <Dialog 
            v-model:visible="showDialog" 
            modal 
            header="Registrar Cliente"
            :style="{ width: '500px' }"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Número de Documento <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        v-model="form.document_number" 
                        placeholder="Ej: 12345678" 
                        class="w-full"
                        :class="{ 'p-invalid': errors.document_number }"
                    />
                    <small v-if="errors.document_number" class="p-error">
                        {{ errors.document_number }}
                    </small>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        v-model="form.name" 
                        placeholder="Ej: Juan Pérez López" 
                        class="w-full"
                        :class="{ 'p-invalid': errors.name }"
                    />
                    <small v-if="errors.name" class="p-error">
                        {{ errors.name }}
                    </small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" severity="secondary" text @click="closeDialog" :disabled="loading" />
                <Button 
                    label="Guardar Cliente" 
                    icon="pi pi-check" 
                    severity="contrast"
                    @click="saveClient"
                    :loading="loading"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

interface Customer {
    id?: number;
    name: string;
    document_number: string;
}

interface Props {
    disabled?: boolean;
    modelValue?: Customer | null;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    modelValue: null
});

const emit = defineEmits<{
    'update:modelValue': [customer: Customer | null];
    'customer-saved': [customer: Customer];
}>();

const toast = useToast();
const showDialog = ref(false);
const loading = ref(false);
const selectedClient = ref<Customer | null>(props.modelValue);

const form = ref({
    document_number: '',
    name: ''
});

const errors = ref<Record<string, string>>({});

// Sincronizar con v-model
watch(() => props.modelValue, (newVal) => {
    selectedClient.value = newVal;
});

const saveClient = async () => {
    // Validación básica
    errors.value = {};
    
    if (!form.value.document_number.trim()) {
        errors.value.document_number = 'El número de documento es obligatorio';
    }
    if (!form.value.name.trim()) {
        errors.value.name = 'El nombre es obligatorio';
    }
    
    if (Object.keys(errors.value).length > 0) {
        return;
    }

    loading.value = true;

    try {
        const response = await axios.post('/customer', {
            document_number: form.value.document_number.trim(),
            name: form.value.name.trim()
        });

        // CORRECCIÓN: Capturar el ID correctamente según respuesta del backend
        console.log('========================================');
        console.log('Respuesta completa del servidor:', response.data);
        console.log('========================================');
        
        // Intentar obtener el ID de diferentes estructuras posibles
        const customerId = response.data.data?.id || response.data.id || response.data.customer?.id;
        
        console.log('ID del cliente capturado:', customerId);
        
        if (!customerId) {
            console.error('⚠️ ERROR: No se pudo obtener el ID del cliente');
            console.error('Estructura de respuesta:', JSON.stringify(response.data, null, 2));
            throw new Error('No se pudo obtener el ID del cliente desde la respuesta del servidor');
        }

        const newCustomer: Customer = {
            id: customerId,
            name: form.value.name.trim(),
            document_number: form.value.document_number.trim()
        };

        console.log('Cliente creado:', newCustomer);
        console.log('========================================');

        selectedClient.value = newCustomer;
        emit('update:modelValue', newCustomer);
        emit('customer-saved', newCustomer);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Cliente registrado correctamente',
            life: 3000
        });

        closeDialog();
    } catch (error: any) {
        console.error('❌ Error al guardar cliente:', error);
        
        // Manejar errores de validación del backend
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || error.message || 'No se pudo registrar el cliente',
                life: 5000
            });
        }
    } finally {
        loading.value = false;
    }
};

const removeClient = () => {
    if (!props.disabled) {
        selectedClient.value = null;
        emit('update:modelValue', null);
    }
};

const closeDialog = () => {
    showDialog.value = false;
    form.value = { document_number: '', name: '' };
    errors.value = {};
};
</script>

<style scoped>
.p-invalid {
    border-color: #ef4444;
}

.p-error {
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}
</style>