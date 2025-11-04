<template>
  <div>
    <!-- Toolbar de búsqueda y filtros -->
    <Toolbar class="mb-6">
      <template #start>
        <div class="flex items-center gap-2">
          <i class="pi pi-warehouse text-2xl text-blue-600"></i>
          <div>
            <h3 class="m-0 font-bold">Inventario de Sucursal</h3>
            <p class="text-sm mt-1" v-if="getSucursalNombre()">
              {{ getSucursalNombre() }}
            </p>
          </div>
        </div>
      </template>
      
      <template #center>
        <div class="flex gap-2">
          <Select v-model="subBranchSeleccionada" :options="subBranches" class="w-96"
            optionLabel="name" optionValue="id" placeholder="Seleccionar sucursal..."
            @change="onSubBranchSeleccionada">
            <template #option="{ option }">
              <div>
                <strong>{{ option.name }}</strong>
                <div class="text-sm">Código: {{ option.code }}</div>
              </div>
            </template>
          </Select>
          
          <SelectButton v-model="filtroStock" :options="filtrosStock" optionLabel="label" optionValue="value"
            @change="onFiltroChange" />
        </div>
      </template>
      
      <template #end>
        <div class="flex gap-2">
          <Button icon="pi pi-refresh" label="Actualizar" severity="secondary" @click="cargarInventario" />
        </div>
      </template>
    </Toolbar>

    <!-- Mensaje cuando no hay sucursal seleccionada -->
    <Message v-if="!subBranchSeleccionada" severity="info" :closable="false" class="mb-4">
      <div class="flex items-center gap-2">
        <i class="pi pi-info-circle text-xl"></i>
        <div>
          <strong>Seleccione una sucursal</strong> para ver el inventario disponible.
        </div>
      </div>
    </Message>

    <!-- Cards de resumen mejorado -->
    <div v-if="subBranchSeleccionada && inventarioStore.resumen" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <Card class="bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-500">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-600 font-semibold mb-1">Total Productos</p>
              <p class="text-3xl font-bold text-blue-700">{{ inventarioStore.resumen.total_productos }}</p>
            </div>
            <i class="pi pi-box text-4xl text-blue-300"></i>
          </div>
        </template>
      </Card>

      <Card class="bg-gradient-to-br from-green-50 to-green-100 border-l-4 border-green-500">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-green-600 font-semibold mb-1">Stock Disponible</p>
              <p class="text-3xl font-bold text-green-700">{{ inventarioStore.resumen.stock_disponible }}</p>
              <p class="text-xs text-green-500 mt-1">
                {{ calcularPorcentaje(inventarioStore.resumen.stock_disponible, inventarioStore.resumen.total_productos) }}%
              </p>
            </div>
            <i class="pi pi-check-circle text-4xl text-green-300"></i>
          </div>
        </template>
      </Card>

      <Card class="bg-gradient-to-br from-red-50 to-red-100 border-l-4 border-red-500">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-red-600 font-semibold mb-1">Stock Bajo</p>
              <p class="text-3xl font-bold text-red-700">{{ inventarioStore.resumen.stock_bajo }}</p>
              <p class="text-xs text-red-500 mt-1">
                {{ calcularPorcentaje(inventarioStore.resumen.stock_bajo, inventarioStore.resumen.total_productos) }}%
              </p>
            </div>
            <i class="pi pi-exclamation-triangle text-4xl text-red-300"></i>
          </div>
        </template>
      </Card>

      <Card class="bg-gradient-to-br from-orange-50 to-orange-100 border-l-4 border-orange-500">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-orange-600 font-semibold mb-1">Sin Stock</p>
              <p class="text-3xl font-bold text-orange-700">{{ inventarioStore.resumen.sin_stock }}</p>
              <p class="text-xs text-orange-500 mt-1">
                {{ calcularPorcentaje(inventarioStore.resumen.sin_stock, inventarioStore.resumen.total_productos) }}%
              </p>
            </div>
            <i class="pi pi-times-circle text-4xl text-orange-300"></i>
          </div>
        </template>
      </Card>

      <Card class="bg-gradient-to-br from-purple-50 to-purple-100 border-l-4 border-purple-500">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-purple-600 font-semibold mb-1">Stock Total</p>
              <p class="text-3xl font-bold text-purple-700">{{ formatearNumero(inventarioStore.resumen.stock_total) }}</p>
              <p class="text-xs text-purple-500 mt-1">
                Ocupación: {{ inventarioStore.resumen.porcentaje_ocupacion }}%
              </p>
            </div>
            <i class="pi pi-chart-bar text-4xl text-purple-300"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- DataTable -->
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="inventarioStore.inventarioData" 
      dataKey="id" 
      :lazy="true"
      :paginator="true" 
      :rows="inventarioStore.pagination.perPage" 
      :totalRecords="inventarioStore.pagination.total"
      :first="(inventarioStore.pagination.currentPage - 1) * inventarioStore.pagination.perPage"
      @page="onPage"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[10, 15, 25, 50]"
      :currentPageReportTemplate="`Mostrando ${inventarioStore.pagination.from || 0} a ${inventarioStore.pagination.to || 0} de ${inventarioStore.pagination.total} productos`"
      class="p-datatable-sm"
      :loading="inventarioStore.loading"
      stripedRows
      :globalFilterFields="['product_name', 'category_name']">
      
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <div>
            <h4 class="m-0">Inventario de Productos</h4>
            <div class="flex gap-2 mt-2">
              <Tag v-if="filtroStock !== 'todos'" :value="obtenerEtiquetaFiltro()" severity="info" />
              <Tag v-if="filters['global'].value" icon="pi pi-search" severity="secondary">
                Buscando: "{{ filters['global'].value }}"
              </Tag>
            </div>
          </div>
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Buscar producto o categoría..." 
              class="w-80" @input="onSearchChange" />
          </IconField>
        </div>
      </template>

      <template #empty>
        <div class="text-center py-8">
          <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
          <p class="text-gray-600">No hay productos en el inventario.</p>
          <p class="text-sm text-gray-500">Seleccione una sucursal para ver el inventario.</p>
        </div>
      </template>

      <Column selectionMode="multiple" style="width: 3rem" :exportable="false" frozen></Column>
      
      <Column field="product_name" header="Producto" sortable style="min-width: 18rem" frozen>
        <template #body="{ data }">
          <div class="flex items-center gap-3">
            <Avatar icon="pi pi-box" shape="circle" size="large" 
              :style="{ backgroundColor: getColorByCategory(data.category_name), color: '#fff' }" />
            <div>
              <div class="font-bold">{{ data.product_name }}</div>
            </div>
          </div>
        </template>
      </Column>

      <Column field="category_name" header="Categoría" sortable style="min-width: 11rem">
        <template #body="{ data }">
          <Tag :value="data.category_name" :style="{ backgroundColor: getColorByCategory(data.category_name), color: '#fff' }" />
        </template>
      </Column>

      <Column field="current_stock" header="Stock Actual" sortable style="min-width: 14rem">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Chip :label="data.current_stock.toString()" 
              :class="getStockClass(data.current_stock, data.min_stock, data.max_stock)" 
              class="font-bold text-lg px-3 py-1" />
            <div class="flex flex-col" style="width: 100px;">
              <ProgressBar :value="calcularPorcentajeStock(data.current_stock, data.max_stock)" 
                :showValue="false" 
                :style="{ height: '8px' }"
                :pt="{ value: { style: getProgressBarStyle(data.current_stock, data.min_stock) } }" />
              <span class="text-xs mt-1">
                {{ calcularPorcentajeStock(data.current_stock, data.max_stock).toFixed(0) }}%
              </span>
            </div>
          </div>
        </template>
      </Column>

      <Column field="min_stock" header="Stock Mínimo" sortable style="min-width: 10rem">
        <template #body="{ data }">
          <div class="text-center">
            <Chip :label="data.min_stock.toString()" class="bg-orange-100 text-orange-700 font-semibold" />
          </div>
        </template>
      </Column>

      <Column field="max_stock" header="Stock Máximo" sortable style="min-width: 10rem">
        <template #body="{ data }">
          <div class="text-center">
            <Chip :label="data.max_stock.toString()" class="bg-blue-100 text-blue-700 font-semibold" />
          </div>
        </template>
      </Column>

      <Column header="Estado Stock" sortable style="min-width: 14rem">
        <template #body="{ data }">
          <div v-if="data.is_out_of_stock" class="flex items-center gap-2">
            <Tag value="SIN STOCK" severity="danger" icon="pi pi-times-circle" />
          </div>
          <div v-else-if="data.is_low_stock" class="flex items-center gap-2">
            <Tag value="STOCK BAJO" severity="warn" icon="pi pi-exclamation-triangle" />
            <span class="text-xs text-orange-600">¡Reabastecer!</span>
          </div>
          <div v-else class="flex items-center gap-2">
            <Tag value="STOCK OK" severity="success" icon="pi pi-check-circle" />
            <span class="text-xs text-green-600">Disponible</span>
          </div>
        </template>
      </Column>

      <Column field="purchase_price" header="P. Compra" sortable style="min-width: 8rem">
        <template #body="{ data }">
          <div class="flex items-center gap-1">
            <span class="text-xs">S/</span>
            <span class="font-semibold">{{ parseFloat(data.purchase_price).toFixed(2) }}</span>
          </div>
        </template>
      </Column>

      <Column field="sale_price" header="P. Venta" sortable style="min-width: 8rem">
        <template #body="{ data }">
          <div class="flex items-center gap-1">
            <span class="text-xs">S/</span>
            <span class="font-semibold">{{ parseFloat(data.sale_price).toFixed(2) }}</span>
          </div>
        </template>
      </Column>
      <Column field="is_active" header="Estado" sortable style="min-width: 9rem">
        <template #body="{ data }">
          <Tag :value="data.is_active ? 'ACTIVO' : 'INACTIVO'" 
            :severity="data.is_active ? 'success' : 'danger'" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import ProgressBar from 'primevue/progressbar';
import Toolbar from 'primevue/toolbar';
import Message from 'primevue/message';
import axios from 'axios';
import { useInventarioStore } from './inventarioStore';

const inventarioStore = useInventarioStore();

const dt = ref();
const selectedProducts = ref();
const subBranchSeleccionada = ref(null);
const subBranches = ref([]);
const searchTimeout = ref(null);

const filtroStock = ref('todos');
const filtrosStock = ref([
  { label: 'Todos', value: 'todos' },
  { label: 'Con Stock', value: 'con_stock' },
  { label: 'Stock Bajo', value: 'stock_bajo' },
  { label: 'Sin Stock', value: 'sin_stock' }
]);

const filters = ref({
  'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Función para obtener el nombre de la sucursal
const getSucursalNombre = () => {
  if (!subBranchSeleccionada.value || !subBranches.value.length) return '';
  const sucursal = subBranches.value.find(s => s.id === subBranchSeleccionada.value);
  return sucursal ? sucursal.name : '';
};

// Función para obtener etiqueta del filtro activo
const obtenerEtiquetaFiltro = () => {
  const filtro = filtrosStock.value.find(f => f.value === filtroStock.value);
  return filtro ? `Filtro: ${filtro.label}` : '';
};

// Función para formatear números
const formatearNumero = (numero) => {
  return new Intl.NumberFormat('es-PE').format(numero || 0);
};

// Función para calcular porcentaje
const calcularPorcentaje = (parte, total) => {
  if (!total || total === 0) return 0;
  return ((parte / total) * 100).toFixed(1);
};

// Función para calcular margen de ganancia
const calcularMargen = (precioCompra, precioVenta) => {
  const compra = parseFloat(precioCompra);
  const venta = parseFloat(precioVenta);
  if (compra === 0) return 0;
  const margen = ((venta - compra) / compra) * 100;
  return margen.toFixed(1);
};

// Función para calcular ganancia
const calcularGanancia = (precioCompra, precioVenta) => {
  const ganancia = parseFloat(precioVenta) - parseFloat(precioCompra);
  return ganancia.toFixed(2);
};

// Función para obtener clase del margen
const getMargenClass = (precioCompra, precioVenta) => {
  const margen = calcularMargen(precioCompra, precioVenta);
  if (margen >= 50) return 'text-green-600';
  if (margen >= 25) return 'text-blue-600';
  if (margen >= 10) return 'text-orange-600';
  return 'text-red-600';
};

// Función para manejar el cambio de página
const onPage = (event) => {
  console.log("Cambio de página:", event);
  const page = event.page + 1;
  const perPage = event.rows;
  
  inventarioStore.pagination.perPage = perPage;
  cargarInventario(page);
};

// Función para cargar sub-branches
const cargarSubBranches = async () => {
  try {
    const response = await axios.get("/sub-branches/search");
    subBranches.value = response.data.data;
    
    if (subBranches.value.length > 0) {
      subBranchSeleccionada.value = subBranches.value[0].id;
      cargarInventario();
    }
  } catch (error) {
    console.error('Error al cargar sub-branches:', error);
  }
};

// Función para cargar inventario con paginación
const cargarInventario = async (page = 1) => {
  if (!subBranchSeleccionada.value) {
    console.log("No hay sucursal seleccionada");
    return;
  }

  const params = {
    per_page: inventarioStore.pagination.perPage
  };

  if (filters.value.global.value) {
    params.search = filters.value.global.value;
  }

  if (filtroStock.value !== 'todos') {
    if (filtroStock.value === 'stock_bajo') {
      params.is_low_stock = true;
    } else if (filtroStock.value === 'sin_stock') {
      params.no_stock = true;
    } else if (filtroStock.value === 'con_stock') {
      params.has_stock = true;
    }
  }

  await inventarioStore.fetchInventario(subBranchSeleccionada.value, params, page);
};

const onSubBranchSeleccionada = () => {
  console.log("Sucursal seleccionada:", subBranchSeleccionada.value);
  cargarInventario(1);
};

const onFiltroChange = () => {
  console.log("Filtro de stock:", filtroStock.value);
  cargarInventario(1);
};

const onSearchChange = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  
  searchTimeout.value = setTimeout(() => {
    cargarInventario(1);
  }, 500);
};

const getColorByCategory = (category) => {
  const colors = {
    'Bebidas': '#3b82f6',
    'Snacks': '#f59e0b',
    'Limpieza': '#10b981',
    'Otros': '#6366f1',
    'Lácteos': '#ec4899',
    'Panadería': '#f97316'
  };
  return colors[category] || '#6b7280';
};

const getStockClass = (current, min, max) => {
  if (current === 0) return 'bg-red-100 text-red-700 border-2 border-red-300';
  if (current <= min) return 'bg-orange-100 text-orange-700 border-2 border-orange-300';
  if (current >= max) return 'bg-blue-100 text-blue-700 border-2 border-blue-300';
  return 'bg-green-100 text-green-700 border-2 border-green-300';
};

const calcularPorcentajeStock = (current, max) => {
  if (max === 0) return 0;
  return Math.min((current / max) * 100, 100);
};

const getProgressBarStyle = (current, min) => {
  if (current === 0) return { backgroundColor: '#ef4444' };
  if (current <= min) return { backgroundColor: '#f97316' };
  return { backgroundColor: '#10b981' };
};

const exportData = () => {
  dt.value.exportCSV();
};

onMounted(() => {
  cargarSubBranches();
});
</script>