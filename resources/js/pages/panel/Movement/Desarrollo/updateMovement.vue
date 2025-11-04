<template>
    <Dialog v-model:visible="isVisible" :style="{ width: '500px' }" header="Editar Movimiento" :modal="true"
        @hide="onHide">
        <!-- Mensaje de carga -->
        <div v-if="loadingMovement" class="flex justify-center items-center py-8">
            <ProgressSpinner style="width:50px;height:50px" strokeWidth="8" />
        </div>

        <!-- Formulario de edición -->
        <div v-else class="flex flex-col gap-6">
            <!-- TIPO DE MOVIMIENTO -->
            <div class="col-span-12">
                <label class="block font-bold mb-2">
                    Tipo de Movimiento <span class="text-red-500">*</span>
                </label>
                <SelectButton v-model="movement.movement_type" :options="movementTypeOptions" optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': submitted && (!movement.movement_type || serverErrors.movement_type) }" />
                <small v-if="submitted && !movement.movement_type" class="text-red-500">
                    El tipo de movimiento es obligatorio.
                </small>
                <small v-else-if="serverErrors.movement_type" class="text-red-500">
                    {{ serverErrors.movement_type[0] }}
                </small>
            </div>

            <!-- Tipo de Comprobante -->
            <div class="col-span-6">
                <label class="block font-bold mb-2">
                    Tipo de Comprobante <span class="text-red-500">*</span>
                </label>
                <SelectButton v-model="movement.voucher_type" :options="voucherTypeOptions" optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': submitted && (!movement.voucher_type || serverErrors.voucher_type) }" />
                <small v-if="submitted && !movement.voucher_type" class="text-red-500">
                    El tipo de comprobante es obligatorio.
                </small>
                <small v-else-if="serverErrors.voucher_type" class="text-red-500">
                    {{ serverErrors.voucher_type[0] }}
                </small>
            </div>

            <!-- Código -->
            <div class="col-span-6">
                <label for="code" class="block font-bold mb-2">
                    Código <span class="text-red-500">*</span>
                </label>
                <InputText id="code" v-model.trim="movement.code" maxlength="255" fluid
                    :class="{ 'p-invalid': submitted && (!movement.code || serverErrors.code) }" />
                <small v-if="submitted && !movement.code" class="text-red-500">
                    El código es obligatorio.
                </small>
                <small v-else-if="serverErrors.code" class="text-red-500">
                    {{ serverErrors.code[0] }}
                </small>
            </div>

            <!-- Fecha de Emisión -->
            <div class="col-span-6">
                <label for="date" class="block font-bold mb-2">
                    Fecha de Emisión <span class="text-red-500">*</span>
                </label>
                <DatePicker id="date" v-model="movement.date" dateFormat="dd-mm-yy" fluid
                    :class="{ 'p-invalid': submitted && (!movement.date || serverErrors.date) }" />
                <small v-if="submitted && !movement.date" class="text-red-500">
                    La fecha es obligatoria.
                </small>
                <small v-else-if="serverErrors.date" class="text-red-500">
                    {{ serverErrors.date[0] }}
                </small>
            </div>

            <!-- Proveedor -->
            <div class="col-span-12">
                <label for="provider" class="block font-bold mb-2">
                    Proveedor <span class="text-red-500">*</span>
                </label>
                <AutoComplete id="provider" v-model="selectedProvider" :suggestions="providerSuggestions"
                    @complete="searchProviders" @option-select="onProviderSelect" optionLabel="razon_social"
                    placeholder="Buscar proveedor..." fluid
                    :class="{ 'p-invalid': submitted && (!movement.provider_id || serverErrors.provider_id) }">
                    <template #option="slotProps">
                        <div class="flex align-items-center gap-2">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ slotProps.option.razon_social }}</span>
                                <small class="text-gray-500">RUC: {{ slotProps.option.ruc }}</small>
                            </div>
                        </div>
                    </template>
                </AutoComplete>
                <small v-if="submitted && !movement.provider_id" class="text-red-500">
                    El proveedor es obligatorio.
                </small>
                <small v-else-if="serverErrors.provider_id" class="text-red-500">
                    {{ serverErrors.provider_id[0] }}
                </small>
            </div>

            <!-- Tipo de Pago -->
            <div class="col-span-12">
                <label class="block font-bold mb-2">
                    Tipo de Pago <span class="text-red-500">*</span>
                </label>
                <SelectButton v-model="movement.payment_type" :options="paymentTypeOptions" optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': submitted && (!movement.payment_type || serverErrors.payment_type) }" />
                <small v-if="submitted && !movement.payment_type" class="text-red-500">
                    El tipo de pago es obligatorio.
                </small>
                <small v-else-if="serverErrors.payment_type" class="text-red-500">
                    {{ serverErrors.payment_type[0] }}
                </small>
            </div>

            <!-- Fecha de Crédito (solo si es crédito) -->
            <div v-if="movement.payment_type === 'credito'" class="col-span-6">
                <label for="credit_date" class="block font-bold mb-2">
                    Fecha de Crédito <span class="text-red-500">*</span>
                </label>
                <DatePicker id="credit_date" v-model="movement.credit_date" dateFormat="dd-mm-yy" fluid
                    :class="{ 'p-invalid': submitted && movement.payment_type === 'credito' && (!movement.credit_date || serverErrors.credit_date) }" />
                <small v-if="submitted && movement.payment_type === 'credito' && !movement.credit_date"
                    class="text-red-500">
                    La fecha de crédito es obligatoria.
                </small>
                <small v-else-if="serverErrors.credit_date" class="text-red-500">
                    {{ serverErrors.credit_date[0] }}
                </small>
            </div>

            <!-- Incluye IGV -->
            <div class="col-span-6">
                <label class="block font-bold mb-2">
                    Incluye IGV <span class="text-red-500">*</span>
                </label>
                <SelectButton v-model="movement.includes_igv" :options="igvOptions" optionLabel="label"
                    optionValue="value" :class="{ 'p-invalid': serverErrors.includes_igv }" />
                <small v-if="serverErrors.includes_igv" class="text-red-500">
                    {{ serverErrors.includes_igv[0] }}
                </small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <small>
                    <span class="text-red-500">*</span> Campos obligatorios
                </small>

                <!-- Botones -->
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary"/>
                    <Button label="Guardar Cambios" icon="pi pi-check" :loading="loading"
                        :disabled="!isFormValid || loading || loadingMovement" @click="updateMovement" severity="contrast"/>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import AutoComplete from 'primevue/autocomplete';
import SelectButton from 'primevue/selectbutton';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import { defineEmits } from 'vue';

const emit = defineEmits(['actualizado', 'hide']);
const toast = useToast();

const isVisible = ref(false);
const loadingMovement = ref(false);
const submitted = ref(false);
const loading = ref(false);
const serverErrors = ref({});

const selectedProvider = ref(null);
const providerSuggestions = ref([]);

const movement = ref({
    id: '',
    movement_type: 'ingreso',
    code: '',
    date: null,
    provider_id: '',
    payment_type: 'contado',
    credit_date: null,
    includes_igv: true,
    voucher_type: 'guia',
});

// OPCIONES
const movementTypeOptions = [
    { label: 'Ingreso', value: 'ingreso' },
    { label: 'Egreso', value: 'egreso' },
];

const paymentTypeOptions = [
    { label: 'Contado', value: 'contado' },
    { label: 'Crédito', value: 'credito' },
];

const igvOptions = [
    { label: 'Sí', value: true },
    { label: 'No', value: false },
];

const voucherTypeOptions = [
    { label: 'Factura', value: 'factura' },
    { label: 'Boleta', value: 'boleta' },
    { label: 'Guia', value: 'guia' },
];

// Validación del formulario
const isFormValid = computed(() => {
    const basic = movement.value.movement_type &&
        movement.value.code &&
        movement.value.date &&
        movement.value.provider_id &&
        movement.value.payment_type &&
        movement.value.voucher_type &&
        (movement.value.includes_igv === true || movement.value.includes_igv === false);

    if (movement.value.payment_type === 'credito') {
        return basic && movement.value.credit_date;
    }

    return basic;
});

// Watchers
watch(() => movement.value.payment_type, (newValue) => {
    if (newValue !== 'credito') {
        movement.value.credit_date = null;
    }
});

watch(selectedProvider, (newProvider) => {
    if (newProvider && typeof newProvider === 'object' && newProvider.id) {
        movement.value.provider_id = newProvider.id;
    } else if (typeof newProvider === 'string') {
        movement.value.provider_id = '';
    } else {
        movement.value.provider_id = '';
    }
});

// Función para parsear fecha del formato dd-mm-yyyy
function parseDate(dateString) {
    if (!dateString) return null;

    const parts = dateString.split('-');
    if (parts.length === 3) {
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = parseInt(parts[2], 10);
        return new Date(year, month, day);
    }

    return null;
}

// Cargar datos del movimiento
async function loadMovement(movementId) {
    loadingMovement.value = true;
    try {
        const response = await axios.get(`/movements/${movementId}`);
        const data = response.data.data;

        movement.value = {
            id: data.id,
            movement_type: data.movement_type || 'ingreso',
            code: data.code || '',
            date: parseDate(data.date),
            provider_id: data.provider?.id || '',
            payment_type: data.payment_type || 'contado',
            credit_date: parseDate(data.credit_date),
            includes_igv: data.includes_igv ?? true,
            voucher_type: data.voucher_type || 'guia',
        };

        // Establecer el proveedor seleccionado
        if (data.provider) {
            selectedProvider.value = {
                id: data.provider.id,
                ruc: data.provider.ruc,
                razon_social: data.provider.razon_social
            };
        }

    } catch (error) {
        console.error('Error cargando movimiento:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar el movimiento',
            life: 3000
        });
        hideDialog();
    } finally {
        loadingMovement.value = false;
    }
}

// Buscar proveedores
async function searchProviders(event) {
    try {
        const response = await axios.get(`/providers?search=${event.query}`);
        providerSuggestions.value = response.data.data || [];
    } catch (error) {
        console.error('Error buscando proveedores:', error);
        providerSuggestions.value = [];
    }
}

function onProviderSelect(event) {
    selectedProvider.value = event.value;
    movement.value.provider_id = event.value.id;
}

// Formatear fecha para el backend
function formatDateForBackend(date) {
    if (!date) return null;
    if (date instanceof Date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }
    if (typeof date === 'string') {
        return date;
    }
    return null;
}

// Actualizar movimiento
async function updateMovement() {
    submitted.value = true;
    serverErrors.value = {};
    loading.value = true;

    try {
        const payload = {
            movement_type: movement.value.movement_type,
            code: movement.value.code,
            date: formatDateForBackend(movement.value.date),
            provider_id: movement.value.provider_id,
            payment_type: movement.value.payment_type,
            credit_date: formatDateForBackend(movement.value.credit_date),
            includes_igv: movement.value.includes_igv,
            voucher_type: movement.value.voucher_type,
        };

        await axios.put(`/movements/${movement.value.id}`, payload);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Movimiento actualizado correctamente',
            life: 3000,
        });

        hideDialog();
        emit('actualizado');

    } catch (error) {
        if (error.response && error.response.status === 422) {
            serverErrors.value = error.response.data.errors || {};
            toast.add({
                severity: 'error',
                summary: 'Error de Validación',
                detail: 'Por favor, revise los campos del formulario',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar el movimiento',
                life: 3000,
            });
        }
    } finally {
        loading.value = false;
    }
}

// Resetear formulario
function resetMovement() {
    movement.value = {
        id: '',
        movement_type: 'ingreso',
        code: '',
        date: null,
        provider_id: '',
        payment_type: 'contado',
        credit_date: null,
        includes_igv: true,
        voucher_type: 'guia',
    };
    selectedProvider.value = null;
    serverErrors.value = {};
    submitted.value = false;
    loading.value = false;
    loadingMovement.value = false;
}

// Abrir modal con datos del movimiento
function open(movementId) {
    resetMovement();
    isVisible.value = true;
    loadMovement(movementId);
}

// Ocultar dialog
function hideDialog() {
    isVisible.value = false;
    resetMovement();
}

// Manejar evento de cierre
function onHide() {
    emit('hide');
    resetMovement();
}

// Exponer métodos públicos
defineExpose({
    open
});
</script>