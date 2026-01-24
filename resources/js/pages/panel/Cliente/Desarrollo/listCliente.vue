<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useCustomerStore } from '../Interface/customerStore';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const customerStore = useCustomerStore();
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

onMounted(() => {
  customerStore.fetchCustomers();
});
</script>

<template>
  <DataTable 
    :value="customerStore.customers" 
    :paginator="true" 
    :rows="10"
    :filters="filters"
    :loading="customerStore.loading"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} clientes"
  >
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">Clientes</h4>
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText v-model="filters.global.value" placeholder="Buscar..." />
        </IconField>
      </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
    <Column field="document_number" header="Documento" sortable style="min-width: 12rem"></Column>
    <Column field="name" header="Nombre" sortable style="min-width: 16rem"></Column>
  </DataTable>
</template>