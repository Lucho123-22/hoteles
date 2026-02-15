<template>
    <Head title="Tipos de Habitación" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <AddRoomType ref="addRef" @refresh="refreshList" />
                    <ListRoomType ref="listRef" @edit="handleEdit" />
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
import ListRoomType from './Desarrollo/ListRoomType.vue';
import AddRoomType from './Desarrollo/AddRoomType.vue';

const isLoading = ref(true);
const listRef = ref<InstanceType<typeof ListRoomType>>();
const addRef = ref<InstanceType<typeof AddRoomType>>();

const refreshList = () => {
    // El store ya actualiza el array localmente
};

const handleEdit = (id: string | number) => {
    if (addRef.value) {
        addRef.value.openEdit(id);
    }
};

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>