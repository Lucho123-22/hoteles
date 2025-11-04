    <script setup>
    import { ref, onMounted, watch } from 'vue';
    import Button from 'primevue/button';
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import InputText from 'primevue/inputtext';
    import Tag from 'primevue/tag';
    import axios from 'axios';
    import { debounce } from 'lodash';
    import DeleteCategoria from './DeleteCategoria.vue';
    import UpdateCategoria from './UpdateCategoria.vue';
    import Select from 'primevue/select';

    const dt = ref();
    const categorias = ref([]);
    const selectedCategorias = ref();
    const loading = ref(false);
    const globalFilterValue = ref('');
    const deleteCategoriaDialog = ref(false);
    const categoria = ref({});
    const selectedCategoriaId = ref(null);
    const selectedEstadoCategoria = ref(null);
    const updateCategoriaDialog = ref(false);
    const currentPage = ref(1);

    const props = defineProps({
        refresh: {
            type: Number,
            required: true
        }
    });
    watch(() => props.refresh, () => {
        loadCategoria();
    });
    watch(() => selectedEstadoCategoria.value, () => {
        currentPage.value = 1;
        loadCategoria();
    });

    function editCategoria(categoria) {
        selectedCategoriaId.value = categoria.id;
        updateCategoriaDialog.value = true;
    }

    const estadoCategoriaOptions = ref([
        { nombre: 'TODOS', value: '' },
        { nombre: 'ACTIVOS', value: 1 },
        { nombre: 'INACTIVOS', value: 0 },
    ]);

    function handleCategoriaUpdated() {
        loadCategoria();
    }

    function confirmDeleteCategoria(selected) {
        categoria.value = selected;
        deleteCategoriaDialog.value = true;
    }

    const pagination = ref({
        currentPage: 1,
        perPage: 15,
        total: 0
    });

    const filters = ref({
        estado: null,
        online: null
    });

    function handleCategoriaDeleted() {
        loadCategoria();
    }

    const loadCategoria = async () => {
        loading.value = true;
        try {
            const params = {
                page: pagination.value.currentPage,
                per_page: pagination.value.perPage,
                search: globalFilterValue.value,
                state: filters.value.estado,
            };
            if (selectedEstadoCategoria.value !== null && selectedEstadoCategoria.value.value !== '') {
                params.state = selectedEstadoCategoria.value.value;
            }

            const response = await axios.get('/categoria', { params });

            categorias.value = response.data.data;
            pagination.value.currentPage = response.data.meta.current_page;
            pagination.value.total = response.data.meta.total;
        } catch (error) {
            console.error('Error al cargar categoría:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las categorías', life: 3000 });
        } finally {
            loading.value = false;
        }
    };

    const onPage = (event) => {
        pagination.value.currentPage = event.page + 1;
        pagination.value.perPage = event.rows;
        loadCategoria();
    };

    const getSeverity = (value) => {
        if (value === true || value === '1') return 'success';
        if (value === false || value === '0') return 'danger';
        return null;
    };

    const onGlobalSearch = debounce(() => {
        pagination.value.currentPage = 1;
        loadCategoria();
    }, 500);

    onMounted(() => {
        loadCategoria();
    });
    </script>

    <template>
        <DataTable ref="dt" v-model:selection="selectedCategorias" :value="categorias" dataKey="id" :paginator="true"
            :rows="pagination.perPage" :totalRecords="pagination.total" :loading="loading" :lazy="true" @page="onPage"
            :rowsPerPageOptions="[15, 20, 25]" scrollable scrollHeight="574px"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} categorías" class="p-datatable-sm">

            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">CATEGORÍAS</h4>
                    <div class="flex flex-wrap gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                        </IconField>
                        <Select v-model="selectedEstadoCategoria" :options="estadoCategoriaOptions" optionLabel="nombre"
                            placeholder="Estado" class="w-full md:w-auto" />
                        <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadCategoria" severity="contrast" />
                    </div>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 1rem" :exportable="false"></Column>
            <Column field="codigo" header="Codigo" sortable style="min-width: 5rem"></Column>
            <Column field="nombre" header="Nombre" sortable style="min-width: 15rem"></Column>
            <Column field="creacion" header="Creación" sortable style="min-width: 13rem"></Column>
            <Column field="actualizacion" header="Actualización" sortable style="min-width: 13rem"></Column>
            <Column field="estado" header="Estado" sortable style="min-width: 4rem">
                <template #body="{ data }">
                    <Tag :value="data.estado ? 'Activo' : 'Inactivo'" :severity="getSeverity(data.estado)" />
                </template>
            </Column>
            <Column field="accions" header="Acciones" :exportable="false" style="min-width: 8rem">
                <template #body="slotProps">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editCategoria(slotProps.data)" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteCategoria(slotProps.data)" />
                </template>
            </Column>
        </DataTable>

        <DeleteCategoria
            v-model:visible="deleteCategoriaDialog"
            :categoria="categoria"
            @deleted="handleCategoriaDeleted"
        />
        <UpdateCategoria
            v-model:visible="updateCategoriaDialog"
            :categoriaId="selectedCategoriaId"
            @updated="handleCategoriaUpdated"
        />
    </template>
