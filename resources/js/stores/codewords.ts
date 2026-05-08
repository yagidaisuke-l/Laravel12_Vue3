import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export interface Codeword {
  id: number
  siteName: string
  word: string
  discountType: 'fixed' | 'percent'
  discountValue: number
  description: string
  isActive: boolean
}

function toCodeword(c: any): Codeword {
  return {
    id:            c.id,
    siteName:      c.site_name,
    word:          c.word,
    discountType:  c.discount_type,
    discountValue: c.discount_value,
    description:   c.description ?? '',
    isActive:      c.is_active,
  }
}

export const useCodewordsStore = defineStore('codewords', () => {
  const codewords = ref<Codeword[]>([])

  async function fetchAll() {
    const res = await api.get('/codewords')
    codewords.value = res.data.map(toCodeword)
  }

  async function add(data: Omit<Codeword, 'id'>) {
    const res = await api.post('/codewords', {
      site_name:      data.siteName,
      word:           data.word,
      discount_type:  data.discountType,
      discount_value: data.discountValue,
      description:    data.description,
      is_active:      data.isActive,
    })
    codewords.value.push(toCodeword(res.data))
  }

  async function update(id: number, data: Omit<Codeword, 'id'>) {
    const res = await api.put(`/codewords/${id}`, {
      site_name:      data.siteName,
      word:           data.word,
      discount_type:  data.discountType,
      discount_value: data.discountValue,
      description:    data.description,
      is_active:      data.isActive,
    })
    const idx = codewords.value.findIndex(c => c.id === id)
    if (idx !== -1) codewords.value[idx] = toCodeword(res.data)
  }

  async function remove(id: number) {
    await api.delete(`/codewords/${id}`)
    codewords.value = codewords.value.filter(c => c.id !== id)
  }

  async function toggleActive(id: number) {
    const c = codewords.value.find(c => c.id === id)
    if (!c) return
    await update(id, { ...c, isActive: !c.isActive })
  }

  return { codewords, fetchAll, add, update, remove, toggleActive }
})
