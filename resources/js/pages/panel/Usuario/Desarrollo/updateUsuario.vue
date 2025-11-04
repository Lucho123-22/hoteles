<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import Password from 'primevue/password';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    visible: Boolean,
    UsuarioId: Number
});
const emit = defineEmits(['update:visible', 'updated']);

const serverErrors = ref({});
const submitted = ref(false);
const toast = useToast();
const user = ref({});
const password = ref('');
const loading = ref(false);
const originalEmail = ref('');
const originalUsername = ref('');
const roles = ref([]);
const branches = ref([]);
const subBranches = ref([]);
const maxDate = ref(new Date());

const dialogVisible = ref(props.visible);
watch(() => props.visible, (val) => dialogVisible.value = val);
watch(dialogVisible, (val) => emit('update:visible', val));

watch(() => props.visible, (newVal) => {
    if (newVal && props.UsuarioId) {
        fetchUser();
    }
});

const fetchUser = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/usuarios/${props.UsuarioId}`);
        user.value = response.data.user;
        originalEmail.value = response.data.user.email;
        originalUsername.value = response.data.user.username;
        user.value.status = response.data.user.status === true ||
            response.data.user.status === 1 ||
            response.data.user.status === 'activo' ? true : false;
        
        // Ensure role_id is properly converted to number
        if (user.value.role_id) {
            user.value.role_id = Number(user.value.role_id);
        }
        
        // Handle branch and sub-branch logic
        if (user.value.sub_branch_id && !user.value.branch_id) {
            // If we have sub_branch_id but no branch_id, we need to find the branch_id
            await obtenerBranchIdDeSubBranch(user.value.sub_branch_id);
        } else if (user.value.branch_id) {
            // If we have branch_id, load sub-branches
            await cargarSubBranches(user.value.branch_id);
        }
        
        // Convert birth date string to Date object if needed
        if (user.value.nacimiento && typeof user.value.nacimiento === 'string') {
            // Handle different date formats (dd/mm/yyyy or dd-mm-yyyy)
            const fechaStr = user.value.nacimiento.replace(/-/g, '/');
            const fechaParts = fechaStr.split('/');
            if (fechaParts.length === 3) {
                const dia = parseInt(fechaParts[0]);
                const mes = parseInt(fechaParts[1]) - 1;
                const año = parseInt(fechaParts[2]);
                user.value.nacimiento = new Date(año, mes, dia);
            }
        }
        
        password.value = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el usuario', life: 3000 });
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const onBranchChange = () => {
    // Reset sub-branch when branch changes
    user.value.sub_branch_id = null;
    subBranches.value = [];
    
    if (user.value.branch_id) {
        cargarSubBranches(user.value.branch_id);
    }
};

const cargarSubBranches = async (branchId) => {
    try {
        const response = await axios.get(`/sub-branches/${branchId}`);
        if (response.data.success && response.data.data) {
            subBranches.value = response.data.data;
        } else {
            subBranches.value = [];
        }
    } catch (error) {
        console.error('Error al cargar sub-sucursales:', error);
        subBranches.value = [];
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'No se pudieron cargar las sub-sucursales', 
            life: 3000 
        });
    }
};

const obtenerBranchIdDeSubBranch = async (subBranchId) => {
    try {
        // First, we need to find which branch this sub-branch belongs to
        // We'll iterate through all branches to find the correct one
        for (const branch of branches.value) {
            try {
                const response = await axios.get(`/sub-branches/${branch.id}`);
                if (response.data.success && response.data.data) {
                    const subBranch = response.data.data.find(sb => sb.id === subBranchId);
                    if (subBranch) {
                        // Found the branch that contains this sub-branch
                        user.value.branch_id = branch.id;
                        subBranches.value = response.data.data;
                        return;
                    }
                }
            } catch (error) {
                // Continue with next branch if this one fails
                continue;
            }
        }
        
        // If we couldn't find the branch, show a warning
        toast.add({ 
            severity: 'warn', 
            summary: 'Advertencia', 
            detail: 'No se pudo determinar la sucursal de la sub-sucursal asignada', 
            life: 3000 
        });
    } catch (error) {
        console.error('Error al obtener branch_id de sub-branch:', error);
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'Error al cargar información de sucursales', 
            life: 3000 
        });
    }
};

const updateUser = async () => {
    submitted.value = true;
    serverErrors.value = {};

    try {
        const statusValue = user.value.status === true;
        const userData = {
            dni: user.value.dni,
            name: user.value.name,
            apellidos: user.value.apellidos,
            email: user.value.email,
            username: user.value.username,
            status: statusValue,
            role_id: user.value.role_id,
            branch_id: user.value.branch_id,
            sub_branch_id: user.value.sub_branch_id,
        };

        // Convert Date to string format for backend
        if (userData.nacimiento instanceof Date) {
            const dia = userData.nacimiento.getDate().toString().padStart(2, '0');
            const mes = (userData.nacimiento.getMonth() + 1).toString().padStart(2, '0');
            const año = userData.nacimiento.getFullYear();
            userData.nacimiento = `${dia}/${mes}/${año}`;
        } else if (user.value.nacimiento) {
            userData.nacimiento = user.value.nacimiento;
        }

        if (password.value && password.value.trim() !== '') {
            userData.password = password.value;
        }

        await axios.put(`/usuarios/${props.UsuarioId}`, userData);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Usuario actualizado correctamente',
            life: 3000
        });

        dialogVisible.value = false;
        emit('updated');
    } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
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
                detail: 'No se pudo actualizar el usuario',
                life: 3000
            });
        }
        console.error(error);
    }
};

const buscarPorDni = async () => {
    if (user.value.dni.length !== 8) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'El DNI debe tener 8 dígitos.',
            life: 3000
        })
        return
    }

    try {
        const response = await axios.get(`/consulta/${user.value.dni}`)
        const data = response.data

        if (data.success && data.data && data.data.nombre_completo) {
            const nombres = data.data.nombres ?? ''
            const apePat = data.data.apellido_paterno ?? ''
            const apeMat = data.data.apellido_materno ?? ''
            const nacimiento = data.data.fecha_nacimiento ?? ''

            user.value.name = nombres
            user.value.apellidos = `${apePat} ${apeMat}`
            
            // Convert string to Date object for DatePicker
            if (nacimiento) {
                const fechaParts = nacimiento.split('/');
                if (fechaParts.length === 3) {
                    const dia = parseInt(fechaParts[0]);
                    const mes = parseInt(fechaParts[1]) - 1;
                    const año = parseInt(fechaParts[2]);
                    user.value.nacimiento = new Date(año, mes, dia);
                }
            }
            
            user.value.username = generarUsername(nombres, apePat, apeMat, nacimiento)
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Advertencia',
                detail: 'No se encontraron datos para este DNI.',
                life: 3000
            })
        }
    } catch (error) {
        console.error(error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo consultar el DNI.',
            life: 3000
        })
    }
}

const generarUsername = (nombres, apePat, apeMat, nacimiento) => {
    const normalizar = (texto) => {
        return texto
            ?.replace(/ñ/g, 'n')
            .replace(/Ñ/g, 'n')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase() || '';
    };

    const partesNombre = normalizar(nombres).trim().split(/\s+/);
    const inicialNombre = partesNombre[0]?.charAt(0) || '';

    const apellido = normalizar(apePat).replace(/\s+/g, '');
    const segundoApellido = normalizar(apeMat).replace(/\s+/g, '').slice(0, 2) || '';
    
    // Handle both string and Date formats for birth date
    let dia = '00';
    if (typeof nacimiento === 'string' && nacimiento.includes('/')) {
        dia = nacimiento.split('/')[0]?.padStart(2, '0') || '00';
    } else if (user.value.nacimiento instanceof Date) {
        dia = user.value.nacimiento.getDate().toString().padStart(2, '0');
    }

    return `${inicialNombre}${apellido}${segundoApellido}${dia}`.toUpperCase();
};

onMounted(() => {
    // Load roles
    axios.get('/rol')
        .then(response => {
            roles.value = response.data.data;
            if (user.value && user.value.role_id) {
                user.value.role_id = Number(user.value.role_id);
            }
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los roles', life: 3000 });
        });
    
    // Load branches
    axios.get('/branches')
        .then(response => {
            if (response.data.data) {
                branches.value = response.data.data;
            }
        })
        .catch(error => {
            console.error('Error al cargar sucursales:', error);
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: 'No se pudieron cargar las sucursales', 
                life: 3000 
            });
        });
});
</script>

<template>
    <Dialog v-model:visible="dialogVisible" header="Editar Usuario" modal :closable="true" :closeOnEscape="true"
        :style="{ width: '600px' }">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-9">
                    <label for="dni" class="block font-bold mb-3">DNI <span class="text-red-500">*</span></label>
                    <InputText v-model="user.dni" maxlength="8" required @keyup.enter="buscarPorDni" fluid />
                    <small v-if="submitted && !user.dni" class="text-red-500">El DNI es obligatorio.</small>
                    <small v-else-if="serverErrors.dni" class="text-red-500">{{ serverErrors.dni[0] }}</small>
                </div>
                <div class="col-span-3">
                    <label for="status" class="block font-bold mb-2">Estado <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="user.status" :binary="true" inputId="status" />
                        <Tag :value="user.status ? 'Con Acceso' : 'Sin Acceso'"
                            :severity="user.status ? 'success' : 'danger'" />
                    </div>
                </div>
            </div>

            <div>
                <label for="name" class="block font-bold mb-3">Nombre completo <span
                        class="text-red-500">*</span></label>
                <InputText v-model="user.name" required disabled maxlength="100" fluid />
                <small v-if="submitted && !user.name" class="text-red-500">El nombre es obligatorio.</small>
                <small v-else-if="serverErrors.name" class="text-red-500">{{ serverErrors.name[0] }}</small>
            </div>

            <div>
                <label for="apellidos" class="block font-bold mb-3">Apellidos <span
                        class="text-red-500">*</span></label>
                <InputText v-model="user.apellidos" required disabled maxlength="100" fluid />
                <small v-if="submitted && !user.apellidos" class="text-red-500">Los apellidos son obligatorios.</small>
                <small v-else-if="serverErrors.apellidos" class="text-red-500">{{ serverErrors.apellidos[0] }}</small>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="nacimiento" class="block font-bold mb-3">Fecha de nacimiento <span
                            class="text-red-500">*</span></label>
                    <DatePicker v-model="user.nacimiento" dateFormat="dd/mm/yy" placeholder="dd/mm/aaaa" 
                        :maxDate="maxDate" showIcon fluid disabled />
                    <small v-if="submitted && !user.nacimiento" class="text-red-500">La fecha de nacimiento es obligatoria.</small>
                    <small v-else-if="serverErrors.nacimiento" class="text-red-500">{{ serverErrors.nacimiento[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="username" class="block font-bold mb-3">Usuario <span
                            class="text-red-500">*</span></label>
                    <InputText v-model="user.username" fluid disabled />
                    <small v-if="submitted && !user.username" class="text-red-500">El usuario es obligatorio.</small>
                    <small v-else-if="serverErrors.username" class="text-red-500">{{ serverErrors.username[0] }}</small>
                </div>
            </div>

            <div>
                <label for="email" class="block font-bold mb-3">Email <span class="text-red-500">*</span></label>
                <InputText v-model="user.email" maxlength="150" fluid />
                <small v-if="submitted && !user.email" class="text-red-500">El email es obligatorio.</small>
                <small v-else-if="serverErrors.email" class="text-red-500">{{ serverErrors.email[0] }}</small>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="password" class="block font-bold mb-3"><small>Dejar vacío para mantener la
                            actual</small></label>
                    <Password v-model="password" toggleMask placeholder="Nueva contraseña" :feedback="false"
                        inputId="password" fluid />
                    <small v-if="serverErrors.password" class="text-red-500">{{ serverErrors.password[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="role" class="block font-bold mb-3">Rol <span class="text-red-500">*</span></label>
                    <Select v-model="user.role_id" :options="roles" optionLabel="name" optionValue="id"
                        placeholder="Seleccione un rol" fluid />
                    <small v-if="submitted && !user.role_id" class="text-red-500">El rol es obligatorio.</small>
                    <small v-else-if="serverErrors.role_id" class="text-red-500">{{ serverErrors.role_id[0] }}</small>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label for="branch" class="block font-bold mb-3">Sucursal <span class="text-red-500">*</span></label>
                    <Select v-model="user.branch_id" :options="branches" optionLabel="name" optionValue="id"
                        placeholder="Seleccione una sucursal" fluid @change="onBranchChange" />
                    <small v-if="submitted && !user.branch_id" class="text-red-500">La sucursal es obligatoria.</small>
                    <small v-else-if="serverErrors.branch_id" class="text-red-500">{{ serverErrors.branch_id[0] }}</small>
                </div>
                <div class="col-span-6">
                    <label for="sub_branch" class="block font-bold mb-3">Sub-Sucursal <span class="text-red-500">*</span></label>
                    <Select v-model="user.sub_branch_id" :options="subBranches" optionLabel="name" optionValue="id"
                        placeholder="Seleccione una sub-sucursal" fluid :disabled="!user.branch_id" />
                    <small v-if="submitted && !user.sub_branch_id" class="text-red-500">La sub-sucursal es obligatoria.</small>
                    <small v-else-if="serverErrors.sub_branch_id" class="text-red-500">{{ serverErrors.sub_branch_id[0] }}</small>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" severity="secondary"/>
            <Button label="Guardar" icon="pi pi-check" @click="updateUser" :loading="loading" severity="contrast"/>
        </template>
    </Dialog>
</template>