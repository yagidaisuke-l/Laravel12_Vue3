import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export interface Rank {
  id: number
  name: string
  designationFee: number
  color: string
  order: number
}

function toRank(r: any): Rank {
  return { id: r.id, name: r.name, designationFee: r.designation_fee, color: r.color, order: r.order }
}

export const useRanksStore = defineStore('ranks', () => {
  const ranks = ref<Rank[]>([])

  async function fetchAll() {
    const res = await api.get('/ranks')
    ranks.value = res.data.map(toRank)
  }

  async function add(name: string, designationFee: number, color: string) {
    const order = Math.max(...ranks.value.map(r => r.order), 0) + 1
    const res = await api.post('/ranks', { name, designation_fee: designationFee, color, order })
    ranks.value.push(toRank(res.data))
  }

  async function update(id: number, name: string, designationFee: number, color: string) {
    const res = await api.put(`/ranks/${id}`, { name, designation_fee: designationFee, color })
    const idx = ranks.value.findIndex(r => r.id === id)
    if (idx !== -1) ranks.value[idx] = toRank(res.data)
  }

  async function remove(id: number) {
    await api.delete(`/ranks/${id}`)
    ranks.value = ranks.value.filter(r => r.id !== id)
  }

  function getById(id: number | null): Rank | null {
    return ranks.value.find(r => r.id === id) ?? null
  }

  const sorted = () => [...ranks.value].sort((a, b) => a.order - b.order)

  return { ranks, fetchAll, add, update, remove, getById, sorted }
})
