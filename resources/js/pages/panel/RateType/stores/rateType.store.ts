import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { rateTypeService } from '../services/rateType.service';
import type { RateType, RateTypeFormData, RateTypeFilters } from '../interfaces/rateType.interface';

export const useRateTypeStore = defineStore('rateType', () => {
    // State
    const rateTypes = ref<RateType[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const filters = ref<RateTypeFilters>({
        is_active: undefined,
        search: '',
        sort_by: 'name',
        sort_order: 'asc',
        with_pricing_ranges_count: false
    });

    // Getters
    const activeRateTypes = computed(() => 
        rateTypes.value.filter(rt => rt.is_active)
    );

    const hourlyRateType = computed(() => 
        rateTypes.value.find(rt => rt.code === 'HOURLY')
    );

    const dailyRateType = computed(() => 
        rateTypes.value.find(rt => rt.code === 'DAILY')
    );

    const nightlyRateType = computed(() => 
        rateTypes.value.find(rt => rt.code === 'NIGHTLY')
    );

    const totalRateTypes = computed(() => rateTypes.value.length);

    // Actions
    async function fetchRateTypes(customFilters?: RateTypeFilters) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const filtersToUse = customFilters || filters.value;
            const response = await rateTypeService.getAll(filtersToUse);
            rateTypes.value = response.data;
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar tipos de tarifa';
            console.error('Error fetching rate types:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchRateTypeById(id: string, withCount = false) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const rateType = await rateTypeService.getById(id, withCount);
            
            // Actualizar en el array local si existe
            const index = rateTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                rateTypes.value[index] = rateType;
            } else {
                rateTypes.value.push(rateType);
            }
            
            return rateType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar el tipo de tarifa';
            console.error('Error fetching rate type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createRateType(data: RateTypeFormData) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const newRateType = await rateTypeService.create(data);
            
            // Agregar al array local
            rateTypes.value.push(newRateType);
            
            return newRateType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al crear el tipo de tarifa';
            console.error('Error creating rate type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateRateType(id: string, data: Partial<RateTypeFormData>) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const updatedRateType = await rateTypeService.update(id, data);
            
            // Actualizar en el array local
            const index = rateTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                rateTypes.value[index] = updatedRateType;
            }
            
            return updatedRateType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al actualizar el tipo de tarifa';
            console.error('Error updating rate type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteRateType(id: string) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await rateTypeService.delete(id);
            
            // Eliminar del array local
            const index = rateTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                rateTypes.value.splice(index, 1);
            }
            
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al eliminar el tipo de tarifa';
            console.error('Error deleting rate type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function setFilters(newFilters: Partial<RateTypeFilters>) {
        filters.value = { ...filters.value, ...newFilters };
    }

    function resetFilters() {
        filters.value = {
            is_active: undefined,
            search: '',
            sort_by: 'name',
            sort_order: 'asc',
            with_pricing_ranges_count: false
        };
    }

    function getRateTypeById(id: string): RateType | undefined {
        return rateTypes.value.find(rt => rt.id === id);
    }

    function getRateTypeByCode(code: string): RateType | undefined {
        return rateTypes.value.find(rt => rt.code === code);
    }

    return {
        // State
        rateTypes,
        isLoading,
        error,
        filters,
        
        // Getters
        activeRateTypes,
        hourlyRateType,
        dailyRateType,
        nightlyRateType,
        totalRateTypes,
        
        // Actions
        fetchRateTypes,
        fetchRateTypeById,
        createRateType,
        updateRateType,
        deleteRateType,
        setFilters,
        resetFilters,
        getRateTypeById,
        getRateTypeByCode
    };
});