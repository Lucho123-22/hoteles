<template>
    <div>
        <Button 
            v-if="!isTimerRunning"
            label="Iniciar Servicio" 
            icon="pi pi-play" 
            severity="success"
            size="large"
            class="w-full mb-4"
            :disabled="!canStart"
            @click="$emit('start')"
        />
        <Button 
            v-else
            label="Finalizar Servicio" 
            icon="pi pi-stop" 
            severity="danger"
            size="large"
            class="w-full mb-4"
            @click="$emit('finish')"
        />
    </div>
</template>

<script setup lang="ts">
import Button from 'primevue/button';

interface Props {
    isTimerRunning: boolean;
    canStart: boolean;
}

interface Emits {
    (e: 'start'): void;
    (e: 'finish'): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>