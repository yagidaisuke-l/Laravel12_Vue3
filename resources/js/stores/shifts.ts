import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export type ShiftStatus = '出勤予定' | '体調不良' | '無断欠勤' | 'その他欠勤'

export const ABSENT_REASONS: ShiftStatus[] = ['体調不良', '無断欠勤', 'その他欠勤']

export interface Shift {
  id: number
  castId: number
  date: string
  startTime: string
  endTime: string
  status: ShiftStatus
  absentNote: string
  note: string
}

function toShift(s: any): Shift {
  return {
    id:         s.id,
    castId:     s.cast_id,
    date:       s.date,
    startTime:  s.start_time,
    endTime:    s.end_time,
    status:     s.status,
    absentNote: s.absent_note ?? '',
    note:       s.note ?? '',
  }
}

export const useShiftsStore = defineStore('shifts', () => {
  const shifts = ref<Shift[]>([])

  async function fetchRange(from: string, to: string) {
    const res = await api.get('/shifts', { params: { from, to } })
    const fetched: Shift[] = res.data.map(toShift)
    shifts.value = [
      ...shifts.value.filter(s => s.date < from || s.date > to),
      ...fetched,
    ]
  }

  async function fetchDate(date: string) {
    const res = await api.get('/shifts', { params: { date } })
    const fetched: Shift[] = res.data.map(toShift)
    shifts.value = [...shifts.value.filter(s => s.date !== date), ...fetched]
  }

  function getByDate(date: string): Shift[] {
    return shifts.value.filter(s => s.date === date)
  }

  function getByCastAndDate(castId: number, date: string): Shift | undefined {
    return shifts.value.find(s => s.castId === castId && s.date === date)
  }

  function getCastIdsByDate(date: string): number[] {
    return shifts.value
      .filter(s => s.date === date && s.status === '出勤予定')
      .map(s => s.castId)
  }

  function getAllCastIdsByDate(date: string): number[] {
    return shifts.value.filter(s => s.date === date).map(s => s.castId)
  }

  async function add(data: { castId: number; date: string; startTime: string; endTime: string; note?: string }) {
    const res = await api.post('/shifts', {
      cast_id:    data.castId,
      date:       data.date,
      start_time: data.startTime,
      end_time:   data.endTime,
      note:       data.note ?? '',
    })
    const shift = toShift(res.data)
    shifts.value = shifts.value.filter(s => !(s.castId === shift.castId && s.date === shift.date))
    shifts.value.push(shift)
  }

  async function update(id: number, data: Partial<Omit<Shift, 'id'>>) {
    const payload: Record<string, any> = {}
    if (data.startTime  !== undefined) payload.start_time  = data.startTime
    if (data.endTime    !== undefined) payload.end_time    = data.endTime
    if (data.status     !== undefined) payload.status      = data.status
    if (data.absentNote !== undefined) payload.absent_note = data.absentNote || null
    if (data.note       !== undefined) payload.note        = data.note
    const res = await api.put(`/shifts/${id}`, payload)
    const idx = shifts.value.findIndex(s => s.id === id)
    if (idx !== -1) shifts.value[idx] = toShift(res.data)
  }

  async function markAbsent(id: number, reason: ShiftStatus, absentNote: string) {
    await update(id, { status: reason, absentNote })
  }

  async function markAbsentFromDate(castId: number, fromDate: string, reason: ShiftStatus, absentNote: string) {
    await api.post('/shifts/absent-from', {
      cast_id:     castId,
      from_date:   fromDate,
      status:      reason,
      absent_note: absentNote,
    })
    shifts.value
      .filter(s => s.castId === castId && s.date >= fromDate)
      .forEach(s => { s.status = reason; s.absentNote = absentNote })
  }

  async function cancelAbsent(id: number) {
    await update(id, { status: '出勤予定', absentNote: '' })
  }

  async function cancelAbsentFromDate(castId: number, fromDate: string) {
    const targets = shifts.value.filter(s => s.castId === castId && s.date >= fromDate && s.status !== '出勤予定')
    await Promise.all(targets.map(s => api.put(`/shifts/${s.id}`, { status: '出勤予定', absent_note: null })))
    targets.forEach(s => { s.status = '出勤予定'; s.absentNote = '' })
  }

  async function bulkAdd(params: {
    castId: number; startDate: string; endDate: string
    dows: number[]; startTime: string; endTime: string
    overwrite: boolean; note: string
  }): Promise<number> {
    const res = await api.post('/shifts/bulk', {
      cast_id:    params.castId,
      start_date: params.startDate,
      end_date:   params.endDate,
      dows:       params.dows,
      start_time: params.startTime,
      end_time:   params.endTime,
      overwrite:  params.overwrite,
      note:       params.note,
    })
    return res.data.registered
  }

  async function remove(id: number) {
    await api.delete(`/shifts/${id}`)
    shifts.value = shifts.value.filter(s => s.id !== id)
  }

  return {
    shifts,
    fetchRange, fetchDate,
    getByDate, getByCastAndDate, getCastIdsByDate, getAllCastIdsByDate,
    add, update, markAbsent, markAbsentFromDate,
    cancelAbsent, cancelAbsentFromDate, bulkAdd, remove,
  }
})
