<template>
  <DataTable ref="dt" v-model:selection="selectedRecords" :value="kardexValorizadoStore.kardexData" 
    dataKey="id" :paginator="true" :rows="15" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[10, 15, 25, 50]"
    :currentPageReportTemplate="`Mostrando {first} a {last} de ${kardexValorizadoStore.pagination.total} registros`"
    class="p-datatable-sm"
    :loading="kardexValorizadoStore.loading"
    stripedRows
    :scrollable="true"
    scrollHeight="600px">
    
    <template #header>
      <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <div>
          <h4 style="margin: 0;">Kardex Valorizado</h4>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Buscar en resultados..." />
          </IconField>
        </div>
      </div>
    </template>

    <template #empty>
      <div style="text-align: center; padding: 2rem 0;">
        <i class="pi pi-chart-line" style="font-size: 2.5rem; color: #9ca3af; margin-bottom: 1rem;"></i>
        <p>No hay registros de kardex valorizado.</p>
        <p>Seleccione un producto, fechas y sucursal para comenzar.</p>
      </div>
    </template>

    <!-- Columnas principales -->
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false" frozen></Column>
    
    <Column field="fecha" header="Fecha" sortable style="min-width: 11rem" frozen></Column>
    
    <Column field="producto" header="Producto" sortable style="min-width: 18rem" frozen></Column>

    <Column field="tipo_movimiento" header="Tipo Movimiento" sortable style="min-width: 11rem">
      <template #body="{ data }">
        <Tag 
          :value="data.tipo_movimiento.toUpperCase()" 
          :severity="data.tipo_movimiento.toLowerCase() === 'entrada' ? 'success' : 'danger'" 
          :icon="data.tipo_movimiento.toLowerCase() === 'entrada' ? 'pi pi-arrow-down' : 'pi pi-arrow-up'"
        />
      </template>
    </Column>

    <!-- Columnas de Cantidades -->
    <Column header="Cantidad Caja" field="cantidad_caja" sortable style="min-width: 9rem">
      <template #body="{ data }">
        <span :style="getCantidadStyle(data.cantidad_caja)">
          {{ parseFloat(data.cantidad_caja).toFixed(2) }}
        </span>
      </template>
    </Column>

    <Column header="Cantidad Fracción" field="cantidad_fraccion" sortable style="min-width: 11rem">
      <template #body="{ data }">
        <span :style="getCantidadStyle(data.cantidad_fraccion)">
          {{ parseFloat(data.cantidad_fraccion).toFixed(2) }}
        </span>
      </template>
    </Column>

    <!-- Columnas de Costos -->
    <Column field="costo_unitario" header="Costo Unitario" sortable style="min-width: 11rem">
      <template #body="{ data }">
        <div style="display: flex; align-items: center; gap: 0.25rem;">
          <span style="font-size: 0.75rem; color: #6b7280;">S/</span>
          <span style="font-weight: 600; color: #1d4ed8;">{{ data.costo_unitario }}</span>
        </div>
      </template>
    </Column>

    <Column field="costo_total" header="Costo Movimiento" sortable style="min-width: 12rem">
      <template #body="{ data }">
        <div style="display: flex; align-items: center; gap: 0.25rem;">
          <span style="font-size: 0.75rem; color: #6b7280;">S/</span>
          <span style="font-weight: 700; color: #1e3a8a;">{{ data.costo_total }}</span>
        </div>
      </template>
    </Column>

    <!-- Columnas de Saldos -->
    <Column header="Saldo Caja" field="saldo_caja" sortable style="min-width: 9rem">
      <template #body="{ data }">
        <span style="font-weight: 700; color: #15803d;">
          {{ parseFloat(data.saldo_caja).toFixed(2) }}
        </span>
      </template>
    </Column>

    <Column header="Saldo Fracción" field="saldo_fraccion" sortable style="min-width: 11rem">
      <template #body="{ data }">
        <span style="font-weight: 700; color: #15803d;">
          {{ parseFloat(data.saldo_fraccion).toFixed(2) }}
        </span>
      </template>
    </Column>

    <!-- Columna de Saldo Valorizado -->
<Column field="saldo_valorizado" header="Saldo Valorizado" sortable style="min-width: 13rem">
  <template #body="{ data }">
    <Tag 
      :value="`S/ ${data.saldo_valorizado}`" 
      severity="success"
      style="font-weight: 700; font-size: 1.125rem; padding: 0.5rem 0.75rem;"
    />
  </template>
</Column>
    <!-- Columnas adicionales -->
    <Column field="precio_venta" header="Precio Venta" sortable style="min-width: 11rem">
      <template #body="{ data }">
        <div style="display: flex; align-items: center; gap: 0.25rem;">
          <span style="font-size: 0.75rem; color: #6b7280;">S/</span>
          <span style="font-weight: 600; color: #ea580c;">{{ data.precio_venta }}</span>
        </div>
      </template>
    </Column>

    <Column field="estado" header="Estado" sortable style="min-width: 8rem">
      <template #body="{ data }">
        <Tag 
          :value="data.estado === 1 ? 'ACTIVO' : 'INACTIVO'" 
          :severity="data.estado === 1 ? 'success' : 'danger'" 
        />
      </template>
    </Column>

  </DataTable>
</template>

<script setup>
import { ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useKardexValorizadoStore } from './kardexValorizadoStore';

const kardexValorizadoStore = useKardexValorizadoStore();

const dt = ref();
const selectedRecords = ref();
const filters = ref({
  'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const getCantidadStyle = (cantidad) => {
  const value = parseFloat(cantidad);
  let color = '';
  if (value > 0) color = '';
  if (value < 0) color = '';
  
  return {
    fontWeight: '600',
    color: color
  };
};
</script>