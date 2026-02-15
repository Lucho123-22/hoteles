export interface PricingRange {
    id: string; // UUID
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    
    // Rangos de tiempo
    time_from_minutes: number | null;
    time_to_minutes: number | null;
    formatted_time_range: string;
    duration_hours: number | null;
    
    // Precio
    price: number;
    price_per_hour?: number;
    
    // Vigencia
    effective_from: string;
    effective_to: string | null;
    is_effective: boolean;
    
    // Estado
    is_active: boolean;
    
    // Flags útiles
    is_hourly_rate: boolean;
    is_daily_rate: boolean;
    is_nightly_rate: boolean;
    
    // Relaciones
    room_type?: {
        id: string;
        name: string;
        code: string;
        category: string | null;
    };
    rate_type?: {
        id: string;
        name: string;
        code: string;
        display_name: string;
        icon: string;
    };
    sub_branch?: {
        id: string;
        name: string;
    };
    
    // Timestamps
    created_at: string;
    updated_at: string;
}

export interface PricingRangeFormData {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    time_from_minutes?: number | null;
    time_to_minutes?: number | null;
    price: number;
    effective_from: string;
    effective_to?: string | null;
    is_active: boolean;
}

export interface PricingRangeResponse {
    data: PricingRange;
    message?: string;
}

export interface PricingRangeCollection {
    data: PricingRange[];
    meta: {
        total: number;
        price_stats: {
            min: number;
            max: number;
            avg: number;
        };
    };
}

export interface PricingRangeFilters {
    sub_branch_id?: string;
    room_type_id?: string;
    rate_type_id?: string;
    rate_type_code?: string;
    is_active?: boolean;
    only_effective?: boolean;
    minutes?: number;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
}

export interface FindPriceParams {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    minutes?: number;
    date?: string;
}

export interface AvailableRangesParams {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_code?: string;
    date?: string;
}

// Helpers para rangos de tiempo comunes
export const COMMON_TIME_RANGES = [
    { label: '3 horas', from: 0, to: 180 },
    { label: '4 horas', from: 0, to: 240 },
    { label: '6 horas', from: 0, to: 360 },
    { label: '8 horas', from: 0, to: 480 },
    { label: '12 horas', from: 0, to: 720 },
    { label: '24 horas', from: 0, to: 1440 }
] as const;

// Helper para convertir minutos a formato legible
export function minutesToTimeString(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (mins === 0) {
        return `${hours}h`;
    }
    return `${hours}h ${mins}min`;
}

// Helper para convertir horas a minutos
export function hoursToMinutes(hours: number): number {
    return hours * 60;
}