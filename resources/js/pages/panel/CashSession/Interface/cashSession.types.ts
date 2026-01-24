export interface User {
  id: number;
  name: string;
}

export interface SubBranch {
  id: string;
  name: string;
}

export interface CashRegister {
  id: string;
  name: string;
  is_active: boolean;
  is_occupied: boolean;
  occupied_by: User | null;
  sub_branch: SubBranch;
  created_at: string;
}

export interface CashSession {
  id: string;
  cash_register_id: string;
  status: 'abierta' | 'cerrada';
  opening_amount: string;
  system_total_amount: string;
  counted_total_amount: string;
  difference_amount: string;
  opened_at: string;
  closed_at: string | null;
  opened_by: User;
  closed_by: User | null;
}

export interface CashSessionsResponse {
  data: CashSession[];
}

export interface CashRegisterResponse {
  data: CashRegister;
}