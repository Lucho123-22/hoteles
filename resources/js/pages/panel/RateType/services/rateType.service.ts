import axios from 'axios';
import type { 
    RateType,
    RateTypeFormData,
    RateTypeResponse,
    RateTypeCollection,
    RateTypeFilters 
} from '../interfaces/rateType.interface';

const API_URL = '/rate-types';

export const rateTypeService = {
    /**
     * Obtener todos los tipos de tarifa con filtros opcionales
     */
    async getAll(filters?: RateTypeFilters): Promise<RateTypeCollection> {
        const params = new URLSearchParams();
        
        if (filters?.search) params.append('search', filters.search);
        if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));
        if (filters?.code) params.append('code', filters.code);
        if (filters?.sort_by) params.append('sort_by', filters.sort_by);
        if (filters?.sort_order) params.append('sort_order', filters.sort_order);
        if (filters?.with_pricing_ranges_count) params.append('with_pricing_ranges_count', 'true');

        const response = await axios.get<RateTypeCollection>(`${API_URL}?${params.toString()}`);
        return response.data;
    },

    /**
     * Obtener un tipo de tarifa por ID
     */
    async getById(id: string, withCount = false): Promise<RateType> {
        const params = withCount ? '?with_pricing_ranges_count=true' : '';
        const response = await axios.get<RateTypeResponse>(`${API_URL}/${id}${params}`);
        return response.data.data;
    },

    /**
     * Crear nuevo tipo de tarifa
     */
    async create(data: RateTypeFormData): Promise<RateType> {
        const response = await axios.post<RateTypeResponse>(API_URL, data);
        return response.data.data;
    },

    /**
     * Actualizar tipo de tarifa existente
     */
    async update(id: string, data: Partial<RateTypeFormData>): Promise<RateType> {
        const response = await axios.put<RateTypeResponse>(`${API_URL}/${id}`, data);
        return response.data.data;
    },

    /**
     * Eliminar tipo de tarifa (soft delete)
     */
    async delete(id: string): Promise<{ message: string }> {
        const response = await axios.delete<{ message: string }>(`${API_URL}/${id}`);
        return response.data;
    }
};