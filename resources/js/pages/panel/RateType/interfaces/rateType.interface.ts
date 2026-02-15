export interface RateType {
    id: string; // UUID
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    display_name: string;
    icon: string;
    requires_time_range: boolean;
    pricing_ranges_count?: number;
    created_at: string;
    updated_at: string;
}

export interface RateTypeFormData {
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
}

export interface RateTypeResponse {
    data: RateType;
    message?: string;
}

export interface RateTypeCollection {
    data: RateType[];
    meta: {
        total: number;
    };
}

export interface RateTypeFilters {
    search?: string;
    is_active?: boolean;
    code?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    with_pricing_ranges_count?: boolean;
}