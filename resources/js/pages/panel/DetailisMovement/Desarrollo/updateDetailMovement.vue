<template>
  <Dialog v-model:visible="visible" modal header="Editar Producto" :style="{ width: '600px' }">
    <div v-if="cargando" class="flex justify-center items-center py-8">
      <i class="pi pi-spin pi-spinner text-4xl"></i>
    </div>

    <div v-else class="flex flex-col gap-4">
      <!-- Producto (solo lectura) -->
      <div class="flex flex-col gap-2">
        <label class="font-semibold text-sm">Producto</label>
        <div class="p-3 bg-gray-100 rounded border border-gray-300">
          <strong>{{ productoNombre }}</strong>
          <div class="text-sm text-gray-600">{{ productoCodigo }}</div>
        </div>
      </div>

      <!-- Tipo de cantidad -->
      <div v-if="productoCompleto" class="flex flex-col gap-2">
        <label class="font-semibold text-sm">Tipo de Cantidad <span class="text-red-500">*</span></label>
        
        <Select
          v-model="tipoSeleccionado"
          :options="opcionesTipo"
          :style="{ width: '100%' }"
          optionLabel="label"
          optionValue="value"
          placeholder="Seleccione tipo..."
        />
      </div>

      <!-- Cantidad según tipo seleccionado -->
      <div v-if="tipoSeleccionado" class="flex flex-col gap-2">
        <!-- Solo Paquetes -->
        <div v-if="tipoSeleccionado === 'paquete'" class="flex flex-col gap-2">
          <label class="font-semibold text-sm">Cantidad de Paquetes <span class="text-red-500">*</span></label>
          <InputNumber
            v-model="cantidadPaquetes"
            :min="1"
            :style="{ width: '100%' }"
            placeholder="Ingrese cantidad de paquetes"
          />
          <small v-if="productoCompleto?.is_fractionable" class="text-gray-500">
            Total de fracciones: {{ cantidadPaquetes * productoCompleto.fraction_units }}
          </small>
        </div>

        <!-- Solo Fracciones -->
        <div v-if="tipoSeleccionado === 'fraccion'" class="flex flex-col gap-2">
          <label class="font-semibold text-sm">Cantidad de Fracciones <span class="text-red-500">*</span></label>
          <InputNumber
            v-model="cantidadFracciones"
            :min="1"
            :style="{ width: '100%' }"
            placeholder="Ingrese cantidad de fracciones"
          />
          <small v-if="productoCompleto?.is_fractionable && cantidadFracciones" class="text-gray-500">
            Equivale a: {{ Math.floor(cantidadFracciones / productoCompleto.fraction_units) }} paquete(s) y {{ cantidadFracciones % productoCompleto.fraction_units }} fracción(es)
          </small>
        </div>

        <!-- Ambas -->
        <div v-if="tipoSeleccionado === 'ambas'" class="flex flex-col gap-4">
          <div class="flex gap-4">
            <div class="flex flex-col gap-2 flex-1">
              <label class="font-semibold text-sm">Paquetes</label>
              <InputNumber
                v-model="cantidadPaquetes"
                :min="0"
                :style="{ width: '100%' }"
                placeholder="Paquetes"
              />
            </div>
            <div class="flex flex-col gap-2 flex-1">
              <label class="font-semibold text-sm">Fracciones</label>
              <InputNumber
                v-model="cantidadFracciones"
                :min="0"
                :style="{ width: '100%' }"
                placeholder="Fracciones"
                @update:modelValue="onFraccionesChange"
              />
            </div>
          </div>
          <small v-if="productoCompleto?.is_fractionable && (cantidadPaquetes || cantidadFracciones)" class="text-gray-500">
            Total: {{ calcularTotalPaquetes }} paquete(s) y {{ calcularTotalFracciones }} fracción(es) = {{ calcularTotalFraccionesAbsolutas }} fracciones totales
          </small>
        </div>
      </div>

      <!-- Precio Total -->
      <div v-if="tipoSeleccionado" class="flex flex-col gap-2">
        <label class="font-semibold text-sm">Precio Total <span class="text-red-500">*</span></label>
        <InputNumber
          v-model="precioTotal"
          :min="0"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          :style="{ width: '100%' }"
          placeholder="0.00"
          mode="currency"
          currency="PEN"
          locale="es-PE"
        />
      </div>

      <!-- Precio Unitario (calculado) -->
      <div v-if="tipoSeleccionado && precioTotal" class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <label class="font-semibold text-sm">Precio Unitario (por fracción)</label>
          <div class="flex items-center gap-2">
            <Checkbox v-model="editarPrecioManual" inputId="editarManual" binary />
            <label for="editarManual" class="text-sm cursor-pointer">Editar manualmente</label>
          </div>
        </div>
        <InputNumber
          v-model="precioUnitario"
          :min="0"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          :style="{ width: '100%' }"
          :disabled="!editarPrecioManual"
          placeholder="0.00"
          mode="currency"
          currency="PEN"
          locale="es-PE"
        />
        <small class="text-gray-500">
          {{ editarPrecioManual ? '⚠️ Editando manualmente' : `Calculado: S/ ${precioTotal} ÷ ${totalUnidades} unidades` }}
        </small>
      </div>

      <!-- Fecha de Vencimiento -->
      <div v-if="tipoSeleccionado" class="flex flex-col gap-2">
        <label class="font-semibold text-sm">Fecha de Vencimiento</label>
        <DatePicker
          v-model="fechaVencimiento"
          :style="{ width: '100%' }"
          dateFormat="dd/mm/yy"
          placeholder="Seleccione fecha"
          showIcon
          :minDate="new Date()"
        />
      </div>
    </div>

    <template #footer>
      <Button 
        label="Cancelar" 
        icon="pi pi-times" 
        @click="closeDialog" 
        severity="secondary"
        :disabled="guardando"
      />
      <Button
        label="Actualizar"
        icon="pi pi-check"
        @click="updateProduct"
        severity="contrast"
        :disabled="!formularioValido || guardando || cargando"
        :loading="guardando"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

interface Product {
  id: string;
  codigo: string;
  nombre: string;
  is_fractionable: boolean;
  fraction_units: number;
}

interface DetailProps {
  id: string;
  product_id: string;
  producto?: {
    nombre: string;
    codigo: string;
  };
}

const props = defineProps<{
  detail: DetailProps | null;
}>();

const visible = defineModel<boolean>('visible');
const emit = defineEmits(['updated']);

const cargando = ref<boolean>(false);
const guardando = ref<boolean>(false);
const productoCompleto = ref<Product | null>(null);
const productoNombre = ref<string>('');
const productoCodigo = ref<string>('');

const tipoSeleccionado = ref<string | null>(null);
const cantidadPaquetes = ref<number>(0);
const cantidadFracciones = ref<number>(0);
const precioTotal = ref<number>(0);
const precioUnitario = ref<number>(0);
const fechaVencimiento = ref<Date | null>(null);
const editarPrecioManual = ref<boolean>(false);

// Mapeo de tipos español a inglés
const quantityTypeMap = {
  'packages': 'paquete',
  'fractions': 'fraccion',
  'both': 'ambas'
};

const quantityTypeReverseMap = {
  'paquete': 'packages',
  'fraccion': 'fractions',
  'ambas': 'both'
};

// Auto-ajustar cuando las fracciones exceden el paquete
const onFraccionesChange = () => {
  if (!productoCompleto.value || !productoCompleto.value.is_fractionable) return;
  
  if (cantidadFracciones.value >= productoCompleto.value.fraction_units) {
    const paquetesExtra = Math.floor(cantidadFracciones.value / productoCompleto.value.fraction_units);
    const fraccionesRestantes = cantidadFracciones.value % productoCompleto.value.fraction_units;
    
    cantidadPaquetes.value = (cantidadPaquetes.value || 0) + paquetesExtra;
    cantidadFracciones.value = fraccionesRestantes;
  }
};

// Opciones de tipo de cantidad
const opcionesTipo = computed(() => {
  if (!productoCompleto.value) return [];
  
  if (productoCompleto.value.is_fractionable) {
    return [
      { label: 'Paquete', value: 'paquete' },
      { label: 'Fracción', value: 'fraccion' },
      { label: 'Ambas', value: 'ambas' }
    ];
  } else {
    return [
      { label: 'Unidades', value: 'paquete' }
    ];
  }
});

// Calcular totales para modo "Ambas"
const calcularTotalPaquetes = computed(() => {
  if (!productoCompleto.value || tipoSeleccionado.value !== 'ambas') return 0;
  
  const totalFracciones = (cantidadPaquetes.value || 0) * productoCompleto.value.fraction_units + (cantidadFracciones.value || 0);
  return Math.floor(totalFracciones / productoCompleto.value.fraction_units);
});

const calcularTotalFracciones = computed(() => {
  if (!productoCompleto.value || tipoSeleccionado.value !== 'ambas') return 0;
  
  const totalFracciones = (cantidadPaquetes.value || 0) * productoCompleto.value.fraction_units + (cantidadFracciones.value || 0);
  return totalFracciones % productoCompleto.value.fraction_units;
});

const calcularTotalFraccionesAbsolutas = computed(() => {
  if (!productoCompleto.value || tipoSeleccionado.value !== 'ambas') return 0;
  
  return (cantidadPaquetes.value || 0) * productoCompleto.value.fraction_units + (cantidadFracciones.value || 0);
});

// Calcular total de unidades
const totalUnidades = computed(() => {
  if (!productoCompleto.value) return 0;
  
  if (tipoSeleccionado.value === 'paquete') {
    return productoCompleto.value.is_fractionable 
      ? cantidadPaquetes.value * productoCompleto.value.fraction_units 
      : cantidadPaquetes.value;
  } else if (tipoSeleccionado.value === 'fraccion') {
    return cantidadFracciones.value;
  } else if (tipoSeleccionado.value === 'ambas') {
    return (cantidadPaquetes.value || 0) * productoCompleto.value.fraction_units + (cantidadFracciones.value || 0);
  }
  
  return 0;
});

// Calcular precio unitario automáticamente
watch([precioTotal, totalUnidades, editarPrecioManual], () => {
  if (!editarPrecioManual.value && totalUnidades.value > 0 && precioTotal.value > 0) {
    precioUnitario.value = parseFloat((precioTotal.value / totalUnidades.value).toFixed(2));
  }
});

// Validar que la cantidad sea válida
const validarCantidad = computed(() => {
  if (!tipoSeleccionado.value) return false;
  
  if (tipoSeleccionado.value === 'paquete') {
    return cantidadPaquetes.value > 0;
  } else if (tipoSeleccionado.value === 'fraccion') {
    return cantidadFracciones.value > 0;
  } else if (tipoSeleccionado.value === 'ambas') {
    return cantidadPaquetes.value > 0 || cantidadFracciones.value > 0;
  }
  
  return false;
});

// Validar formulario completo
const formularioValido = computed(() => {
  const valido = productoCompleto.value && 
         tipoSeleccionado.value && 
         validarCantidad.value && 
         precioTotal.value > 0;
  
  console.log('Validación formulario:', {
    productoCompleto: !!productoCompleto.value,
    tipoSeleccionado: tipoSeleccionado.value,
    validarCantidad: validarCantidad.value,
    precioTotal: precioTotal.value,
    resultado: valido
  });
  
  return valido;
});

// Cargar datos del detalle
const cargarDetalle = async () => {
  if (!props.detail?.id) return;
  
  cargando.value = true;
  
  try {
    const response = await axios.get(`/movement-detail/${props.detail.id}`);
    const data = response.data.data;
    
    console.log('Datos cargados:', data);
    
    // ✅ PRIMERO: Cargar información del producto
    const productResponse = await axios.get(`/producto/${data.product_id}`);
    productoCompleto.value = productResponse.data.data;
    productoNombre.value = productoCompleto.value?.nombre || props.detail.producto?.nombre || '';
    productoCodigo.value = productoCompleto.value?.codigo || props.detail.producto?.codigo || '';
    
    console.log('Producto cargado:', productoCompleto.value);
    
    // ✅ SEGUNDO: Esperar un tick para que Vue actualice las opciones de tipo
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // ✅ TERCERO: CARGAR TIPO DE CANTIDAD (convertir de inglés a español)
    const tipoMapeado = quantityTypeMap[data.quantity_type];
    tipoSeleccionado.value = tipoMapeado || data.quantity_type;
    
    console.log('Tipo seleccionado:', tipoSeleccionado.value, 'desde', data.quantity_type);
    
    // ✅ CARGAR CANTIDADES
    cantidadPaquetes.value = parseInt(data.boxes) || 0;
    cantidadFracciones.value = parseInt(data.fractions) || 0;
    
    // ✅ CARGAR PRECIOS (unit_price y total_price)
    precioUnitario.value = parseFloat(data.unit_price) || 0;
    precioTotal.value = parseFloat(data.total_price) || 0;
    
    // ✅ CARGAR FECHA DE VENCIMIENTO
    if (data.expiry_date) {
      fechaVencimiento.value = new Date(data.expiry_date);
    } else {
      fechaVencimiento.value = null;
    }
    
    // Esperar otro tick para que totalUnidades se calcule correctamente
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Si el precio unitario no coincide con el cálculo automático, activar edición manual
    const precioCalculado = totalUnidades.value > 0 
      ? parseFloat((precioTotal.value / totalUnidades.value).toFixed(2))
      : 0;
    
    editarPrecioManual.value = Math.abs(precioUnitario.value - precioCalculado) > 0.01;
    
    console.log('Datos cargados completamente:', {
      tipo: tipoSeleccionado.value,
      paquetes: cantidadPaquetes.value,
      fracciones: cantidadFracciones.value,
      precioTotal: precioTotal.value,
      precioUnitario: precioUnitario.value,
      totalUnidades: totalUnidades.value
    });
    
  } catch (error) {
    console.error('Error al cargar el detalle:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la información del detalle',
      life: 3000
    });
    closeDialog();
  } finally {
    cargando.value = false;
  }
};

// Observar cambios en el dialog visible
watch(visible, (newValue) => {
  if (newValue && props.detail?.id) {
    cargarDetalle();
  }
});

const closeDialog = () => {
  visible.value = false;
  resetForm();
};

const resetForm = () => {
  productoCompleto.value = null;
  productoNombre.value = '';
  productoCodigo.value = '';
  tipoSeleccionado.value = null;
  cantidadPaquetes.value = 0;
  cantidadFracciones.value = 0;
  precioTotal.value = 0;
  precioUnitario.value = 0;
  fechaVencimiento.value = null;
  editarPrecioManual.value = false;
  guardando.value = false;
  cargando.value = false;
};

const updateProduct = async () => {
  if (!productoCompleto.value || !tipoSeleccionado.value || !precioTotal.value || !props.detail?.id) {
    toast.add({
      severity: "warn",
      summary: "Advertencia",
      detail: "Complete todos los campos requeridos",
      life: 3000,
    });
    return;
  }

  guardando.value = true;

  try {
    let boxes = 0;
    let fractions = 0;
    
    // Convertir tipo al formato inglés que espera el backend
    const quantityType = quantityTypeReverseMap[tipoSeleccionado.value] || tipoSeleccionado.value;

    if (tipoSeleccionado.value === 'paquete') {
      boxes = cantidadPaquetes.value;
      fractions = 0;
    } else if (tipoSeleccionado.value === 'fraccion') {
      boxes = 0;
      fractions = cantidadFracciones.value;
    } else if (tipoSeleccionado.value === 'ambas') {
      boxes = cantidadPaquetes.value || 0;
      fractions = cantidadFracciones.value || 0;
    }

    const unitsPerBox = productoCompleto.value.fraction_units;

    const payload = {
      unit_price: precioUnitario.value,
      boxes: boxes,
      units_per_box: unitsPerBox,
      fractions: fractions,
      quantity_type: quantityType,
      expiry_date: fechaVencimiento.value 
        ? new Date(fechaVencimiento.value).toISOString().split('T')[0] 
        : null,
      total_price: precioTotal.value
    };

    console.log('Enviando actualización:', payload);

    const response = await axios.put(`/movement-detail/${props.detail.id}`, payload);

    console.log('Respuesta del servidor:', response.data);

    toast.add({
      severity: "success",
      summary: "Éxito",
      detail: response.data.message || "Producto actualizado correctamente",
      life: 3000,
    });

    emit('updated', response.data.data);
    closeDialog();

  } catch (error) {
    console.error('Error al actualizar el producto:', error);
    
    let errorMessage = "Error al actualizar el producto";
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat();
      errorMessage = errors.join(', ');
    }

    toast.add({
      severity: "error",
      summary: "Error",
      detail: errorMessage,
      life: 5000,
    });
  } finally {
    guardando.value = false;
  }
};
</script>