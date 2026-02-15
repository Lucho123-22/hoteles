<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button 
                label="Nuevo" 
                icon="pi pi-plus" 
                severity="contrast" 
                @click="openNew" 
            />
        </template>
        <template #end>
            <div class="flex gap-2">
                <!-- Filtro por Sucursal -->
                <Select 
                    v-model="selectedSubBranch" 
                    :options="subBranches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Filtrar por sucursal"
                    @change="applyFilters"
                    style="min-width: 200px"
                    showClear
                />
                
                <!-- Filtro por Tipo de Tarifa -->
                <Select 
                    v-model="selectedRateType" 
                    :options="rateTypeStore.activeRateTypes"
                    optionLabel="display_name"
                    optionValue="code"
                    placeholder="Filtrar por tipo"
                    @change="applyFilters"
                    style="min-width: 200px"
                    showClear
                />
                
                <Button 
                    icon="pi pi-refresh" 
                    severity="contrast" 
                    rounded 
                    variant="outlined"
                    v-tooltip.top="'Actualizar'"
                    @click="fetchPricingRanges" 
                    :loading="pricingRangeStore.isLoading"
                />
            </div>
        </template>
    </Toolbar>

    <DataTable 
        ref="dt" 
        :value="pricingRangeStore.pricingRanges" 
        :paginator="true" 
        :rows="10" 
        :rowsPerPageOptions="[5, 10, 25, 50]"
        :filters="filters" 
        stripedRows 
        responsiveLayout="scroll" 
        :loading="pricingRangeStore.isLoading"
        class="p-datatable-sm"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} rangos de precio"
        dataKey="id"
    >
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h4 class="m-0">Gestionar Rangos de Precio</h4>

                <div class="flex items-center gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText 
                            v-model="filters['global'].value" 
                            placeholder="Buscar..." 
                        />
                    </IconField>
                </div>
            </div>
        </template>

        <!-- Columna: Tipo de Habitación -->
        <Column 
            field="room_type.name" 
            header="Habitación" 
            :sortable="true" 
            style="min-width: 16rem"
        >
            <template #body="{ data }">
                <div class="flex flex-col gap-1">
                    <span class="font-semibold">{{ data.room_type?.name || '-' }}</span>
                    <Tag 
                        v-if="data.room_type?.code" 
                        :value="data.room_type.code" 
                        severity="info" 
                        size="small"
                    />
                </div>
            </template>
        </Column>

        <!-- Columna: Tipo de Tarifa -->
        <Column 
            field="rate_type.name" 
            header="Tipo Tarifa" 
            :sortable="true" 
            style="min-width: 14rem"
        >
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <i 
                        v-if="data.rate_type?.icon" 
                        :class="`pi pi-${data.rate_type.icon}`"
                    ></i>
                    <span>{{ data.rate_type?.display_name || '-' }}</span>
                </div>
            </template>
        </Column>

        <!-- Columna: Rango de Tiempo -->
        <Column 
            header="Rango Tiempo" 
            style="min-width: 14rem"
        >
            <template #body="{ data }">
                <div v-if="data.time_from_minutes !== null && data.time_to_minutes !== null">
                    <Tag 
                        :value="data.formatted_time_range" 
                        severity="info"
                    />
                </div>
                <span v-else class="text-surface-500">-</span>
            </template>
        </Column>

        <!-- Columna: Precio -->
        <Column 
            field="price" 
            header="Precio" 
            :sortable="true" 
            style="min-width: 12rem"
        >
            <template #body="{ data }">
                <div class="flex flex-col gap-1">
                    <span class="font-semibold text-lg">
                        S/ {{ formatPrice(data.price) }}
                    </span>
                    <span 
                        v-if="data.price_per_hour" 
                        class="text-surface-500 text-sm"
                    >
                        S/ {{ formatPrice(data.price_per_hour) }}/hora
                    </span>
                </div>
            </template>
        </Column>

        <!-- Columna: Vigencia -->
        <Column 
            header="Vigencia" 
            style="min-width: 16rem"
        >
            <template #body="{ data }">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-calendar text-surface-500"></i>
                        <span class="text-sm">
                            {{ data.effective_from }}
                        </span>
                    </div>
                    <div v-if="data.effective_to" class="flex items-center gap-2">
                        <i class="pi pi-calendar-times text-surface-500"></i>
                        <span class="text-sm">
                            {{ data.effective_to }}
                        </span>
                    </div>
                    <Tag 
                        v-else 
                        value="Sin límite" 
                        severity="success" 
                        size="small"
                    />
                </div>
            </template>
        </Column>

        <!-- Columna: Estado Efectivo -->
        <Column 
            header="Estado" 
            style="min-width: 12rem"
        >
            <template #body="{ data }">
                <div class="flex flex-col gap-2">
                    <Tag 
                        :value="data.is_active ? 'Activo' : 'Inactivo'"
                        :severity="data.is_active ? 'success' : 'danger'" 
                        size="small"
                    />
                    <Tag 
                        v-if="data.is_active"
                        :value="data.is_effective ? 'Vigente' : 'No vigente'"
                        :severity="data.is_effective ? 'info' : 'warn'" 
                        size="small"
                    />
                </div>
            </template>
        </Column>

        <!-- Columna: Fecha de Creación -->
        <Column 
            field="created_at" 
            header="Fecha Creación" 
            :sortable="true" 
            style="min-width: 12rem"
        >
        </Column>

        <!-- Columna: Acciones -->
        <Column 
            :exportable="false" 
            style="min-width: 10rem"
            header="Acciones"
        >
            <template #body="{ data }">
                <Button 
                    icon="pi pi-pencil" 
                    variant="outlined" 
                    rounded 
                    class="mr-2" 
                    severity="info"
                    v-tooltip.top="'Editar'" 
                    @click="editPricingRange(data.id)" 
                />
                <Button 
                    icon="pi pi-trash" 
                    variant="outlined" 
                    rounded 
                    severity="danger" 
                    v-tooltip.top="'Eliminar'"
                    @click="confirmDelete(data)" 
                />
            </template>
        </Column>

        <template #empty>
            <div class="text-center p-4">
                <i class="pi pi-inbox" style="font-size: 3rem; color: var(--surface-400);"></i>
                <p class="mt-3 text-500">No se encontraron rangos de precio</p>
                <p class="text-sm text-surface-500">Intenta cambiar los filtros o crear uno nuevo</p>
            </div>
        </template>

        <template #loading>
            <div class="text-center p-4">
                <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                <p class="mt-3">Cargando rangos de precio...</p>
            </div>
        </template>
    </DataTable>

    <ConfirmDialog />
    <Toast />
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { usePricingRangeStore } from '../stores/pricingRange.store';
import { useRateTypeStore } from '../../RateType/stores/rateType.store';
import { useSubBranchStore } from '@/stores/subBranch.store';
import type { PricingRange } from '../interfaces/pricingRange.interface';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const emit = defineEmits<{
    edit: [id: string];
}>();

const pricingRangeStore = usePricingRangeStore();
const rateTypeStore = useRateTypeStore();
const subBranchStore = useSubBranchStore();
const confirm = useConfirm();
const toast = useToast();
const dt = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const selectedSubBranch = ref<string | null>(null);
const selectedRateType = ref<string | null>(null);

const subBranches = computed(() => subBranchStore.activeSubBranches);

const fetchPricingRanges = async () => {
    try {
        const filters: any = {
            is_active: true,
            only_effective: false
        };

        if (selectedSubBranch.value) {
            filters.sub_branch_id = selectedSubBranch.value;
        }

        if (selectedRateType.value) {
            filters.rate_type_code = selectedRateType.value;
        }

        await pricingRangeStore.fetchPricingRanges(filters);
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudieron cargar los rangos de precio',
            life: 3000
        });
    }
};

const applyFilters = () => {
    fetchPricingRanges();
};

const openNew = () => {
    emit('edit', '0');
};

const editPricingRange = (id: string) => {
    emit('edit', id);
};

const confirmDelete = (pricingRange: PricingRange) => {
    const roomTypeName = pricingRange.room_type?.name || 'Desconocida';
    const rateTypeName = pricingRange.rate_type?.display_name || 'Desconocida';
    
    confirm.require({
        message: `¿Está seguro de eliminar el rango de precio para "${roomTypeName}" - "${rateTypeName}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await pricingRangeStore.deletePricingRange(pricingRange.id);
                toast.add({
                    severity: 'success',
                    summary: 'Eliminado',
                    detail: 'Rango de precio eliminado correctamente',
                    life: 3000
                });
            } catch (error: any) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'No se pudo eliminar el rango de precio',
                    life: 3000
                });
            }
        }
    });
};


const formatPrice = (price: number): string => {
    return price.toFixed(2);
};

onMounted(async () => {
    try {
        // Cargar sub-branches
        if (subBranchStore.subBranches.length === 0) {
            await subBranchStore.fetchActiveSubBranches();
        }
        
        // Cargar tipos de tarifa
        if (rateTypeStore.rateTypes.length === 0) {
            await rateTypeStore.fetchRateTypes({ is_active: true });
        }
        
        // Cargar rangos de precio
        await fetchPricingRanges();
    } catch (error) {
        console.error('Error loading data:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los datos iniciales',
            life: 3000
        });
    }
});

defineExpose({
    fetchPricingRanges
});
</script>