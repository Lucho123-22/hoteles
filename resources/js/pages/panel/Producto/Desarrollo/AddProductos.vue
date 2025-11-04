<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo producto" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="productoDialog" :style="{ width: '900px' }" header="Registro de productos" :modal="true">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <!-- Nombre y Estado en una sola fila -->
                <div class="col-span-12 grid grid-cols-12 gap-4">
                    <!-- Nombre -->
                    <div class="col-span-10">
                        <label class="block font-bold mb-2">Nombre <span class="text-red-500">*</span></label>
                        <InputText v-model.trim="producto.name" fluid maxlength="100" />
                        <small v-if="submitted && !producto.name" class="text-red-500">El nombre es obligatorio.</small>
                        <small v-else-if="submitted && producto.name.length < 2" class="text-red-500">El nombre debe tener al menos 2 caracteres.</small>
                        <small v-else-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
                    </div>
                    <!-- Estado -->
                    <div class="col-span-2">
                        <label class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="producto.is_active" :binary="true" />
                            <Tag :value="producto.is_active ? 'Activo' : 'Inactivo'" :severity="producto.is_active ? 'success' : 'danger'" />
                        </div>
                        <small v-if="submitted && producto.is_active === null" class="text-red-500">El estado es obligatorio.</small>
                        <small v-else-if="serverErrors.is_active" class="text-red-500">{{ serverErrors.is_active[0] }}</small>
                    </div>
                </div>
                
                <!-- Precio Compra -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Precio Compra <span class="text-red-500">*</span></label>
                    <InputText v-model.number="producto.purchase_price" type="number" fluid min="0" step="0.01" />
                    <small v-if="submitted && (producto.purchase_price === null || producto.purchase_price === '')" class="text-red-500">El precio de compra es obligatorio.</small>
                    <small v-else-if="serverErrors.purchase_price" class="text-red-500">{{ serverErrors.purchase_price[0] }}</small>
                </div>

                <!-- Precio Venta -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Precio Venta <span class="text-red-500">*</span></label>
                    <InputText v-model.number="producto.sale_price" fluid type="number" min="0" step="0.01" />
                    <small v-if="submitted && (producto.sale_price === null || producto.sale_price === '')" class="text-red-500">El precio de venta es obligatorio.</small>
                    <small v-else-if="serverErrors.sale_price" class="text-red-500">{{ serverErrors.sale_price[0] }}</small>
                </div>

                <!-- Categoría -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Categoría <span class="text-red-500">*</span></label>
                    <Select v-model="producto.category_id" :options="categorias" fluid optionLabel="label" optionValue="value" placeholder="Seleccione categoría" />
                    <small v-if="submitted && !producto.category_id" class="text-red-500">La categoría es obligatoria.</small>
                    <small v-else-if="serverErrors.category_id" class="text-red-500">{{ serverErrors.category_id[0] }}</small>
                </div>

                <!-- Tipo de Unidad -->
                <div class="col-span-6">
                    <label class="block font-bold mb-2">Tipo de Unidad <span class="text-red-500">*</span></label>
                    <Select v-model="producto.unit_type" :options="tiposUnidad" fluid optionLabel="label" optionValue="value" placeholder="Seleccione tipo de unidad" />
                    <small v-if="submitted && !producto.unit_type" class="text-red-500">El tipo de unidad es obligatorio.</small>
                    <small v-else-if="serverErrors.unit_type" class="text-red-500">{{ serverErrors.unit_type[0] }}</small>
                </div>

                <!-- Stock Mínimo y Máximo -->
                <div class="col-span-12 grid grid-cols-12 gap-4">
                    <!-- Stock Mínimo -->
                    <div class="col-span-6">
                        <label class="block font-bold mb-2">Stock Mínimo <span class="text-red-500">*</span></label>
                        <InputText 
                            v-model.number="producto.min_stock" 
                            type="number" 
                            fluid 
                            min="0" 
                            step="1" 
                            placeholder="Ej: 10"
                        />
                        <small>Cantidad mínima requerida en inventario</small>
                        <small v-if="submitted && (producto.min_stock === null || producto.min_stock === '')" class="text-red-500 block">El stock mínimo es obligatorio.</small>
                        <small v-else-if="submitted && producto.min_stock < 0" class="text-red-500 block">El stock mínimo debe ser mayor o igual a 0.</small>
                        <small v-else-if="serverErrors.min_stock" class="text-red-500 block">{{ serverErrors.min_stock[0] }}</small>
                    </div>

                    <!-- Stock Máximo -->
                    <div class="col-span-6">
                        <label class="block font-bold mb-2">Stock Máximo <span class="text-red-500">*</span></label>
                        <InputText 
                            v-model.number="producto.max_stock" 
                            type="number" 
                            fluid 
                            min="0" 
                            step="1" 
                            placeholder="Ej: 100"
                        />
                        <small>Cantidad máxima permitida en inventario</small>
                        <small v-if="submitted && (producto.max_stock === null || producto.max_stock === '')" class="text-red-500 block">El stock máximo es obligatorio.</small>
                        <small v-else-if="submitted && producto.max_stock < 0" class="text-red-500 block">El stock máximo debe ser mayor o igual a 0.</small>
                        <small v-else-if="submitted && producto.max_stock < producto.min_stock" class="text-red-500 block">El stock máximo debe ser mayor o igual al stock mínimo.</small>
                        <small v-else-if="serverErrors.max_stock" class="text-red-500 block">{{ serverErrors.max_stock[0] }}</small>
                    </div>
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
                        <small>Indica si el producto puede venderse en fracciones</small>
                        <small v-if="serverErrors.is_fractionable" class="text-red-500 block">{{ serverErrors.is_fractionable[0] }}</small>
                    </div>

                    <!-- Unidades de Fracción -->
                    <div class="col-span-6" v-show="producto.is_fractionable">
                        <label class="block font-bold mb-2">Unidades por Fracción <span class="text-red-500">*</span></label>
                        <InputText 
                            v-model.number="producto.fraction_units" 
                            type="number" 
                            fluid 
                            min="1" 
                            step="1" 
                            placeholder="Ej: 12 (para docena)" 
                        />
                        <small>Número de unidades que componen una fracción completa</small>
                        <small v-if="submitted && producto.is_fractionable && (!producto.fraction_units || producto.fraction_units < 1)" class="text-red-500 block">Las unidades de fracción son obligatorias cuando el producto es fraccionable.</small>
                        <small v-else-if="serverErrors.fraction_units" class="text-red-500 block">{{ serverErrors.fraction_units[0] }}</small>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="col-span-12">
                    <label class="block font-bold mb-2">Descripción</label>
                    <Textarea v-model="producto.description" fluid rows="3" maxlength="1000" placeholder="Descripción del producto (opcional)" />
                    <small v-if="serverErrors.description" class="text-red-500">{{ serverErrors.description[0] }}</small>
                </div>
            </div>
        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="guardarProducto" severity="contrast"/>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const submitted = ref(false);
const productoDialog = ref(false);
const serverErrors = ref({});
const emit = defineEmits(['producto-agregado']);

const producto = ref({
    name: '',
    is_active: true,
    purchase_price: null,
    sale_price: null,
    category_id: null,
    unit_type: null,
    description: '',
    is_fractionable: false,
    fraction_units: null,
    min_stock: 0,
    max_stock: 100,
});

const categorias = ref([]);

const tiposUnidad = ref([
    { label: 'Unidad', value: 'piece' },
    { label: 'Botella', value: 'bottle' },
    { label: 'Paquete', value: 'pack' },
    { label: 'Kilogramo', value: 'kg' },
    { label: 'Litro', value: 'liter' },
]);

// Watch para limpiar fraction_units cuando is_fractionable es false
watch(() => producto.value.is_fractionable, (newValue) => {
    if (!newValue) {
        producto.value.fraction_units = null;
    }
});

function resetProducto() {
    producto.value = {
        name: '',
        is_active: true,
        purchase_price: null,
        sale_price: null,
        category_id: null,
        unit_type: null,
        description: '',
        is_fractionable: false,
        fraction_units: null,
        min_stock: 0,
        max_stock: 100,
    };
    serverErrors.value = {};
    submitted.value = false;
}

function openNew() {
    resetProducto();
    fetchCategorias();
    productoDialog.value = true;
}

function hideDialog() {
    productoDialog.value = false;
    resetProducto();
}

async function fetchCategorias() {
    try {
        const { data } = await axios.get('/categoria');
        categorias.value = data.data.map(c => ({ label: c.nombre, value: c.id }));
    } catch (e) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No se pudieron cargar categorías' });
    }
}

function validateForm() {
    const errors = [];
    
    // Validaciones básicas
    if (!producto.value.name || producto.value.name.length < 2) {
        errors.push('El nombre es obligatorio y debe tener al menos 2 caracteres');
    }
    
    if (producto.value.purchase_price === null || producto.value.purchase_price === '') {
        errors.push('El precio de compra es obligatorio');
    }
    
    if (producto.value.sale_price === null || producto.value.sale_price === '') {
        errors.push('El precio de venta es obligatorio');
    }
    
    if (!producto.value.category_id) {
        errors.push('La categoría es obligatoria');
    }
    
    if (!producto.value.unit_type) {
        errors.push('El tipo de unidad es obligatorio');
    }
    
    // Validaciones de stock
    if (producto.value.min_stock === null || producto.value.min_stock === '') {
        errors.push('El stock mínimo es obligatorio');
    }
    
    if (producto.value.max_stock === null || producto.value.max_stock === '') {
        errors.push('El stock máximo es obligatorio');
    }
    
    if (producto.value.min_stock < 0) {
        errors.push('El stock mínimo debe ser mayor o igual a 0');
    }
    
    if (producto.value.max_stock < 0) {
        errors.push('El stock máximo debe ser mayor o igual a 0');
    }
    
    if (producto.value.max_stock < producto.value.min_stock) {
        errors.push('El stock máximo debe ser mayor o igual al stock mínimo');
    }
    
    // Validación específica para productos fraccionables
    if (producto.value.is_fractionable && (!producto.value.fraction_units || producto.value.fraction_units < 1)) {
        errors.push('Las unidades de fracción son obligatorias cuando el producto es fraccionable');
    }
    
    return errors;
}

function guardarProducto() {
    submitted.value = true;
    serverErrors.value = {};

    // Validación del lado del cliente
    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: validationErrors[0],
            life: 3000
        });
        return;
    }

    // Preparar datos para envío
    const dataToSend = { ...producto.value };
    
    // Si no es fraccionable, asegurar que fraction_units sea null
    if (!dataToSend.is_fractionable) {
        dataToSend.fraction_units = null;
    }

    axios.post('/producto', dataToSend)
        .then(() => {
            toast.add({ 
                severity: 'success', 
                summary: 'Éxito', 
                detail: 'Producto registrado correctamente', 
                life: 3000 
            });
            hideDialog();
            emit('producto-agregado');
        })
        .catch(error => {
            if (error.response?.status === 422) {
                serverErrors.value = error.response.data.errors || {};
                const firstError = Object.values(serverErrors.value)[0];
                if (firstError && Array.isArray(firstError)) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error de validación',
                        detail: firstError[0],
                        life: 3000
                    });
                }
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'No se pudo registrar el producto',
                    life: 3000
                });
            }
        });
}
</script>