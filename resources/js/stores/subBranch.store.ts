import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { subBranchService } from '../services/subBranch.service';
import type { SubBranch } from '../interfaces/subBranch.interface';

export const useSubBranchStore = defineStore('subBranch', () => {
    // State
    const subBranches = ref<SubBranch[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const activeSubBranches = computed(() => 
        subBranches.value.filter(sb => sb.is_active)
    );

    const totalSubBranches = computed(() => subBranches.value.length);

    // Actions
    async function fetchSubBranches(filters?: {
        search?: string;
        is_active?: boolean;
    }) {
        isLoading.value = true;
        error.value = null;
        
        try {
            subBranches.value = await subBranchService.search(filters);
            return subBranches.value;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar sucursales';
            console.error('Error fetching sub-branches:', err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchActiveSubBranches() {
        return fetchSubBranches({ is_active: true });
    }

    function getSubBranchById(id: string): SubBranch | undefined {
        return subBranches.value.find(sb => sb.id === id);
    }

    function getSubBranchByCode(code: string): SubBranch | undefined {
        return subBranches.value.find(sb => sb.code === code);
    }

    return {
        // State
        subBranches,
        isLoading,
        error,
        
        // Getters
        activeSubBranches,
        totalSubBranches,
        
        // Actions
        fetchSubBranches,
        fetchActiveSubBranches,
        getSubBranchById,
        getSubBranchByCode
    };
});