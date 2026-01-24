<template>
    <div class="card">
        <Menubar :model="items">
            <template #end>
                <Button 
                    label="Cerrar Caja" 
                    icon="pi pi-lock" 
                    severity="danger" 
                    @click="cerrarCaja"
                    class="ml-2"
                />
            </template>
        </Menubar>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from '@inertiajs/vue3';
import Menubar from 'primevue/menubar';
import Button from 'primevue/button';

const items = ref([
    {
        label: 'Home',
        icon: 'pi pi-home',
        command: () => router.visit('/panel/habitaciones/online')
    },
    {
        label: 'Huéspedes',
        icon: 'pi pi-users',
        command: () => router.visit('/panel/huespedes')
    },
    {
        label: 'Reservas',
        icon: 'pi pi-calendar',
        items: [
            {
                label: 'Nueva Reserva',
                icon: 'pi pi-plus',
                command: () => router.visit('/panel/reservas/nueva')
            },
            {
                label: 'Listado de Reservas',
                icon: 'pi pi-list',
                command: () => router.visit('/panel/reservas')
            },
            {
                label: 'Calendario de Reservas',
                icon: 'pi pi-calendar',
                command: () => router.visit('/panel/reservas/calendario')
            },
            {
                label: 'Reservas Pendientes',
                icon: 'pi pi-clock',
                command: () => router.visit('/panel/reservas/pendientes')
            }
        ]
    },
]);

const cerrarCaja = () => {
    router.visit('/panel/caja/cerrar');
};
</script>