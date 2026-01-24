import { defineStore } from 'pinia';
import axios from 'axios';
import type { Customer, CustomerResponse } from './customer';

export const useCustomerStore = defineStore('customer', {
  state: () => ({
    customers: [] as Customer[],
    loading: false,
  }),

  actions: {
    async fetchCustomers() {
      this.loading = true;
      try {
        const { data } = await axios.get<CustomerResponse>('/customer');
        this.customers = data.data;
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    }
  }
});