<template>
    <DataTable ref="dt" v-model:selection="selectedSessions" :value="cashSessionStore.sessions" dataKey="id"
        :paginator="true" :rows="10" :filters="filters" :loading="cashSessionStore.isLoading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} sesiones" responsiveLayout="scroll" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0 text-xl font-bold">Sesiones de Caja</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <template #empty>
            <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-500">No se encontraron sesiones.</p>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

        <Column field="status" header="Estado" sortable style="min-width: 10rem">
            <template #body="{ data }">
                <span :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold',
                    data.status === 'abierta' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                ]">
                    {{ data.status.charAt(0).toUpperCase() + data.status.slice(1) }}
                </span>
            </template>
        </Column>

        <Column field="opened_by.name" header="Abierta por" sortable style="min-width: 14rem">
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <i class="pi pi-user text-blue-500"></i>
                    <span>{{ data.opened_by.name }}</span>
                </div>
            </template>
        </Column>

        <Column field="opening_amount" header="Monto Apertura" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <span class="font-semibold ">
                    S/ {{ parseFloat(data.opening_amount).toFixed(2) }}
                </span>
            </template>
        </Column>

        <Column field="system_total_amount" header="Total Sistema" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <span class="font-semibold">
                    S/ {{ parseFloat(data.system_total_amount).toFixed(2) }}
                </span>
            </template>
        </Column>

        <Column field="counted_total_amount" header="Total Contado" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <span class="font-semibold">
                    S/ {{ parseFloat(data.counted_total_amount).toFixed(2) }}
                </span>
            </template>
        </Column>

        <Column field="difference_amount" header="Diferencia" sortable style="min-width: 11rem">
            <template #body="{ data }">
                <span :class="[
                    'font-bold',
                    parseFloat(data.difference_amount) < 0 ? 'text-red-600' :
                        parseFloat(data.difference_amount) > 0 ? '' :
                            'text-gray-600'
                ]">
                    S/ {{ parseFloat(data.difference_amount).toFixed(2) }}
                </span>
            </template>
        </Column>

        <Column field="opened_at" header="Fecha Apertura" sortable style="min-width: 14rem">
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-gray-500"></i>
                    <span>{{ formatDate(data.opened_at) }}</span>
                </div>
            </template>
        </Column>

        <Column field="closed_at" header="Fecha Cierre" sortable style="min-width: 14rem">
            <template #body="{ data }">
                <div v-if="data.closed_at" class="flex items-center gap-2">
                    <i class="pi pi-calendar text-gray-500"></i>
                    <span>{{ formatDate(data.closed_at) }}</span>
                </div>
                <span v-else class="text-gray-400 italic">Sesión abierta</span>
            </template>
        </Column>

        <Column field="closed_by.name" header="Cerrada por" sortable style="min-width: 14rem">
            <template #body="{ data }">
                <div v-if="data.closed_by" class="flex items-center gap-2">
                    <i class="pi pi-user text-red-500"></i>
                    <span>{{ data.closed_by.name }}</span>
                </div>
                <span v-else class="text-gray-400">-</span>
            </template>
        </Column>

        <Column :exportable="false" style="min-width: 10rem" header="Acciones">
            <template #body="{ data }">
                <div class="flex gap-2">
                    <Button icon="pi pi-eye" outlined rounded severity="info" @click="viewSession(data)"
                        v-tooltip.top="'Ver detalles'" />
                    <Button v-if="data.status === 'abierta'" icon="pi pi-times-circle" outlined rounded
                        severity="danger" @click="closeSession(data)" v-tooltip.top="'Cerrar sesión'" />
                </div>
            </template>
        </Column>
    </DataTable>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import { useCashSessionStore } from '../Interface/cashSessionStore';
import type { CashSession } from '../Interface/cashSessionStore';

const dt = ref();
const selectedSessions = ref<CashSession[]>([]);
const cashSessionStore = useCashSessionStore();

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const viewSession = (session: CashSession) => {
    console.log('Ver sesión:', session);
};

const closeSession = (session: CashSession) => {
    console.log('Cerrar sesión:', session);
};
</script>