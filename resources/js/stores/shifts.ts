import { defineStore } from 'pinia'
import { ref } from 'vue'

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

function today(offset = 0): string {
  const d = new Date()
  d.setDate(d.getDate() + offset)
  return d.toISOString().slice(0, 10)
}

export const useShiftsStore = defineStore('shifts', () => {
  const shifts = ref<Shift[]>([
    { id: 1,  castId: 1, date: today(0),  startTime: '18:00', endTime: '24:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 2,  castId: 2, date: today(0),  startTime: '19:00', endTime: '25:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 3,  castId: 5, date: today(0),  startTime: '20:00', endTime: '26:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 4,  castId: 1, date: today(1),  startTime: '18:00', endTime: '24:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 5,  castId: 3, date: today(1),  startTime: '17:00', endTime: '23:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 6,  castId: 4, date: today(1),  startTime: '20:00', endTime: '26:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 7,  castId: 2, date: today(2),  startTime: '18:00', endTime: '24:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 8,  castId: 5, date: today(2),  startTime: '19:00', endTime: '25:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 9,  castId: 1, date: today(-1), startTime: '18:00', endTime: '24:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 10, castId: 3, date: today(-1), startTime: '19:00', endTime: '25:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 11, castId: 2, date: today(3),  startTime: '17:00', endTime: '23:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 12, castId: 4, date: today(3),  startTime: '20:00', endTime: '26:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 13, castId: 5, date: today(-2), startTime: '18:00', endTime: '24:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 14, castId: 1, date: today(4),  startTime: '19:00', endTime: '25:00', status: '出勤予定', absentNote: '', note: '' },
    { id: 15, castId: 3, date: today(4),  startTime: '20:00', endTime: '26:00', status: '出勤予定', absentNote: '', note: '' },
  ])

  let nextId = 16

  function getByDate(date: string): Shift[] {
    return shifts.value.filter(s => s.date === date)
  }

  function getByCastAndDate(castId: number, date: string): Shift | undefined {
    return shifts.value.find(s => s.castId === castId && s.date === date)
  }

  // 出勤予定のキャストIDのみ返す（欠勤除外）
  function getCastIdsByDate(date: string): number[] {
    return shifts.value
      .filter(s => s.date === date && s.status === '出勤予定')
      .map(s => s.castId)
  }

  // 欠勤含む全シフトのキャストID
  function getAllCastIdsByDate(date: string): number[] {
    return shifts.value.filter(s => s.date === date).map(s => s.castId)
  }

  function add(data: Omit<Shift, 'id'>) {
    shifts.value.push({ id: nextId++, ...data })
  }

  function update(id: number, data: Omit<Shift, 'id'>) {
    const s = shifts.value.find(s => s.id === id)
    if (s) Object.assign(s, data)
  }

  function markAbsent(id: number, reason: ShiftStatus, absentNote: string) {
    const s = shifts.value.find(s => s.id === id)
    if (s) { s.status = reason; s.absentNote = absentNote }
  }

  // 指定日以降の同キャストのシフトをすべて欠勤にする
  function markAbsentFromDate(castId: number, fromDate: string, reason: ShiftStatus, absentNote: string) {
    shifts.value
      .filter(s => s.castId === castId && s.date >= fromDate)
      .forEach(s => { s.status = reason; s.absentNote = absentNote })
  }

  function cancelAbsent(id: number) {
    const s = shifts.value.find(s => s.id === id)
    if (s) { s.status = '出勤予定'; s.absentNote = '' }
  }

  // 指定日以降の同キャストの欠勤をすべて出勤予定に戻す
  function cancelAbsentFromDate(castId: number, fromDate: string) {
    shifts.value
      .filter(s => s.castId === castId && s.date >= fromDate && s.status !== '出勤予定')
      .forEach(s => { s.status = '出勤予定'; s.absentNote = '' })
  }

  function remove(id: number) {
    shifts.value = shifts.value.filter(s => s.id !== id)
  }

  return { shifts, getByDate, getByCastAndDate, getCastIdsByDate, getAllCastIdsByDate, add, update, markAbsent, markAbsentFromDate, cancelAbsent, cancelAbsentFromDate, remove }
})
