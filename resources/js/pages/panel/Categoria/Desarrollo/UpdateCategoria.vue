<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    visible: Boolean,
    categoriaId: Number
});
const emit = defineEmits(['update:visible', 'updated']);

const serverErrors = ref({});
const submitted = ref(false);
const toast = useToast();
const loading = ref(false);

const dialogVisible = ref(props.visible);
watch(() => props.visible, (val) => dialogVisible.value = val);
watch(dialogVisible, (val) => emit('update:visible', val));

watch(() => props.visible, (newVal) => {
    if (newVal && props.categoriaId) {
        fetchCategoria();
    }
});

const categoria = ref({
    nombre: '',
    estado: false
});

const fetchCategoria = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/categoria/${props.categoriaId}`);
        const data = response.data.category;

        categoria.value.nombre = data.nombre;
        categoria.value.estado = data.estado;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar la categoría',
            life: 3000
        });
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const updateCategoria = async () => {
    submitted.value = true;
    serverErrors.value = {};

    try {
        const categoriaData = {
            name: categoria.value.nombre,
            is_active: categoria.value.estado === true,
        };

        await axios.put(`/categoria/${props.categoriaId}`, categoriaData);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Categoría actualizada correctamente',
            life: 3000
        });

        dialogVisible.value = false;
        emit('updated');
    } catch (error) {
        if (error.response && error.response.data?.errors) {
            serverErrors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: 'Revisa los campos e intenta nuevamente.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo actualizar la categoría',
                life: 3000
            });
        }
        console.error(error);
    }
};
</script>

<template>
    <Dialog v-model:visible="dialogVisible" header="Editar Categoría" modal :closable="true" :closeOnEscape="true"
        :style="{ width: '600px' }">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-9">
                    <label for="nombre" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                    <InputText
                        v-model="categoria.nombre"
                        required
                        maxlength="100"
                        fluid
                        :class="{ 'p-invalid': serverErrors.nombre }"
                    />
                    <small v-if="serverErrors.nombre" class="p-error">{{ serverErrors.nombre[0] }}</small>
                </div>

                <div class="col-span-3">
                    <label for="estado" class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="categoria.estado" :binary="true" inputId="estado" />
                        <Tag :value="categoria.estado ? 'Activo' : 'Inactivo'" :severity="categoria.estado ? 'success' : 'danger'" />
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="updateCategoria" :loading="loading" severity="contrast"/>
        </template>
    </Dialog>
</template>
