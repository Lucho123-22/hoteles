import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { pricingRangeService } from '../services/pricingRange.service';
import type { 
    PricingRange, 
    PricingRangeFormData, 
    PricingRangeFilters,
    FindPriceParams,
    AvailableRangesParams
} from '../interfaces/pricingRange.interface';

export const usePricingRangeStore = defineStore('pricingRange', () => {
    // State
    const pricingRanges = ref<PricingRange[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const filters = ref<PricingRangeFilters>({
        sub_branch_id: undefined,
        room_type_id: undefined,
        rate_type_id: undefined,
        rate_type_code: undefined,
        is_active: true,
        only_effective: true,
        sort_by: 'time_from_minutes',
        sort_order: 'asc'
    });

    // Getters
    const activePricingRanges = computed(() => 
        pricingRanges.value.filter(pr => pr.is_active)
    );

    const effectivePricingRanges = computed(() => 
        pricingRanges.value.filter(pr => pr.is_effective && pr.is_active)
    );

    const hourlyPricingRanges = computed(() => 
        pricingRanges.value.filter(pr => pr.is_hourly_rate)
    );

    const dailyPricingRanges = computed(() => 
        pricingRanges.value.filter(pr => pr.is_daily_rate)
    );

    const nightlyPricingRanges = computed(() => 
        pricingRanges.value.filter(pr => pr.is_nightly_rate)
    );

    const priceStats = computed(() => {
        const prices = activePricingRanges.value.map(pr => pr.price);
        if (prices.length === 0) {
            return { min: 0, max: 0, avg: 0 };
        }
        return {
            min: Math.min(...prices),
            max: Math.max(...prices),
            avg: prices.reduce((a, b) => a + b, 0) / prices.length
        };
    });

    const totalPricingRanges = computed(() => pricingRanges.value.length);

    const groupedByRoomType = computed(() => {
        const grouped: Record<string, PricingRange[]> = {};
        pricingRanges.value.forEach(pr => {
            const roomTypeName = pr.room_type?.name || 'Sin tipo';
            if (!grouped[roomTypeName]) {
                grouped[roomTypeName] = [];
            }
            grouped[roomTypeName].push(pr);
        });
        return grouped;
    });

    const groupedByRateType = computed(() => {
        const grouped: Record<string, PricingRange[]> = {};
        pricingRanges.value.forEach(pr => {
            const rateTypeName = pr.rate_type?.name || 'Sin tipo';
            if (!grouped[rateTypeName]) {
                grouped[rateTypeName] = [];
            }
            grouped[rateTypeName].push(pr);
        });
        return grouped;
    });

    // Actions
    async function fetchPricingRanges(customFilters?: PricingRangeFilters) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const filtersToUse = customFilters || filters.value;
            const response = await pricingRangeService.getAll(filtersToUse);
            pricingRanges.value = response.data;
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar rangos de precio';
            console.error('Error fetching pricing ranges:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchPricingRangeById(id: string) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const pricingRange = await pricingRangeService.getById(id);
            
            // Actualizar en el array local si existe
            const index = pricingRanges.value.findIndex(pr => pr.id === id);
            if (index !== -1) {
                pricingRanges.value[index] = pricingRange;
            } else {
                pricingRanges.value.push(pricingRange);
            }
            
            return pricingRange;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar el rango de precio';
            console.error('Error fetching pricing range:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createPricingRange(data: PricingRangeFormData) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const newPricingRange = await pricingRangeService.create(data);
            
            // Agregar al array local
            pricingRanges.value.push(newPricingRange);
            
            return newPricingRange;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al crear el rango de precio';
            console.error('Error creating pricing range:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updatePricingRange(id: string, data: Partial<PricingRangeFormData>) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const updatedPricingRange = await pricingRangeService.update(id, data);
            
            // Actualizar en el array local
            const index = pricingRanges.value.findIndex(pr => pr.id === id);
            if (index !== -1) {
                pricingRanges.value[index] = updatedPricingRange;
            }
            
            return updatedPricingRange;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al actualizar el rango de precio';
            console.error('Error updating pricing range:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function deletePricingRange(id: string) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await pricingRangeService.delete(id);
            
            // Eliminar del array local
            const index = pricingRanges.value.findIndex(pr => pr.id === id);
            if (index !== -1) {
                pricingRanges.value.splice(index, 1);
            }
            
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al eliminar el rango de precio';
            console.error('Error deleting pricing range:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function findPrice(params: FindPriceParams) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const price = await pricingRangeService.findPrice(params);
            return price;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'No se encontró un precio para las condiciones especificadas';
            console.error('Error finding price:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchAvailableRanges(params: AvailableRangesParams) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await pricingRangeService.getAvailableRanges(params);
            return response.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar rangos disponibles';
            console.error('Error fetching available ranges:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function setFilters(newFilters: Partial<PricingRangeFilters>) {
        filters.value = { ...filters.value, ...newFilters };
    }

    function resetFilters() {
        filters.value = {
            sub_branch_id: undefined,
            room_type_id: undefined,
            rate_type_id: undefined,
            rate_type_code: undefined,
            is_active: true,
            only_effective: true,
            sort_by: 'time_from_minutes',
            sort_order: 'asc'
        };
    }

    function getPricingRangeById(id: string): PricingRange | undefined {
        return pricingRanges.value.find(pr => pr.id === id);
    }

    function getPricingRangesByRoomType(roomTypeId: string): PricingRange[] {
        return pricingRanges.value.filter(pr => pr.room_type_id === roomTypeId);
    }

    function getPricingRangesByRateType(rateTypeId: string): PricingRange[] {
        return pricingRanges.value.filter(pr => pr.rate_type_id === rateTypeId);
    }

    function getPricingRangesBySubBranch(subBranchId: string): PricingRange[] {
        return pricingRanges.value.filter(pr => pr.sub_branch_id === subBranchId);
    }

    return {
        // State
        pricingRanges,
        isLoading,
        error,
        filters,
        
        // Getters
        activePricingRanges,
        effectivePricingRanges,
        hourlyPricingRanges,
        dailyPricingRanges,
        nightlyPricingRanges,
        priceStats,
        totalPricingRanges,
        groupedByRoomType,
        groupedByRateType,
        
        // Actions
        fetchPricingRanges,
        fetchPricingRangeById,
        createPricingRange,
        updatePricingRange,
        deletePricingRange,
        findPrice,
        fetchAvailableRanges,
        setFilters,
        resetFilters,
        getPricingRangeById,
        getPricingRangesByRoomType,
        getPricingRangesByRateType,
        getPricingRangesBySubBranch
    };
});