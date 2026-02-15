<template>
    <Head title="Rangos de Precio" />
    <AppLayout>
        <div class="container-fluid">
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <AddPricingRange ref="addRef" @refresh="handleRefresh" />
                    <ListPricingRange ref="listRef" @edit="handleEdit" />
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
import ListPricingRange from './Desarrollo/ListPricingRange.vue';
import AddPricingRange from './Desarrollo/AddPricingRange.vue';

const isLoading = ref(true);
const listRef = ref<InstanceType<typeof ListPricingRange>>();
const addRef = ref<InstanceType<typeof AddPricingRange>>();

const handleRefresh = async () => {
    // Recargar la lista desde el servidor
    if (listRef.value?.fetchPricingRanges) {
        await listRef.value.fetchPricingRanges();
    }
};

const handleEdit = (id: string | number) => {
    if (addRef.value?.openEdit) {
        addRef.value.openEdit(id);
    }
};

onMounted(async () => {
    try {
        // Inicializar datos si es necesario
        isLoading.value = false;
    } catch (error) {
        console.error('Error initializing:', error);
        isLoading.value = false;
    }
});
</script>