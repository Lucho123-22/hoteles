<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    visible: Boolean,
    categoria: Object,
});
const emit = defineEmits(['update:visible', 'deleted']);

const toast = useToast();
const localVisible = ref(false);

watch(() => props.visible, (newVal) => {
    localVisible.value = newVal;
});

function closeDialog() {
    emit('update:visible', false);
}

async function deleteCategoria() {
    try {
        await axios.delete(`/categoria/${props.categoria.id}`);
        emit('deleted');
        closeDialog();
        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Categoría eliminada correctamente',
            life: 3000
        });
    } catch (error) {
        console.error(error);
        let errorMessage = 'Error eliminando la categoría';
        if (error.response) {
            errorMessage = error.response.data.message || errorMessage;
        }
        toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    }
}
</script>

<template>
    <Dialog v-model:visible="localVisible" :style="{ width: '450px' }" header="Confirmar" :modal="true"
        @update:visible="closeDialog">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="categoria">¿Estás seguro de eliminar esta categoría <b>{{ categoria.nombre }}</b>?</span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="closeDialog" severity="secondary"/>
            <Button label="Sí" icon="pi pi-check" @click="deleteCategoria" severity="danger"/>
        </template>
    </Dialog>
</template>
