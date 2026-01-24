import { defineStore } from 'pinia'
import axios from 'axios'

export interface SubBranch {
  id: string
  name: string
}

export interface CashRegister {
  id: string
  name: string
  is_active: boolean
  sub_branch: SubBranch
  created_at: string
}

interface State {
  items: CashRegister[]
  loading: boolean
  error: string | null
}

export const useCashRegisterStore = defineStore('cashRegister', {
  state: (): State => ({
    items: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchAll() {
      this.loading = true
      this.error = null

      try {
        const { data } = await axios.get('/cash')

        if (data.success) {
          this.items = data.data
        } else {
          this.error = data.message
        }
      } catch (e) {
        console.error(e)
        this.error = 'Error al obtener las cajas registradoras'
      } finally {
        this.loading = false
      }
    }
  }
})
