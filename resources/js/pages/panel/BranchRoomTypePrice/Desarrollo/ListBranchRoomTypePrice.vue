<template>
    <!-- Tabla -->
    <DataTable
      ref="dt"
      :value="prices"
      :loading="loading"
      stripedRows
      responsiveLayout="scroll"
      :paginator="true"
      :rows="10"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
      currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros"
      dataKey="id"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Configuraciones de Precios</h4>
        </div>
      </template>

      <template #empty>
        <div class="text-center p-4">
          No se encontraron configuraciones de precio
        </div>
      </template>

      <Column field="sub_branch.name" header="Sucursal" sortable style="min-width: 12rem">
        <template #body="{ data }">
          <div>
            <div class="font-semibold">{{ data.sub_branch?.name }}</div>
            <small class="text-muted">{{ data.sub_branch?.code }}</small>
          </div>
        </template>
      </Column>

      <Column field="room_type.name" header="Tipo Habitación" sortable style="min-width: 14rem">
        <template #body="{ data }">
          <div>
            <div class="font-semibold">{{ data.room_type?.name }}</div>
            <small class="text-muted">{{ data.room_type?.category }}</small>
          </div>
        </template>
      </Column>

      <Column field="rate_type.name" header="Tipo Tarifa" sortable style="min-width: 10rem">
        <template #body="{ data }">
          <Tag :value="data.rate_type?.name" severity="info" />
        </template>
      </Column>

      <Column field="effective_from" header="Vigente Desde" sortable style="min-width: 10rem">
        <template #body="{ data }">
          {{ formatDate(data.effective_from) }}
        </template>
      </Column>

      <Column field="effective_to" header="Vigente Hasta" sortable style="min-width: 10rem">
        <template #body="{ data }">
          {{ data.effective_to ? formatDate(data.effective_to) : 'Sin límite' }}
        </template>
      </Column>

      <Column field="is_active" header="Estado" sortable style="min-width: 8rem">
        <template #body="{ data }">
          <Tag
            :value="data.is_active ? 'Activo' : 'Inactivo'"
            :severity="data.is_active ? 'success' : 'danger'"
          />
        </template>
      </Column>

      <Column field="is_currently_effective" header="Vigencia" sortable style="min-width: 8rem">
        <template #body="{ data }">
          <Tag
            v-if="data.is_currently_effective"
            value="Vigente"
            severity="success"
          />
          <Tag
            v-else-if="data.has_expired"
            value="Expirado"
            severity="warn"
          />
          <Tag
            v-else
            value="Futuro"
            severity="secondary"
          />
        </template>
      </Column>

      <Column header="Acciones" :exportable="false" style="min-width: 10rem">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button
              icon="pi pi-eye"
              severity="info"
              size="small"
              variant="outlined"
              rounded
              v-tooltip.top="'Ver detalles'"
              @click="viewPrice(data)"
            />
            <Button
              icon="pi pi-pencil"
              severity="success"
              size="small"
              variant="outlined"
              rounded
              v-tooltip.top="'Editar'"
              @click="editPrice(data)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              size="small"
              variant="outlined"
              rounded
              v-tooltip.top="'Eliminar'"
              @click="confirmDeletePrice(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Modal de Detalles -->
    <Dialog
      v-model:visible="showDetailModal"
      header="Detalles de Configuración"
      :modal="true"
      :style="{ width: '900px' }"
      :closable="true"
    >
      <div v-if="selectedPrice">
        <div class="grid">
          <!-- Información General -->
          <div class="col-12 md:col-6">
            <Card>
              <template #title>Información General</template>
              <template #content>
                <div class="detail-row">
                  <span class="detail-label">Sucursal:</span>
                  <span class="detail-value">
                    {{ selectedPrice.sub_branch?.name }}
                    <br>
                    <small>{{ selectedPrice.sub_branch?.code }}</small>
                  </span>
                </div>
                <Divider />
                <div class="detail-row">
                  <span class="detail-label">Tipo de Habitación:</span>
                  <span class="detail-value">
                    {{ selectedPrice.room_type?.name }}
                    <br>
                    <small>{{ selectedPrice.room_type?.category }}</small>
                  </span>
                </div>
                <Divider />
                <div class="detail-row">
                  <span class="detail-label">Tipo de Tarifa:</span>
                  <span class="detail-value">
                    {{ selectedPrice.rate_type?.name }}
                  </span>
                </div>
              </template>
            </Card>
          </div>

          <!-- Vigencia -->
          <div class="col-12 md:col-6">
            <Card>
              <template #title>Vigencia</template>
              <template #content>
                <div class="detail-row">
                  <span class="detail-label">Desde:</span>
                  <span class="detail-value">{{ formatDate(selectedPrice.effective_from) }}</span>
                </div>
                <Divider />
                <div class="detail-row">
                  <span class="detail-label">Hasta:</span>
                  <span class="detail-value">
                    {{ selectedPrice.effective_to ? formatDate(selectedPrice.effective_to) : 'Sin límite' }}
                  </span>
                </div>
                <Divider />
                <div class="detail-row">
                  <span class="detail-label">Estado:</span>
                  <Tag
                    :value="selectedPrice.is_active ? 'Activo' : 'Inactivo'"
                    :severity="selectedPrice.is_active ? 'success' : 'danger'"
                  />
                </div>
                <Divider />
                <div class="detail-row">
                  <span class="detail-label">Vigencia Actual:</span>
                  <Tag
                    v-if="selectedPrice.is_currently_effective"
                    value="Vigente"
                    severity="success"
                  />
                  <Tag
                    v-else-if="selectedPrice.has_expired"
                    value="Expirado"
                    severity="warn"
                  />
                  <Tag
                    v-else
                    value="Futuro"
                    severity="secondary"
                  />
                </div>
              </template>
            </Card>
          </div>

          <!-- Rangos de Precio -->
          <div v-if="selectedPrice.pricing_ranges && selectedPrice.pricing_ranges.length > 0" class="col-12">
            <Card>
              <template #title>Rangos de Precio</template>
              <template #content>
                <DataTable :value="selectedPrice.pricing_ranges" stripedRows>
                  <Column field="formatted_time" header="Rango de Tiempo" />
                  <Column field="price" header="Precio">
                    <template #body="{ data }">
                      <span class="font-semibold text-green-600">S/ {{ data.price }}</span>
                    </template>
                  </Column>
                </DataTable>
              </template>
            </Card>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Modal de Confirmación de Eliminación -->
    <Dialog
      v-model:visible="showDeleteModal"
      header="Confirmar Eliminación"
      :modal="true"
      :style="{ width: '450px' }"
      :closable="true"
    >
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle !text-3xl text-orange-500 mb-3"></i>
        <p class="mb-3">
          ¿Estás seguro de que deseas eliminar esta configuración de precio?
        </p>
        <div v-if="selectedPrice" class="surface-100 p-3 border-round">
          <p class="m-0 mb-2"><strong>Sucursal:</strong> {{ selectedPrice.sub_branch?.name }}</p>
          <p class="m-0 mb-2"><strong>Tipo Habitación:</strong> {{ selectedPrice.room_type?.name }}</p>
          <p class="m-0"><strong>Tipo Tarifa:</strong> {{ selectedPrice.rate_type?.name }}</p>
        </div>
      </div>
      <template #footer>
        <Button
          label="Cancelar"
          icon="pi pi-times"
          @click="showDeleteModal = false"
          severity="secondary"
          variant="text"
        />
        <Button
          label="Eliminar"
          icon="pi pi-check"
          @click="deletePrice"
          severity="danger"
        />
      </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { storeToRefs } from 'pinia'; // ⭐ IMPORTANTE
import { useBranchRoomTypePriceStore } from '../stores/useBranchRoomTypePriceStore';
import type { BranchRoomTypePrice, FilterParams } from '../interfaces';
import { useToast } from 'primevue/usetoast';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';

const toast = useToast();
const store = useBranchRoomTypePriceStore();
const dt = ref();

// ⭐ USAR storeToRefs
const { prices, loading } = storeToRefs(store);

const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedPrice = ref<BranchRoomTypePrice | undefined>(undefined);

const emit = defineEmits<{
  (e: 'edit', price: BranchRoomTypePrice): void;
}>();

const props = defineProps<{
  filters?: FilterParams;
}>();

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('es-PE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
}

function viewPrice(price: BranchRoomTypePrice) {
  selectedPrice.value = price;
  showDetailModal.value = true;
}

function editPrice(price: BranchRoomTypePrice) {
  emit('edit', price);
}

function confirmDeletePrice(price: BranchRoomTypePrice) {
  selectedPrice.value = price;
  showDeleteModal.value = true;
}

async function deletePrice() {
  if (!selectedPrice.value) return;

  try {
    await store.deletePrice(selectedPrice.value.id);
    showDeleteModal.value = false;
    selectedPrice.value = undefined;
    toast.add({ 
      severity: 'success', 
      summary: 'Éxito', 
      detail: 'Configuración eliminada correctamente', 
      life: 3000 
    });
  } catch (error) {
    console.error('Error al eliminar:', error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudo eliminar la configuración', 
      life: 3000 
    });
  }
}

watch(() => props.filters, async (newFilters) => {
  console.log('🔄 Filtros cambiados:', newFilters);
  await store.fetchPrices(newFilters);
}, { deep: true });

onMounted(async () => {
  console.log('🔵 Montando ListBranchRoomTypePrice...');
  await store.fetchPrices(props.filters);
  console.log('✅ Precios cargados:', prices.value?.length);
});
</script>

<!-- El template permanece igual -->