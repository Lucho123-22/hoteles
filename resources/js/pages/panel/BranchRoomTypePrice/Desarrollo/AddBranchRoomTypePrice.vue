<template>
    <div class="mb-4">
        <Toolbar class="mb-6">
            <template #start>
                <Button label="Nueva" icon="pi pi-plus" class="mr-2" @click="openNew" severity="contrast" />
                <div class="flex items-center h-full">
                    <Checkbox
                        v-model="filterCurrentOnly"
                        inputId="current_only"
                        :binary="true"
                    />
                    <label for="current_only" class="ml-2">Solo vigentes</label>
                </div>
            </template>
            <template #center>
                <Select
                    v-model="filterSubBranchId"
                    :options="subBranches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Seleccione una sucursal"
                    showClear
                    class="w-full mr-2"
                />
                <Select
                    v-model="filterRoomTypeId"
                    :options="roomTypes"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Tipo de habitación"
                    showClear
                    class="w-full"
                />
            </template>
            <template #end>
                <Select
                    v-model="filterRateTypeId"
                    :options="rateTypes"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Tipo de tarifa"
                    showClear
                    class="w-full mr-2"
                />
                <Select
                    v-model="filterIsActive"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Todos"
                    showClear
                    class="w-full mr-2"
                />
                <Button
                    icon="pi pi-filter"
                    :loading="loading"
                    @click="emitFilters"
                    v-tooltip.top="'Aplicar filtros'"
                />
                <Button
                    icon="pi pi-filter-slash"
                    severity="secondary"
                    @click="clearFilters"
                    v-tooltip.top="'Limpiar filtros'"
                />
            </template>
        </Toolbar>

        <!-- Modal de Formulario -->
        <Dialog v-model:visible="showModal" :header="isEditing ? 'Editar Configuración' : 'Nueva Configuración'"
            :modal="true" :style="{ width: '600px' }">
            <div v-if="loadingOptions" class="flex justify-center items-center p-8">
                <i class="pi pi-spin pi-spinner text-4xl"></i>
            </div>

            <form v-else @submit.prevent="handleSubmit">
                <div class="flex flex-col gap-6">
                    <!-- DEBUG: Mostrar cantidad de opciones -->
                    <div v-if="false" class="p-3 bg-blue-50 rounded">
                        <small>
                            Debug: SubBranches: {{ subBranches?.length || 0 }} | 
                            RoomTypes: {{ roomTypes?.length || 0 }} | 
                            RateTypes: {{ rateTypes?.length || 0 }}
                        </small>
                    </div>

                    <!-- Sucursal -->
                    <div>
                        <label for="sub_branch_id" class="block font-bold mb-3">Sucursal</label>
                        <Select 
                            id="sub_branch_id" 
                            v-model="formData.sub_branch_id" 
                            :options="subBranches"
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Seleccione una sucursal"
                            :disabled="loading" 
                            :invalid="submitted && !formData.sub_branch_id" 
                            fluid
                        >
                            <template #option="slotProps">
                                <div>
                                    <div>{{ slotProps.option.name }}</div>
                                    <small class="text-muted">{{ slotProps.option.code }}</small>
                                </div>
                            </template>
                        </Select>
                        <small v-if="submitted && !formData.sub_branch_id" class="text-red-500">
                            Sucursal es requerida.
                        </small>
                    </div>

                    <!-- Tipo de Habitación -->
                    <div>
                        <label for="room_type_id" class="block font-bold mb-3">Tipo de Habitación</label>
                        <Select 
                            id="room_type_id" 
                            v-model="formData.room_type_id" 
                            :options="roomTypes"
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Seleccione un tipo de habitación"
                            :disabled="loading" 
                            :invalid="submitted && !formData.room_type_id" 
                            fluid
                        >
                            <template #option="slotProps">
                                <div>
                                    <div>{{ slotProps.option.name }}</div>
                                    <small class="text-muted">{{ slotProps.option.category }}</small>
                                </div>
                            </template>
                        </Select>
                        <small v-if="submitted && !formData.room_type_id" class="text-red-500">
                            Tipo de habitación es requerido.
                        </small>
                    </div>

                    <!-- Tipo de Tarifa -->
                    <div>
                        <label for="rate_type_id" class="block font-bold mb-3">Tipo de Tarifa</label>
                        <Select 
                            id="rate_type_id" 
                            v-model="formData.rate_type_id" 
                            :options="rateTypes"
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Seleccione un tipo de tarifa"
                            :disabled="loading" 
                            :invalid="submitted && !formData.rate_type_id" 
                            fluid 
                        />
                        <small v-if="submitted && !formData.rate_type_id" class="text-red-500">
                            Tipo de tarifa es requerido.
                        </small>
                    </div>

                    <!-- Fechas en Grid -->
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6">
                            <label for="effective_from" class="block font-bold mb-3">Vigente Desde</label>
                            <DatePicker 
                                id="effective_from" 
                                v-model="effectiveFromDate" 
                                dateFormat="yy-mm-dd"
                                :disabled="loading" 
                                :invalid="submitted && !effectiveFromDate" 
                                fluid 
                            />
                            <small v-if="submitted && !effectiveFromDate" class="text-red-500">
                                Fecha de inicio es requerida.
                            </small>
                        </div>
                        <div class="col-span-6">
                            <label for="effective_to" class="block font-bold mb-3">Vigente Hasta</label>
                            <DatePicker 
                                id="effective_to" 
                                v-model="effectiveToDate" 
                                dateFormat="yy-mm-dd"
                                :disabled="loading" 
                                :minDate="effectiveFromDate" 
                                placeholder="Sin límite" 
                                fluid 
                            />
                            <small class="text-muted">Opcional</small>
                        </div>
                    </div>

                    <!-- Estado Activo -->
                    <div>
                        <span class="block font-bold mb-4">Estado</span>
                        <div class="flex items-center gap-2">
                            <Checkbox 
                                id="is_active" 
                                v-model="formData.is_active" 
                                :binary="true" 
                                :disabled="loading" 
                            />
                            <label for="is_active">Configuración activa</label>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <Button 
                    label="Cancelar" 
                    icon="pi pi-times" 
                    @click="hideDialog" 
                    :disabled="loading" 
                    severity="secondary"
                    text 
                />
                <Button 
                    :label="isEditing ? 'Actualizar' : 'Guardar'" 
                    icon="pi pi-check" 
                    @click="handleSubmit" 
                    severity="contrast"
                    :loading="loading" 
                    :disabled="loadingOptions" 
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { storeToRefs } from 'pinia'; // ⭐ IMPORTANTE: Importar storeToRefs
import { useBranchRoomTypePriceStore } from '../stores/useBranchRoomTypePriceStore';
import type { BranchRoomTypePrice, BranchRoomTypePriceFormData, FilterParams } from '../interfaces';
import { useToast } from 'primevue/usetoast';

import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';

const toast = useToast();
const store = useBranchRoomTypePriceStore();

// ⭐ USAR storeToRefs para mantener la reactividad
const { subBranches, roomTypes, rateTypes, loading: loadingOptions } = storeToRefs(store);

const showModal = ref(false);
const loading = ref(false);
const isEditing = ref(false);
const submitted = ref(false);

// Filtros separados
const filterSubBranchId = ref<string | null>(null);
const filterRoomTypeId = ref<string | null>(null);
const filterRateTypeId = ref<string | null>(null);
const filterIsActive = ref<boolean | null>(null);
const filterCurrentOnly = ref(false);

const statusOptions = [
    { label: 'Activos', value: true },
    { label: 'Inactivos', value: false },
];

const formData = reactive<BranchRoomTypePriceFormData>({
    sub_branch_id: '',
    room_type_id: '',
    rate_type_id: '',
    effective_from: '',
    effective_to: null,
    is_active: true,
});

const effectiveFromDate = ref<Date | null>(null);
const effectiveToDate = ref<Date | null>(null);

const emit = defineEmits<{
    (e: 'filtersChanged', filters: FilterParams): void;
}>();

function openNew() {
    resetForm();
    isEditing.value = false;
    showModal.value = true;
}

function hideDialog() {
    showModal.value = false;
    resetForm();
}

function resetForm() {
    formData.sub_branch_id = '';
    formData.room_type_id = '';
    formData.rate_type_id = '';
    formData.effective_from = '';
    formData.effective_to = null;
    formData.is_active = true;
    effectiveFromDate.value = null;
    effectiveToDate.value = null;
    submitted.value = false;
}

function formatDateToISO(date: Date | null): string | null {
    if (!date) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function emitFilters() {
    const filters: FilterParams = {};
    
    if (filterSubBranchId.value) filters.sub_branch_id = filterSubBranchId.value;
    if (filterRoomTypeId.value) filters.room_type_id = filterRoomTypeId.value;
    if (filterRateTypeId.value) filters.rate_type_id = filterRateTypeId.value;
    if (filterIsActive.value !== null) filters.is_active = filterIsActive.value;
    if (filterCurrentOnly.value) filters.current_only = filterCurrentOnly.value;

    emit('filtersChanged', filters);
}

function clearFilters() {
    filterSubBranchId.value = null;
    filterRoomTypeId.value = null;
    filterRateTypeId.value = null;
    filterIsActive.value = null;
    filterCurrentOnly.value = false;
    
    emit('filtersChanged', {});
}

async function handleSubmit() {
    submitted.value = true;

    // Validación básica
    if (!formData.sub_branch_id || !formData.room_type_id || !formData.rate_type_id || !effectiveFromDate.value) {
        toast.add({ 
            severity: 'warn', 
            summary: 'Validación', 
            detail: 'Por favor complete todos los campos requeridos', 
            life: 3000 
        });
        return;
    }

    loading.value = true;

    formData.effective_from = formatDateToISO(effectiveFromDate.value) || '';
    formData.effective_to = formatDateToISO(effectiveToDate.value);

    try {
        let response;

        if (isEditing.value) {
            response = await store.updatePrice(formData.sub_branch_id, formData);
            toast.add({ 
                severity: 'success', 
                summary: 'Éxito', 
                detail: 'Configuración actualizada correctamente', 
                life: 3000 
            });
        } else {
            response = await store.createPrice(formData);
            toast.add({ 
                severity: 'success', 
                summary: 'Éxito', 
                detail: 'Configuración creada correctamente', 
                life: 3000 
            });
        }

        hideDialog();
        // Recargar datos
        await store.fetchPrices();
    } catch (error: any) {
        console.error('Error al guardar:', error);
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: error.response?.data?.message || 'Ocurrió un error al guardar', 
            life: 3000 
        });
    } finally {
        loading.value = false;
    }
}

// Exponer método para editar desde el componente padre
defineExpose({
    editPrice: (price: BranchRoomTypePrice) => {
        formData.sub_branch_id = price.sub_branch_id;
        formData.room_type_id = price.room_type_id;
        formData.rate_type_id = price.rate_type_id;
        formData.effective_from = price.effective_from;
        formData.effective_to = price.effective_to;
        formData.is_active = price.is_active;

        effectiveFromDate.value = price.effective_from ? new Date(price.effective_from) : null;
        effectiveToDate.value = price.effective_to ? new Date(price.effective_to) : null;

        submitted.value = false;
        isEditing.value = true;
        showModal.value = true;
    }
});

onMounted(async () => {
    console.log('🔵 Montando AddBranchRoomTypePrice...');
    await store.loadOptions();
    console.log('✅ Opciones cargadas:', { 
        subBranches: subBranches.value?.length, 
        roomTypes: roomTypes.value?.length, 
        rateTypes: rateTypes.value?.length 
    });
});
</script>