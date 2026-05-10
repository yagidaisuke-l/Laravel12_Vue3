<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const today = new Date().toISOString().slice(0, 10)

const reservations = ref<any[]>([])
const casts        = ref<any[]>([])
const drivers      = ref<any[]>([])
const loading      = ref(true)

onMounted(async () => {
  const [rRes, cRes, dRes] = await Promise.all([
    api.get('/reservations', { params: { date: today } }),
    api.get('/casts'),
    api.get('/drivers'),
  ])
  reservations.value = rRes.data.data ?? rRes.data
  casts.value        = cRes.data
  drivers.value      = dRes.data
  loading.value      = false
})

const stats = computed(() => {
  const rList    = reservations.value
  const active   = casts.value.filter(c => c.status === '稼働中').length
  const waiting  = casts.value.filter(c => c.status === '待機中').length
  const dActive  = drivers.value.filter(d => d.status === '稼働中').length
  const dWaiting = drivers.value.filter(d => d.status === '待機中').length
  const sales    = rList
    .filter(r => r.reservation_status === '確定')
    .reduce((sum: number, r: any) => sum + (r.total_price ?? 0), 0)
  const unassigned = rList.filter(r => {
    const send = (r.dispatches ?? []).find((d: any) => d.type === '送り')
    return !send || send.status === '未配車'
  }).length

  return [
    { label: '本日の予約',       value: rList.length, sub: `未配車 ${unassigned}件`,        color: 'bg-blue-500' },
    { label: '稼働中キャスト',   value: active,        sub: `待機中 ${waiting}名`,           color: 'bg-green-500' },
    { label: '稼働中ドライバー', value: dActive,        sub: `待機中 ${dWaiting}名`,          color: 'bg-orange-500' },
    { label: '本日の売上',        value: `¥${sales.toLocaleString()}`, sub: '確定分合計', color: 'bg-purple-500' },
  ]
})

function dispatchStatus(r: any): string {
  if (r.reservation_status === 'キャンセル') return 'キャンセル'
  const send = (r.dispatches ?? []).find((d: any) => d.type === '送り')
  if (!send || send.status === '未配車') return '未配車'
  if (send.status === '配車済') return '配車済'
  return '完了'
}

function driverName(r: any): string {
  const send = (r.dispatches ?? []).find((d: any) => d.type === '送り')
  return send?.driver?.name ?? '未割当'
}

const statusColor: Record<string, string> = {
  '未配車':    'bg-red-100 text-red-600',
  '配車済':    'bg-yellow-100 text-yellow-700',
  '完了':      'bg-gray-100 text-gray-500',
  'キャンセル':'bg-gray-100 text-gray-400',
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-lg font-bold text-gray-800">ダッシュボード</h2>

    <!-- 統計カード -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="s in stats" :key="s.label" class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center gap-3">
          <div :class="s.color" class="w-2 h-10 rounded-full shrink-0"></div>
          <div>
            <p class="text-xs text-gray-500">{{ s.label }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ s.value }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ s.sub }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 直近の予約 -->
    <div class="bg-white rounded-xl shadow-sm">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="font-semibold text-gray-700">本日の予約</h3>
        <RouterLink to="/reservations" class="text-xs text-blue-600 hover:underline">すべて見る</RouterLink>
      </div>

      <div v-if="loading" class="px-5 py-10 text-center text-gray-400 text-sm">読み込み中...</div>
      <div v-else-if="reservations.length === 0" class="px-5 py-10 text-center text-gray-400 text-sm">本日の予約はありません</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b">
              <th class="px-5 py-3">時間</th>
              <th class="px-5 py-3">顧客</th>
              <th class="px-5 py-3">キャスト</th>
              <th class="px-5 py-3">ドライバー</th>
              <th class="px-5 py-3">エリア</th>
              <th class="px-5 py-3">ステータス</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reservations" :key="r.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-5 py-3 font-medium">{{ r.time }}</td>
              <td class="px-5 py-3">{{ r.customer?.name ?? '-' }}</td>
              <td class="px-5 py-3" :class="!r.cast ? 'text-red-400' : ''">{{ r.cast?.name ?? '未割当' }}</td>
              <td class="px-5 py-3" :class="driverName(r) === '未割当' ? 'text-red-400' : ''">{{ driverName(r) }}</td>
              <td class="px-5 py-3 text-gray-500">{{ r.area ?? '-' }}</td>
              <td class="px-5 py-3">
                <span :class="statusColor[dispatchStatus(r)]" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ dispatchStatus(r) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
