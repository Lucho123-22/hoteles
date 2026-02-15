// interfaces/index.ts

export interface SubBranch {
  id: string;
  branch_id: string;
  name: string;
  code: string;
  address: string;
  phone: string;
  is_active: boolean;
  available_rooms_count: number;
  creacion: string;
  actualizacion: string;
}

export interface RoomType {
  id: string;
  name: string;
  code: string;
  description: string;
  capacity: number;
  max_capacity: number;
  category: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface RateType {
  id: string;
  name: string;
  code: string;
  description: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface PricingRange {
  id: string;
  time_from_minutes: number;
  time_to_minutes: number;
  formatted_time: string;
  price: string;
}

export interface BranchRoomTypePrice {
  id: string;
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
  is_currently_effective: boolean;
  has_expired: boolean;
  sub_branch?: SubBranch;
  room_type?: RoomType;
  rate_type?: RateType;
  pricing_ranges?: PricingRange[];
  created_at: string;
  updated_at: string;
}

export interface BranchRoomTypePriceFormData {
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
}

export interface PricingOptionsResponse {
  branch_room_type_price: BranchRoomTypePrice;
  pricing_options: PricingRange[];
}

export interface CalculatePriceRequest {
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  minutes: number;
  date?: string;
}

export interface CalculatePriceResponse {
  minutes: number;
  price: string;
  date: string;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
  };
}

export interface FilterParams {
  sub_branch_id?: string;
  room_type_id?: string;
  rate_type_id?: string;
  is_active?: boolean;
  current_only?: boolean;
}