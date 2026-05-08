import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export interface AreaFee {
  id: number
  area: string
  fee: number
}

export const useAreasStore = defineStore('areas', () => {
  const areas = ref<AreaFee[]>([])

  async function fetchAll() {
    const res = await api.get('/areas')
    areas.value = res.data
  }

  async function add(area: string, fee: number) {
    const res = await api.post('/areas', { area, fee })
    areas.value.push(res.data)
  }

  async function update(id: number, area: string, fee: number) {
    const res = await api.put(`/areas/${id}`, { area, fee })
    const idx = areas.value.findIndex(a => a.id === id)
    if (idx !== -1) areas.value[idx] = res.data
  }

  async function remove(id: number) {
    await api.delete(`/areas/${id}`)
    areas.value = areas.value.filter(a => a.id !== id)
  }

  function getFee(areaName: string): number | null {
    const found = areas.value.find(a => areaName.includes(a.area))
    return found ? found.fee : null
  }

  return { areas, fetchAll, add, update, remove, getFee }
})
