import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export type DriverStatus = '待機中' | '稼働中' | '休み'

export interface Driver {
  id: number
  name: string
  status: DriverStatus
  car: string
  phone: string
  todayCount: number
  returnAt: string
}

function toDriver(d: any): Driver {
  return {
    id:         d.id,
    name:       d.name,
    status:     d.status,
    car:        d.car ?? '',
    phone:      d.phone ?? '',
    todayCount: d.today_count ?? 0,
    returnAt:   d.return_at ? d.return_at.slice(0, 5) : '',
  }
}

export const useDriversStore = defineStore('drivers', () => {
  const drivers = ref<Driver[]>([])

  const standbyDrivers = computed(() => drivers.value.filter(d => d.status === '待機中'))
  const busyDrivers    = computed(() => drivers.value.filter(d => d.status === '稼働中'))
  const standbyCount   = computed(() => standbyDrivers.value.length)
  const busyCount      = computed(() => busyDrivers.value.length)

  async function fetchAll() {
    const res = await api.get('/drivers')
    drivers.value = res.data.map(toDriver)
  }

  async function update(id: number, data: Partial<Omit<Driver, 'id'>>) {
    const body: any = { ...data }
    if ('todayCount' in data) { delete body.todayCount }
    if ('returnAt'   in data) { body.return_at = data.returnAt; delete body.returnAt }
    const res = await api.put(`/drivers/${id}`, body)
    const idx = drivers.value.findIndex(d => d.id === id)
    if (idx !== -1) drivers.value[idx] = toDriver(res.data)
  }

  async function add(data: Omit<Driver, 'id'>) {
    const res = await api.post('/drivers', {
      name: data.name, status: data.status, car: data.car,
      phone: data.phone, return_at: data.returnAt || null,
    })
    drivers.value.push(toDriver(res.data))
  }

  async function remove(id: number) {
    await api.delete(`/drivers/${id}`)
    drivers.value = drivers.value.filter(d => d.id !== id)
  }

  return { drivers, standbyDrivers, busyDrivers, standbyCount, busyCount, fetchAll, update, add, remove }
})
