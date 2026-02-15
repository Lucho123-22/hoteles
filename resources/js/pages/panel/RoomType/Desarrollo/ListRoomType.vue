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
            <Button 
                icon="pi pi-refresh" 
                severity="contrast" 
                rounded 
                variant="outlined"
                v-tooltip.top="'Actualizar'"
                @click="fetchRoomTypes" 
                :loading="roomTypeStore.isLoading"
            />
        </template>
    </Toolbar>

    <DataTable 
        ref="dt" 
        :value="roomTypeStore.roomTypes" 
        :paginator="true" 
        :rows="10" 
        :rowsPerPageOptions="[5, 10, 25, 50]"
        :filters="filters" 
        stripedRows 
        responsiveLayout="scroll" 
        :loading="roomTypeStore.isLoading"
        class="p-datatable-sm"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de habitación"
        dataKey="id"
    >
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h4 class="m-0">Gestionar Tipos de Habitación</h4>

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

        <!-- Columna: Código -->
        <Column 
            field="code" 
            header="Código" 
            :sortable="true" 
            style="min-width: 10rem"
        >
            <template #body="{ data }">
                <Tag :value="data.code" severity="info" />
            </template>
        </Column>

        <!-- Columna: Nombre -->
        <Column 
            field="name" 
            header="Nombre" 
            :sortable="true" 
            style="min-width: 16rem" 
        />

        <!-- Columna: Categoría -->
        <Column 
            field="category" 
            header="Categoría" 
            :sortable="true" 
            style="min-width: 12rem"
        >
            <template #body="{ data }">
                <Tag 
                    :value="data.category || 'Sin categoría'"
                    :severity="getCategorySeverity(data.category)" 
                />
            </template>
        </Column>

        <!-- Columna: Capacidad -->
        <Column 
            field="capacity" 
            header="Capacidad" 
            :sortable="true" 
            style="min-width: 10rem"
        >
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <i class="pi pi-users"></i>
                    <span>{{ data.capacity }}</span>
                    <span v-if="data.max_capacity" class="text-surface-500">
                        (máx: {{ data.max_capacity }})
                    </span>
                </div>
            </template>
        </Column>

        <!-- Columna: Descripción -->
        <Column 
            field="description" 
            header="Descripción" 
            style="min-width: 20rem"
        >
            <template #body="{ data }">
                <span class="text-surface-600">
                    {{ data.description || '-' }}
                </span>
            </template>
        </Column>

        <!-- Columna: Estado -->
        <Column 
            field="is_active" 
            header="Estado" 
            :sortable="true" 
            style="min-width: 10rem"
        >
            <template #body="{ data }">
                <Tag 
                    :value="data.is_active ? 'Activo' : 'Inactivo'"
                    :severity="data.is_active ? 'success' : 'danger'" 
                />
            </template>
        </Column>

        <!-- Columna: Fecha de Creación -->
        <Column 
            field="created_at" 
            header="Fecha Creación" 
            :sortable="true" 
            style="min-width: 12rem"
        >
            <template #body="{ data }">
                {{ formatDate(data.created_at) }}
            </template>
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
                    @click="editRoomType(data.id)" 
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
                <p class="mt-3 text-500">No se encontraron tipos de habitación</p>
            </div>
        </template>

        <template #loading>
            <div class="text-center p-4">
                <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                <p class="mt-3">Cargando...</p>
            </div>
        </template>
    </DataTable>

    <ConfirmDialog />
    <Toast />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useRoomTypeStore } from '../stores/roomType.store';
import type { RoomType } from '../interfaces/roomType.interface';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const emit = defineEmits<{
    edit: [id: string];
}>();

const roomTypeStore = useRoomTypeStore();
const confirm = useConfirm();
const toast = useToast();
const dt = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const fetchRoomTypes = async () => {
    try {
        await roomTypeStore.fetchRoomTypes({
            with_rooms_count: false,
            with_pricing_ranges_count: false
        });
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudieron cargar los tipos de habitación',
            life: 3000
        });
    }
};

const openNew = () => {
    emit('edit', '0');
};

const editRoomType = (id: string) => {
    emit('edit', id);
};

const confirmDelete = (roomType: RoomType) => {
    confirm.require({
        message: `¿Está seguro de eliminar el tipo de habitación "${roomType.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await roomTypeStore.deleteRoomType(roomType.id);
                toast.add({
                    severity: 'success',
                    summary: 'Eliminado',
                    detail: 'Tipo de habitación eliminado correctamente',
                    life: 3000
                });
            } catch (error: any) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'No se pudo eliminar el tipo de habitación',
                    life: 3000
                });
            }
        }
    });
};

const getCategorySeverity = (category: string | null) => {
    if (!category) return 'secondary';
    
    const severityMap: Record<string, string> = {
        'Económica': 'secondary',
        'Estándar': 'info',
        'Premium': 'warn',
        'Lujo': 'success'
    };
    
    return severityMap[category] || 'secondary';
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchRoomTypes();
});

defineExpose({
    fetchRoomTypes
});
</script>