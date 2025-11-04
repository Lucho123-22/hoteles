<template>
    <Toolbar class="mb-6">
        <template #start>
            <DatePicker 
                :modelValue="dates" 
                @update:modelValue="updateDates"
                @hide="onDatePickerClose"
                selectionMode="range" 
                :manualInput="false" 
                class="w-96" 
                placeholder="Seleccione un rango de fechas"
            />
        </template>
        <template #center>
            <Select 
                :modelValue="sucursal"
                @update:modelValue="updateSucursal"
                :options="subBranches" 
                class="w-96"
                optionLabel="name" 
                optionValue="id" 
                showClear 
                placeholder="Seleccionar sucursal..."
            >
                <template #option="{ option }">
                    <div>
                        <strong>{{ option.name }}</strong>
                        <div class="text-sm">Código: {{ option.code }}</div>
                    </div>
                </template>
            </Select>
        </template>
        <template #end>
            <Button 
                label="Ir a Habitaciones" 
                icon="pi pi-arrow-right" 
                severity="contrast" 
                @click="goToHabitaciones" 
                :disabled="!isFormComplete"
            />
        </template>
    </Toolbar>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Select from "primevue/select";
import axios from "axios";

const props = defineProps({
    dates: null,
    sucursal: null
});

const emit = defineEmits(['update:dates', 'update:sucursal']);

// Definir las variables reactivas
const dates = ref(props.dates);
const sucursal = ref(props.sucursal);
const subBranches = ref([]);
const dateRangeSelected = ref(false);

// Computed property para verificar si el formulario está completo
// Solo requiere que haya una sucursal seleccionada (las fechas son opcionales)
const isFormComplete = computed(() => {
    return !!sucursal.value; // Solo verifica que exista una sucursal
});

const updateDates = (value: any) => {
    dates.value = value;
    emit('update:dates', value);
    console.log("Fechas seleccionadas:", value);
    
    // Solo marcar como seleccionado completo cuando hay 2 fechas
    if (value && value.length === 2) {
        dateRangeSelected.value = true;
        console.log("Rango de fechas completo seleccionado");
    } else {
        dateRangeSelected.value = false;
    }
};

// Manejar cuando el DatePicker se cierra
const onDatePickerClose = () => {
    // Aquí puedes agregar lógica adicional si necesitas hacer algo cuando el datepicker se cierra
    console.log("DatePicker cerrado, estado de fechas:", dates.value);
};

const updateSucursal = (value: any) => {
    sucursal.value = value;
    emit('update:sucursal', value);
    console.log("Sub-branch seleccionada ID:", value);
};

const cargarSubBranches = async () => {
    try {
        const response = await axios.get("/sub-branches/search");
        subBranches.value = response.data.data;
        
        // Seleccionar automáticamente la primera sucursal si no hay ninguna seleccionada
        if (subBranches.value.length > 0 && !sucursal.value) {
            sucursal.value = subBranches.value[0].id;
            emit('update:sucursal', subBranches.value[0].id);
            console.log("Sucursal por defecto:", subBranches.value[0].name);
        }
    } catch (error) {
        console.error('Error al cargar sucursales:', error);
    }
};

const goToHabitaciones = () => {
    if (!isFormComplete.value) {
        console.warn("Debe seleccionar una sucursal para continuar.");
        return;
    }
    
    console.log("Navegando a habitaciones con:", {
        dates: dates.value,
        sucursal: sucursal.value
    });
    
    router.visit('/panel/aperturar');
};

onMounted(() => {
    cargarSubBranches();
});
</script>