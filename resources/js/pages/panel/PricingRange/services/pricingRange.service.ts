import axios from 'axios';
import type { 
    PricingRange, 
    PricingRangeFormData, 
    PricingRangeResponse, 
    PricingRangeCollection,
    PricingRangeFilters,
    FindPriceParams,
    AvailableRangesParams
} from '../interfaces/pricingRange.interface';

const API_URL = '/pricing-ranges';

export const pricingRangeService = {
    /**
     * Obtener todos los rangos de precio con filtros opcionales
     */
    async getAll(filters?: PricingRangeFilters): Promise<PricingRangeCollection> {
        const params = new URLSearchParams();
        
        if (filters?.sub_branch_id) params.append('sub_branch_id', filters.sub_branch_id);
        if (filters?.room_type_id) params.append('room_type_id', filters.room_type_id);
        if (filters?.rate_type_id) params.append('rate_type_id', filters.rate_type_id);
        if (filters?.rate_type_code) params.append('rate_type_code', filters.rate_type_code);
        if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));
        if (filters?.only_effective) params.append('only_effective', 'true');
        if (filters?.minutes !== undefined) params.append('minutes', String(filters.minutes));
        if (filters?.sort_by) params.append('sort_by', filters.sort_by);
        if (filters?.sort_order) params.append('sort_order', filters.sort_order);

        const response = await axios.get<PricingRangeCollection>(`${API_URL}?${params.toString()}`);
        return response.data;
    },

    /**
     * Obtener un rango de precio por ID
     */
    async getById(id: string): Promise<PricingRange> {
        const response = await axios.get<PricingRangeResponse>(`${API_URL}/${id}`);
        return response.data.data;
    },

    /**
     * Crear nuevo rango de precio
     */
    async create(data: PricingRangeFormData): Promise<PricingRange> {
        const response = await axios.post<PricingRangeResponse>(API_URL, data);
        return response.data.data;
    },

    /**
     * Actualizar rango de precio existente
     */
    async update(id: string, data: Partial<PricingRangeFormData>): Promise<PricingRange> {
        const response = await axios.put<PricingRangeResponse>(`${API_URL}/${id}`, data);
        return response.data.data;
    },

    /**
     * Eliminar rango de precio (soft delete)
     */
    async delete(id: string): Promise<{ message: string }> {
        const response = await axios.delete<{ message: string }>(`${API_URL}/${id}`);
        return response.data;
    },

    /**
     * Buscar precio específico según condiciones
     */
    async findPrice(params: FindPriceParams): Promise<PricingRange> {
        const searchParams = new URLSearchParams();
        searchParams.append('sub_branch_id', params.sub_branch_id);
        searchParams.append('room_type_id', params.room_type_id);
        searchParams.append('rate_type_id', params.rate_type_id);
        
        if (params.minutes !== undefined) searchParams.append('minutes', String(params.minutes));
        if (params.date) searchParams.append('date', params.date);

        const response = await axios.get<PricingRangeResponse>(
            `${API_URL}/find-price?${searchParams.toString()}`
        );
        return response.data.data;
    },

    /**
     * Obtener rangos disponibles para una habitación
     */
    async getAvailableRanges(params: AvailableRangesParams): Promise<PricingRangeCollection> {
        const searchParams = new URLSearchParams();
        searchParams.append('sub_branch_id', params.sub_branch_id);
        searchParams.append('room_type_id', params.room_type_id);
        
        if (params.rate_type_code) searchParams.append('rate_type_code', params.rate_type_code);
        if (params.date) searchParams.append('date', params.date);

        const response = await axios.get<PricingRangeCollection>(
            `${API_URL}/available-ranges?${searchParams.toString()}`
        );
        return response.data;
    }
};