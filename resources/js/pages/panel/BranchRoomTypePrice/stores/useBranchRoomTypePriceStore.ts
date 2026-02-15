// stores/useBranchRoomTypePriceStore.ts

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import {
  branchRoomTypePriceService,
  subBranchService,
  roomTypeService,
  rateTypeService,
} from '../services/branchRoomTypePriceService';
import type {
  BranchRoomTypePrice,
  BranchRoomTypePriceFormData,
  SubBranch,
  RoomType,
  RateType,
  FilterParams,
  PricingOptionsResponse,
  CalculatePriceRequest,
  CalculatePriceResponse,
} from '../interfaces';

export const useBranchRoomTypePriceStore = defineStore('branchRoomTypePrice', () => {
  const prices = ref<BranchRoomTypePrice[]>([]);
  const currentPrice = ref<BranchRoomTypePrice | null>(null);
  const subBranches = ref<SubBranch[]>([]);
  const roomTypes = ref<RoomType[]>([]);
  const rateTypes = ref<RateType[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const activePrices = computed(() => prices.value.filter((price) => price.is_active));
  const currentEffectivePrices = computed(() =>
    prices.value.filter((price) => price.is_currently_effective)
  );
  const expiredPrices = computed(() => prices.value.filter((price) => price.has_expired));

  async function loadOptions() {
    loading.value = true;
    error.value = null;

    try {
      console.log('Cargando opciones...');
      const [subBranchesData, roomTypesData, rateTypesData] = await Promise.all([
        subBranchService.search(),
        roomTypeService.getOptions(),
        rateTypeService.getOptions(),
      ]);

      console.log('SubBranches obtenidas:', subBranchesData);
      console.log('RoomTypes obtenidos:', roomTypesData);
      console.log('RateTypes obtenidos:', rateTypesData);

      subBranches.value = subBranchesData;
      roomTypes.value = roomTypesData;
      rateTypes.value = rateTypesData;
    } catch (err: any) {
      console.error('Error al cargar opciones:', err);
      error.value = err.response?.data?.message || 'Error al cargar las opciones';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPrices(filters?: FilterParams) {
    loading.value = true;
    error.value = null;

    try {
      console.log('Fetching prices con filtros:', filters);
      const result = await branchRoomTypePriceService.getAll(filters);
      console.log('Resultado de la API:', result);
      prices.value = result;
      console.log('Precios almacenados en store:', prices.value);
    } catch (err: any) {
      console.error('Error al obtener precios:', err);
      error.value = err.response?.data?.message || 'Error al obtener los precios';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPriceById(id: string) {
    loading.value = true;
    error.value = null;

    try {
      currentPrice.value = await branchRoomTypePriceService.getById(id);
      return currentPrice.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al obtener el precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createPrice(data: BranchRoomTypePriceFormData) {
    loading.value = true;
    error.value = null;

    try {
      const response = await branchRoomTypePriceService.create(data);
      prices.value.unshift(response.data);
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al crear la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updatePrice(id: string, data: BranchRoomTypePriceFormData) {
    loading.value = true;
    error.value = null;

    try {
      const response = await branchRoomTypePriceService.update(id, data);
      const index = prices.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        prices.value[index] = response.data;
      }
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al actualizar la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deletePrice(id: string) {
    loading.value = true;
    error.value = null;

    try {
      const response = await branchRoomTypePriceService.delete(id);
      prices.value = prices.value.filter((p) => p.id !== id);
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al eliminar la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function getPricingOptions(params: {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    date?: string;
  }): Promise<PricingOptionsResponse> {
    loading.value = true;
    error.value = null;

    try {
      return await branchRoomTypePriceService.getPricingOptions(params);
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al obtener las opciones de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function calculatePrice(data: CalculatePriceRequest): Promise<CalculatePriceResponse> {
    loading.value = true;
    error.value = null;

    try {
      return await branchRoomTypePriceService.calculatePrice(data);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al calcular el precio';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError() {
    error.value = null;
  }

  function $reset() {
    prices.value = [];
    currentPrice.value = null;
    subBranches.value = [];
    roomTypes.value = [];
    rateTypes.value = [];
    loading.value = false;
    error.value = null;
  }

  return {
    prices,
    currentPrice,
    subBranches,
    roomTypes,
    rateTypes,
    loading,
    error,
    activePrices,
    currentEffectivePrices,
    expiredPrices,
    loadOptions,
    fetchPrices,
    fetchPriceById,
    createPrice,
    updatePrice,
    deletePrice,
    getPricingOptions,
    calculatePrice,
    clearError,
    $reset,
  };
});