<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        :header="isEditing ? 'Editar Rango de Precio' : 'Nuevo Rango de Precio'" 
        :modal="true"
        :style="{ width: '700px' }"
        :closable="!isLoading"
        :closeOnEscape="!isLoading"
    >
        <div class="flex flex-col gap-6">
            <!-- Sucursal -->
            <div>
                <label for="sub_branch_id" class="block font-bold mb-3">
                    Sucursal <span class="text-red-500">*</span>
                </label>
                <Select 
                    id="sub_branch_id" 
                    v-model="form.sub_branch_id" 
                    :options="subBranches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Seleccione una sucursal"
                    :disabled="isLoading || isEditing"
                    :invalid="submitted && !form.sub_branch_id"
                    fluid
                    filter
                />
                <small v-if="submitted && !form.sub_branch_id" class="text-red-500">
                    La sucursal es obligatoria.
                </small>
                <small v-else-if="errors.sub_branch_id" class="text-red-500">
                    {{ errors.sub_branch_id }}
                </small>
            </div>

            <!-- Tipo de Habitación -->
            <div>
                <label for="room_type_id" class="block font-bold mb-3">
                    Tipo de Habitación <span class="text-red-500">*</span>
                </label>
                <Select 
                    id="room_type_id" 
                    v-model="form.room_type_id" 
                    :options="activeRoomTypes"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Seleccione un tipo de habitación"
                    :disabled="isLoading"
                    :invalid="submitted && !form.room_type_id"
                    fluid
                    filter
                >
                    <template #option="{ option }">
                        <div class="flex items-center justify-between">
                            <span>{{ option.name }}</span>
                            <Tag :value="option.code" severity="info" class="ml-2" />
                        </div>
                    </template>
                </Select>
                <small v-if="submitted && !form.room_type_id" class="text-red-500">
                    El tipo de habitación es obligatorio.
                </small>
                <small v-else-if="errors.room_type_id" class="text-red-500">
                    {{ errors.room_type_id }}
                </small>
            </div>

            <!-- Tipo de Tarifa -->
            <div>
                <label for="rate_type_id" class="block font-bold mb-3">
                    Tipo de Tarifa <span class="text-red-500">*</span>
                </label>
                <Select 
                    id="rate_type_id" 
                    v-model="form.rate_type_id" 
                    :options="activeRateTypes"
                    optionLabel="display_name"
                    optionValue="id"
                    placeholder="Seleccione un tipo de tarifa"
                    :disabled="isLoading"
                    :invalid="submitted && !form.rate_type_id"
                    fluid
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <i :class="`pi pi-${option.icon}`"></i>
                            <span>{{ option.display_name }}</span>
                            <Tag :value="option.code" severity="info" class="ml-auto" />
                        </div>
                    </template>
                </Select>
                <small v-if="submitted && !form.rate_type_id" class="text-red-500">
                    El tipo de tarifa es obligatorio.
                </small>
                <small v-else-if="errors.rate_type_id" class="text-red-500">
                    {{ errors.rate_type_id }}
                </small>
            </div>

            <!-- Rangos de Tiempo - SIEMPRE VISIBLE -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="time_from_minutes" class="block font-bold mb-3">
                        Desde (minutos)
                    </label>
                    <InputNumber 
                        id="time_from_minutes" 
                        v-model="form.time_from_minutes" 
                        :invalid="errors.time_from_minutes"
                        :min="0"
                        :max="43200"
                        placeholder="Ej: 0 (opcional)"
                        :disabled="isLoading"
                        fluid
                    />
                    <small class="text-surface-500">
                        {{ form.time_from_minutes !== null ? minutesToTimeString(form.time_from_minutes) : 'Sin tiempo definido' }}
                    </small>
                    <small v-if="errors.time_from_minutes" class="block text-red-500">
                        {{ errors.time_from_minutes }}
                    </small>
                </div>

                <div>
                    <label for="time_to_minutes" class="block font-bold mb-3">
                        Hasta (minutos)
                    </label>
                    <InputNumber 
                        id="time_to_minutes" 
                        v-model="form.time_to_minutes" 
                        :invalid="errors.time_to_minutes"
                        :min="form.time_from_minutes !== null ? form.time_from_minutes + 1 : 0"
                        :max="43200"
                        placeholder="Ej: 180 (opcional)"
                        :disabled="isLoading"
                        fluid
                    />
                    <small class="text-surface-500">
                        {{ form.time_to_minutes !== null ? minutesToTimeString(form.time_to_minutes) : 'Sin tiempo definido' }}
                    </small>
                    <small v-if="errors.time_to_minutes" class="block text-red-500">
                        {{ errors.time_to_minutes }}
                    </small>
                </div>
            </div>

            <!-- Rangos comunes rápidos - SIEMPRE VISIBLE -->
            <div>
                <label class="block font-bold mb-3">Rangos Comunes</label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="range in commonTimeRanges"
                        :key="range.label"
                        :label="range.label"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="setCommonRange(range)"
                        :disabled="isLoading"
                    />
                    <Button
                        label="Limpiar tiempos"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="clearTimeRange"
                        :disabled="isLoading"
                        icon="pi pi-times"
                    />
                </div>
            </div>

            <!-- Precio -->
            <div>
                <label for="price" class="block font-bold mb-3">
                    Precio (S/) <span class="text-red-500">*</span>
                </label>
                <InputNumber 
                    id="price" 
                    v-model="form.price" 
                    :invalid="submitted && (!form.price || form.price <= 0)"
                    :min="0"
                    :max="9999999.99"
                    :minFractionDigits="2"
                    :maxFractionDigits="2"
                    mode="currency"
                    currency="PEN"
                    locale="es-PE"
                    placeholder="Ej: 50.00"
                    :disabled="isLoading"
                    fluid
                />
                <small v-if="submitted && (!form.price || form.price <= 0)" class="text-red-500">
                    El precio debe ser mayor a 0.
                </small>
                <small v-else-if="errors.price" class="text-red-500">
                    {{ errors.price }}
                </small>
            </div>

            <!-- Vigencia -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="effective_from" class="block font-bold mb-3">
                        Vigente Desde <span class="text-red-500">*</span>
                    </label>
                    <DatePicker 
                        id="effective_from" 
                        v-model="form.effective_from" 
                        :invalid="submitted && !form.effective_from"
                        dateFormat="yy-mm-dd"
                        placeholder="Seleccione fecha"
                        :disabled="isLoading"
                        fluid
                    />
                    <small v-if="submitted && !form.effective_from" class="text-red-500">
                        La fecha de inicio es obligatoria.
                    </small>
                    <small v-else-if="errors.effective_from" class="text-red-500">
                        {{ errors.effective_from }}
                    </small>
                </div>

                <div>
                    <label for="effective_to" class="block font-bold mb-3">
                        Vigente Hasta
                    </label>
                    <DatePicker 
                        id="effective_to" 
                        v-model="form.effective_to" 
                        :minDate="form.effective_from || undefined"
                        dateFormat="yy-mm-dd"
                        placeholder="Sin límite (opcional)"
                        :disabled="isLoading"
                        fluid
                        showButtonBar
                    />
                    <small v-if="errors.effective_to" class="text-red-500">
                        {{ errors.effective_to }}
                    </small>
                    <small v-else class="text-surface-500">
                        Dejar vacío para vigencia indefinida
                    </small>
                </div>
            </div>

            <!-- Estado Activo -->
            <div class="flex items-center gap-2">
                <Checkbox 
                    inputId="is_active" 
                    v-model="form.is_active" 
                    :binary="true" 
                    :disabled="isLoading"
                />
                <label for="is_active" class="cursor-pointer">Activo</label>
            </div>

            <!-- Mensaje de error de solapamiento -->
            <div v-if="errors.overlap" class="p-3 bg-red-50 border border-red-200 rounded text-red-700">
                <i class="pi pi-exclamation-triangle mr-2"></i>
                {{ errors.overlap }}
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                icon="pi pi-times" 
                text
                @click="closeDialog"
                :disabled="isLoading"
                severity="secondary"
            />
            <Button 
                :label="isEditing ? 'Actualizar' : 'Guardar'" 
                icon="pi pi-check"
                @click="onSubmit"
                :loading="isLoading"
            />
        </template>
    </Dialog>
    <Toast />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { usePricingRangeStore } from '../stores/pricingRange.store';
import { useRoomTypeStore } from '../../RoomType/stores/roomType.store';
import { useRateTypeStore } from '../../RateType/stores/rateType.store';
import { useSubBranchStore } from '@/stores/subBranch.store';
import type { PricingRangeFormData } from '../interfaces/pricingRange.interface';
import { COMMON_TIME_RANGES, minutesToTimeString } from '../interfaces/pricingRange.interface';

const emit = defineEmits<{
    refresh: [];
}>();

const pricingRangeStore = usePricingRangeStore();
const roomTypeStore = useRoomTypeStore();
const rateTypeStore = useRateTypeStore();
const subBranchStore = useSubBranchStore();
const toast = useToast();

const dialogVisible = ref(false);
const isLoading = ref(false);
const editingId = ref<string | null>(null);
const submitted = ref(false);
const isEditing = computed(() => editingId.value !== null);

const commonTimeRanges = COMMON_TIME_RANGES;

const form = ref({
    sub_branch_id: '',
    room_type_id: '',
    rate_type_id: '',
    time_from_minutes: null as number | null,
    time_to_minutes: null as number | null,
    price: 0,
    effective_from: new Date() as Date | null,
    effective_to: null as Date | null,
    is_active: true
});

const errors = ref<Partial<Record<keyof PricingRangeFormData | 'overlap', string>>>({});

const subBranches = computed(() => subBranchStore.activeSubBranches);
const activeRoomTypes = computed(() => roomTypeStore.activeRoomTypes);
const activeRateTypes = computed(() => rateTypeStore.activeRateTypes);

const openEdit = async (id: string | number) => {
    if (id === 0 || id === '0') {
        // Nuevo
        resetForm();
        editingId.value = null;
        dialogVisible.value = true;
    } else {
        // Editar
        isLoading.value = true;
        try {
            const pricingRange = pricingRangeStore.getPricingRangeById(String(id));
            
            if (pricingRange) {
                form.value = {
                    sub_branch_id: pricingRange.sub_branch_id,
                    room_type_id: pricingRange.room_type_id,
                    rate_type_id: pricingRange.rate_type_id,
                    time_from_minutes: pricingRange.time_from_minutes,
                    time_to_minutes: pricingRange.time_to_minutes,
                    price: pricingRange.price,
                    effective_from: pricingRange.effective_from ? new Date(pricingRange.effective_from) : new Date(),
                    effective_to: pricingRange.effective_to ? new Date(pricingRange.effective_to) : null,
                    is_active: pricingRange.is_active
                };
                editingId.value = String(id);
                dialogVisible.value = true;
            } else {
                const loadedPricingRange = await pricingRangeStore.fetchPricingRangeById(String(id));
                form.value = {
                    sub_branch_id: loadedPricingRange.sub_branch_id,
                    room_type_id: loadedPricingRange.room_type_id,
                    rate_type_id: loadedPricingRange.rate_type_id,
                    time_from_minutes: loadedPricingRange.time_from_minutes,
                    time_to_minutes: loadedPricingRange.time_to_minutes,
                    price: loadedPricingRange.price,
                    effective_from: loadedPricingRange.effective_from ? new Date(loadedPricingRange.effective_from) : new Date(),
                    effective_to: loadedPricingRange.effective_to ? new Date(loadedPricingRange.effective_to) : null,
                    is_active: loadedPricingRange.is_active
                };
                editingId.value = String(id);
                dialogVisible.value = true;
            }
        } catch (error: any) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'No se pudo cargar el rango de precio',
                life: 3000
            });
        } finally {
            isLoading.value = false;
        }
    }
};

const setCommonRange = (range: typeof COMMON_TIME_RANGES[number]) => {
    form.value.time_from_minutes = range.from;
    form.value.time_to_minutes = range.to;
};

const clearTimeRange = () => {
    form.value.time_from_minutes = null;
    form.value.time_to_minutes = null;
};

const validateForm = (): boolean => {
    errors.value = {};
    let isValid = true;

    if (!form.value.sub_branch_id) {
        errors.value.sub_branch_id = 'La sucursal es obligatoria';
        isValid = false;
    }

    if (!form.value.room_type_id) {
        errors.value.room_type_id = 'El tipo de habitación es obligatorio';
        isValid = false;
    }

    if (!form.value.rate_type_id) {
        errors.value.rate_type_id = 'El tipo de tarifa es obligatorio';
        isValid = false;
    }

    // Validar tiempos solo si ambos están definidos
    if (form.value.time_from_minutes !== null && form.value.time_to_minutes !== null) {
        if (form.value.time_to_minutes <= form.value.time_from_minutes) {
            errors.value.time_to_minutes = 'El tiempo final debe ser mayor al tiempo inicial';
            isValid = false;
        }
    }

    // Validar que si uno está definido, el otro también lo esté
    if ((form.value.time_from_minutes !== null && form.value.time_to_minutes === null) ||
        (form.value.time_from_minutes === null && form.value.time_to_minutes !== null)) {
        errors.value.time_to_minutes = 'Debe definir ambos tiempos o ninguno';
        isValid = false;
    }

    if (!form.value.price || form.value.price <= 0) {
        errors.value.price = 'El precio debe ser mayor a 0';
        isValid = false;
    }

    if (!form.value.effective_from) {
        errors.value.effective_from = 'La fecha de inicio es obligatoria';
        isValid = false;
    }

    if (form.value.effective_to && form.value.effective_from) {
        if (form.value.effective_to <= form.value.effective_from) {
            errors.value.effective_to = 'La fecha de fin debe ser posterior a la fecha de inicio';
            isValid = false;
        }
    }

    return isValid;
};

const formatDateForAPI = (date: Date | null): string | null => {
    if (!date) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const onSubmit = async () => {
    submitted.value = true;
    
    if (!validateForm()) return;

    isLoading.value = true;
    
    try {
        const dataToSubmit: PricingRangeFormData = {
            sub_branch_id: form.value.sub_branch_id,
            room_type_id: form.value.room_type_id,
            rate_type_id: form.value.rate_type_id,
            time_from_minutes: form.value.time_from_minutes,
            time_to_minutes: form.value.time_to_minutes,
            price: form.value.price,
            effective_from: formatDateForAPI(form.value.effective_from) || new Date().toISOString().split('T')[0],
            effective_to: formatDateForAPI(form.value.effective_to),
            is_active: form.value.is_active
        };

        if (isEditing.value && editingId.value) {
            await pricingRangeStore.updatePricingRange(editingId.value, dataToSubmit);
            toast.add({
                severity: 'success',
                summary: 'Actualizado',
                detail: 'Rango de precio actualizado correctamente',
                life: 3000
            });
        } else {
            await pricingRangeStore.createPricingRange(dataToSubmit);
            toast.add({
                severity: 'success',
                summary: 'Creado',
                detail: 'Rango de precio creado correctamente',
                life: 3000
            });
        }
        closeDialog();
        emit('refresh');
    } catch (error: any) {
        const message = error.response?.data?.message || 'Ocurrió un error al guardar';
        const apiErrors = error.response?.data?.errors;
        
        if (apiErrors) {
            Object.keys(apiErrors).forEach(key => {
                if (key === 'overlap') {
                    errors.value.overlap = Array.isArray(apiErrors[key]) ? apiErrors[key][0] : apiErrors[key];
                } else if (key in form.value) {
                    errors.value[key as keyof PricingRangeFormData] = Array.isArray(apiErrors[key]) 
                        ? apiErrors[key][0] 
                        : apiErrors[key];
                }
            });
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const closeDialog = () => {
    dialogVisible.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        sub_branch_id: '',
        room_type_id: '',
        rate_type_id: '',
        time_from_minutes: null,
        time_to_minutes: null,
        price: 0,
        effective_from: new Date(),
        effective_to: null,
        is_active: true
    };
    errors.value = {};
    editingId.value = null;
    submitted.value = false;
};

onMounted(async () => {
    try {
        // Cargar sub-branches
        if (subBranchStore.subBranches.length === 0) {
            await subBranchStore.fetchActiveSubBranches();
        }
        
        // Cargar tipos de habitación
        if (roomTypeStore.roomTypes.length === 0) {
            await roomTypeStore.fetchRoomTypes({ is_active: true });
        }
        
        // Cargar tipos de tarifa
        if (rateTypeStore.rateTypes.length === 0) {
            await rateTypeStore.fetchRateTypes({ is_active: true });
        }
    } catch (error) {
        console.error('Error loading data:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los datos necesarios',
            life: 3000
        });
    }
});

defineExpose({
    openEdit
});
</script>