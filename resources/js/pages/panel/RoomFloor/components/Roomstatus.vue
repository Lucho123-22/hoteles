<template>
    <div class="text-center mb-6">
        <h3 class="text-xl font-bold text-surface-900 dark:text-surface-0 mb-2">
            Estado de la Habitación
        </h3>
        <div class="inline-flex items-center justify-center w-full">
            <Tag 
                :value="statusLabel" 
                :severity="statusSeverity"
                class="text-lg px-6 py-3"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';
import type { RoomData } from '../interface/Useroomservicestore';

interface Props {
    roomData?: RoomData | null;
}

const props = defineProps<Props>();

const statusLabel = computed(() => {
    const labels: Record<string, string> = {
        available: 'Disponible',
        occupied: 'Ocupada',
        maintenance: 'Mantenimiento',
        cleaning: 'Limpieza'
    };
    return props.roomData?.status ? labels[props.roomData.status] : 'Desconocido';
});

const statusSeverity = computed(() => {
    const severities: Record<string, string> = {
        available: 'success',
        occupied: 'danger',
        maintenance: 'warn',
        cleaning: 'info'
    };
    return props.roomData?.status ? severities[props.roomData.status] : 'secondary';
});
</script>