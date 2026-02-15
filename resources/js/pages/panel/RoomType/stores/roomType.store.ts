import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { roomTypeService } from '../services/roomType.service';
import type { RoomType, RoomTypeFormData, RoomTypeFilters } from '../interfaces/roomType.interface';

export const useRoomTypeStore = defineStore('roomType', () => {
    // State
    const roomTypes = ref<RoomType[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const filters = ref<RoomTypeFilters>({
        is_active: undefined,
        search: '',
        category: undefined,
        sort_by: 'name',
        sort_order: 'asc',
        with_rooms_count: false,
        with_pricing_ranges_count: false
    });

    // Getters
    const activeRoomTypes = computed(() => 
        roomTypes.value.filter(rt => rt.is_active)
    );

    const roomTypesByCategory = computed(() => {
        const grouped: Record<string, RoomType[]> = {};
        roomTypes.value.forEach(rt => {
            const category = rt.category || 'Sin categoría';
            if (!grouped[category]) {
                grouped[category] = [];
            }
            grouped[category].push(rt);
        });
        return grouped;
    });

    const totalRoomTypes = computed(() => roomTypes.value.length);

    const categories = computed(() => {
        const cats = roomTypes.value
            .map(rt => rt.category)
            .filter((cat): cat is string => cat !== null && cat !== undefined);
        return [...new Set(cats)];
    });

    // Actions
    async function fetchRoomTypes(customFilters?: RoomTypeFilters) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const filtersToUse = customFilters || filters.value;
            const response = await roomTypeService.getAll(filtersToUse);
            roomTypes.value = response.data;
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar tipos de habitación';
            console.error('Error fetching room types:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchRoomTypeById(id: string, customFilters?: Partial<RoomTypeFilters>) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const roomType = await roomTypeService.getById(id, customFilters);
            
            // Actualizar en el array local si existe
            const index = roomTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                roomTypes.value[index] = roomType;
            } else {
                roomTypes.value.push(roomType);
            }
            
            return roomType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar el tipo de habitación';
            console.error('Error fetching room type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createRoomType(data: RoomTypeFormData) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const newRoomType = await roomTypeService.create(data);
            
            // Agregar al array local
            roomTypes.value.push(newRoomType);
            
            return newRoomType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al crear el tipo de habitación';
            console.error('Error creating room type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateRoomType(id: string, data: Partial<RoomTypeFormData>) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const updatedRoomType = await roomTypeService.update(id, data);
            
            // Actualizar en el array local
            const index = roomTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                roomTypes.value[index] = updatedRoomType;
            }
            
            return updatedRoomType;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al actualizar el tipo de habitación';
            console.error('Error updating room type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteRoomType(id: string) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await roomTypeService.delete(id);
            
            // Eliminar del array local
            const index = roomTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                roomTypes.value.splice(index, 1);
            }
            
            return response;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al eliminar el tipo de habitación';
            console.error('Error deleting room type:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function setFilters(newFilters: Partial<RoomTypeFilters>) {
        filters.value = { ...filters.value, ...newFilters };
    }

    function resetFilters() {
        filters.value = {
            is_active: undefined,
            search: '',
            category: undefined,
            sort_by: 'name',
            sort_order: 'asc',
            with_rooms_count: false,
            with_pricing_ranges_count: false
        };
    }

    function getRoomTypeById(id: string): RoomType | undefined {
        return roomTypes.value.find(rt => rt.id === id);
    }

    function getRoomTypeByCode(code: string): RoomType | undefined {
        return roomTypes.value.find(rt => rt.code === code);
    }

    function getRoomTypesByCategory(category: string): RoomType[] {
        return roomTypes.value.filter(rt => rt.category === category);
    }

    return {
        // State
        roomTypes,
        isLoading,
        error,
        filters,
        
        // Getters
        activeRoomTypes,
        roomTypesByCategory,
        totalRoomTypes,
        categories,
        
        // Actions
        fetchRoomTypes,
        fetchRoomTypeById,
        createRoomType,
        updateRoomType,
        deleteRoomType,
        setFilters,
        resetFilters,
        getRoomTypeById,
        getRoomTypeByCode,
        getRoomTypesByCategory
    };
});