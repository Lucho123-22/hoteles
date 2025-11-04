<template>
    <Dialog v-model:visible="visible" :style="{ width: '800px' }" header="Editar Pago de Personal" :modal="true"
        :closable="true" @hide="resetForm">
        <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Empleado -->
                <div class="flex flex-col gap-2">
                    <label for="empleado" class="font-semibold">Empleado *</label>
                    <Select id="empleado" v-model="form.user_id" :options="empleados" optionLabel="name"
                        optionValue="id" placeholder="Seleccionar empleado" :class="{ 'p-invalid': errors.user_id }"
                        :disabled="loading || loadingData" :loading="loadingData" filter />
                    <small v-if="errors.user_id" class="p-error">{{ errors.user_id }}</small>
                </div>

                <!-- Sucursal -->
                <div class="flex flex-col gap-2">
                    <label for="sucursal" class="font-semibold">Sucursal *</label>
                    <Select id="sucursal" v-model="form.sub_branch_id" :options="sucursales" optionLabel="name"
                        optionValue="id" placeholder="Seleccionar sucursal"
                        :class="{ 'p-invalid': errors.sub_branch_id }" :disabled="loading || loadingData"
                        :loading="loadingData" filter />
                    <small v-if="errors.sub_branch_id" class="p-error">{{ errors.sub_branch_id }}</small>
                </div>

                <!-- Monto -->
                <div class="flex flex-col gap-2">
                    <label for="monto" class="font-semibold">Monto *</label>
                    <InputNumber id="monto" v-model="form.monto" mode="currency" currency="PEN" locale="es-PE"
                        :class="{ 'p-invalid': errors.monto }" :disabled="loading" :min="0" :max="999999.99" />
                    <small v-if="errors.monto" class="p-error">{{ errors.monto }}</small>
                </div>

                <!-- Fecha de Pago -->
                <div class="flex flex-col gap-2">
                    <label for="fecha_pago" class="font-semibold">Fecha de Pago *</label>
                    <DatePicker id="fecha_pago" v-model="form.fecha_pago" dateFormat="dd/mm/yy"
                        :class="{ 'p-invalid': errors.fecha_pago }" :disabled="loading" showIcon />
                    <small v-if="errors.fecha_pago" class="p-error">{{ errors.fecha_pago }}</small>
                </div>

                <!-- Periodo -->
                <div class="flex flex-col gap-2">
                    <label for="periodo" class="font-semibold">Periodo *</label>
                    <InputText id="periodo" v-model="form.periodo" placeholder="Ej: ENERO 2025"
                        :class="{ 'p-invalid': errors.periodo }" :disabled="loading" />
                    <small v-if="errors.periodo" class="p-error">{{ errors.periodo }}</small>
                </div>

                <!-- Tipo de Pago -->
                <div class="flex flex-col gap-2">
                    <label for="tipo_pago" class="font-semibold">Tipo de Pago *</label>
                    <Select id="tipo_pago" v-model="form.tipo_pago" :options="tiposPago" optionLabel="label"
                        optionValue="value" placeholder="Seleccionar tipo" :class="{ 'p-invalid': errors.tipo_pago }"
                        :disabled="loading" />
                    <small v-if="errors.tipo_pago" class="p-error">{{ errors.tipo_pago }}</small>
                </div>

                <!-- Método de Pago -->
                <div class="flex flex-col gap-2">
                    <label for="metodo_pago" class="font-semibold">Método de Pago *</label>
                    <Select id="metodo_pago" v-model="form.metodo_pago" :options="metodosPago" optionLabel="label"
                        optionValue="value" placeholder="Seleccionar método"
                        :class="{ 'p-invalid': errors.metodo_pago }" :disabled="loading" />
                    <small v-if="errors.metodo_pago" class="p-error">{{ errors.metodo_pago }}</small>
                </div>

                <!-- Estado -->
                <div class="flex flex-col gap-2">
                    <label for="estado" class="font-semibold">Estado *</label>
                    <Select id="estado" v-model="form.estado" :options="estados" optionLabel="label"
                        optionValue="value" placeholder="Seleccionar estado" :class="{ 'p-invalid': errors.estado }"
                        :disabled="loading" />
                    <small v-if="errors.estado" class="p-error">{{ errors.estado }}</small>
                </div>
            </div>

            <!-- Concepto -->
            <div class="flex flex-col gap-2">
                <label for="concepto" class="font-semibold">Concepto</label>
                <Textarea id="concepto" v-model="form.concepto" rows="3" placeholder="Descripción o concepto del pago"
                    :class="{ 'p-invalid': errors.concepto }" :disabled="loading" maxlength="1000" />
                <small v-if="errors.concepto" class="p-error">{{ errors.concepto }}</small>
            </div>

            <!-- Comprobante Actual -->
            <div v-if="pagoActual?.tiene_comprobante" class="flex flex-col gap-2">
                <label class="font-semibold">Comprobante Actual</label>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded border">
                    <i class="pi pi-file text-2xl text-blue-500"></i>
                    <div class="flex-1">
                        <p class="font-medium">Comprobante registrado</p>
                        <p class="text-sm text-gray-500">{{ pagoActual.tipo_comprobante }}</p>
                    </div>
                    <Button icon="pi pi-eye" text rounded severity="info" @click="verComprobanteActual" />
                </div>
            </div>

            <!-- Nuevo Comprobante -->
            <div class="flex flex-col gap-2">
                <label for="comprobante" class="font-semibold">
                    {{ pagoActual?.tiene_comprobante ? 'Reemplazar Comprobante' : 'Comprobante' }}
                </label>
                <FileUpload mode="basic" name="comprobante" :auto="false" accept=".pdf,.jpg,.jpeg,.png"
                    :maxFileSize="2048000" chooseLabel="Seleccionar archivo" :disabled="loading" @select="onFileSelect"
                    :class="{ 'p-invalid': errors.comprobante }" />
                <small class="text-gray-500">Formatos: PDF, JPG, PNG (máx. 2MB)</small>
                <small v-if="errors.comprobante" class="p-error">{{ errors.comprobante }}</small>

                <div v-if="form.comprobante" class="flex items-center gap-2 p-2 bg-blue-50 rounded">
                    <i class="pi pi-file text-blue-500"></i>
                    <span class="text-sm">{{ form.comprobante.name }}</span>
                    <Button icon="pi pi-times" text rounded severity="danger" size="small" @click="removeFile" />
                </div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" @click="closeDialog" text severity="secondary"
                :disabled="loading" />
            <Button label="Actualizar" icon="pi pi-check" severity="contrast" @click="handleSubmit"
                :loading="loading" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import { useToast } from 'primevue/usetoast';
import { usePagosPersonal, type PagoPersonal } from './usePagosPersonal';
import axios from 'axios';
import Select from 'primevue/select';

interface Props {
    visible: boolean;
    pago: PagoPersonal | null;
}

interface Emits {
    (e: 'update:visible', value: boolean): void;
    (e: 'updated'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const toast = useToast();
const pagosStore = usePagosPersonal();

const loading = ref(false);
const loadingData = ref(false);
const pagoActual = ref<PagoPersonal | null>(null);
const empleados = ref<any[]>([]);
const sucursales = ref<any[]>([]);

const form = ref({
    user_id: null as number | null,
    sub_branch_id: null as string | null,
    monto: 0,
    fecha_pago: new Date(),
    periodo: '',
    tipo_pago: '',
    metodo_pago: '',
    concepto: '',
    estado: '',
    comprobante: null as File | null
});

const errors = ref<Record<string, string>>({});

const tiposPago = [
    { label: 'Salario', value: 'salario' },
    { label: 'Adelanto', value: 'adelanto' },
    { label: 'Bonificación', value: 'bonificacion' },
    { label: 'Comisión', value: 'comision' },
    { label: 'Otro', value: 'otro' }
];

const metodosPago = [
    { label: 'Efectivo', value: 'efectivo' },
    { label: 'Transferencia', value: 'transferencia' },
    { label: 'Cheque', value: 'cheque' }
];

const estados = [
    { label: 'Pendiente', value: 'pendiente' },
    { label: 'Pagado', value: 'pagado' },
    { label: 'Anulado', value: 'anulado' }
];

const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const loadEmpleadosYSucursales = async () => {
    loadingData.value = true;
    try {
        const [empleadosRes, sucursalesRes] = await Promise.all([
            axios.get('/usuarios'),
            axios.get('/sub-branches/search')
        ]);
        empleados.value = empleadosRes.data.data || empleadosRes.data;
        sucursales.value = sucursalesRes.data.data || sucursalesRes.data;
    } catch (error) {
        console.error('Error cargando datos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los datos necesarios',
            life: 3000
        });
    } finally {
        loadingData.value = false;
    }
};

watch(() => props.visible, (newVal) => {
    if (newVal && empleados.value.length === 0) {
        loadEmpleadosYSucursales();
    }
});

watch(() => props.pago, (newPago) => {
    if (newPago) {
        pagoActual.value = newPago;
        form.value = {
            user_id: newPago.empleado_id,
            sub_branch_id: newPago.sucursal_id,
            monto: parseFloat(newPago.monto.toString()),
            fecha_pago: new Date(newPago.fecha_pago),
            periodo: newPago.periodo,
            tipo_pago: newPago.tipo_pago,
            metodo_pago: newPago.metodo_pago,
            concepto: newPago.concepto || '',
            estado: newPago.estado,
            comprobante: null
        };
    }
}, { immediate: true });

const onFileSelect = (event: any) => {
    form.value.comprobante = event.files[0];
    errors.value.comprobante = '';
};

const removeFile = () => {
    form.value.comprobante = null;
};

const verComprobanteActual = () => {
    if (pagoActual.value?.comprobante_url) {
        window.open(pagoActual.value.comprobante_url, '_blank');
    }
};

const formatDateForAPI = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const validateForm = (): boolean => {
    errors.value = {};
    let isValid = true;

    if (!form.value.user_id) {
        errors.value.user_id = 'El empleado es obligatorio';
        isValid = false;
    }

    if (!form.value.sub_branch_id) {
        errors.value.sub_branch_id = 'La sucursal es obligatoria';
        isValid = false;
    }

    if (!form.value.monto || form.value.monto <= 0) {
        errors.value.monto = 'El monto debe ser mayor a 0';
        isValid = false;
    }

    if (!form.value.fecha_pago) {
        errors.value.fecha_pago = 'La fecha de pago es obligatoria';
        isValid = false;
    }

    if (!form.value.periodo.trim()) {
        errors.value.periodo = 'El periodo es obligatorio';
        isValid = false;
    }

    if (!form.value.tipo_pago) {
        errors.value.tipo_pago = 'El tipo de pago es obligatorio';
        isValid = false;
    }

    if (!form.value.metodo_pago) {
        errors.value.metodo_pago = 'El método de pago es obligatorio';
        isValid = false;
    }

    if (!form.value.estado) {
        errors.value.estado = 'El estado es obligatorio';
        isValid = false;
    }

    return isValid;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Por favor completa todos los campos requeridos',
            life: 3000
        });
        return;
    }

    if (!props.pago) return;

    loading.value = true;

    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('user_id', form.value.user_id!.toString());
        formData.append('sub_branch_id', form.value.sub_branch_id!);
        formData.append('monto', form.value.monto.toString());
        formData.append('fecha_pago', formatDateForAPI(form.value.fecha_pago));
        formData.append('periodo', form.value.periodo);
        formData.append('tipo_pago', form.value.tipo_pago);
        formData.append('metodo_pago', form.value.metodo_pago);
        formData.append('estado', form.value.estado);

        if (form.value.concepto) {
            formData.append('concepto', form.value.concepto);
        }

        if (form.value.comprobante) {
            formData.append('comprobante', form.value.comprobante);
        }

        await axios.post(`/pagos/${props.pago.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Pago actualizado correctamente',
            life: 3000
        });

        emit('updated');
        closeDialog();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo actualizar el pago',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        user_id: null,
        sub_branch_id: null,
        monto: 0,
        fecha_pago: new Date(),
        periodo: '',
        tipo_pago: '',
        metodo_pago: '',
        concepto: '',
        estado: '',
        comprobante: null
    };
    errors.value = {};
    pagoActual.value = null;
};

const closeDialog = () => {
    visible.value = false;
    resetForm();
};

onMounted(() => {
    if (props.visible) {
        loadEmpleadosYSucursales();
    }
});
</script>