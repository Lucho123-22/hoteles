<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layout/AppLayout.vue';
import Espera from '@/components/Espera.vue';
import listCashSession from './Desarrollo/listCashSession.vue';
import Button from 'primevue/button';
import { useCashSessionStore } from './Interface/cashSessionStore';
import type { CashRegister } from './Interface/cashSessionStore';

const page = usePage();
const cashRegister = computed(() => page.props.cashRegister as any);

const cashRegisterData = computed(() => {
    if (cashRegister.value?.data) {
        return cashRegister.value.data as CashRegister;
    }
    return cashRegister.value as CashRegister;
});

const isLoading = ref(true);
const cashSessionStore = useCashSessionStore();

const goBack = () => {
    router.visit('/panel/cajas');
};

onMounted(async () => {
    try {
        console.log('Valor de cashRegister:', cashRegister.value);
        console.log('Datos extraídos:', cashRegisterData.value);
        if (!cashRegisterData.value || !cashRegisterData.value.id) {
            console.error('cashRegister no está disponible:', cashRegisterData.value);
            return;
        }
        console.log('Cash Register ID:', cashRegisterData.value.id);
        cashSessionStore.setCashRegister(cashRegisterData.value);
        await cashSessionStore.fetchCashSessions(cashRegisterData.value.id);
    } catch (error) {
        console.error('Error al cargar datos:', error);
    } finally {
        setTimeout(() => {
            isLoading.value = false;
        }, 1000);
    }
});
</script>

<template>
    <Head title="Sesiones de Caja" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else-if="cashRegisterData">
                <!-- Información de la Caja -->
                <div class="card mb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold">{{ cashRegisterData.name }}</h2>
                        <div class="flex gap-2">
                            <span :class="[
                                'px-3 py-1 rounded-full text-sm font-semibold',
                                cashRegisterData.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            ]">
                                {{ cashRegisterData.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            <span :class="[
                                'px-3 py-1 rounded-full text-sm font-semibold',
                                cashRegisterData.is_occupied ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                            ]">
                                {{ cashRegisterData.is_occupied ? 'Ocupada' : 'Disponible' }}
                            </span>
                        </div>
                        <div class="mb-4">
                            <Button label="Volver" icon="pi pi-arrow-left" severity="contrast" @click="goBack" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm">Sucursal</p>
                            <p class="font-semibold">{{ cashRegisterData.sub_branch?.name || 'N/A' }}</p>
                        </div>
                        <div v-if="cashRegisterData.occupied_by">
                            <p class="text-sm">Ocupada por</p>
                            <p class="font-semibold">{{ cashRegisterData.occupied_by.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm">Fecha de creación</p>
                            <p class="font-semibold">{{ cashRegisterData.created_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Listado de Sesiones -->
                <div class="card">
                    <listCashSession />
                </div>
            </template>
            <template v-else>
                <div class="card p-8 text-center">
                    <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <p class="text-lg">No se pudo cargar la información de la caja registradora.</p>
                </div>
            </template>
        </div>
    </AppLayout>
</template>