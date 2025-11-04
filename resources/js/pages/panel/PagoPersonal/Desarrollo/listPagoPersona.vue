<template>
  <DataTable ref="dt" v-model:selection="selectedProducts" :value="pagosStore.pagos.value" dataKey="id"
    :loading="pagosStore.loading.value" :paginator="true" :rows="15" :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[10, 15, 25, 50]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} pagos" class="p-datatable-sm">
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0 font-semibold text-xl">Listado de Pagos</h4>
        <div class="flex gap-2">
          <Select
            v-model="sucursalSeleccionadaLocal"
            :options="sucursales"
            optionLabel="name"
            optionValue="id"
            placeholder="Todas las sucursales"
            :loading="loadingSucursales"
            class="w-64"
            showClear
            @change="onSucursalChange"
          >
            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-semibold">{{ slotProps.option.name }}</span>
                <span class="text-sm text-gray-600">{{ slotProps.option.code }}</span>
              </div>
            </template>
          </Select>
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Buscar..." />
          </IconField>
          <Button icon="pi pi-refresh" @click="loadPagos" outlined severity="contrast"
            :loading="pagosStore.loading.value" v-tooltip.top="'Actualizar'" />
        </div>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

    <Column field="empleado" header="Empleado" sortable style="min-width: 200px">
      <template #body="slotProps">
        <div class="flex flex-col">
          <span class="font-semibold">{{ slotProps.data.empleado }}</span>
        </div>
      </template>
    </Column>

    <Column field="monto_formateado" header="Monto" sortable style="min-width: 120px">
      <template #body="slotProps">
        <span class="font-semibold text-green-600">{{ slotProps.data.monto_formateado }}</span>
      </template>
    </Column>

    <Column field="fecha_pago_formateada" header="Fecha Pago" sortable style="min-width: 120px"></Column>
    <Column field="periodo" header="Periodo" sortable style="min-width: 120px"></Column>

    <Column field="tipo_pago" header="Tipo" sortable style="min-width: 120px">
      <template #body="slotProps">
        <Tag :value="slotProps.data.tipo_pago" severity="info" />
      </template>
    </Column>

    <Column field="metodo_pago" header="Método" sortable style="min-width: 120px">
      <template #body="slotProps">
        <Tag :value="slotProps.data.metodo_pago" />
      </template>
    </Column>

    <Column field="estado" header="Estado" sortable style="min-width: 100px">
      <template #body="slotProps">
        <Tag :value="slotProps.data.estado" :severity="getEstadoSeverity(slotProps.data.estado)" />
      </template>
    </Column>

    <Column header="Comprobante" style="min-width: 120px">
      <template #body="slotProps">
        <div v-if="slotProps.data.tiene_comprobante" class="flex gap-2">
          <Button icon="pi pi-eye" rounded text severity="info" size="small" @click="verComprobante(slotProps.data)"
            v-tooltip.top="'Ver comprobante'" />
          <Button icon="pi pi-download" rounded text severity="secondary" size="small"
            @click="descargarComprobante(slotProps.data)" v-tooltip.top="'Descargar'" />
        </div>
        <span v-else class="text-gray-400 text-sm">Sin comprobante</span>
      </template>
    </Column>

    <Column :exportable="false" style="min-width: 120px" header="Acciones">
      <template #body="slotProps">
        <div class="flex gap-2">
          <Button icon="pi pi-pencil" outlined rounded severity="info" size="small" @click="editPago(slotProps.data)"
            v-tooltip.top="'Editar'" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
            @click="confirmDeletePago(slotProps.data)" v-tooltip.top="'Eliminar'" />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-8">
        <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
        <p class="">No se encontraron pagos registrados.</p>
      </div>
    </template>
  </DataTable>
  <UpdatePagoPersonal v-model:visible="editDialog" :pago="pagoSeleccionado" @updated="onPagoUpdated" />

  <!-- Diálogo de Eliminación -->
  <DeletePagoPersonal v-model:visible="deleteDialog" :pago="pagoSeleccionado" @deleted="onPagoDeleted" />

  <Dialog v-model:visible="comprobanteDialog" :style="{ width: '800px' }" header="Comprobante de Pago" :modal="true">
    <div v-if="comprobanteSeleccionado">
      <div v-if="comprobanteSeleccionado.tipo_comprobante === 'imagen'" class="text-center">
        <img :src="comprobanteSeleccionado.comprobante_url" alt="Comprobante"
          class="max-w-full h-auto rounded-lg shadow-lg" />
      </div>

      <div v-else-if="comprobanteSeleccionado.tipo_comprobante === 'pdf'" class="h-[600px]">
        <iframe :src="comprobanteSeleccionado.comprobante_url" class="w-full h-full border-0 rounded-lg"></iframe>
      </div>
    </div>

    <template #footer>
      <Button label="Descargar" icon="pi pi-download" @click="descargarComprobante(comprobanteSeleccionado)" outlined />
      <Button label="Cerrar" icon="pi pi-times" @click="comprobanteDialog = false" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { usePagosPersonal, type PagoPersonal } from './usePagosPersonal';
import UpdatePagoPersonal from './updatePagoPersonal.vue';
import DeletePagoPersonal from './deletePagoPersonal.vue';
import axios from 'axios';

// Props y emits para v-model
const props = defineProps<{
  sucursalSeleccionada?: string | null;
  userSubBranchId?: string | null;
}>();

const emit = defineEmits(['update:sucursalSeleccionada']);

const toast = useToast();
const confirm = useConfirm();
const pagosStore = usePagosPersonal();

const dt = ref();
const selectedProducts = ref<PagoPersonal[]>([]);
const comprobanteDialog = ref(false);
const comprobanteSeleccionado = ref<PagoPersonal | null>(null);
const editDialog = ref(false);
const deleteDialog = ref(false);
const pagoSeleccionado = ref<PagoPersonal | null>(null);
const sucursales = ref<any[]>([]);
const loadingSucursales = ref(false);
const isFirstLoad = ref(true);

// Computed para v-model
const sucursalSeleccionadaLocal = computed({
  get: () => props.sucursalSeleccionada,
  set: (value) => emit('update:sucursalSeleccionada', value)
});

const filters = ref({
  'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const loadSucursales = async () => {
  loadingSucursales.value = true;
  try {
    const response = await axios.get('/sub-branches/search');
    sucursales.value = response.data.data || [];
    
    // Si no hay sucursal seleccionada y viene la del usuario, establecerla
    if (!sucursalSeleccionadaLocal.value && props.userSubBranchId) {
      const sucursalExiste = sucursales.value.find(s => s.id === props.userSubBranchId);
      if (sucursalExiste) {
        emit('update:sucursalSeleccionada', props.userSubBranchId);
      }
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las sucursales', life: 3000 });
  } finally {
    loadingSucursales.value = false;
  }
};

const onSucursalChange = () => {
  isFirstLoad.value = false;
  loadPagos();
};

const loadPagos = async () => {
  try {
    let params = {};
    
    // Si hay una sucursal seleccionada, siempre enviarla excepto en la primera carga
    // cuando coincide con la del usuario
    if (sucursalSeleccionadaLocal.value) {
      // Si es la primera carga y es la sucursal del usuario, no enviar parámetro
      if (isFirstLoad.value && sucursalSeleccionadaLocal.value === props.userSubBranchId) {
        params = {};
      } else {
        // En cualquier otro caso, enviar el parámetro
        params = { sub_branch_id: sucursalSeleccionadaLocal.value };
      }
    }
    
    await pagosStore.fetchPagos(params);
    
    // Marcar que ya no es la primera carga
    if (isFirstLoad.value) {
      isFirstLoad.value = false;
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los pagos', life: 3000 });
  }
};

const getEstadoSeverity = (estado: string) => {
  const severities: Record<string, string> = {
    'pagado': 'success',
    'pendiente': 'warn',
    'anulado': 'danger'
  };
  return severities[estado] || 'info';
};

const verComprobante = (pago: PagoPersonal) => {
  comprobanteSeleccionado.value = pago;
  comprobanteDialog.value = true;
};

const descargarComprobante = (pago: PagoPersonal) => {
  if (pago.comprobante_url) {
    window.open(pago.comprobante_url, '_blank');
  }
};

const editPago = (pago: PagoPersonal) => {
  pagoSeleccionado.value = pago;
  editDialog.value = true;
};

const confirmDeletePago = (pago: PagoPersonal) => {
  pagoSeleccionado.value = pago;
  deleteDialog.value = true;
};

const onPagoUpdated = () => {
  loadPagos();
};

const onPagoDeleted = () => {
  loadPagos();
};

// Watch para detectar cambios en sucursalSeleccionada desde el padre
watch(() => props.sucursalSeleccionada, (newVal, oldVal) => {
  // Solo recargar si cambió y no es undefined
  if (newVal !== oldVal && newVal !== undefined) {
    loadPagos();
  }
});

onMounted(async () => {
  await loadSucursales();
  await loadPagos();
});

defineExpose({
  loadPagos
});
</script>