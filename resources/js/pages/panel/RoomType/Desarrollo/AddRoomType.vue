<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        :header="isEditing ? 'Editar Tipo de Habitación' : 'Nuevo Tipo de Habitación'" 
        :modal="true"
        :style="{ width: '600px' }"
        :closable="!isLoading"
        :closeOnEscape="!isLoading"
    >
        <div class="flex flex-col gap-6">
            <!-- Nombre -->
            <div>
                <label for="name" class="block font-bold mb-3">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <InputText 
                    id="name" 
                    v-model.trim="form.name" 
                    :invalid="submitted && !form.name"
                    placeholder="Ej: Habitación Simple, Suite Premium"
                    :disabled="isLoading"
                    autofocus
                    fluid
                />
                <small v-if="submitted && !form.name" class="text-red-500">
                    El nombre es obligatorio.
                </small>
                <small v-else-if="errors.name" class="text-red-500">
                    {{ errors.name }}
                </small>
            </div>

            <!-- Código (Opcional - Se genera automáticamente) -->
            <div>
                <label for="code" class="block font-bold mb-3">
                    Código
                </label>
                <InputText 
                    id="code" 
                    v-model.trim="form.code" 
                    placeholder="Ej: SIMPLE, SUITE (opcional, se genera automáticamente)"
                    :disabled="isLoading"
                    @input="form.code = form.code?.toUpperCase()"
                    fluid
                />
                <small v-if="errors.code" class="text-red-500">
                    {{ errors.code }}
                </small>
                <small v-else class="text-surface-500">
                    Si no se especifica, se generará automáticamente (RT0001, RT0002, etc.)
                </small>
            </div>

            <!-- Capacidad -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="capacity" class="block font-bold mb-3">
                        Capacidad <span class="text-red-500">*</span>
                    </label>
                    <InputNumber 
                        id="capacity" 
                        v-model="form.capacity" 
                        :invalid="submitted && !form.capacity"
                        :min="1"
                        :max="20"
                        placeholder="Ej: 2"
                        :disabled="isLoading"
                        fluid
                    />
                    <small v-if="submitted && !form.capacity" class="text-red-500">
                        La capacidad es obligatoria.
                    </small>
                    <small v-else-if="errors.capacity" class="text-red-500">
                        {{ errors.capacity }}
                    </small>
                </div>

                <div>
                    <label for="max_capacity" class="block font-bold mb-3">
                        Capacidad Máxima
                    </label>
                    <InputNumber 
                        id="max_capacity" 
                        v-model="form.max_capacity" 
                        :min="form.capacity || 1"
                        :max="20"
                        placeholder="Ej: 3"
                        :disabled="isLoading"
                        fluid
                    />
                    <small v-if="errors.max_capacity" class="text-red-500">
                        {{ errors.max_capacity }}
                    </small>
                </div>
            </div>

            <!-- Categoría -->
            <div>
                <label for="category" class="block font-bold mb-3">
                    Categoría
                </label>
                <Select 
                    id="category" 
                    v-model="form.category" 
                    :options="categories"
                    placeholder="Seleccione una categoría"
                    :disabled="isLoading"
                    fluid
                />
                <small v-if="errors.category" class="text-red-500">
                    {{ errors.category }}
                </small>
            </div>

            <!-- Descripción -->
            <div>
                <label for="description" class="block font-bold mb-3">
                    Descripción
                </label>
                <Textarea 
                    id="description" 
                    v-model="form.description" 
                    rows="3"
                    placeholder="Descripción del tipo de habitación (opcional)"
                    :disabled="isLoading"
                    fluid
                />
                <small v-if="errors.description" class="text-red-500">
                    {{ errors.description }}
                </small>
            </div>

            <!-- Estado Activo -->
            <div class="flex items-center gap-2">
                <Checkbox 
                    inputId="is_active" 
                    v-model="form.is_active" 
                    :binary="true" 
                    :disabled="isLoading"
                />
                <label for="is_active" class="cursor-pointer">Activo</label>
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                icon="pi pi-times" 
                text
                @click="closeDialog"
                :disabled="isLoading"
                severity="secondary"
            />
            <Button 
                :label="isEditing ? 'Actualizar' : 'Guardar'" 
                icon="pi pi-check"
                @click="onSubmit"
                :loading="isLoading"
            />
        </template>
    </Dialog>
    <Toast />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useRoomTypeStore } from '../stores/roomType.store';
import type { RoomTypeFormData } from '../interfaces/roomType.interface';
import { ROOM_CATEGORIES } from '../interfaces/roomType.interface';

const emit = defineEmits<{
    refresh: [];
}>();

const roomTypeStore = useRoomTypeStore();
const toast = useToast();

const dialogVisible = ref(false);
const isLoading = ref(false);
const editingId = ref<string | null>(null);
const submitted = ref(false);
const isEditing = computed(() => editingId.value !== null);

const categories = ROOM_CATEGORIES;

const form = ref<RoomTypeFormData>({
    name: '',
    code: '',
    description: '',
    capacity: 2,
    max_capacity: undefined,
    category: undefined,
    is_active: true
});

const errors = ref<Partial<Record<keyof RoomTypeFormData, string>>>({});

const openEdit = async (id: string | number) => {
    if (id === 0 || id === '0') {
        // Nuevo
        resetForm();
        editingId.value = null;
        dialogVisible.value = true;
    } else {
        // Editar
        isLoading.value = true;
        try {
            const roomType = roomTypeStore.getRoomTypeById(String(id));
            
            if (roomType) {
                form.value = {
                    name: roomType.name,
                    code: roomType.code,
                    description: roomType.description || '',
                    capacity: roomType.capacity,
                    max_capacity: roomType.max_capacity || undefined,
                    category: roomType.category || undefined,
                    is_active: roomType.is_active
                };
                editingId.value = String(id);
                dialogVisible.value = true;
            } else {
                const loadedRoomType = await roomTypeStore.fetchRoomTypeById(String(id));
                form.value = {
                    name: loadedRoomType.name,
                    code: loadedRoomType.code,
                    description: loadedRoomType.description || '',
                    capacity: loadedRoomType.capacity,
                    max_capacity: loadedRoomType.max_capacity || undefined,
                    category: loadedRoomType.category || undefined,
                    is_active: loadedRoomType.is_active
                };
                editingId.value = String(id);
                dialogVisible.value = true;
            }
        } catch (error: any) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'No se pudo cargar el tipo de habitación',
                life: 3000
            });
        } finally {
            isLoading.value = false;
        }
    }
};

const validateForm = (): boolean => {
    errors.value = {};
    let isValid = true;

    if (!form.value.name?.trim()) {
        errors.value.name = 'El nombre es obligatorio';
        isValid = false;
    }

    if (!form.value.capacity || form.value.capacity < 1) {
        errors.value.capacity = 'La capacidad debe ser al menos 1';
        isValid = false;
    }

    if (form.value.max_capacity && form.value.max_capacity < form.value.capacity) {
        errors.value.max_capacity = 'La capacidad máxima debe ser mayor o igual a la capacidad estándar';
        isValid = false;
    }

    if (form.value.code && !/^[A-Z0-9_-]+$/.test(form.value.code)) {
        errors.value.code = 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos';
        isValid = false;
    }

    return isValid;
};

const onSubmit = async () => {
    submitted.value = true;
    
    if (!form.value.name?.trim() || !form.value.capacity) {
        return;
    }
    
    if (!validateForm()) return;

    isLoading.value = true;
    
    try {
        const dataToSubmit = { ...form.value };
        
        // Remover campos vacíos opcionales
        if (!dataToSubmit.code?.trim()) {
            delete dataToSubmit.code;
        }
        if (!dataToSubmit.description?.trim()) {
            delete dataToSubmit.description;
        }
        if (!dataToSubmit.max_capacity) {
            delete dataToSubmit.max_capacity;
        }
        if (!dataToSubmit.category) {
            delete dataToSubmit.category;
        }

        if (isEditing.value && editingId.value) {
            await roomTypeStore.updateRoomType(editingId.value, dataToSubmit);
            toast.add({
                severity: 'success',
                summary: 'Actualizado',
                detail: 'Tipo de habitación actualizado correctamente',
                life: 3000
            });
        } else {
            await roomTypeStore.createRoomType(dataToSubmit);
            toast.add({
                severity: 'success',
                summary: 'Creado',
                detail: 'Tipo de habitación creado correctamente',
                life: 3000
            });
        }
        closeDialog();
        emit('refresh');
    } catch (error: any) {
        const message = error.response?.data?.message || 'Ocurrió un error al guardar';
        const apiErrors = error.response?.data?.errors;
        
        if (apiErrors) {
            Object.keys(apiErrors).forEach(key => {
                if (key in form.value) {
                    errors.value[key as keyof RoomTypeFormData] = apiErrors[key][0];
                }
            });
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const closeDialog = () => {
    dialogVisible.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        name: '',
        code: '',
        description: '',
        capacity: 2,
        max_capacity: undefined,
        category: undefined,
        is_active: true
    };
    errors.value = {};
    editingId.value = null;
    submitted.value = false;
};

defineExpose({
    openEdit
});
</script>