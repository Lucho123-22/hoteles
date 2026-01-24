export interface Customer {
  id: string;
  document_type: string;
  document_number: string;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface CustomerResponse {
  current_page: number;
  data: Customer[];
  total: number;
}