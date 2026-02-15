// services/branchRoomTypePriceService.ts
import axios from 'axios';
import type {
  BranchRoomTypePrice,
  BranchRoomTypePriceFormData,
  SubBranch,
  RoomType,
  RateType,
  PricingOptionsResponse,
  CalculatePriceRequest,
  CalculatePriceResponse,
  PaginatedResponse,
  FilterParams,
} from '../interfaces';

const API_BASE_URL = '/branch-room-type-prices';

export const branchRoomTypePriceService = {
  async getAll(filters?: FilterParams): Promise<BranchRoomTypePrice[]> {
    const response = await axios.get<{ data: BranchRoomTypePrice[] }>(API_BASE_URL, {
      params: filters,
    });
    return response.data.data;
  },

  async getById(id: string): Promise<BranchRoomTypePrice> {
    const response = await axios.get<{ data: BranchRoomTypePrice }>(`${API_BASE_URL}/${id}`);
    return response.data.data;
  },

  async create(data: BranchRoomTypePriceFormData): Promise<{
    message: string;
    data: BranchRoomTypePrice;
  }> {
    const response = await axios.post<{
      message: string;
      data: BranchRoomTypePrice;
    }>(API_BASE_URL, data);
    return response.data;
  },

  async update(
    id: string,
    data: BranchRoomTypePriceFormData
  ): Promise<{
    message: string;
    data: BranchRoomTypePrice;
  }> {
    const response = await axios.put<{
      message: string;
      data: BranchRoomTypePrice;
    }>(`${API_BASE_URL}/${id}`, data);
    return response.data;
  },

  async delete(id: string): Promise<{ message: string }> {
    const response = await axios.delete<{ message: string }>(`${API_BASE_URL}/${id}`);
    return response.data;
  },

  async getPricingOptions(params: {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    date?: string;
  }): Promise<PricingOptionsResponse> {
    const response = await axios.get<{ data: PricingOptionsResponse }>(
      `${API_BASE_URL}/pricing-options`,
      { params }
    );
    return response.data.data;
  },

  async calculatePrice(data: CalculatePriceRequest): Promise<CalculatePriceResponse> {
    const response = await axios.post<{ data: CalculatePriceResponse }>(
      `${API_BASE_URL}/calculate-price`,
      data
    );
    return response.data.data;
  },
};

export const subBranchService = {
  async search(): Promise<SubBranch[]> {
    const response = await axios.get<{ data: SubBranch[] }>('/sub-branches/search');
    return response.data.data;
  },
};

export const roomTypeService = {
  async getOptions(): Promise<RoomType[]> {
    const response = await axios.get<PaginatedResponse<RoomType>>('/room-types/opciones');
    return response.data.data;
  },
};

export const rateTypeService = {
  async getOptions(): Promise<RateType[]> {
    // CORREGIDO: era /opcones, ahora es /opciones
    const response = await axios.get<{ data: RateType[] }>('/rate-types/opciones');
    return response.data.data;
  },
};