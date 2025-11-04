<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" :paginator="true" :rows="10"
        :filters="filters" :loading="loading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} productos" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Detalle del Movimiento</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>
        <template #empty>
            <div class="text-center p-4">
                No hay productos en este movimiento
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="tipo" header="Tipo" sortable style="min-width: 5rem"></Column>
        <Column field="cantidades" header="Cantidad" sortable style="min-width: 8rem"></Column>
        <Column field="product.name" header="Producto" sortable style="min-width: 30rem">
            <template #body="slotProps">
                <strong>{{ slotProps.data.producto.nombre }}</strong>
            </template>
        </Column>
        <Column field="fecha_vencimiento" header="Fecha Vencimiento" sortable style="min-width: 12rem"></Column>
        <Column field="precio_unitario" header="Precio Unitario" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span>S/ {{ parseFloat(slotProps.data.precio_unitario).toFixed(2) }}</span>
            </template>
        </Column>
        <Column field="precio_total" header="Precio Total" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span class="font-bold text-green-600">S/ {{ parseFloat(slotProps.data.precio_total).toFixed(2)
                    }}</span>
            </template>
        </Column>
        <Column header="">
            <template #body="slotProps">
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editProduct(slotProps.data)"
                    severity="info" />
                <Button icon="pi pi-trash" outlined rounded severity="danger"
                    @click="confirmDeleteProduct(slotProps.data)" />
            </template>
        </Column>
        <ColumnGroup type="footer">
            <Row>
                <Column footer="" :colspan="5" />
                <Column footer="Subtotal:" footerStyle="text-align: right; font-weight: bold; padding-right: 1rem;" />
                <Column :footer="`S/ ${subtotal.toFixed(2)}`" footerStyle="font-weight: bold; text-align: left;" />
                <Column footer="" />
            </Row>
            <Row>
                <Column footer="" :colspan="5" />
                <Column footer="IGV (18%):" footerStyle="text-align: right; font-weight: bold; padding-right: 1rem;" />
                <Column :footer="`S/ ${igvTotal.toFixed(2)}`"
                    footerStyle="font-weight: bold; color: #3B82F6; text-align: left;" />
                <Column footer="" />
            </Row>
            <Row>
                <Column footer="" :colspan="5" />
                <Column footer="Total:"
                    footerStyle="text-align: right; font-weight: bold; padding-right: 1rem; solid #e5e7eb; padding-top: 0.5rem;" />
                <Column :footer="`S/ ${totalGeneral.toFixed(2)}`"
                    footerStyle="font-weight: bold; color: #10B981; text-align: left; solid #e5e7eb; padding-top: 0.5rem;" />
                <Column footer="" />
            </Row>
        </ColumnGroup>
    </DataTable>

    <UpdateDetailMovement v-model:visible="showEditDialog" :detail="selectedDetail" @updated="handleUpdated" />
    <DeleteDetailMovement v-model:visible="showDeleteDialog" :detail="selectedDetail" @deleted="handleDeleted" />
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import axios from 'axios';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';

// Importar los modales
import UpdateDetailMovement from './updateDetailMovement.vue';
import DeleteDetailMovement from './deleteDetailMovement.vue';

const toast = useToast();

const props = defineProps<{
    movement: any
}>();

const dt = ref();
const products = ref([]);
const selectedProducts = ref();
const loading = ref(false);
const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Estados para los modales
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedDetail = ref(null);

const totalUnidades = computed(() => {
    return products.value.reduce((sum, product) => {
        return sum + (product.boxes * product.units_per_box);
    }, 0);
});

const subtotal = computed(() => {
    return products.value.reduce((sum, product) => {
        return sum + parseFloat(product.precio_total);
    }, 0);
});

const calcularIGV = (monto: number) => {
    return parseFloat(monto) * 0.18;
};

const igvTotal = computed(() => {
    return products.value.reduce((sum, product) => {
        return sum + calcularIGV(product.precio_total);
    }, 0);
});

const totalGeneral = computed(() => {
    return subtotal.value + igvTotal.value;
});

const loadMovementDetails = async () => {
    if (!props.movement?.data?.id) {
        console.error('No se encontró el ID del movimiento');
        return;
    }

    loading.value = true;

    try {
        const response = await axios.get(`/movement-detail/${props.movement.data.id}/details`);
        products.value = response.data.data;
        console.log('Detalles cargados:', products.value);
    } catch (error) {
        console.error('Error al cargar detalles:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los detalles del movimiento',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const editProduct = (product: any) => {
    selectedDetail.value = product;
    showEditDialog.value = true;
};

const confirmDeleteProduct = (product: any) => {
    selectedDetail.value = product;
    showDeleteDialog.value = true;
};

const handleUpdated = () => {
    loadMovementDetails();
    toast.add({
        severity: 'success',
        summary: 'Actualizado',
        detail: 'Los detalles se han actualizado correctamente',
        life: 3000
    });
};

const handleDeleted = () => {
    loadMovementDetails();
    toast.add({
        severity: 'success',
        summary: 'Eliminado',
        detail: 'El detalle se ha eliminado correctamente',
        life: 3000
    });
};

const reloadDetails = () => {
    loadMovementDetails();
};

defineExpose({
    reloadDetails
});

onMounted(() => {
    loadMovementDetails();
});

watch(() => props.movement, () => {
    loadMovementDetails();
}, { deep: true });
</script>