import axios from 'axios';
import { defineStore } from 'pinia';

export interface User {
    id: number;
    name: string;
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

export interface CashRegister {
    id: string;
    name: string;
    is_active: boolean;
    is_occupied: boolean;
    occupied_by: User | null;
    sub_branch: {
        id: string;
        name: string;
    };
    created_at: string;
}

interface CashSessionState {
    sessions: CashSession[];
    cashRegister: CashRegister | null;
    isLoading: boolean;
    error: string | null;
}

export const useCashSessionStore = defineStore('cashSession', {
    state: (): CashSessionState => ({
        sessions: [],
        cashRegister: null,
        isLoading: false,
        error: null,
    }),

    getters: {
        activeSessions: (state) => state.sessions.filter((session) => session.status === 'abierta'),
        closedSessions: (state) => state.sessions.filter((session) => session.status === 'cerrada'),

        totalSessions: (state) => state.sessions.length,
    },

    actions: {
        async fetchCashSessions(cashRegisterId: string) {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await axios.get(`/cash-registers/${cashRegisterId}/sessions`);
                this.sessions = response.data.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Error al cargar las sesiones';
                console.error('Error fetching cash sessions:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        setCashRegister(cashRegister: CashRegister) {
            this.cashRegister = cashRegister;
        },
        clearStore() {
            this.sessions = [];
            this.cashRegister = null;
            this.error = null;
        },
    },
});
