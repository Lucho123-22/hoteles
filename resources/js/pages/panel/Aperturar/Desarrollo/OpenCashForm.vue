<template>
  <div>
    <div class="mb-4">
      <h2 class="text-3xl font-bold">Aperturar Caja</h2>
      <p class="mt-2">Selecciona una caja para iniciar tu turno</p>
    </div>

    <div class="grid">
      <!-- User Info Section -->
      <div class="col-12 lg:col-4">
        <div class="text-center">
          <Avatar icon="pi pi-user" size="xlarge" class="mb-3" />
          <p class="text-sm mb-2">Usuario Autenticado</p>
          <h3 class="text-xl font-bold mb-1">{{ authenticatedUser?.name }}</h3>
          <p class="text-sm text-gray-600">{{ authenticatedUser?.email }}</p>
        </div>
      </div>

      <!-- Cash Register Form Section -->
      <div class="col-12 lg:col-8">
        <div>
          <!-- Cash Register Dropdown -->
          <div class="field mb-4">
            <label for="cash-register" class="font-bold block mb-2">
              <i class="pi pi-calculator mr-2"></i>
              Cajas Disponibles
            </label>
            <Dropdown 
              id="cash-register" 
              :model-value="cashRegisterStore.selectedCashRegister"
              @update:model-value="cashRegisterStore.selectCashRegister"
              :options="cashRegisterStore.closedCashRegisters"
              optionLabel="name" 
              placeholder="Selecciona una caja para aperturar..." 
              class="w-full"
              :class="{ 'p-invalid': cashRegisterStore.errors?.cash_register }" 
              :loading="cashRegisterStore.loadingCashRegisters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="flex align-items-center">
                  <i class="pi pi-calculator mr-2"></i>
                  <span>{{ slotProps.value.name }}</span>
                  <Tag 
                    :value="slotProps.value.is_occupied ? 'OCUPADA' : 'DISPONIBLE'" 
                    :severity="slotProps.value.is_occupied ? 'danger' : 'success'"
                    class="ml-auto" 
                  />
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="flex align-items-center justify-content-between w-full">
                  <div class="flex align-items-center">
                    <i class="pi pi-calculator mr-2"></i>
                    <span>{{ slotProps.option.name }}</span>
                  </div>
                  <Tag 
                    :value="slotProps.option.is_occupied ? 'OCUPADA' : 'DISPONIBLE'" 
                    :severity="slotProps.option.is_occupied ? 'danger' : 'success'" 
                  />
                </div>
              </template>
            </Dropdown>
            <small v-if="cashRegisterStore.errors?.cash_register" class="p-error block mt-2">
              {{ cashRegisterStore.errors.cash_register }}
            </small>
          </div>

          <!-- Selected Cash Register Info -->
          <Message 
            v-if="cashRegisterStore.hasSelectedCashRegister" 
            severity="info" 
            :closable="false" 
            class="mb-4"
          >
            <div class="grid">
              <div class="col-6">
                <p class="font-bold mb-1">Sucursal</p>
                <p>{{ cashRegisterStore.selectedCashRegister?.sub_branch?.name || 'N/A' }}</p>
              </div>
              <div class="col-6">
                <p class="font-bold mb-1">Estado Actual</p>
                <Tag 
                  :value="cashRegisterStore.selectedCashRegister?.is_occupied ? 'OCUPADA' : 'DISPONIBLE'"
                  :severity="cashRegisterStore.selectedCashRegister?.is_occupied ? 'danger' : 'success'" 
                />
              </div>
            </div>
          </Message>

          <!-- Opening Amount Input -->
          <div v-if="cashRegisterStore.hasSelectedCashRegister" class="field mb-4">
            <label for="opening-amount" class="font-bold block mb-2">
              <i class="pi pi-money-bill mr-2"></i>
              Monto de Apertura
            </label>
            <InputNumber 
              id="opening-amount" 
              :model-value="cashRegisterStore.openingAmount"
              @update:model-value="cashRegisterStore.setOpeningAmount"
              mode="currency" 
              currency="PEN" 
              locale="es-PE"
              placeholder="Ingrese el monto inicial de caja" 
              class="w-full"
              :class="{ 'p-invalid': cashRegisterStore.errors?.opening_amount }" 
              :min="0" 
              :minFractionDigits="2" 
              :maxFractionDigits="2" 
            />
            <small v-if="cashRegisterStore.errors?.opening_amount" class="p-error block mt-2">
              {{ cashRegisterStore.errors.opening_amount }}
            </small>
            <small v-else class="text-gray-600 block mt-2">
              Ingrese el monto con el que iniciará la caja
            </small>
          </div>

          <!-- Submit Button -->
          <Button 
            label="Aperturar Caja" 
            icon="pi pi-lock-open" 
            @click="cashRegisterStore.openCashRegister" 
            :loading="cashRegisterStore.isOpening"
            :disabled="!cashRegisterStore.canOpenCashRegister" 
            severity="contrast" 
            class="w-full" 
          />
        </div>
      </div>
    </div>

    <!-- Info Message -->
    <Message severity="warn" :closable="false" class="mt-4">
      <template #icon>
        <i class="pi pi-info-circle text-2xl"></i>
      </template>
      <div>
        <p class="font-bold mb-2">Información Importante</p>
        <ul class="pl-4">
          <li>Solo puedes aperturar cajas que estén <strong>"disponibles"</strong></li>
          <li>Una vez aperturada, la caja quedará asignada a tu usuario</li>
          <li>Podrás registrar movimientos y transacciones en la caja</li>
        </ul>
      </div>
    </Message>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import { useCashRegisterStore } from './useCashRegisterStore';

interface User {
  id: string;
  name: string;
  email: string;
  sub_branch_id: string;
}

const page = usePage();
const authenticatedUser = computed(() => page.props.auth?.user as User);

// Inicializar el store ANTES de usar sus propiedades
const cashRegisterStore = useCashRegisterStore();

onMounted(async () => {
  await cashRegisterStore.loadCashRegisters();
});
</script>