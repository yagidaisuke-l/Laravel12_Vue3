import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export interface Option {
  id: number
  name: string
  price: number
  description: string
}

export const useOptionsStore = defineStore('options', () => {
  const options = ref<Option[]>([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const res = await api.get('/options')
      options.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function add(name: string, price: number, description: string) {
    const res = await api.post('/options', { name, price, description })
    options.value.push(res.data)
  }

  async function update(id: number, name: string, price: number, description: string) {
    const res = await api.put(`/options/${id}`, { name, price, description })
    const idx = options.value.findIndex(o => o.id === id)
    if (idx !== -1) options.value[idx] = res.data
  }

  async function remove(id: number) {
    await api.delete(`/options/${id}`)
    options.value = options.value.filter(o => o.id !== id)
  }

  return { options, loading, fetchAll, add, update, remove }
})
