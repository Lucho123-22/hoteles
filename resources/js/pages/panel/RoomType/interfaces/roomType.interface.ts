export interface RoomType {
    id: string; // UUID
    name: string;
    code: string;
    description: string | null;
    capacity: number;
    max_capacity: number | null;
    category: string | null;
    is_active: boolean;
    
    // Información adicional
    available_rooms_count?: number;
    has_available_rooms?: boolean;
    price_range?: {
        min: number;
        max: number;
    };
    cheapest_price?: number;
    
    // Contadores
    rooms_count?: number;
    pricing_ranges_count?: number;
    
    // Timestamps
    created_at: string;
    updated_at: string;
}

export interface RoomTypeFormData {
    name: string;
    code?: string; // Auto-generado si no se proporciona
    description?: string;
    capacity: number;
    max_capacity?: number;
    category?: string;
    is_active: boolean;
}

export interface RoomTypeResponse {
    data: RoomType;
    message?: string;
}

export interface RoomTypeCollection {
    data: RoomType[];
    meta: {
        total: number;
        categories: string[];
    };
}

export interface RoomTypeFilters {
    search?: string;
    is_active?: boolean;
    category?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    with_rooms_count?: boolean;
    with_pricing_ranges_count?: boolean;
    with_available_rooms?: boolean;
    with_prices?: boolean;
    sub_branch_id?: string;
    rate_type_code?: string;
}

export const ROOM_CATEGORIES = [
    'Económica',
    'Estándar',
    'Premium',
    'Lujo'
] as const;

export type RoomCategory = typeof ROOM_CATEGORIES[number];