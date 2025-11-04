<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
    visible: Boolean,
    productoId: String
});
const emit = defineEmits(['update:visible', 'updated']);

const toast = useToast();
const serverErrors = ref({});
const submitted = ref(false);
const loading = ref(false);

const dialogVisible = ref(props.visible);
watch(() => props.visible, (val) => dialogVisible.value = val);
watch(dialogVisible, (val) => emit('update:visible', val));

const producto = ref({
    name: '',
    category_id: null,
    is_active: false,
    purchase_price: null,
    sale_price: null,
    unit_type: null,
    description: '',
    is_fractionable: false,
    fraction_units: null,
    min_stock: null,
    max_stock: null,
});

const categorias = ref([]);

const tiposUnidad = ref([
    { label: 'Unidad', value: 'piece' },
    { label: 'Botella', value: 'bottle' },
    { label: 'Paquete', value: 'pack' },
    { label: 'Kilogramo', value: 'kg' },
    { label: 'Litro', value: 'liter' },
]);

watch(() => props.visible, async (val) => {
    if (val && props.productoId) {
        await fetchProducto();
        await fetchCategorias();
    }
});

const fetchProducto = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/producto/${props.productoId}`);
        const p = data.product;

        producto.value = {
            name: p.name || p.nombre,
            category_id: p.category_id || p.categoria_id,
            is_active: p.is_active !== undefined ? p.is_active : p.estado,
            purchase_price: parseFloat(p.purchase_price || p.precio_compra),
            sale_price: parseFloat(p.sale_price || p.precio_venta),
            unit_type: p.unit_type || p.unidad,
            description: p.description || p.descripcion || '',
            is_fractionable: p.is_fractionable || false,
            fraction_units: p.fraction_units || null,
            min_stock: p.min_stock || 0,
            max_stock: p.max_stock || 0
        };
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el producto', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const fetchCategorias = async () => {
    try {
        const { data } = await axios.get('/categoria');
        categorias.value = data.data.map(c => ({ label: c.nombre, value: c.id }));
    } catch {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No se pudieron cargar las categorías' });
    }
};

// Watch para limpiar fraction_units cuando is_fractionable es false
watch(() => producto.value.is_fractionable, (newValue) => {
    if (!newValue) {
        producto.value.fraction_units = null;
    }
});

const updateProducto = async () => {
    submitted.value = true;
    serverErrors.value = {};

    // Preparar datos para envío
    const dataToSend = { ...producto.value };
    
    // Si no es fraccionable, asegurar que fraction_units sea null
    if (!dataToSend.is_fractionable) {
        dataToSend.fraction_units = null;
    }

    try {
        await axios.put(`/producto/${props.productoId}`, dataToSend);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Producto actualizado correctamente',
            life: 3000
        });

        dialogVisible.value = false;
        emit('updated');
    } catch (error) {
        if (error.response?.data?.errors) {
            serverErrors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: error.response.data.message || 'Revisa los campos.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar el producto',
                life: 3000
            });
        }
    }
};
</script>

<template>
    <Dialog v-model:visible="dialogVisible" header="Editar Producto" modal :closable="true" :closeOnEscape="true"
        :style="{ width: '900px' }">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <!-- Nombre y Estado en una sola fila -->
                <div class="col-span-12 grid grid-cols-12 gap-4">
                    <!-- Nombre -->
                    <div class="col-span-10">
                        <label class="block font-bold mb-2">Nombre <span class="text-red-500">*</span></label>
                        <InputText v-model="producto.name" maxlength="100" fluid
                            :class="{ 'p-invalid': submitted && serverErrors.name }" />
                        <small v-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
                    </div>
                    <!-- Estado -->
                    <div class="col-span-2">
                        <label class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="producto.is_active" :binary="true" />
                            <Tag :value="producto.is_active ? 'Activo' : 'Inactivo'"
                                :severity="producto.is_active ? 'success' : 'danger'" />
                        </div>
                        <small v-if="serverErrors.is_active" class="text-red-500">{{ serverErrors.is_active[0] }}</small>
                    </div>
                </div>

                <!-- Precio Compra -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Precio Compra <span class="text-red-500">*</span></label>
                    <InputNumber v-model="producto.purchase_price" mode="currency" currency="PEN" locale="es-PE"
                        :minFractionDigits="2" fluid
                        :class="{ 'p-invalid': submitted && serverErrors.purchase_price }" />
                    <small v-if="serverErrors.purchase_price" class="text-red-500">{{ serverErrors.purchase_price[0] }}</small>
                </div>

                <!-- Precio Venta -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Precio Venta <span class="text-red-500">*</span></label>
                    <InputNumber v-model="producto.sale_price" mode="currency" currency="PEN" locale="es-PE"
                        :minFractionDigits="2" fluid
                        :class="{ 'p-invalid': submitted && serverErrors.sale_price }" />
                    <small v-if="serverErrors.sale_price" class="text-red-500">{{ serverErrors.sale_price[0] }}</small>
                </div>

                <!-- Categoría -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Categoría <span class="text-red-500">*</span></label>
                    <Select v-model="producto.category_id" :options="categorias" optionLabel="label" optionValue="value"
                        placeholder="Seleccione una categoría" fluid
                        :class="{ 'p-invalid': submitted && serverErrors.category_id }" />
                    <small v-if="serverErrors.category_id" class="text-red-500">{{ serverErrors.category_id[0] }}</small>
                </div>

                <!-- Tipo de Unidad -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Tipo de Unidad <span class="text-red-500">*</span></label>
                    <Select v-model="producto.unit_type" :options="tiposUnidad" optionLabel="label" optionValue="value"
                        placeholder="Seleccione tipo de unidad" fluid
                        :class="{ 'p-invalid': submitted && serverErrors.unit_type }" />
                    <small v-if="serverErrors.unit_type" class="text-red-500">{{ serverErrors.unit_type[0] }}</small>
                </div>

                <!-- Es Fraccionable y Unidades de Fracción -->
                <div class="col-span-12 grid grid-cols-12 gap-4">
                    <!-- Es Fraccionable -->
                    <div class="col-span-6">
                        <label class="block font-bold mb-2">¿Es Fraccionable?</label>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="producto.is_fractionable" :binary="true" />
                            <Tag :value="producto.is_fractionable ? 'Sí' : 'No'" :severity="producto.is_fractionable ? 'info' : 'secondary'" />
                        </div>
                        <small class="text-gray-600">Indica si el producto puede venderse en fracciones</small>
                        <small v-if="serverErrors.is_fractionable" class="text-red-500 block">{{ serverErrors.is_fractionable[0] }}</small>
                    </div>

                    <!-- Unidades de Fracción -->
                    <div class="col-span-6" v-show="producto.is_fractionable">
                        <label class="block font-bold mb-2">Unidades por Fracción <span class="text-red-500">*</span></label>
                        <InputNumber 
                            v-model="producto.fraction_units" 
                            fluid 
                            :min="1" 
                            :step="1" 
                            placeholder="Ej: 12 (para docena)"
                            :class="{ 'p-invalid': submitted && serverErrors.fraction_units }" 
                        />
                        <small class="text-gray-600">Número de unidades que componen una fracción completa</small>
                        <small v-if="submitted && producto.is_fractionable && (!producto.fraction_units || producto.fraction_units < 1)" class="text-red-500 block">Las unidades de fracción son obligatorias cuando el producto es fraccionable.</small>
                        <small v-else-if="serverErrors.fraction_units" class="text-red-500 block">{{ serverErrors.fraction_units[0] }}</small>
                    </div>
                </div>

                <!-- Stock Mínimo y Máximo -->
                <div class="col-span-12 grid grid-cols-12 gap-4">
                    <!-- Stock Mínimo -->
                    <div class="col-span-6">
                        <label class="block font-bold mb-2">Stock Mínimo</label>
                        <InputNumber 
                            v-model="producto.min_stock" 
                            fluid 
                            :min="0" 
                            :step="1" 
                            placeholder="Stock mínimo permitido"
                            :class="{ 'p-invalid': submitted && serverErrors.min_stock }" 
                        />
                        <small class="text-gray-600">Cantidad mínima en inventario</small>
                        <small v-if="serverErrors.min_stock" class="text-red-500 block">{{ serverErrors.min_stock[0] }}</small>
                    </div>

                    <!-- Stock Máximo -->
                    <div class="col-span-6">
                        <label class="block font-bold mb-2">Stock Máximo</label>
                        <InputNumber 
                            v-model="producto.max_stock" 
                            fluid 
                            :min="0" 
                            :step="1" 
                            placeholder="Stock máximo permitido"
                            :class="{ 'p-invalid': submitted && serverErrors.max_stock }" 
                        />
                        <small class="text-gray-600">Cantidad máxima en inventario</small>
                        <small v-if="serverErrors.max_stock" class="text-red-500 block">{{ serverErrors.max_stock[0] }}</small>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="col-span-12">
                    <label class="block font-bold mb-2">Descripción</label>
                    <Textarea v-model="producto.description" fluid rows="3" maxlength="1000" 
                        placeholder="Descripción del producto (opcional)"
                        :class="{ 'p-invalid': submitted && serverErrors.description }" />
                    <small v-if="serverErrors.description" class="text-red-500">{{ serverErrors.description[0] }}</small>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="updateProducto" :loading="loading" severity="contrast"/>
        </template>
    </Dialog>
</template>