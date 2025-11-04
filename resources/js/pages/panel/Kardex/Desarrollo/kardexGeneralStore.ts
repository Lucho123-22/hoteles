import { defineStore } from 'pinia';
import axios from 'axios';

export const useKardexGeneralStore = defineStore('kardexGeneral', {
  state: () => ({
    kardexData: [],
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    }
  }),
  
  actions: {
    async fetchKardexGeneral(params = {}) {
      this.loading = true;
      
      try {
        const requestParams = {
          page: params.page || this.pagination.current_page,
          per_page: params.perPage || this.pagination.per_page,
          ...params
        };

        const response = await axios.get('/kardex/general', { params: requestParams });
        
        this.kardexData = response.data.data;
        
        // Guardar información de paginación
        if (response.data.meta) {
          this.pagination = {
            current_page: response.data.meta.current_page,
            last_page: response.data.meta.last_page,
            per_page: response.data.meta.per_page,
            total: response.data.meta.total,
            from: response.data.meta.from || 0,
            to: response.data.meta.to || 0
          };
        }
        
        console.log('Kardex general cargado:', this.kardexData.length, 'registros de', this.pagination.total);
        
      } catch (error) {
        console.error('Error al cargar kardex general:', error);
        this.kardexData = [];
      } finally {
        this.loading = false;
      }
    },
    
    clearKardexGeneral() {
      this.kardexData = [];
      this.pagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0
      };
    }
  }
});