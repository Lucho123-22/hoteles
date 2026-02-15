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

export interface SubBranchSearchResponse {
    data: SubBranch[];
}