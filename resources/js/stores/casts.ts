import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export interface Cast {
  id: number
  name: string
  age: number
  status: '待機中' | '稼働中' | '休み'
  phone: string
  rankId: number | null
  ngOptionIds: number[]
  todayCount: number
}

function toCast(c: any): Cast {
  return {
    id:           c.id,
    name:         c.name,
    age:          c.age ?? 0,
    status:       c.status,
    phone:        c.phone ?? '',
    rankId:       c.rank_id ?? null,
    ngOptionIds:  c.ng_option_ids ?? [],
    todayCount:   c.today_count ?? 0,
  }
}

export const useCastsStore = defineStore('casts', () => {
  const casts = ref<Cast[]>([])

  async function fetchAll() {
    const res = await api.get('/casts')
    casts.value = res.data.map(toCast)
  }

  async function updateRank(castId: number, rankId: number | null) {
    await api.put(`/casts/${castId}`, { rank_id: rankId })
    const c = casts.value.find(c => c.id === castId)
    if (c) c.rankId = rankId
  }

  async function updateNgOptions(castId: number, ngOptionIds: number[]) {
    await api.put(`/casts/${castId}`, { ng_option_ids: ngOptionIds })
    const c = casts.value.find(c => c.id === castId)
    if (c) c.ngOptionIds = [...ngOptionIds]
  }

  return { casts, fetchAll, updateRank, updateNgOptions }
})
