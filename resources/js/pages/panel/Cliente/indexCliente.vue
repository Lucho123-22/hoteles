<template>
    <Head title="Cliente" />
    <AppLayouth>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <indexMenuRecepcionista/>
                <div class="card">
                    <listCliente/>
                </div>
            </template>
        </div>
    </AppLayouth>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayouth from '@/layout/AppLayouth.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import indexMenuRecepcionista from '../MenuRecepcionista/indexMenuRecepcionista.vue';
import listCliente from './Desarrollo/listCliente.vue';

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>