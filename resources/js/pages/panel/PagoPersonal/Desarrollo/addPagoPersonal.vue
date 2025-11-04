<template>
  <Toolbar class="mb-6">
    <template #start>
      <Button label="Nuevo" icon="pi pi-plus" class="mr-2" @click="openNew" severity="contrast" />
    </template>
    <template #end>
      
    </template>
  </Toolbar>

  <Dialog v-model:visible="productDialog" :style="{ width: '600px' }" header="Registrar Pago Personal" :modal="true">
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-2">
        <label for="user" class="font-semibold">Empleado <span class="text-red-500">*</span></label>
        <Select
          id="user"
          v-model="pago.user_id"
          :options="usuarios"
          optionLabel="name"
          optionValue="id"
          placeholder="Seleccione un empleado"
          filter
          :loading="loadingUsuarios"
          class="w-full"
          :disabled="usuarios.length === 0"
        >
          <template #option="slotProps">
            <div class="flex flex-col">
              <span class="font-semibold">{{ slotProps.option.name }}</span>
              <span class="text-sm text-gray-600">DNI: {{ slotProps.option.dni || 'N/A' }}</span>
            </div>
          </template>
        </Select>
        <small class="text-red-500" v-if="errors.user_id">{{ errors.user_id }}</small>
        <small class="text-gray-600" v-if="usuarios.length === 0 && !loadingUsuarios">
          No hay empleados en esta sucursal
        </small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="monto" class="font-semibold">Monto <span class="text-red-500">*</span></label>
        <InputNumber
          id="monto"
          v-model="pago.monto"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :minFractionDigits="2"
          class="w-full"
        />
        <small class="text-red-500" v-if="errors.monto">{{ errors.monto }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="fecha_pago" class="font-semibold">Fecha de Pago <span class="text-red-500">*</span></label>
        <DatePicker
          id="fecha_pago"
          v-model="pago.fecha_pago"
          dateFormat="dd/mm/yy"
          showIcon
          class="w-full"
        />
        <small class="text-red-500" v-if="errors.fecha_pago">{{ errors.fecha_pago }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="periodo" class="font-semibold">Periodo <span class="text-red-500">*</span></label>
        <InputText
          id="periodo"
          v-model="pago.periodo"
          placeholder="Ej: Octubre 2025"
          class="w-full"
        />
        <small class="text-red-500" v-if="errors.periodo">{{ errors.periodo }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="tipo_pago" class="font-semibold">Tipo de Pago <span class="text-red-500">*</span></label>
        <Select
          id="tipo_pago"
          v-model="pago.tipo_pago"
          :options="tiposPago"
          optionLabel="label"
          optionValue="value"
          placeholder="Seleccione tipo de pago"
          class="w-full"
        />
        <small class="text-red-500" v-if="errors.tipo_pago">{{ errors.tipo_pago }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="metodo_pago" class="font-semibold">Método de Pago <span class="text-red-500">*</span></label>
        <Select
          id="metodo_pago"
          v-model="pago.metodo_pago"
          :options="metodosPago"
          optionLabel="label"
          optionValue="value"
          placeholder="Seleccione método de pago"
          class="w-full"
        />
        <small class="text-red-500" v-if="errors.metodo_pago">{{ errors.metodo_pago }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="concepto" class="font-semibold">Concepto</label>
        <Textarea
          id="concepto"
          v-model="pago.concepto"
          rows="3"
          placeholder="Descripción del pago"
          class="w-full"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label for="comprobante" class="font-semibold">Comprobante</label>
        <FileUpload
          mode="basic"
          name="comprobante"
          accept="image/*,application/pdf"
          :maxFileSize="5242880"
          @select="onFileSelect"
          :auto="false"
          chooseLabel="Seleccionar archivo"
          class="w-full"
        />
        <small class="text-gray-600">Formatos: JPG, PNG, PDF (Máx. 5MB)</small>
        
        <div v-if="selectedFile" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
          <i class="pi pi-file text-2xl text-blue-500"></i>
          <div class="flex-1">
            <p class="font-semibold text-sm">{{ selectedFile.name }}</p>
            <p class="text-xs text-gray-600">{{ formatFileSize(selectedFile.size) }}</p>
          </div>
          <Button 
            icon="pi pi-times" 
            severity="danger" 
            text 
            rounded 
            @click="removeFile"
            size="small"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" severity="secondary"/>
      <Button label="Guardar" icon="pi pi-check" @click="saveProduct" :loading="saving" severity="contrast"/>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { usePagosPersonal } from './usePagosPersonal';

// Props
const props = defineProps<{
  sucursalSeleccionada: string | null;
}>();

const toast = useToast();
const pagosStore = usePagosPersonal();
const emit = defineEmits(['refresh']);

const productDialog = ref(false);
const saving = ref(false);
const selectedFile = ref<File | null>(null);
const loadingUsuarios = ref(false);
const usuarios = ref<any[]>([]);
const errors = ref<Record<string, string>>({});

const pago = ref({
  user_id: null as number | null,
  monto: 0,
  fecha_pago: new Date(),
  periodo: '',
  tipo_pago: '',
  metodo_pago: '',
  concepto: '',
  estado: 'pagado'
});

const tiposPago = ref([
  { label: 'Salario', value: 'salario' },
  { label: 'Adelanto', value: 'adelanto' },
  { label: 'Bonificación', value: 'bonificacion' },
  { label: 'Comisión', value: 'comision' },
  { label: 'Otro', value: 'otro' }
]);

const metodosPago = ref([
  { label: 'Efectivo', value: 'efectivo' },
  { label: 'Transferencia Bancaria', value: 'transferencia' },
  { label: 'Cheque', value: 'cheque' }
]);

// Watcher para detectar cambios en la sucursal mientras el diálogo está abierto
watch(() => props.sucursalSeleccionada, (newVal, oldVal) => {
  // Si el diálogo está abierto y cambió la sucursal, recargar usuarios
  if (productDialog.value && newVal !== oldVal && newVal) {
    usuarios.value = []; // Limpiar usuarios anteriores
    pago.value.user_id = null; // Limpiar empleado seleccionado
    loadUsuarios();
  }
});

const loadUsuarios = async () => {
  if (!props.sucursalSeleccionada) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Advertencia', 
      detail: 'Debe seleccionar una sucursal primero', 
      life: 3000 
    });
    return;
  }
  
  loadingUsuarios.value = true;
  try {
    const response = await axios.get('/usuarios/search/by-subranch', {
      params: { sub_branch_id: props.sucursalSeleccionada }
    });
    usuarios.value = response.data.data || [];
    
    if (usuarios.value.length === 0) {
      toast.add({ 
        severity: 'info', 
        summary: 'Información', 
        detail: 'No hay empleados registrados en esta sucursal', 
        life: 3000 
      });
    }
  } catch (error: any) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'No se pudieron cargar los usuarios de la sucursal', 
      life: 3000 
    });
    usuarios.value = [];
  } finally {
    loadingUsuarios.value = false;
  }
};

const openNew = () => {
  if (!props.sucursalSeleccionada) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Advertencia', 
      detail: 'Debe seleccionar una sucursal antes de registrar un pago', 
      life: 3000 
    });
    return;
  }

  pago.value = {
    user_id: null,
    monto: 0,
    fecha_pago: new Date(),
    periodo: '',
    tipo_pago: '',
    metodo_pago: '',
    concepto: '',
    estado: 'pagado'
  };
  selectedFile.value = null;
  errors.value = {};
  usuarios.value = []; // Limpiar usuarios antes de abrir
  
  productDialog.value = true; // Abrir diálogo primero
  loadUsuarios(); // Luego cargar usuarios
};

const hideDialog = () => {
  productDialog.value = false;
  errors.value = {};
  selectedFile.value = null;
};

const onFileSelect = (event: any) => {
  selectedFile.value = event.files[0];
  if (errors.value.comprobante) errors.value.comprobante = '';
};

const removeFile = () => {
  selectedFile.value = null;
};

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const saveProduct = async () => {
  saving.value = true;
  errors.value = {};

  try {
    if (!pago.value.user_id) {
      errors.value.user_id = 'El empleado es requerido';
      throw new Error('Validación fallida');
    }
    if (!pago.value.monto || pago.value.monto <= 0) {
      errors.value.monto = 'El monto debe ser mayor a 0';
      throw new Error('Validación fallida');
    }
    if (!pago.value.periodo) {
      errors.value.periodo = 'El periodo es requerido';
      throw new Error('Validación fallida');
    }
    if (!pago.value.tipo_pago) {
      errors.value.tipo_pago = 'El tipo de pago es requerido';
      throw new Error('Validación fallida');
    }
    if (!pago.value.metodo_pago) {
      errors.value.metodo_pago = 'El método de pago es requerido';
      throw new Error('Validación fallida');
    }

    const formData = new FormData();
    formData.append('user_id', pago.value.user_id.toString());
    formData.append('monto', pago.value.monto.toString());
    
    const fechaPago = pago.value.fecha_pago instanceof Date
      ? pago.value.fecha_pago.toISOString().split('T')[0]
      : pago.value.fecha_pago;
    formData.append('fecha_pago', fechaPago);
    
    formData.append('periodo', pago.value.periodo);
    formData.append('tipo_pago', pago.value.tipo_pago);
    formData.append('metodo_pago', pago.value.metodo_pago);
    formData.append('estado', pago.value.estado);
    
    if (pago.value.concepto) formData.append('concepto', pago.value.concepto);
    if (selectedFile.value) formData.append('comprobante', selectedFile.value);

    await pagosStore.createPago(formData);

    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Pago registrado correctamente', life: 3000 });
    hideDialog();
    emit('refresh');
    
  } catch (error: any) {
    if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors || {};
      Object.keys(validationErrors).forEach(key => {
        errors.value[key] = Array.isArray(validationErrors[key]) 
          ? validationErrors[key][0] 
          : validationErrors[key];
      });
      toast.add({ severity: 'warn', summary: 'Validación', detail: 'Por favor corrija los errores en el formulario', life: 3000 });
    } else if (error.message !== 'Validación fallida') {
      toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Error al registrar el pago', life: 3000 });
    }
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  // No cargamos usuarios aquí, solo cuando se abre el diálogo
});
</script>