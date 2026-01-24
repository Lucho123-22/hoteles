<template>
    <div class="">
        <DataView :value="store.floors" :layout="store.layout">
            <template #header>
                <div class="flex justify-between items-center">
                    <div class="text-xl font-semibold">Gestión de Habitaciones</div>
                    <SelectButton v-model="store.layout" :options="store.layoutOptions" :allowEmpty="false">
                        <template #option="{ option }">
                            <i :class="[option === 'list' ? 'pi pi-bars' : 'pi pi-table']" />
                        </template>
                    </SelectButton>
                </div>
            </template>

            <template #list="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <FloorHeader :floor="floor" />

                        <div class="border border-t-0 border-surface-200 dark:border-surface-700 rounded-b-lg">
                            <div v-for="(room, roomIndex) in floor.rooms" :key="room.id">
                                <RoomListItem 
                                    :room="room"
                                    :is-first="roomIndex === 0"
                                    @view-details="viewRoomDetails"
                                    @extend-time="store.openExtenderDialog"
                                    @charge-extra="store.openCobrarDialog"
                                    @finish-booking="(id, number) => store.openFinalizarDialog(id, number)"
                                    @liberar="store.openLiberarDialog"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #grid="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <FloorHeader :floor="floor" />

                        <div class="grid grid-cols-12 gap-4">
                            <div 
                                v-for="room in floor.rooms" 
                                :key="room.id" 
                                class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-3"
                            >
                                <RoomGridItem 
                                    :room="room"
                                    @view-details="viewRoomDetails"
                                    @extend-time="store.openExtenderDialog"
                                    @charge-extra="store.openCobrarDialog"
                                    @finish-booking="(id, number) => store.openFinalizarDialog(id, number)"
                                    @liberar="store.openLiberarDialog"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #empty>
                <LoadingSkeleton v-if="store.loading" :layout="store.layout" />
                <EmptyState v-else />
            </template>
        </DataView>

        <!-- Diálogos -->
        <LiberarRoom 
            v-model:visible="store.showLiberarDialog" 
            :roomId="store.selectedRoomId"
            @room-liberated="store.handleRoomLiberated"
        />

        <ExtenderTiempo 
            v-model:visible="store.showExtenderDialog" 
            :roomId="store.selectedRoomId"
            @time-extended="store.handleTimeExtended"
        />

        <CobrarTiempoExtra 
            v-model:visible="store.showCobrarDialog" 
            :roomId="store.selectedRoomId"
            @extra-time-charged="store.handleExtraTimeCharged"
        />

        <FinalizarReserva 
            v-model:visible="store.showFinalizarDialog" 
            :roomId="store.selectedRoomId"
            :roomNumber="store.selectedRoomNumber"
            @booking-finished="store.handleBookingFinished"
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import { useRoomManagementStore } from '../interface/Roommanagement';
import type { RoomStatus } from '../interface/Roommanagement';

import LoadingSkeleton from '../components/LoadingSkeleton.vue';
import EmptyState from '../components/EmptyState.vue';
import LiberarRoom from './liberarRoom.vue';
import ExtenderTiempo from './extenderTiempo.vue';
import CobrarTiempoExtra from './cobrarTiempoExtra.vue';
import FinalizarReserva from './finalizarReserva.vue';

import FloorHeader from '../components/Floorheader.vue';
import RoomListItem from '../components/Roomlistitem.vue';
import RoomGridItem from '../components/Roomgriditem.vue';

// Store
const store = useRoomManagementStore();

// Timer interval
let timerInterval: NodeJS.Timeout | null = null;

// Lifecycle
onMounted(async () => {
    await store.fetchFloors();
    
    timerInterval = setInterval(() => {
        store.updateCurrentTime();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

// Métodos
const viewRoomDetails = (roomId: number, roomStatus?: RoomStatus) => {
    console.log('Navegando a habitación:', roomId, 'con estado:', roomStatus);
    const url = roomStatus === 'occupied'
        ? `/panel/cuarto/${roomId}/detalles-checkout`
        : `/panel/cuarto/${roomId}`;
    router.visit(url);
};
</script>