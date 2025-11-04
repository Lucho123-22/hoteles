<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DeleteProducto from './DeleteProductos.vue';
import UpdateProducto from './UpdateProductos.vue';
import { debounce } from 'lodash';
import Tag from 'primevue/tag';

const productos = ref([]);
const loading = ref(false);
const globalFilterValue = ref('');
const deleteProductoDialog = ref(false);
const updateProductoDialog = ref(false);
const selectedProductoId = ref(null);
const producto = ref({});
const currentPage = ref(1);
const selectedEstadoProducto = ref(null);

const pagination = ref({
    currentPage: 1,
    perPage: 15,
    total: 0
});

const estadoProductoOptions = ref([
    { name: 'TODOS', value: '' },
    { name: 'ACTIVOS', value: 1 },
    { name: 'INACTIVOS', value: 0 },
]);

const loadProductos = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.value.currentPage,
            per_page: pagination.value.perPage,
            search: globalFilterValue.value,
            state: selectedEstadoProducto.value?.value ?? '',
        };
        const response = await axios.get('/producto', { params });
        productos.value = response.data.data;
        pagination.value.currentPage = response.data.meta.current_page;
        pagination.value.total = response.data.meta.total;
    } catch (error) {
        console.error('Error al cargar productos:', error);
    } finally {
        loading.value = false;
    }
};

const props = defineProps({
    refresh: {
        type: Number,
        required: true
    }
});

watch(() => props.refresh, loadProductos);
watch(() => selectedEstadoProducto.value, () => {
    currentPage.value = 1;
    loadProductos();
});

const onPage = (event) => {
    pagination.value.currentPage = event.page + 1;
    pagination.value.perPage = event.rows;
    loadProductos();
};

const onGlobalSearch = debounce(() => {
    pagination.value.currentPage = 1;
    loadProductos();
}, 500);

const editarProducto = (prod) => {
    selectedProductoId.value = prod.id;
    updateProductoDialog.value = true;
};

const confirmarDeleteProducto = (prod) => {
    producto.value = prod;
    deleteProductoDialog.value = true;
};

function handleProductoUpdated() {
    loadProductos();
}

function handleProductoDeleted() {
    loadProductos();
}

const getSeverity = (value) => {
    return value ? 'success' : 'danger';
};

onMounted(loadProductos);
</script>

<template>
    <DataTable
        :value="productos"
        :paginator="true"
        :rows="pagination.perPage"
        :totalRecords="pagination.total"
        :loading="loading"
        :lazy="true"
        @page="onPage"
        dataKey="id"
        scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} productos"
        class="p-datatable-sm"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">PRODUCTOS</h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Select
                        v-model="selectedEstadoProducto"
                        :options="estadoProductoOptions"
                        optionLabel="name"
                        placeholder="Estado"
                    />
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadProductos" severity="contrast"/>
                </div>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 1rem" />
        <Column field="codigo" header="Codigo" sortable style="min-width: 5rem" />
        <Column field="nombre" header="Nombre" sortable style="min-width: 20rem" />
        <Column 
            field="descripcion" 
            header="Descripcion" 
            sortable 
            style="min-width: 20rem"
        >
            <template #body="slotProps">
            <div class="truncate max-w-xs" :title="slotProps.data.descripcion">
                {{ slotProps.data.descripcion }}
            </div>
            </template>
        </Column>
        <Column field="precio_compra" header="Precio Compra" sortable style="min-width: 10rem" />
        <Column field="precio_venta" header="Precio Venta" sortable style="min-width: 10rem" />
        <Column field="unidad" header="Unidad" sortable style="min-width: 5rem" />
        <Column field="Categoria_nombre" header="Categoría" sortable style="min-width: 10rem" />
        <Column field="is_fractionable" header="Fraccionable" sortable style="min-width: 8rem">
            <template #body="{ data }">
                <Tag :value="data.is_fractionable ? 'Sí' : 'No'" :severity="data.is_fractionable ? 'success' : 'danger'" />
            </template>
        </Column>

        <Column field="fraction_units" header="Unidades por paquete" sortable style="min-width: 13rem">
            <template #body="{ data }">
                <div>{{ data.is_fractionable ? data.fraction_units : '-' }}</div>
            </template>
        </Column>
        <Column field="creacion" header="Creación" sortable style="min-width: 13rem" />
        <Column field="actualizacion" header="Actualización" sortable style="min-width: 13rem" />
        <Column field="estado" header="Estado" sortable>
            <template #body="{ data }">
                <Tag :value="data.estado ? 'Activo' : 'Inactivo'" :severity="getSeverity(data.estado)" />
            </template>
        </Column>
         <Column field="accions" header="" :exportable="false" style="min-width: 8rem">
            <template #body="{ data }">
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editarProducto(data)" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmarDeleteProducto(data)" />
            </template>
        </Column>
    </DataTable>

    <DeleteProducto
        v-model:visible="deleteProductoDialog"
        :producto="producto"
        @deleted="handleProductoDeleted"
    />
    <UpdateProducto
        v-model:visible="updateProductoDialog"
        :productoId="selectedProductoId"
        @updated="handleProductoUpdated"
    />
</template>
