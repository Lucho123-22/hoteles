<template>
  <Head title="Configuración de Precios" />
  <AppLayout>
    <div>
      <template v-if="isLoading">
        <Espera/>
      </template>
      <template v-else>
        <div class="card">
          <AddBranchRoomTypePrice 
            ref="addComponent" 
            @filtersChanged="handleFiltersChanged"
          />
          <ListBranchRoomTypePrice 
            :filters="currentFilters"
            @edit="handleEdit" 
          />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import AddBranchRoomTypePrice from './Desarrollo/AddBranchRoomTypePrice.vue';
import ListBranchRoomTypePrice from './Desarrollo/ListBranchRoomTypePrice.vue';
import type { BranchRoomTypePrice, FilterParams } from './interfaces';

const isLoading = ref(true);
const addComponent = ref();
const currentFilters = ref<FilterParams>({});

function handleEdit(price: BranchRoomTypePrice) {
  addComponent.value?.editPrice(price);
}

function handleFiltersChanged(filters: FilterParams) {
  console.log('Nuevos filtros recibidos:', filters);
  currentFilters.value = filters;
}

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
});
</script>