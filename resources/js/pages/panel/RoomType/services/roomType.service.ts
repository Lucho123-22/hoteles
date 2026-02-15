import axios from 'axios';
import type { 
    RoomType, 
    RoomTypeFormData, 
    RoomTypeResponse, 
    RoomTypeCollection,
    RoomTypeFilters 
} from '../interfaces/roomType.interface';

const API_URL = '/room-types';

export const roomTypeService = {
    /**
     * Obtener todos los tipos de habitación con filtros opcionales
     */
    async getAll(filters?: RoomTypeFilters): Promise<RoomTypeCollection> {
        const params = new URLSearchParams();
        
        if (filters?.search) params.append('search', filters.search);
        if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));
        if (filters?.category) params.append('category', filters.category);
        if (filters?.sort_by) params.append('sort_by', filters.sort_by);
        if (filters?.sort_order) params.append('sort_order', filters.sort_order);
        if (filters?.with_rooms_count) params.append('with_rooms_count', 'true');
        if (filters?.with_pricing_ranges_count) params.append('with_pricing_ranges_count', 'true');
        if (filters?.with_available_rooms) params.append('with_available_rooms', 'true');
        if (filters?.with_prices) params.append('with_prices', 'true');
        if (filters?.sub_branch_id) params.append('sub_branch_id', filters.sub_branch_id);
        if (filters?.rate_type_code) params.append('rate_type_code', filters.rate_type_code);

        const response = await axios.get<RoomTypeCollection>(`${API_URL}?${params.toString()}`);
        return response.data;
    },

    /**
     * Obtener un tipo de habitación por ID
     */
    async getById(id: string, filters?: Partial<RoomTypeFilters>): Promise<RoomType> {
        const params = new URLSearchParams();
        
        if (filters?.with_rooms_count) params.append('with_rooms_count', 'true');
        if (filters?.with_pricing_ranges_count) params.append('with_pricing_ranges_count', 'true');
        if (filters?.with_available_rooms) params.append('with_available_rooms', 'true');
        if (filters?.with_prices) params.append('with_prices', 'true');
        if (filters?.sub_branch_id) params.append('sub_branch_id', filters.sub_branch_id);
        if (filters?.rate_type_code) params.append('rate_type_code', filters.rate_type_code);

        const queryString = params.toString();
        const url = queryString ? `${API_URL}/${id}?${queryString}` : `${API_URL}/${id}`;
        
        const response = await axios.get<RoomTypeResponse>(url);
        return response.data.data;
    },

    /**
     * Crear nuevo tipo de habitación
     */
    async create(data: RoomTypeFormData): Promise<RoomType> {
        const response = await axios.post<RoomTypeResponse>(API_URL, data);
        return response.data.data;
    },

    /**
     * Actualizar tipo de habitación existente
     */
    async update(id: string, data: Partial<RoomTypeFormData>): Promise<RoomType> {
        const response = await axios.put<RoomTypeResponse>(`${API_URL}/${id}`, data);
        return response.data.data;
    },

    /**
     * Eliminar tipo de habitación (soft delete)
     */
    async delete(id: string): Promise<{ message: string }> {
        const response = await axios.delete<{ message: string }>(`${API_URL}/${id}`);
        return response.data;
    }
};