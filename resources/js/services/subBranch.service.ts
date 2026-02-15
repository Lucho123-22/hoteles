import axios from 'axios';
import type { SubBranch, SubBranchSearchResponse } from '../interfaces/subBranch.interface';

const API_URL = '/sub-branches';

export const subBranchService = {
    /**
     * Buscar sub-branches (para select/autocomplete)
     */
    async search(filters?: {
        search?: string;
        is_active?: boolean;
    }): Promise<SubBranch[]> {
        const params = new URLSearchParams();
        
        if (filters?.search) params.append('search', filters.search);
        if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));

        const response = await axios.get<SubBranchSearchResponse>(
            `${API_URL}/search?${params.toString()}`
        );
        return response.data.data;
    },

    /**
     * Obtener todas las sub-branches activas
     */
    async getActive(): Promise<SubBranch[]> {
        return this.search({ is_active: true });
    }
};