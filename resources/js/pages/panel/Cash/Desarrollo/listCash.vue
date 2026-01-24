<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3' // Importar router de Inertia
import { useCashRegisterStore } from './cashRegister.store'
import { FilterMatchMode } from '@primevue/core/api'
import Message from 'primevue/message';
// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Button from 'primevue/button'
import Menu from 'primevue/menu'

const dt = ref()
const menu = ref()
const store = useCashRegisterStore()

const filters = ref({
  'global': { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const selectedCashRegister = ref(null)

// Opciones del menú
const menuItems = ref([
  {
    label: 'Historial',
    icon: 'pi pi-history',
    command: () => handleHistory(selectedCashRegister.value)
  },
  {
    label: 'Editar',
    icon: 'pi pi-pencil',
    command: () => handleEdit(selectedCashRegister.value)
  },
  {
    separator: true
  },
  {
    label: 'Eliminar',
    icon: 'pi pi-trash',
    command: () => handleDelete(selectedCashRegister.value),
    class: 'text-red-600'
  }
])

// Mostrar menú
const toggleMenu = (event: any, cashRegister: any) => {
  selectedCashRegister.value = cashRegister
  menu.value.toggle(event)
}

// Métodos de acción
const handleEdit = (cashRegister: any) => {
  console.log('Editar caja:', cashRegister)
  // Implementar lógica de edición
}

const handleDelete = (cashRegister: any) => {
  console.log('Eliminar caja:', cashRegister)
  // Implementar lógica de eliminación
}

const handleHistory = (cashRegister: any) => {
  console.log('Ver historial:', cashRegister)
  // Redirigir a la página de historial con el ID usando Inertia
  router.visit(`/panel/cajas/usuarios/${cashRegister.id}`)
}

// Función para cargar las cajas (expuesta para ser llamada desde el componente padre)
const loadCashRegisters = async () => {
  await store.fetchAll()
}

// Exponer el método para que pueda ser llamado desde el componente padre
defineExpose({
  loadCashRegisters
})

onMounted(async () => {
  await loadCashRegisters()
})
</script>

<template>
  <div class="">
    <Message severity="error" v-if="store.error">{{ store.error }}</Message>
    <!-- Tabla de datos -->
    <DataTable 
      ref="dt" 
      :value="store.items" 
      :loading="store.loading" 
      stripedRows 
      responsiveLayout="scroll" 
      :paginator="true"
      :rows="10" 
      :filters="filters" 
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 25, 50]"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cajas" 
      class="p-datatable-sm">
      <!-- Header de la tabla -->
      <template #header>
        <div class="flex flex-wrap gap-3 items-center justify-between p-2">
          <h4 class="text-lg font-semibold m-0">
            Listado de Cajas
          </h4>
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Buscar caja..." class="w-64" />
          </IconField>
        </div>
      </template>

      <!-- Mensaje cuando no hay datos -->
      <template #empty>
        <div class="text-center py-12">
          <i class="pi pi-inbox text-gray-300 text-5xl mb-3"></i>
          <p class="text-lg">
            No hay cajas registradoras disponibles
          </p>
        </div>
      </template>

      <!-- Mensaje de carga -->
      <template #loading>
        <div class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-blue-500 text-3xl"></i>
          <p class="text-gray-500 mt-2">Cargando cajas...</p>
        </div>
      </template>

      <!-- Columnas -->
      <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
      <Column field="name" header="Nombre" sortable/>
      <Column header="Activo" sortable field="is_active">
        <template #body="{ data }">
          <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'"
            :icon="data.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
        </template>
      </Column>

      <Column header="Estado" sortable field="is_occupied">
        <template #body="{ data }">
          <Tag :value="data.is_occupied ? 'Ocupada' : 'Libre'" :severity="data.is_occupied ? 'warn' : 'success'"
            :icon="data.is_occupied ? 'pi pi-lock' : 'pi pi-unlock'" />
        </template>
      </Column>

      <Column header="Usuario Actual" field="occupied_by.name">
        <template #body="{ data }">
          <div v-if="data.occupied_by" class="flex items-center gap-2">
            <i class="pi pi-user text-gray-400 text-sm"></i>
            <span class="">
              {{ data.occupied_by.name }}
            </span>
          </div>
          <span v-else class="italic">Sin asignar</span>
        </template>
      </Column>

      <Column field="created_at" header="Fecha Creación" sortable/>
      <!-- Columna de Acciones con menú de 3 puntos -->
      <Column>
        <template #body="{ data }">
          <Button icon="pi pi-ellipsis-v" text rounded severity="secondary" @click="toggleMenu($event, data)"
            class="hover:bg-gray-100" aria-label="Opciones" />
        </template>
      </Column>
    </DataTable>

    <!-- Menú contextual -->
    <Menu ref="menu" :model="menuItems" :popup="true" />
  </div>
</template>