<template>
    <div class="flex gap-2" :class="gridLayout ? 'flex-wrap' : ''" v-if="room.status !== 'maintenance'">
        <Button 
            icon="pi pi-eye" 
            severity="info"
            outlined
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="$emit('viewDetails')"
            v-tooltip.top="'Ver detalles'"
        />
        
        <!-- Botones para habitación OCUPADA -->
        <template v-if="room.status === 'occupied'">
            <Button 
                v-if="isExpired"
                icon="pi pi-clock" 
                severity="warning"
                outlined
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="$emit('extendTime')"
                v-tooltip.top="'Extender tiempo'"
            />
            <Button 
                v-if="isExpired"
                icon="pi pi-dollar" 
                severity="success"
                outlined
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="$emit('chargeExtra')"
                v-tooltip.top="'Cobrar tiempo extra'"
            />
            <Button 
                icon="pi pi-sign-out" 
                severity="danger"
                outlined
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="$emit('finishBooking')"
                v-tooltip.top="'Finalizar reserva'"
            />
        </template>

        <!-- Botón para habitación en LIMPIEZA -->
        <Button 
            v-if="room.status === 'cleaning'"
            icon="pi pi-check-circle" 
            severity="success"
            outlined
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="$emit('liberar')"
            v-tooltip.top="'Liberar habitación'"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';
import { useRoomManagementStore, isCheckoutExpired } from '../interface/Roommanagement';
import type { Room } from '../interface/Roommanagement';

const props = defineProps<{
    room: Room;
    gridLayout?: boolean;
}>();

defineEmits<{
    viewDetails: [];
    extendTime: [];
    chargeExtra: [];
    finishBooking: [];
    liberar: [];
}>();

const store = useRoomManagementStore();

const isExpired = computed(() => 
    isCheckoutExpired(props.room.check_out || null, store.currentTime)
);
</script>