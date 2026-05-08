<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api'

interface Reservation {
  id: number
  date: string
  time: string
  duration: number
  area: string
  total_price: number
  reservation_status: '仮予約' | '確定' | 'キャンセル'
  customer: { id: number; name: string; phone: string } | null
  cast: { id: number; name: string; rank?: { name: string; color: string } } | null
  dispatches: { id: number; type: string; status: string; driver: { name: string } | null }[]
}

const reservations = ref<Reservation[]>([])
const loading      = ref(false)
const filterDate   = ref(new Date().toISOString().slice(0, 10))
const filterStatus = ref('')

async function fetchReservations() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (filterDate.value)   params.date   = filterDate.value
    if (filterStatus.value) params.status = filterStatus.value
    const res = await api.get('/reservations', { params })
    reservations.value = res.data.data ?? res.data
  } finally {
    loading.value = false
  }
}

onMounted(fetchReservations)
watch([filterDate, filterStatus], fetchReservations)

// 送りドライバー名
function driverName(r: Reservation): string {
  const d = r.dispatches.find(d => d.type === '送り')
  return d?.driver?.name ?? '未割当'
}

const statusColor: Record<string, string> = {
  '確定':       'bg-blue-100 text-blue-700',
  '仮予約':     'bg-yellow-100 text-yellow-700',
  'キャンセル': 'bg-red-100 text-red-600',
}

const today = new Date().toISOString().slice(0, 10)
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">予約管理</h2>
      <RouterLink
        to="/reservations/new"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
      >
        + 新規予約
      </RouterLink>
    </div>

    <!-- フィルター -->
    <div class="bg-white rounded-xl shadow-sm p-4 flex gap-3 flex-wrap items-center">
      <input
        v-model="filterDate"
        type="date"
        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <button @click="filterDate = today"
        class="text-xs text-blue-600 hover:underline">今日</button>
      <button @click="filterDate = ''"
        class="text-xs text-gray-400 hover:underline">日付解除</button>
      <select
        v-model="filterStatus"
        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">すべてのステータス</option>
        <option>確定</option>
        <option>仮予約</option>
        <option>キャンセル</option>
      </select>
      <span v-if="loading" class="text-xs text-gray-400">読み込み中...</span>
      <span v-else class="text-xs text-gray-400 ml-auto">{{ reservations.length }}件</span>
    </div>

    <!-- テーブル -->
    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">日時</th>
            <th class="px-5 py-3">顧客</th>
            <th class="px-5 py-3">キャスト</th>
            <th class="px-5 py-3">ドライバー</th>
            <th class="px-5 py-3">エリア</th>
            <th class="px-5 py-3">時間</th>
            <th class="px-5 py-3">料金</th>
            <th class="px-5 py-3">ステータス</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && reservations.length === 0">
            <td colspan="9" class="px-5 py-10 text-center text-gray-400">予約がありません</td>
          </tr>
          <tr v-for="r in reservations" :key="r.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="font-medium">{{ r.date }}</div>
              <div class="text-gray-500 text-xs">{{ r.time }}</div>
            </td>
            <td class="px-5 py-3">
              <div class="font-medium">{{ r.customer?.name || '（名前未登録）' }}</div>
              <div class="text-gray-400 text-xs font-mono">{{ r.customer?.phone }}</div>
            </td>
            <td class="px-5 py-3">
              <div class="flex items-center gap-1">
                <span v-if="r.cast?.rank"
                  :class="r.cast.rank.color"
                  class="text-white text-xs px-1.5 py-0.5 rounded-full font-bold leading-none shrink-0">
                  {{ r.cast.rank.name }}
                </span>
                <span>{{ r.cast?.name ?? '—' }}</span>
              </div>
            </td>
            <td class="px-5 py-3" :class="driverName(r) === '未割当' ? 'text-red-400' : 'text-gray-700'">
              {{ driverName(r) }}
            </td>
            <td class="px-5 py-3 text-gray-500">{{ r.area || '—' }}</td>
            <td class="px-5 py-3 text-gray-500">{{ r.duration }}分</td>
            <td class="px-5 py-3 font-medium">¥{{ r.total_price.toLocaleString() }}</td>
            <td class="px-5 py-3">
              <span :class="statusColor[r.reservation_status]"
                class="px-2 py-0.5 rounded-full text-xs font-medium">
                {{ r.reservation_status }}
              </span>
            </td>
            <td class="px-5 py-3">
              <RouterLink :to="`/reservations/${r.id}/edit`"
                class="text-blue-600 hover:underline text-xs">編集</RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
